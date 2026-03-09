<?php
require_once 'includes/db.php';
require_once 'includes/mpwa_helper.php';

$message_result = "";
$debug_info = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_send'])) {
    $target_phone = $_POST['target_phone'];
    $test_message = $_POST['test_message'];
    
    // Use current settings
    $url = $app_settings['mpwa_url'];
    $token = $app_settings['mpwa_token'];
    $sender = $app_settings['mpwa_sender'];
    
    $message_result = "Sedang mengirim...";
    
    // Re-formatting for display
    if (strpos($target_phone, '0') === 0) $target_phone = '62' . substr($target_phone, 1);
    
    $res = send_wa_notification($url, $token, $sender, $target_phone, $test_message);
    
    if ($res) {
        $message_result = "<div class='alert alert-success mt-3'>Pesan Berhasil Dikirim! ✅</div>";
    } else {
        $message_result = "<div class='alert alert-danger mt-3'>Gagal Mengirim Pesan! ❌</div>";
    }
    
    // Read the last log entry for display
    if (file_exists('wa_debug.log')) {
        $logs = file('wa_debug.log');
        $last_logs = array_slice($logs, -10); // get last 10 lines
        $debug_info = implode("", $last_logs);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Kirim WA - Absen Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .test-card { max-width: 600px; margin: 50px auto; border-radius: 15px; border: none; box-shadow: 0 5px 25px rgba(0,0,0,0.1); }
        .header-test { background: #25d366; color: white; border-radius: 15px 15px 0 0; padding: 25px; text-align: center; }
        pre { background: #1e293b; color: #34d399; padding: 15px; border-radius: 8px; font-size: 12px; }
    </style>
</head>
<body>

<div class="container">
    <div class="card test-card">
        <div class="header-test">
            <i class="fab fa-whatsapp fa-3x mb-2"></i>
            <h4 class="fw-bold mb-0">WhatsApp API Tester</h4>
            <p class="mb-0 small">Uji Koneksi MPWA Server</p>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-info small">
                <strong>Config Saat Ini:</strong><br>
                URL: <code><?= htmlspecialchars($app_settings['mpwa_url'] ?: '-') ?></code><br>
                Sender: <code><?= htmlspecialchars($app_settings['mpwa_sender'] ?: '-') ?></code>
            </div>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nomor Tujuan (HP Admin/Lainnya)</label>
                    <input type="text" name="target_phone" class="form-control" placeholder="0812345678" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Isi Pesan</label>
                    <textarea name="test_message" class="form-control" rows="2">Halo! Ini adalah pesan percobaan dari sistem Absen Digital. 👋</textarea>
                </div>
                <button type="submit" name="test_send" class="btn btn-success w-100 fw-bold py-2">
                    <i class="fas fa-paper-plane me-2"></i> KIRIM TEST SEKARANG
                </button>
            </form>

            <?= $message_result ?>

            <?php if ($debug_info): ?>
                <div class="mt-4">
                    <label class="form-label small fw-bold text-muted">Log Debug Terakhir:</label>
                    <pre><?= htmlspecialchars($debug_info) ?></pre>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-white border-0 text-center pb-4">
            <a href="admin/settings.php" class="text-decoration-none small text-muted"><i class="fas fa-arrow-left me-1"></i> Kembali ke Pengaturan</a>
        </div>
    </div>
</div>

</body>
</html>
