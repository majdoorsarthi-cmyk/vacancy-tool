<?php
$plain_password = "admin123"; // <-- यह आपका वर्तमान पासवर्ड है
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
echo "आपका HASHED पासवर्ड है: " . $hashed_password;
?>