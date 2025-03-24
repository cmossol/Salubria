<?php
// URL to send the POST request to
$url = "http://192.168.1.185/control";

// Data to send in the POST request
$data = [
    'setting' => 'valve',
    'value' => 'pool'
];

// Initialize cURL session
$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute cURL request and get the response
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    echo 'cURL error: ' . curl_error($ch);
} else {
    echo 'Response from ESP8266: ' . $response;
}

// Close cURL session
curl_close($ch);
?>
