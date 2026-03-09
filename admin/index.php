<?php
require_once 'header.php';

// Get total santri
$res_santri = $conn->query("SELECT COUNT(*) as total FROM santri");
$total_santri = $res_santri->fetch_assoc()['total'];

// Get attendance today
$today = date('Y-m-d');
$res_att = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE scan_date = '$today'");
$total_att_today = $res_att->fetch_assoc()['total'];

// Get recent attendance list
$res_recent = $conn->query("
    SELECT a.*, s.name, s.nis, s.class_name 
    FROM attendance a 
    JOIN santri s ON a.santri_id = s.id 
    ORDER BY a.id DESC LIMIT 5
");

// Fetch data for Chart.js (Last 7 Days)
$chart_labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $label = date('d M', strtotime($d));
    $chart_labels[] = $label;
    
    $res_count = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE scan_date = '$d'");
    $chart_data[] = $res_count->fetch_assoc()['total'];
}
?>

<h4 class="fw-bold mb-4">Dashboard</h4>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100 overflow-hidden" style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); color: white; position: relative;">
            <div class="card-body p-4 position-relative" style="z-index: 2;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-2 small" style="letter-spacing: 1px;">Total Murid</h6>
                        <h2 class="mb-0 fw-bold display-5"><?= $total_santri ?></h2>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.1);">
                        <i class="fas fa-user-graduate fa-2x"></i>
                    </div>
                </div>
            </div>
            <i class="fas fa-users position-absolute" style="bottom: -20px; right: -10px; font-size: 8rem; opacity: 0.1; z-index: 1;"></i>
            <div class="card-footer bg-black bg-opacity-10 border-0 py-3 position-relative" style="z-index: 2;">
                <a href="santri.php" class="text-white text-decoration-none d-flex justify-content-between align-items-center small fw-bold">
                    <span>Kelola Database</span> <i class="fas fa-chevron-right fa-xs"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100 overflow-hidden" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; position: relative;">
            <div class="card-body p-4 position-relative" style="z-index: 2;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-2 small" style="letter-spacing: 1px;">Hadir Hari Ini</h6>
                        <h2 class="mb-0 fw-bold display-5"><?= $total_att_today ?></h2>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.1);">
                        <i class="fas fa-calendar-check fa-2x"></i>
                    </div>
                </div>
            </div>
            <i class="fas fa-check-double position-absolute" style="bottom: -20px; right: -10px; font-size: 8rem; opacity: 0.1; z-index: 1;"></i>
            <div class="card-footer bg-black bg-opacity-10 border-0 py-3 position-relative" style="z-index: 2;">
                <a href="attendance.php" class="text-white text-decoration-none d-flex justify-content-between align-items-center small fw-bold">
                    <span>Lihat Laporan</span> <i class="fas fa-chevron-right fa-xs"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100 bg-white group hover-shadow-lg transition-all" style="border-radius: 16px;">
            <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                <div class="mb-3 p-3 bg-primary bg-opacity-10 rounded-circle text-primary">
                    <i class="fas fa-qrcode fa-2x"></i>
                </div>
                <h5 class="fw-bold mb-2">Pindai QR Code</h5>
                <p class="text-muted small mb-3">Gunakan kamera untuk mencatat kehadiran santri secara real-time.</p>
                <a href="../scan.php" target="_blank" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                    <i class="fas fa-camera me-2"></i> Buka Scanner
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm border-0" style="border-radius: 20px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-chart-line me-2 text-primary"></i>Statistik Kehadiran 7 Hari Terakhir
                    </h6>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 rounded-pill py-2 small fw-bold">Statistik Mingguan</span>
                </div>
                <div style="height: 300px;">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-gray-800">Riwayat Terakhir</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nama Murid</th>
                        <th>NIS/Kelas</th>
                        <th>Waktu Scan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($res_recent->num_rows > 0): ?>
                        <?php while($row = $res_recent->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 fw-medium"><?= htmlspecialchars($row['name']) ?></td>
                                <td>
                                    <span class="d-block small"><?= htmlspecialchars($row['nis']) ?></span>
                                    <span class="text-muted small"><?= htmlspecialchars($row['class_name']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><i class="far fa-clock me-1"></i> <?= $row['scan_time'] ?></span>
                                    <div class="small text-muted mt-1"><?= date('d M Y', strtotime($row['scan_date'])) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2">
                                        <i class="fas fa-check-circle me-1"></i> <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Belum ada riwayat absensi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>
