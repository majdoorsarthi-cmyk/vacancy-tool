<?php
// सत्र सुरक्षा जांच (Admin Session Check)
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// डेटाबेस कनेक्शन फ़ाइल
include 'db_connect.php'; 

// URL से ऑपरेटर आईडी और एक्शन प्राप्त करना
if (isset($_GET['action']) && isset($_GET['id'])) {
    $operator_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    // एक्शन के आधार पर नया स्टेटस तय करें (1 = सक्रिय, 0 = सस्पेंड)
    $new_status = ($action === 'approve') ? 1 : 0;
    
    // 1. ऑपरेटर का स्टेटस डेटाबेस में अपडेट करें
    $update_query = "UPDATE operators SET is_active = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $new_status, $operator_id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            
            // 2. ऑपरेटर के लिए लाइव लॉग (Notification) दर्ज करना
            $action_type = ($new_status === 1) ? "Approved" : "Rejected";
            $action_message = ($new_status === 1) 
                ? "एडमिन द्वारा आपकी ऑपरेटर प्रोफाइल को सफलतापूर्वक सक्रिय कर दिया गया है।" 
                : "एडमिन द्वारा आपकी ऑपरेटर प्रोफाइल को सस्पेंड/ब्लॉक कर दिया गया है।";
                
            $log_query = "INSERT INTO activity_logs (operator_id, action_type, action_message, created_at) VALUES (?, ?, ?, NOW())";
            $log_stmt = mysqli_prepare($conn, $log_query);
            
            if ($log_stmt) {
                mysqli_stmt_bind_param($log_stmt, "iss", $operator_id, $action_type, $action_message);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
            }
            
            // काम पूरा होने के बाद वापस उसी सत्यापन पेज पर भेजें जहाँ से एडमिन आया था
            header("Location: verify_operator.php?msg=success");
            exit();
        }
    }
}

// अगर कुछ गड़बड़ होती है तो भी वापस सुरक्षित भेजें
header("Location: verify_operator.php?msg=error");
exit();
?>