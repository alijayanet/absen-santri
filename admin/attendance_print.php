<?php
require_once '../includes/db.php';

// Check auth
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../login.php");
    exit;
}

$filter_date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : date('Y-m-d');
$filter_class = isset($_GET['kelas']) ? $conn->real_escape_string($_GET['kelas']) : '';

$class_where = '';
if (!empty($filter_class)) {
    $class_where = "AND s.class_name = '$filter_class'";
}

$sql = "
    SELECT a.*, s.nis, s.name, s.class_name, s.gender 
    FROM attendance a 
    JOIN santri s ON a.santri_id = s.id 
    WHERE a.scan_date = '$filter_date' $class_where
    ORDER BY a.scan_time ASC
";
$res = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Kehadiran - <?= $app_settings['app_name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: white; color: #333; }
        .print-header { border-bottom: 2px solid #333; margin-bottom: 20px; padding-bottom: 10px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            .container { width: 100%; max-width: none; }
        }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="no-print text-center mb-4">
        <button class="btn btn-primary rounded-pill px-4" onclick="window.print()"><i class="fas fa-print me-1"></i> Cetak / Save PDF</button>
        <button class="btn btn-secondary rounded-pill px-4" onclick="window.close()">Tutup</button>
    </div>

    <div class="print-header text-center">
        <h4 class="fw-bold mb-1">LAPORAN KEHADIRAN</h4>
        <h5 class="mb-1"><?= htmlspecialchars($app_settings['app_name']) ?></h5>
        <p class="mb-0">Tanggal: <strong><?= date('d F Y', strtotime($filter_date)) ?></strong>
        <?php if(!empty($filter_class)): ?>
            &mdash; Kelas: <strong><?= htmlspecialchars($filter_class) ?></strong>
        <?php endif; ?>
        </p>
    </div>

    <table class="table table-bordered align-middle">
        <thead class="table-light text-center">
            <tr>
                <th width="50">No</th>
                <th>NIS</th>
                <th>Nama Murid</th>
                <th>Kelas</th>
                <th>L/P</th>
                <th>Jam Absen</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if($res->num_rows > 0): $no=1; ?>
                <?php while($row = $res->fetch_assoc()): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['nis']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['class_name']) ?></td>
                        <td class="text-center"><?= $row['gender'] ?></td>
                        <td class="text-center"><?= date('H:i', strtotime($row['scan_time'])) ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['status']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Tidak ada data kehadiran pada tanggal ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="mt-4 d-flex justify-content-between">
        <div class="text-center" style="width: 200px;">
            <p class="mb-5">Mengetahui,</p>
            <p class="fw-bold mb-0">( ............................ )</p>
            <p class="small text-muted">Kepala Instansi</p>
        </div>
        <div class="text-center" style="width: 200px;">
            <p class="mb-5">Dicetak pada: <?= date('d/m/Y H:i') ?></p>
            <p class="fw-bold mb-0">( ............................ )</p>
            <p class="small text-muted">Admin / Petugas</p>
        </div>
    </div>
</div>

</body>
</html>
