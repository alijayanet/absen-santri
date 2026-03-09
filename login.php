<?php
require_once 'includes/db.php';

if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin/index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_name'] = $user['name'];
            header("Location: admin/index.php");
            exit;
        } else {
            $error = 'Password salah!';
        }
    } else {
        $error = 'Username tidak ditemukan!';
    }
}

require_once 'includes/header.php';
?>
<div class="login-page">
    <div class="login-card">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-primary-custom">
                <i class="fas fa-mosque me-2"></i><?= isset($app_settings['app_name']) ? htmlspecialchars($app_settings['app_name']) : 'Absen Santri Digital' ?>
            </h3>
            <p class="text-muted">Silakan login untuk mengelola sistem</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="username" class="form-control" required placeholder="Masukkan username">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control" required placeholder="Masukkan password">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary bg-primary-custom w-100 py-2 fw-semibold">
                Login <i class="fas fa-sign-in-alt ms-1"></i>
            </button>
        </form>
        <div class="mt-4 text-center">
            <a href="scan.php" class="text-decoration-none">
                <i class="fas fa-qrcode"></i> Buka Halaman Scan QR
            </a>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
