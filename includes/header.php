<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($app_settings['app_name']) ? $app_settings['app_name'] : 'Absen Santri Digital' ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php 
        // Get dynamic base path instead of hardcoded subfolder
        $script_path = $_SERVER['PHP_SELF']; // e.g., /absen-digital/admin/index.php or /index.php
        $current_dir = dirname($script_path); // e.g., /absen-digital/admin or /
        
        // If we are in the admin folder, we go up one level to get the project root
        if (basename($current_dir) == 'admin') {
            $root_path = dirname($current_dir);
        } else {
            $root_path = $current_dir;
        }
        
        // Ensure root_path ends with a single slash
        $root_path = rtrim($root_path, '/\\') . '/';
        
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . $root_path;
    ?>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
