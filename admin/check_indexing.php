<?php
// 1. Session sabse pehle start hona chahiye (koi bhi HTML ya whitespace hone se pehle)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Admin Login Verification Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Agar login nahi hai to Access Denied message
    http_response_code(403);
    echo '<span style="color:#e11d48; font-weight:bold;">Unauthorized Access</span>';
    exit;
}

// 3. Database connection
include_once '../includes/db_connect.php';

// --- AAPKA INDEXING CHECK LOGIC HERE ---
// Udaaharan (Example) ke liye status response:
try {
    // Google Search Console API ya indexing database check logic
    $indexing_active = true; // Aapka status variable

    if ($indexing_active) {
        echo '<span style="color:#10b981; font-weight:800;"><i class="fa-solid fa-circle-check"></i> Connected & Active</span>';
    } else {
        echo '<span style="color:#f59e0b; font-weight:800;"><i class="fa-solid fa-circle-exclamation"></i> Pending</span>';
    }
} catch (Exception $e) {
    echo '<span style="color:#e11d48; font-weight:800;"><i class="fa-solid fa-triangle-exclamation"></i> Error</span>';
}
?>