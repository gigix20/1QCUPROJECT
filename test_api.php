<?php
$url = 'http://localhost/1QCUPROJECT/backend/routes/borrows_route.php?resource=borrows&action=getAll';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);
echo 'HTTP Code: ' . $httpCode . PHP_EOL;
echo 'Curl Error: ' . $curlError . PHP_EOL;
echo 'Response: ' . substr($response, 0, 500) . '...' . PHP_EOL;
?>