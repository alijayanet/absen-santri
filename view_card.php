<?php
require_once 'includes/db.php';

if (!isset($_GET['hash'])) {
    die("Akses ditolak.");
}

$hash = trim((string)$_GET['hash']);
if ($hash === '' || !preg_match('/^[a-f0-9]{32}$/i', $hash)) {
    die("Akses ditolak.");
}

$stmt = $conn->prepare("SELECT name, nis, class_name, photo, qrcode_hash FROM santri WHERE qrcode_hash = ? LIMIT 1");
if (!$stmt) {
    die("Server error.");
}
$stmt->bind_param("s", $hash);
$stmt->execute();
$stmt->bind_result($name, $nis, $class_name, $photo, $qrcode_hash);
$found = $stmt->fetch();
$stmt->close();

if (!$found) {
    die("Data tidak ditemukan.");
}

$row = [
    'name' => $name,
    'nis' => $nis,
    'class_name' => $class_name,
    'photo' => $photo,
    'qrcode_hash' => $qrcode_hash
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Identitas - <?= htmlspecialchars($row['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .id-card { margin: 0 auto; box-shadow: 0 30px 60px -12px rgba(0,0,0,0.25); }
        .btn-print { margin-top: 25px; }
        @media print { .no-print { display: none !important; } .id-card { box-shadow: none; border: 1px solid #e2e8f0; } }
    </style>
</head>
<body>

<div class="text-center">
    <div class="id-card">
        <div class="id-card-header">
            <div class="d-flex align-items-center justify-content-center">
                <?php if(!empty($app_settings['logo'])): ?>
                    <img src="assets/images/<?= htmlspecialchars($app_settings['logo']) ?>" alt="Logo" class="id-card-logo me-2">
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
                    <img src="assets/images/<?= htmlspecialchars($row['photo']) ?>" alt="Foto">
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

    <div class="no-print mt-4 d-flex gap-2 justify-content-center">
        <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="fas fa-print me-2"></i> Cetak Sekarang
        </button>
    </div>
    <p class="no-print mt-3 text-muted small">Silakan simpan halaman ini sebagai backup kartu digital Anda.</p>
</div>

</body>
</html>
