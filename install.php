<?php
/**
 * Absen Digital - Installation Wizard
 * Designed for easy hosting deployment.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$already_installed = false;
if (file_exists('includes/db.php')) {
    // Disable mysqli exceptions for the check
    @mysqli_report(MYSQLI_REPORT_OFF);
    try {
        include 'includes/db.php';
        if (isset($conn) && $conn instanceof mysqli && @$conn->ping()) {
            $already_installed = true;
        }
    } catch (Throwable $e) {
        $already_installed = false;
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['install'])) {
    $host = $_POST['db_host'];
    $user = $_POST['db_user'];
    $pass = $_POST['db_pass'];
    $name = $_POST['db_name'];
    
    // 1. Test Connection
    $conn = @new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        $error = "Koneksi Gagal: " . $conn->connect_error;
    } else {
        // 2. Create Database
        $conn->query("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $conn->select_db($name);
        
        // 3. Create Tables
        $queries = [
            "CREATE TABLE IF NOT EXISTS `users` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `username` varchar(50) NOT NULL,
              `password` varchar(255) NOT NULL,
              `name` varchar(100) NOT NULL,
              `role` varchar(20) DEFAULT 'admin',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            "CREATE TABLE IF NOT EXISTS `santri` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `nis` varchar(20) NOT NULL,
              `name` varchar(100) NOT NULL,
              `class_name` varchar(50) NOT NULL,
              `gender` enum('L','P') NOT NULL,
              `parent_phone` varchar(20) NOT NULL,
              `photo` varchar(255) DEFAULT '',
              `qrcode_hash` varchar(100) NOT NULL,
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `nis` (`nis`),
              UNIQUE KEY `qrcode_hash` (`qrcode_hash`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `attendance` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `santri_id` int(11) NOT NULL,
              `scan_date` date NOT NULL,
              `scan_time` time NOT NULL,
              `status` varchar(20) DEFAULT 'Hadir',
              PRIMARY KEY (`id`),
              KEY `santri_id` (`santri_id`),
              CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`santri_id`) REFERENCES `santri` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `settings` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `app_name` varchar(100) DEFAULT 'Absen Santri Digital',
              `mpwa_url` varchar(255) DEFAULT '',
              `mpwa_token` varchar(255) DEFAULT '',
              `mpwa_sender` varchar(50) DEFAULT '',
              `admin_phone` varchar(20) DEFAULT '',
              `institution_type` varchar(50) DEFAULT 'pesantren',
              `scanner_announcement` text DEFAULT 'Selamat datang di layanan Absensi Digital kami. Silakan siapkan QR Code Anda untuk melakukan pemindaian.',
              `card_title` varchar(255) DEFAULT 'KARTU IDENTITAS MURID',
              `card_footer` text DEFAULT 'Gunakan kartu ini untuk absensi digital.<br>\nHarap tidak merusak atau mencoret barcode.',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];

        foreach ($queries as $q) {
            $conn->query($q);
        }

        // 4. Insert Default Data
        $cek_user = $conn->query("SELECT id FROM users LIMIT 1");
        if ($cek_user->num_rows == 0) {
            $pass_hashed = password_hash('admin', PASSWORD_DEFAULT);
            $conn->query("INSERT INTO users (username, password, name, role) VALUES ('admin', '$pass_hashed', 'Administrator', 'admin')");
        }

        $cek_settings = $conn->query("SELECT id FROM settings LIMIT 1");
        if ($cek_settings->num_rows == 0) {
            $conn->query("INSERT INTO settings (app_name, institution_type, card_title, card_footer) VALUES ('Absen Santri Digital', 'pesantren', 'KARTU IDENTITAS MURID', 'Gunakan kartu ini untuk absensi digital.<br>\nHarap tidak merusak atau mencoret barcode.')");
        }

        // 5. Generate db.php
        $db_content = "<?php
\$host = \"$host\";
\$user = \"$user\";
\$pass = \"$pass\";
\$db   = \"$name\";

\$conn = new mysqli(\$host, \$user, \$pass, \$db);

if (\$conn->connect_error) {
    die(\"Connection failed: \" . \$conn->connect_error);
}

session_start();
date_default_timezone_set('Asia/Jakarta');

// Get Settings
\$settings_sql = \"SELECT * FROM settings LIMIT 1\";
\$settings_result = \$conn->query(\$settings_sql);
\$app_settings = [];
if (\$settings_result && \$settings_result->num_rows > 0) {
    \$app_settings = \$settings_result->fetch_assoc();
} else {
    \$app_settings = [
        'app_name' => 'Absen Santri Digital',
        'mpwa_url' => '',
        'mpwa_token' => '',
        'mpwa_sender' => '',
        'institution_type' => 'pesantren',
        'scanner_announcement' => 'Selamat datang di layanan Absensi Digital kami.'
    ];
}
?>";
        
        if (!is_dir('includes')) mkdir('includes');
        if (file_put_contents('includes/db.php', $db_content)) {
            $success = "Instalasi Berhasil! Database telah dikonfigurasi.";
        } else {
            $error = "Gagal menulis file includes/db.php. Pastikan folder memiliki izin menulis.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Absen Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .install-card { max-width: 500px; margin: 80px auto; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .gradient-top { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border-radius: 20px 20px 0 0; padding: 40px; text-align: center; }
        .form-control { border-radius: 10px; padding: 12px 15px; background: #f1f5f9; border: 1px solid #e2e8f0; }
        .btn-install { border-radius: 10px; padding: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>

<div class="container">
    <div class="card install-card">
        <div class="gradient-top">
            <i class="fas fa-server fa-3x mb-3"></i>
            <h3 class="fw-bold mb-1">Easy Installer</h3>
            <p class="mb-0 text-white-50">Menyiapkan Database Absen Digital</p>
        </div>
        <div class="card-body p-5">
            <?php if ($success): ?>
                <div class="text-center">
                    <div class="text-success mb-4">
                        <i class="fas fa-check-circle fa-5x"></i>
                    </div>
                    <h4 class="fw-bold mb-3"><?= $success ?></h4>
                    <p class="text-muted mb-4">Login admin default:<br>User: <b>admin</b> | Pass: <b>admin</b></p>
                    <div class="alert alert-warning small">
                        <i class="fas fa-exclamation-triangle me-1"></i> <b>PENTING:</b> Segera hapus file <code>install.php</code> dari server Anda demi keamanan.
                    </div>
                    <a href="login.php" class="btn btn-primary w-100 rounded-pill py-3 fw-bold">Masuk ke Dashboard</a>
                </div>
            <?php elseif ($already_installed): ?>
                <div class="text-center py-4">
                    <i class="fas fa-check-double text-primary fa-4x mb-3"></i>
                    <h5 class="fw-bold">Aplikasi Sudah Terpasang</h5>
                    <p class="text-muted">Aplikasi sudah terhubung ke database. Jika ingin menginstall ulang, hapus file <code>includes/db.php</code> terlebih dahulu.</p>
                    <a href="index.php" class="btn btn-outline-primary rounded-pill px-4 mt-2">Buka Aplikasi</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 small"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">DB Host</label>
                        <input type="text" name="db_host" class="form-control" value="localhost" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">DB Username</label>
                        <input type="text" name="db_user" class="form-control" placeholder="dns_user" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">DB Password</label>
                        <input type="password" name="db_pass" class="form-control" placeholder="db_password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">DB Name</label>
                        <input type="text" name="db_name" class="form-control" value="absen_santri_db" required>
                    </div>
                    
                    <button type="submit" name="install" class="btn btn-primary w-100 btn-install mt-4 shadow-sm">
                        Mulai Instalasi <i class="fas fa-rocket ms-2"></i>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-white border-0 text-center pb-4">
            <small class="text-muted">Absen Digital &copy; 2026</small>
        </div>
    </div>
</div>

</body>
</html>
