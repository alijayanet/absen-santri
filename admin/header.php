<?php
require_once '../includes/db.php';

// Check auth
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../includes/header.php';
?>
<!-- Sidebar -->
<div class="sidebar">
    <div class="logo-area">
        <h5 class="fw-bold mb-0 text-white"><i class="fas fa-university me-2"></i>Admin Panel</h5>
        <small><?= htmlspecialchars($app_settings['app_name']) ?></small>
    </div>
    
    <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a href="santri.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'santri.php' || basename($_SERVER['PHP_SELF']) == 'santri_print.php') ? 'active' : '' ?>">
        <i class="fas fa-users"></i> Data Absensi
    </a>
    <a href="attendance.php" class="<?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : '' ?>">
        <i class="fas fa-calendar-check"></i> Kehadiran Harian
    </a>
    <a href="recap.php" class="<?= basename($_SERVER['PHP_SELF']) == 'recap.php' ? 'active' : '' ?>">
        <i class="fas fa-file-invoice"></i> Rekap Bulanan
    </a>
    <a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
        <i class="fas fa-cog"></i> Pengaturan
    </a>
    <hr class="border-secondary mx-3">
    <a href="../scan.php" target="_blank">
        <i class="fas fa-qrcode"></i> Buka Scanner
    </a>
    <a href="../logout.php" class="text-danger mt-3">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<!-- Main Content wrapper -->
<div class="main-content">
    <!-- Topbar -->
    <div class="top-navbar d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold d-none d-md-block">Hi, <?= htmlspecialchars($_SESSION['admin_name']) ?></h5>
        <h5 class="mb-0 fw-bold d-block d-md-none"><i class="fas fa-bars"></i></h5>
        <span class="text-muted"><i class="far fa-calendar-alt me-1"></i> <?= date('d M Y') ?></span>
    </div>
    <div class="p-4 pb-5 mb-5 mb-md-0">

<!-- Bottom Navbar for Mobile -->
<div class="bottom-navbar d-flex d-md-none justify-content-around align-items-center">
    <a href="index.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>
    <a href="santri.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'santri.php' || basename($_SERVER['PHP_SELF']) == 'santri_print.php') ? 'active' : '' ?>">
        <i class="fas fa-users"></i>
        <span>Absensi</span>
    </a>
    <a href="attendance.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : '' ?>">
        <i class="fas fa-calendar-day"></i>
        <span>Harian</span>
    </a>
    <a href="recap.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'recap.php' ? 'active' : '' ?>">
        <i class="fas fa-file-invoice"></i>
        <span>Rekap</span>
    </a>
    <a href="settings.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
        <i class="fas fa-cog"></i>
        <span>Menu</span>
    </a>
</div>
