<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$is_logged_in = isset($_SESSION['admin_logged_in']);
$app_name = $app_settings['app_name'] ?? 'Absen Santri Digital';
$institution_type = $app_settings['institution_type'] ?? '';
$scanner_announcement = $app_settings['scanner_announcement'] ?? '';
$logo = $app_settings['logo'] ?? '';
?>

<div class="container py-5">
    <div class="row align-items-center g-4">
        <div class="col-md-7">
            <h1 class="fw-bold mb-3"><?= htmlspecialchars($app_name) ?></h1>
            <?php if (!empty($institution_type)): ?>
                <p class="text-muted mb-2">Tipe Instansi: <span class="fw-semibold"><?= htmlspecialchars($institution_type) ?></span></p>
            <?php endif; ?>
            <?php if (!empty($scanner_announcement)): ?>
                <div class="alert alert-light border mb-4">
                    <?= htmlspecialchars($scanner_announcement) ?>
                </div>
            <?php else: ?>
                <p class="text-muted mb-4">Portal utama sistem absensi QR.</p>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2">
                <?php if ($is_logged_in): ?>
                    <a href="admin/index.php" class="btn btn-primary rounded-pill px-4">
                        <i class="fas fa-gauge me-2"></i> Dashboard Admin/Guru
                    </a>
                    <a href="scan.php" class="btn btn-outline-primary rounded-pill px-4">
                        <i class="fas fa-qrcode me-2"></i> Buka Scanner
                    </a>
                    <a href="logout.php" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fas fa-right-from-bracket me-2"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary rounded-pill px-4">
                        <i class="fas fa-lock me-2"></i> Login Admin/Guru
                    </a>
                    <a href="login.php?next=<?= urlencode('scan.php') ?>" class="btn btn-outline-primary rounded-pill px-4">
                        <i class="fas fa-qrcode me-2"></i> Login untuk Scan
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-5 text-center">
            <?php if (!empty($logo) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $logo)): ?>
                <img src="assets/images/<?= htmlspecialchars($logo) ?>" alt="Logo" class="img-fluid" style="max-height: 180px;">
            <?php else: ?>
                <div class="rounded-4 border bg-light d-inline-flex align-items-center justify-content-center" style="width: 220px; height: 180px;">
                    <div class="text-center">
                        <i class="fas fa-school fa-3x text-primary mb-2"></i>
                        <div class="fw-semibold text-muted">Portal Absen</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <hr class="my-5">

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="me-3 text-primary"><i class="fas fa-gear fa-lg"></i></div>
                        <h6 class="fw-bold mb-0">Nama Aplikasi</h6>
                    </div>
                    <div class="text-muted"><?= htmlspecialchars($app_name) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="me-3 text-primary"><i class="fas fa-building-columns fa-lg"></i></div>
                        <h6 class="fw-bold mb-0">Tipe Instansi</h6>
                    </div>
                    <div class="text-muted"><?= !empty($institution_type) ? htmlspecialchars($institution_type) : '-' ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="me-3 text-primary"><i class="fas fa-bullhorn fa-lg"></i></div>
                        <h6 class="fw-bold mb-0">Pengumuman Scan</h6>
                    </div>
                    <div class="text-muted"><?= !empty($scanner_announcement) ? htmlspecialchars($scanner_announcement) : '-' ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
