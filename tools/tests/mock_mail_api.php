<?php
/** Tiny stand-in for the email provider APIs, used by mail_test.php (php -S 127.0.0.1:8911 tools/tests/mock_mail_api.php). It writes each request to MOCK_MAIL_LOG. */
$log = getenv('MOCK_MAIL_LOG') ?: sys_get_temp_dir() . '/mock_mail.log';
$headers = function_exists('getallheaders') ? getallheaders() : [];
file_put_contents($log, json_encode(['path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 'headers' => $headers, 'body' => file_get_contents('php://input')]) . "\n", FILE_APPEND);
$fail = is_file($log . '.fail');
http_response_code($fail ? 401 : 202);
header('Content-Type: application/json');
echo $fail ? '{"message":"Invalid API key"}' : '{"id":"ok"}';
