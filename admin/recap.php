<?php
require_once 'header.php';

$filter_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$filter_class = isset($_GET['kelas']) ? $conn->real_escape_string($_GET['kelas']) : '';
$class_list = $conn->query("SELECT DISTINCT class_name FROM santri ORDER BY class_name ASC");

// Get all santri (filtered by class if set)
$class_where = !empty($filter_class) ? "WHERE class_name = '$filter_class'" : '';
$santri_res = $conn->query("SELECT id, name, nis, class_name FROM santri $class_where ORDER BY class_name ASC, name ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Rekap Bulanan</h4>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" class="row align-items-end g-3">
            <div class="col-md-3">
                <label class="form-label fw-medium"><i class="far fa-calendar-alt me-1"></i> Pilih Bulan</label>
                <input type="month" name="month" class="form-control" value="<?= $filter_month ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium"><i class="fas fa-filter me-1"></i> Filter Kelas</label>
                <select name="kelas" class="form-select">
                    <option value="">-- Semua Kelas --</option>
                    <?php while($c = $class_list->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($c['class_name']) ?>" <?= $filter_class == $c['class_name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['class_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <span class="text-muted small"><?= $santri_res->num_rows ?> anggota ditemukan</span>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">No</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th class="text-center text-success">Hadir</th>
                        <th class="text-center text-warning">Sakit</th>
                        <th class="text-center text-info">Izin</th>
                        <th class="text-center text-danger">Alpa</th>
                        <th class="text-center fw-bold">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if($santri_res->num_rows > 0): 
                        $no=1; 
                        while($s = $santri_res->fetch_assoc()):
                            $sid = $s['id'];
                            
                            // Query counts for the month
                            $counts = $conn->query("
                                SELECT 
                                    SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) as h,
                                    SUM(CASE WHEN status = 'Sakit' THEN 1 ELSE 0 END) as s,
                                    SUM(CASE WHEN status = 'Izin' THEN 1 ELSE 0 END) as i,
                                    SUM(CASE WHEN status = 'Alpa' THEN 1 ELSE 0 END) as a
                                FROM attendance 
                                WHERE santri_id = $sid AND DATE_FORMAT(scan_date, '%Y-%m') = '$filter_month'
                            ")->fetch_assoc();
                            
                            $total = $counts['h'] + $counts['s'] + $counts['i'] + $counts['a'];
                    ?>
                            <tr>
                                <td class="ps-4"><?= $no++ ?></td>
                                <td class="fw-medium"><?= htmlspecialchars($s['name']) ?></td>
                                <td><?= htmlspecialchars($s['class_name']) ?></td>
                                <td class="text-center"><?= (int)$counts['h'] ?></td>
                                <td class="text-center"><?= (int)$counts['s'] ?></td>
                                <td class="text-center"><?= (int)$counts['i'] ?></td>
                                <td class="text-center"><?= (int)$counts['a'] ?></td>
                                <td class="text-center fw-bold"><?= $total ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Belum ada data.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
