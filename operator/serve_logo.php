<?php
// Server ke internal path se direct file read karne ke liye
$logo_path = '/DATA/AppData/vacancy_portal/operator/vp.png';

if (file_exists($logo_path)) {
    header('Content-Type: image/png');
    header('Content-Length: ' . filesize($logo_path));
    readfile($logo_path);
    exit;
} else {
    // Agar server par file nahi milti to 404 header dega taaki fallback icon trigger ho sake
    header("HTTP/1.0 404 Not Found");
    exit;
}
?>