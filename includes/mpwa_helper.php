<?php
function send_wa_notification($url, $token, $sender, $phone, $message) {
    if (empty($url) || empty($token) || empty($phone) || empty($sender)) return false;

    // Formatting phone - Robust version from reference
    $formatPhone = function($p) {
        $p = preg_replace('/[^0-9]/', '', $p);
        if (strpos($p, '0') === 0) {
            $p = '62' . substr($p, 1);
        } elseif (strpos($p, '8') === 0) {
            $p = '62' . $p;
        }
        return $p;
    };

    $phone = $formatPhone($phone);
    $sender = $formatPhone($sender);

    $data = [
        'api_key' => $token,
        'sender'  => $sender,
        'number'  => $phone,
        'message' => $message
    ];

    // Robust URL Detection from Reference
    $baseUrl = rtrim($url, '/');
    if (strpos($baseUrl, '/send-message') !== false) {
        $apiUrl = $baseUrl;
    } elseif (strpos($baseUrl, '/api') !== false) {
        $apiUrl = $baseUrl . '/send-message';
    } else {
        $apiUrl = $baseUrl . '/api/send-message';
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $apiUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    ));
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Logging for debug - Use dynamic path
    $log_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'wa_debug.log';
    $log_msg = "[" . date('Y-m-d H:i:s') . "] WA DEBUG (FINAL FIX):\n";
    $log_msg .= "Target URL: $apiUrl\n";
    $log_msg .= "Target Phone: $phone\n";
    $log_msg .= "HTTP Code: $http_code\n";
    if ($err) $log_msg .= "Curl Error: $err\n";
    $log_msg .= "Response Content: $response\n";
    $log_msg .= "-----------------------------------\n";
    file_put_contents($log_path, $log_msg, FILE_APPEND);

    return !$err && ($http_code === 200 || $http_code === 201 || !empty($response));
}
?>
