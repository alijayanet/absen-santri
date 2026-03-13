<?php
require_once '../includes/db.php';

// Check auth
if (!isset($_SESSION['admin_logged_in'])) {
    exit('Unauthorized');
}
$current_role = $_SESSION['admin_role'] ?? 'admin';
$is_admin = $current_role === 'admin';
$current_user_id = (int)($_SESSION['admin_id'] ?? 0);

// Fetch santri (filtered by class if set)
$filter_class = isset($_GET['kelas']) ? $conn->real_escape_string($_GET['kelas']) : '';
$where_parts = [];
if (!empty($filter_class)) {
    $where_parts[] = "class_name = '$filter_class'";
}
if (!$is_admin) {
    $where_parts[] = "teacher_id = $current_user_id";
}
$where = '';
if (!empty($where_parts)) {
    $where = "WHERE " . implode(' AND ', $where_parts);
}
$res = $conn->query("SELECT nis, name, class_name, gender, parent_phone FROM santri $where ORDER BY class_name ASC, name ASC");

if ($res->num_rows > 0) {
    $label = !empty($filter_class) ? preg_replace('/[^a-zA-Z0-9]/', '_', $filter_class) : 'semua';
    $filename = "data_absensi_{$label}_" . date('Ymd_His') . ".csv";
    
    // Headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, ['NIS', 'NAMA LENGKAP', 'KELAS', 'L/P', 'NO WA WALI']);
    
    // Data rows
    while ($row = $res->fetch_assoc()) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
} else {
    echo "<script>alert('Data kosong!'); window.location='santri.php';</script>";
}
?>
