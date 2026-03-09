<?php
require_once 'header.php';

$filter_date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : date('Y-m-d');

$sql = "
    SELECT a.*, s.nis, s.name, s.class_name, s.gender 
    FROM attendance a 
    JOIN santri s ON a.santri_id = s.id 
    WHERE a.scan_date = '$filter_date'
    ORDER BY a.scan_time DESC
";
$res = $conn->query($sql);

// Statistik Hari ini
$hadir = $res->num_rows;

// Handle Manual Input (Sakit/Izin/Alpa)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['manual_attendance'])) {
    $santri_id = (int)$_POST['santri_id'];
    $status = $conn->real_escape_string($_POST['status']);
    $scan_date = $conn->real_escape_string($_POST['scan_date']);
    $scan_time = date('H:i:s');

    // Cek jika sudah ada data di tanggal tersebut
    $cek = $conn->query("SELECT id FROM attendance WHERE santri_id = $santri_id AND scan_date = '$scan_date'");
    if ($cek->num_rows > 0) {
        $conn->query("UPDATE attendance SET status = '$status' WHERE santri_id = $santri_id AND scan_date = '$scan_date'");
    } else {
        $conn->query("INSERT INTO attendance (santri_id, scan_date, scan_time, status) VALUES ($santri_id, '$scan_date', '$scan_time', '$status')");
    }
    echo "<script>window.location='attendance.php?date=$scan_date&msg=saved';</script>";
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Laporan Kehadiran</h4>
    <div>
        <button class="btn btn-success btn-sm rounded-pill px-3 me-2" data-bs-toggle="modal" data-bs-target="#modalManual">
            <i class="fas fa-plus-circle me-1"></i> Input Manual (S/I/A)
        </button>
        <a href="attendance_print.php?date=<?= $filter_date ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3">
            <i class="fas fa-print me-1"></i> Cetak Laporan
        </a>
    </div>
</div>

<?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Data absensi berhasil diperbarui!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" class="row align-items-end g-3">
            <div class="col-md-4">
                <label class="form-label fw-medium"><i class="far fa-calendar-alt me-1"></i> Pilih Tanggal</label>
                <input type="date" name="date" class="form-control" value="<?= $filter_date ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <div class="border rounded px-3 py-2 d-inline-block bg-light">
                    <span class="text-muted small">Total Hadir:</span>
                    <span class="fw-bold ms-2 text-success fs-5"><?= $hadir ?> Murid</span>
                </div>
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
                        <th>NIS</th>
                        <th>Nama Murid</th>
                        <th>Kelas</th>
                        <th>L/P</th>
                        <th>Jam Scan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($res->num_rows > 0): $no=1; ?>
                        <?php while($row = $res->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['nis']) ?></td>
                                <td class="fw-medium"><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['class_name']) ?></td>
                                <td><?= $row['gender'] ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="far fa-clock me-1"></i> <?= $row['scan_time'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $status = $row['status'];
                                    $badge_class = 'bg-success';
                                    if($status == 'Sakit') $badge_class = 'bg-warning';
                                    if($status == 'Izin') $badge_class = 'bg-info';
                                    if($status == 'Alpa') $badge_class = 'bg-danger';
                                    ?>
                                    <span class="badge <?= $badge_class ?> bg-opacity-10 text-<?= str_replace('bg-', '', $badge_class) ?> border border-<?= str_replace('bg-', '', $badge_class) ?> border-opacity-25 py-1 px-2 text-capitalize">
                                        <?php if($status == 'Hadir'): ?>
                                            <i class="fas fa-check-circle me-1"></i>
                                        <?php elseif($status == 'Sakit'): ?>
                                            <i class="fas fa-medkit me-1"></i>
                                        <?php elseif($status == 'Izin'): ?>
                                            <i class="fas fa-envelope me-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle me-1"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($status) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>
                                Tidak ada data absensi pada tanggal <?= date('d M Y', strtotime($filter_date)) ?>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<!-- Modal Manual Input -->
<div class="modal fade" id="modalManual" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="manual_attendance" value="1">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Input Absensi Manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Murid</label>
                        <select name="santri_id" class="form-select" required>
                            <option value="">-- Pilih Murid --</option>
                            <?php 
                            $santris = $conn->query("SELECT id, name, nis FROM santri ORDER BY name ASC");
                            while($s = $santris->fetch_assoc()):
                            ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['nis'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="scan_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status Kehadiran</label>
                        <select name="status" class="form-select" required>
                            <option value="Sakit">Sakit</option>
                            <option value="Izin">Izin</option>
                            <option value="Alpa">Alpa</option>
                            <option value="Hadir">Hadir (Manual)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4">Simpan Absensi</button>
                </div>
            </form>
        </div>
    </div>
</div>
