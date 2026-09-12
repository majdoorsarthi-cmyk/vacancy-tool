<?php
// 1. PHP में भारतीय समय (Asia/Kolkata) सेट करें
date_default_timezone_set('Asia/Kolkata');

// डेटाबेस कनेक्शन सेटिंग्स
$servername = "100.101.113.86:3307"; 
$username = "root";
$password = "admin123"; 
$dbname = "job_portal_db"; 

// कनेक्शन बनाना
$conn = new mysqli($servername, $username, $password, $dbname);

// कनेक्शन चेक करना
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. MySQL डेटाबेस सर्वर के लिए भी टाइमज़ोन IST (+05:30) सेट करें
$conn->query("SET time_zone = '+05:30'");

// महेन्द्र जी, यह लाइन '0000-00-00' वाली डेट एरर को फिक्स कर देगी
$conn->query("SET sql_mode=''");

// हिंदी फोंट सही से दिखाने के लिए
$conn->set_charset("utf8mb4");
?>