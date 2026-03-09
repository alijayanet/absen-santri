<?php
require_once '../includes/db.php';

// Check auth
if (!isset($_SESSION['admin_logged_in'])) {
    exit('Unauthorized');
}

// Fetch all santri
$res = $conn->query("SELECT nis, name, class_name, gender, parent_phone FROM santri ORDER BY class_name ASC, name ASC");

if ($res->num_rows > 0) {
    $filename = "data_murid_" . date('Ymd_His') . ".csv";
    
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
