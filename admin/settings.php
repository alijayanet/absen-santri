<?php
require_once 'header.php';

// Auto-Migration for Card Customization Columns
$check_cols = $conn->query("SHOW COLUMNS FROM settings LIKE 'card_title'");
if ($check_cols->num_rows == 0) {
    $conn->query("ALTER TABLE settings ADD COLUMN card_title VARCHAR(255) DEFAULT 'KARTU IDENTITAS MURID'");
}
$check_cols = $conn->query("SHOW COLUMNS FROM settings LIKE 'card_footer'");
if ($check_cols->num_rows == 0) {
    $conn->query("ALTER TABLE settings ADD COLUMN card_footer TEXT");
}
$check_cols = $conn->query("SHOW COLUMNS FROM settings LIKE 'wa_card_message'");
if ($check_cols->num_rows == 0) {
    $conn->query("ALTER TABLE settings ADD COLUMN wa_card_message TEXT");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $app_name = $conn->real_escape_string($_POST['app_name']);
    $institution_type = $conn->real_escape_string($_POST['institution_type']);
    $mpwa_url = $conn->real_escape_string($_POST['mpwa_url']);
    $mpwa_token = $conn->real_escape_string($_POST['mpwa_token']);
    $mpwa_sender = $conn->real_escape_string($_POST['mpwa_sender']);
    $admin_phone = $conn->real_escape_string($_POST['admin_phone']);
    $scanner_announcement = $conn->real_escape_string($_POST['scanner_announcement']);
    $card_title = $conn->real_escape_string($_POST['card_title']);
    $card_footer = $conn->real_escape_string($_POST['card_footer']);
    $wa_card_message = $conn->real_escape_string($_POST['wa_card_message']);
    
    if (strpos($admin_phone, '0') === 0) {
        $admin_phone = '62' . substr($admin_phone, 1);
    }
    if (strpos($mpwa_sender, '0') === 0) {
        $mpwa_sender = '62' . substr($mpwa_sender, 1);
    }

    // Check if exists
    $cek = $conn->query("SELECT id FROM settings LIMIT 1");
    if ($cek->num_rows > 0) {
        $conn->query("UPDATE settings SET app_name='$app_name', institution_type='$institution_type', mpwa_url='$mpwa_url', mpwa_token='$mpwa_token', mpwa_sender='$mpwa_sender', admin_phone='$admin_phone', scanner_announcement='$scanner_announcement', card_title='$card_title', card_footer='$card_footer', wa_card_message='$wa_card_message'");
    } else {
        $conn->query("INSERT INTO settings (app_name, institution_type, mpwa_url, mpwa_token, mpwa_sender, admin_phone, scanner_announcement, card_title, card_footer, wa_card_message) VALUES ('$app_name', '$institution_type', '$mpwa_url', '$mpwa_token', '$mpwa_sender', '$admin_phone', '$scanner_announcement', '$card_title', '$card_footer', '$wa_card_message')");
    }
    
    // Refresh admin credentials optionally (Not strictly required here, but logic is added later if needed)
    
    // update password admin
    if (!empty($_POST['admin_password'])) {
        $password = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$password' WHERE username='admin'");
    }

    echo "<script>window.location='settings.php?msg=updated';</script>";
    exit;
}

// Handle Test WA
$test_msg = "";
if (isset($_POST['test_wa'])) {
    require_once '../includes/mpwa_helper.php';
    $test_phone = $conn->real_escape_string($_POST['admin_phone']);
    if (empty($test_phone)) {
        $test_msg = "<div class='alert alert-warning'>Silakan isi nomor WhatsApp Admin terlebih dahulu untuk testing.</div>";
    } else {
        $msg = "Tes koneksi WhatsApp dari {$app_settings['app_name']}. Jika Anda menerima ini, konfigurasi sudah benar! ✅";
        $res = send_wa_notification($_POST['mpwa_url'], $_POST['mpwa_token'], $_POST['mpwa_sender'], $test_phone, $msg);
        
        if ($res) {
            $test_msg = "<div class='alert alert-success'>Pesan test berhasil dikirim ke $test_phone! Silakan cek HP Anda.</div>";
        } else {
            $test_msg = "<div class='alert alert-danger'>Gagal mengirim pesan. Silakan cek link wa_debug.log di root folder untuk detail error.</div>";
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Pengaturan <i class="fas fa-cog fa-sm"></i></h4>
</div>

<?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Pengaturan berhasil diperbarui!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST">
                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-laptop me-2"></i>Aplikasi & Instansi</h6>
                    
                    <div class="mb-4">
                        <label class="form-label">Nama Aplikasi / Sekolah / Instansi</label>
                        <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars($app_settings['app_name']) ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Tipe Instansi (<small>Muncul di pesan WA, misal: pesantren, sekolah, kantor</small>)</label>
                        <input type="text" name="institution_type" class="form-control" value="<?= htmlspecialchars($app_settings['institution_type'] ?? 'pesantren') ?>" placeholder="pesantren" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Pengumuman Halaman Scan (<small>Muncul di bawah scanner untuk dilihat wali/murid</small>)</label>
                        <textarea name="scanner_announcement" class="form-control" rows="3" placeholder="Masukkan pengumuman di sini..."><?= htmlspecialchars($app_settings['scanner_announcement'] ?? '') ?></textarea>
                    </div>

                    <h6 class="fw-bold text-info mb-3 border-top pt-4"><i class="fas fa-id-card me-2"></i>Kustomisasi Kartu Murid</h6>
                    <div class="mb-4">
                        <label class="form-label">Judul Kartu</label>
                        <input type="text" name="card_title" class="form-control" value="<?= htmlspecialchars($app_settings['card_title'] ?? 'KARTU IDENTITAS MURID') ?>" placeholder="Misal: KARTU IDENTITAS SANTRI">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Keterangan / Aturan Kartu (<small>Format HTML diperbolehkan</small>)</label>
                        <textarea name="card_footer" class="form-control" rows="3" placeholder="Misal: Gunakan kartu ini untuk absensi digital..."><?= htmlspecialchars($app_settings['card_footer'] ?? "Gunakan kartu ini untuk absensi digital.<br>\nHarap tidak merusak atau mencoret barcode.") ?></textarea>
                    </div>

                    <h6 class="fw-bold text-dark mb-3 border-top pt-4"><i class="fas fa-paper-plane me-2"></i>Pesan Pengiriman Kartu (WA)</h6>
                    <div class="mb-4">
                        <label class="form-label">Teks Pesan Saat Mengirim Kartu ke Wali</label>
                        <textarea name="wa_card_message" class="form-control" rows="3" placeholder="Misal: Halo, berikut adalah kartu identitas digital anak Anda..."><?= htmlspecialchars($app_settings['wa_card_message'] ?? "Halo, berikut adalah *Kartu Identitas Digital* [nama] untuk absensi di [instansi]. Silakan simpan gambar ini atau buka link berikut untuk mencetak mandiri:\n\n[link]") ?></textarea>
                        <div class="form-text small">Gunakan <code>[nama]</code> untuk nama murid, <code>[instansi]</code> untuk nama sekolah, dan <code>[link]</code> untuk link kartu.</div>
                    </div>

                    <h6 class="fw-bold text-success mb-3 border-top pt-4"><i class="fab fa-whatsapp me-2"></i>Integrasi MPWA (WhatsApp)</h6>
                    <div class="alert alert-info small py-2">
                        Dapatkan URL, Token, dan Nomor Pengirim dari provider MPWA Anda untuk mengaktifkan fitur notifikasi otomatis ke nomor wali santri.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL MPWA Server</label>
                        <input type="url" name="mpwa_url" class="form-control" value="<?= htmlspecialchars($app_settings['mpwa_url']) ?>" placeholder="https://api.domainku.com/">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Token / API Key</label>
                        <input type="text" name="mpwa_token" class="form-control" value="<?= htmlspecialchars($app_settings['mpwa_token']) ?>" placeholder="Masukkan token MPWA">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Nomor WhatsApp Pengirim / Sender</label>
                        <input type="text" name="mpwa_sender" class="form-control" value="<?= htmlspecialchars($app_settings['mpwa_sender']) ?>" placeholder="0812345678">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Nomor WhatsApp Admin / Guru (<small>Untuk rekap urutan absen</small>)</label>
                        <input type="text" name="admin_phone" class="form-control" value="<?= htmlspecialchars($app_settings['admin_phone'] ?? '') ?>" placeholder="0812345678">
                        <div class="form-text">Admin akan menerima notifikasi setiap kali santri melakukan scan.</div>
                    </div>

                    <div class="mb-4 p-3 bg-light rounded shadow-sm border">
                        <label class="form-label fw-bold mb-2"><i class="fas fa-vial me-2 text-warning"></i>Uji Coba Pengiriman</label>
                        <p class="small text-muted mb-3">Klik tombol di bawah untuk mengetes apakah pesan bisa terkirim ke nomor Admin di atas.</p>
                        <?= $test_msg ?>
                        <button type="submit" name="test_wa" class="btn btn-outline-success btn-sm rounded-pill px-4">
                            <i class="fab fa-whatsapp me-1"></i> Kirim Pesan Test Ke Admin
                        </button>
                    </div>

                    <h6 class="fw-bold text-danger mb-3 border-top pt-4"><i class="fas fa-user-shield me-2"></i>Keamanan (Admin)</h6>
                    <div class="mb-4">
                        <label class="form-label">Ubah Password Admin <small class="text-muted">(Kosongkan jika tidak ingin diubah)</small></label>
                        <input type="password" name="admin_password" class="form-control" placeholder="Masukkan password baru">
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 float-end">
                        <i class="fas fa-save me-1"></i> Simpan Pengaturan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
