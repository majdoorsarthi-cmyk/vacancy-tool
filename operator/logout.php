<?php
// सेशन शुरू करें
session_start();

// 1. सभी सेशन वेरिएबल्स को खाली (unset) करें
$_SESSION = array();

// 2. अगर कुकीज़ का उपयोग हो रहा है, तो उन्हें एक्सपायर करके हटा दें
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. सेशन को डिस्ट्रॉय करें
session_destroy();

// 4. लॉगिन पेज पर रीडायरेक्ट करें
header("Location: login.php");
exit();
?>