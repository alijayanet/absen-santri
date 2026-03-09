<?php
require_once '../includes/db.php';

// Check auth
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../login.php");
    exit;
}

$id_filter = "";
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $id_filter = "WHERE id = $id";
}

$res = $conn->query("SELECT * FROM santri $id_filter ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak ID Card Murid - <?= $app_settings['app_name'] ?></title>
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
                    KARTU IDENTITAS MURID
                    <br><small><?= htmlspecialchars($app_settings['app_name']) ?></small>
                </div>
                <div class="id-card-body">
                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($row['name']) ?></h5>
                    <p class="text-muted small mb-2">NIS: <?= htmlspecialchars($row['nis']) ?> | Kelas: <?= htmlspecialchars($row['class_name']) ?></p>
                    
                    <div class="id-card-qrcode">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= $row['qrcode_hash'] ?>" alt="QR" width="120" class="border p-1 rounded">
                    </div>
                </div>
                <div class="id-card-footer">
                    Gunakan kartu ini untuk absensi digital.<br>
                    Harap tidak merusak atau mencoret barcode.
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Data kosong.</p>
    <?php endif; ?>
</div>

</body>
</html>
