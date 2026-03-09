<?php
require_once 'includes/db.php';
require_once 'includes/mpwa_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['qrcode'])) {
    
    // Auto-Reset Absensi Setiap Berganti Bulan
    // Mengecek tanggal absensi terakhir
    $cek_bulan = $conn->query("SELECT scan_date FROM attendance ORDER BY id DESC LIMIT 1");
    if($cek_bulan->num_rows > 0) {
        $last_scan = $cek_bulan->fetch_assoc()['scan_date'];
        $last_month = date('Y-m', strtotime($last_scan));
        $current_month = date('Y-m');
        
        // Jika bulan ini berbeda dengan bulan data terakhir, reset tabel absensi
        if($last_month !== $current_month) {
            $conn->query("TRUNCATE TABLE attendance");
        }
    }

    $qrcode = $conn->real_escape_string($_POST['qrcode']);
    $today = date('Y-m-d');
    $time = date('H:i:s');

    // Find santri by qrcode
    $sql = "SELECT * FROM santri WHERE qrcode_hash = '$qrcode' LIMIT 1";
    $res = $conn->query($sql);

    if ($res->num_rows > 0) {
        $santri = $res->fetch_assoc();
        $santri_id = $santri['id'];

        // Cek absen hari ini
        $cek = $conn->query("SELECT id FROM attendance WHERE santri_id = $santri_id AND scan_date = '$today'");
        
        if ($cek->num_rows == 0) {
            // Catat absen
            $conn->query("INSERT INTO attendance (santri_id, scan_date, scan_time, status) VALUES ($santri_id, '$today', '$time', 'Hadir')");

            // Get sequence number for today
            $count_res = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE scan_date = '$today'");
            $sequence_no = $count_res->fetch_assoc()['total'];

            // Send WA Notif to Parent
            if (!empty($app_settings['mpwa_url']) && !empty($app_settings['mpwa_token'])) {
                $inst_type = !empty($app_settings['institution_type']) ? $app_settings['institution_type'] : 'pesantren';
                $msg_parent = "Assalamualaikum.\n\nINFO KEHADIRAN\nNama: *{$santri['name']}*\nNIS: {$santri['nis']}\nKelas: {$santri['class_name']}\n\nTelah *HADIR* di $inst_type pada tanggal " . date('d M Y') . " pukul $time.\n\nTerima Kasih.\n_{$app_settings['app_name']}_";
                send_wa_notification($app_settings['mpwa_url'], $app_settings['mpwa_token'], $app_settings['mpwa_sender'], $santri['parent_phone'], $msg_parent);
                
                // Send WA Notif to Admin (Real-time Sequence)
                if (!empty($app_settings['admin_phone'])) {
                    $msg_admin = "📢 *LAPORAN ABSENSI*\n\nUrutan ke: *#$sequence_no*\nNama: *{$santri['name']}*\nNIS: {$santri['nis']}\nKelas: {$santri['class_name']}\nJam: $time\n\n_Sistem Absensi Digital_";
                    send_wa_notification($app_settings['mpwa_url'], $app_settings['mpwa_token'], $app_settings['mpwa_sender'], $app_settings['admin_phone'], $msg_admin);
                }
            }

            echo json_encode(['status' => 'success', 'message' => 'Absen berhasil: ' . $santri['name'], 'data' => ['name' => $santri['name'], 'nis' => $santri['nis'], 'class_name' => $santri['class_name'], 'photo' => $santri['photo']]]);
        } else {
            echo json_encode(['status' => 'warning', 'message' => 'Sudah absen hari ini: ' . $santri['name'], 'data' => ['name' => $santri['name'], 'nis' => $santri['nis'], 'class_name' => $santri['class_name'], 'photo' => $santri['photo']]]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'QR Code tidak terdaftar!']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
