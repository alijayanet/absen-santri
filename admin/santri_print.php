<?php
require_once '../includes/db.php';

// Check auth
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../login.php");
    exit;
}

$current_role = $_SESSION['admin_role'] ?? 'admin';
$is_admin = $current_role === 'admin';
$current_user_id = (int)($_SESSION['admin_id'] ?? 0);

$where_parts = [];
$filter_label = "Semua";
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $where_parts[] = "id = $id";
} elseif (isset($_GET['kelas']) && !empty($_GET['kelas'])) {
    $kelas = $conn->real_escape_string($_GET['kelas']);
    $where_parts[] = "class_name = '$kelas'";
    $filter_label = htmlspecialchars($_GET['kelas']);
}

if (!$is_admin) {
    $where_parts[] = "teacher_id = $current_user_id";
}
$where = '';
if (!empty($where_parts)) {
    $where = "WHERE " . implode(' AND ', $where_parts);
}

$res = $conn->query("SELECT * FROM santri $where ORDER BY class_name ASC, name ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak ID Card - <?= htmlspecialchars($app_settings['app_name'], ENT_QUOTES, 'UTF-8') ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f4f6f9; }
    </style>
</head>
<body class="p-3">

<div class="container text-center mb-4 no-print">
    <button class="btn btn-primary rounded-pill px-4" onclick="window.print()"><i class="fas fa-print"></i> Cetak / Save PDF</button>
    <button class="btn btn-secondary rounded-pill px-4" onclick="window.close()">Tutup</button>
</div>

<div class="d-flex flex-wrap justify-content-center">
    <?php if($res->num_rows > 0): ?>
        <?php while($row = $res->fetch_assoc()): ?>
            <div class="id-card">
                <div class="id-card-header">
                    <div class="d-flex align-items-center justify-content-center">
                        <?php if(!empty($app_settings['logo'])): ?>
                            <img src="../assets/images/<?= htmlspecialchars($app_settings['logo']) ?>" alt="Logo" class="id-card-logo me-2">
                        <?php endif; ?>
                        <div class="text-center">
                            <span class="inst-name d-block"><?= htmlspecialchars($app_settings['app_name']) ?></span>
                            <span class="card-label small"><?= htmlspecialchars($app_settings['card_title'] ?? 'KARTU IDENTITAS MURID') ?></span>
                        </div>
                    </div>
                </div>
                <div class="id-card-body">
                    <div class="id-phone-photo-wrap">
                        <?php if(!empty($row['photo'])): ?>
                            <img src="../assets/images/<?= htmlspecialchars($row['photo']) ?>" alt="Foto">
                        <?php else: ?>
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['name']) ?>&background=random&size=128" alt="Avatar">
                        <?php endif; ?>
                    </div>
                    
                    <h5 class="id-card-name"><?= htmlspecialchars($row['name']) ?></h5>
                    <p class="id-card-info">NIS: <?= htmlspecialchars($row['nis']) ?> | Kelas: <?= htmlspecialchars($row['class_name']) ?></p>
                    
                    <div class="id-card-qrcode">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= $row['qrcode_hash'] ?>" alt="QR" width="90">
                    </div>
                </div>
                <div class="id-card-footer">
                    <?= !empty($app_settings['card_footer']) ? $app_settings['card_footer'] : "Gunakan kartu ini untuk absensi digital.<br>\nHarap tidak merusak atau mencoret barcode." ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Data kosong.</p>
    <?php endif; ?>
</div>

</body>
</html>
