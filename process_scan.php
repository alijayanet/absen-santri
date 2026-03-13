<?php
require_once 'includes/db.php';
require_once 'includes/mpwa_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Silakan login admin/guru terlebih dahulu.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['qrcode'])) {
    $qrcode = trim((string)$_POST['qrcode']);
    if ($qrcode === '' || !preg_match('/^[a-f0-9]{32}$/i', $qrcode)) {
        echo json_encode(['status' => 'error', 'message' => 'QR Code tidak valid!']);
        exit;
    }

    $today = date('Y-m-d');
    $time = date('H:i:s');

    // Find santri by qrcode
    $stmtSantri = $conn->prepare("SELECT s.id, s.name, s.nis, s.class_name, s.parent_phone, s.photo, u.name as teacher_name, u.phone as teacher_phone FROM santri s LEFT JOIN users u ON u.id = s.teacher_id WHERE s.qrcode_hash = ? LIMIT 1");
    if (!$stmtSantri) {
        echo json_encode(['status' => 'error', 'message' => 'Server error.']);
        exit;
    }
    $stmtSantri->bind_param("s", $qrcode);
    $stmtSantri->execute();
    $stmtSantri->bind_result($santri_id, $santri_name, $santri_nis, $santri_class, $santri_parent_phone, $santri_photo, $teacher_name, $teacher_phone);
    $found = $stmtSantri->fetch();
    $stmtSantri->close();

    if ($found) {

        // Cek absen hari ini
        $stmtCek = $conn->prepare("SELECT id FROM attendance WHERE santri_id = ? AND scan_date = ? LIMIT 1");
        if (!$stmtCek) {
            echo json_encode(['status' => 'error', 'message' => 'Server error.']);
            exit;
        }
        $stmtCek->bind_param("is", $santri_id, $today);
        $stmtCek->execute();
        $stmtCek->store_result();
        
        if ($stmtCek->num_rows === 0) {
            // Catat absen
            $stmtInsert = $conn->prepare("INSERT INTO attendance (santri_id, scan_date, scan_time, status) VALUES (?, ?, ?, 'Hadir')");
            if (!$stmtInsert) {
                $stmtCek->close();
                echo json_encode(['status' => 'error', 'message' => 'Server error.']);
                exit;
            }
            $stmtInsert->bind_param("iss", $santri_id, $today, $time);
            $stmtInsert->execute();
            $stmtInsert->close();

            // Get sequence number for today
            $stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE scan_date = ?");
            if (!$stmtCount) {
                $stmtCek->close();
                echo json_encode(['status' => 'error', 'message' => 'Server error.']);
                exit;
            }
            $stmtCount->bind_param("s", $today);
            $stmtCount->execute();
            $stmtCount->bind_result($sequence_no);
            $stmtCount->fetch();
            $stmtCount->close();

            // Send WA Notif to Parent & Guru terkait
            if (!empty($app_settings['mpwa_url']) && !empty($app_settings['mpwa_token'])) {
                $inst_type = !empty($app_settings['institution_type']) ? $app_settings['institution_type'] : 'pesantren';
                $msg_parent = "Assalamualaikum.\n\nINFO KEHADIRAN\nNama: *{$santri_name}*\nNIS: {$santri_nis}\nKelas: {$santri_class}\n\nTelah *HADIR* di $inst_type pada tanggal " . date('d M Y') . " pukul $time.\n\nTerima Kasih.\n_{$app_settings['app_name']}_";
                if (!empty($santri_parent_phone)) {
                    send_wa_notification($app_settings['mpwa_url'], $app_settings['mpwa_token'], $app_settings['mpwa_sender'], $santri_parent_phone, $msg_parent);
                }

                if (!empty($teacher_phone)) {
                    $teacher_label = !empty($teacher_name) ? $teacher_name : 'Guru';
                    $msg_teacher = "📢 *LAPORAN ABSENSI*\n\nUrutan ke: *#$sequence_no*\nNama: *{$santri_name}*\nNIS: {$santri_nis}\nKelas: {$santri_class}\nJam: $time\n\nYth. {$teacher_label}\n_{$app_settings['app_name']}_";
                    send_wa_notification($app_settings['mpwa_url'], $app_settings['mpwa_token'], $app_settings['mpwa_sender'], $teacher_phone, $msg_teacher);
                }
            }

            $stmtCek->close();
            echo json_encode(['status' => 'success', 'message' => 'Absen berhasil: ' . $santri_name, 'data' => ['name' => $santri_name, 'nis' => $santri_nis, 'class_name' => $santri_class, 'photo' => $santri_photo]]);
        } else {
            $stmtCek->close();
            echo json_encode(['status' => 'warning', 'message' => 'Sudah absen hari ini: ' . $santri_name, 'data' => ['name' => $santri_name, 'nis' => $santri_nis, 'class_name' => $santri_class, 'photo' => $santri_photo]]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'QR Code tidak terdaftar!']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
