<?php
// 1. सेशन शुरू करें
session_start();

// 2. सभी सेशन वैरिएबल को अनसेट करें
$_SESSION = array();

// 3. यदि सेशन कुकी मौजूद है, तो इसे हटा दें (सेशन को नष्ट करने के लिए सर्वोत्तम अभ्यास)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. सेशन को नष्ट करें
session_destroy();

// 5. एडमिन लॉगिन पेज पर रीडायरेक्ट करें
header("location: login.php");
exit;
?>