<?php
// 1. PHP में भारतीय समय (Asia/Kolkata) सेट करें
date_default_timezone_set('Asia/Kolkata');

// Aiven डेटाबेस कनेक्शन सेटिंग्स
$host = "mysql-9d9cc53-majdoorsarthi-d1a8.k.aivencloud.com";
$port = 13848;
$username = "avnadmin";
$password = "AVNS_CXh977fYw0GUSdyTCUU";
$dbname = "defaultdb";

// SSL कनेक्शन बनाना (Aiven के लिए आवश्यक)
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

// डेटाबेस से कनेक्ट करना
if (!mysqli_real_connect($conn, $host, $username, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("Connection failed: " . mysqli_connect_error());
}

// 2. MySQL डेटाबेस सर्वर के लिए भी टाइमज़ोन IST (+05:30) सेट करें
$conn->query("SET time_zone = '+05:30'");

// '0000-00-00' वाली डेट एरर को फिक्स करने के लिए
$conn->query("SET sql_mode=''");

// हिंदी फोंट सही से दिखाने के लिए
$conn->set_charset("utf8mb4");
?>
