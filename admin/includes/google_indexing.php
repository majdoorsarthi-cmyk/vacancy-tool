<?php
// admin/includes/google_indexing.php

function sendToGoogleIndexing($url, $type = 'URL_UPDATED') {
    $jsonKeyFile = __DIR__ . '/google-service-account-key.json'; 

    if (!file_exists($jsonKeyFile)) {
        return ['success' => false, 'message' => 'Service Account Key File Missing!'];
    }

    $authConfig = json_decode(file_get_contents($jsonKeyFile), true);

    // JWT Header & Claim
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $now = time();
    $claim = json_encode([
        'iss' => $authConfig['client_email'],
        'scope' => 'https://www.googleapis.com/auth/indexing',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlClaim  = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claim));

    openssl_sign($base64UrlHeader . "." . $base64UrlClaim, $signature, $authConfig['private_key'], 'SHA256');
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    $jwt = $base64UrlHeader . "." . $base64UrlClaim . "." . $base64UrlSignature;

    // Get Access Token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion'  => $jwt
    ]));
    $response = curl_exec($ch);
    curl_close($ch);

    $tokenData = json_decode($response, true);
    if (empty($tokenData['access_token'])) {
        $errorMsg = 'Failed to retrieve access token.';
        saveIndexingLog($url, 'FAILED', $errorMsg);
        return ['success' => false, 'message' => $errorMsg];
    }

    $accessToken = $tokenData['access_token'];

    // Send Indexing API Request
    $apiEndpoint = 'https://indexing.googleapis.com/v1/urlNotifications:publish';
    $postData = json_encode([
        'url'  => $url,
        'type' => $type
    ]);

    $ch = curl_init($apiEndpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);

    $apiResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $successMsg = 'Google Indexing Request Sent Successfully!';
        saveIndexingLog($url, 'SUCCESS', $successMsg);
        return ['success' => true, 'message' => $successMsg];
    } else {
        $errorMsg = 'Error: ' . $apiResponse;
        saveIndexingLog($url, 'FAILED', $errorMsg);
        return ['success' => false, 'message' => $errorMsg];
    }
}

// 🛠️ ऑटोमैटिक डेटाबेस में लॉग सेव करने के लिए फंक्शन
function saveIndexingLog($url, $status, $message) {
    // ध्यान दें: आपकी मुख्य डेटाबेस कनेक्शन फाइल (जैसे db.php या config.php) यहाँ कनेक्ट होनी चाहिए
    global $conn; 

    if (isset($conn) && $conn) {
        // सुरक्षा के लिए सुरक्षित तरीके से क्वेरी चलाना
        $stmt = $conn->prepare("INSERT INTO indexing_logs (url, status, message) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $url, $status, $message);
            $stmt->execute();
            $stmt->close();
        }
    }
}