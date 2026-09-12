<?php
session_start();
// सभी सेशन वैरिएबल को अनसेट करें
$_SESSION = array();

// यदि सेशन कुकी मौजूद है, तो इसे हटा दें
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// सेशन को नष्ट करें
session_destroy();

// लॉगिन पेज पर रीडायरेक्ट करें
header("location: login.php");
exit;
?>