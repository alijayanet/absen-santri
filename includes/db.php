<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "absen_santri_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
date_default_timezone_set('Asia/Jakarta');

// Get Settings
$settings_sql = "SELECT * FROM settings LIMIT 1";
$settings_result = $conn->query($settings_sql);
$app_settings = [];
if ($settings_result && $settings_result->num_rows > 0) {
    $app_settings = $settings_result->fetch_assoc();
} else {
    $app_settings = [
        'app_name' => 'Absen Santri Digital',
        'mpwa_url' => '',
        'mpwa_token' => '',
        'mpwa_sender' => '',
        'institution_type' => 'pesantren',
        'scanner_announcement' => 'Selamat datang di layanan Absensi Digital kami.'
    ];
}
?>
