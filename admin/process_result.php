<?php
// सत्र शुरू करें और सुरक्षा जाँचें
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("location: login.php");
    exit;
}

// 1. डेटाबेस कनेक्शन फ़ाइल शामिल करें (पाथ फिक्स किया गया)
include '../includes/db_connect.php'; 

// यह सुनिश्चित करें कि कनेक्शन सफल रहा
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // सुरक्षित रूप से सभी POST डेटा प्राप्त करें
    $post_id = $_POST['post_id'] ?? null;
    $title = trim($_POST['title']);
    $post_date = trim($_POST['post_date']);
    $result_link = trim($_POST['result_link']);
    $official_website = trim($_POST['official_website']);
    $full_details = trim($_POST['description']); // फ़ॉर्म फ़ील्ड 'description' है, DB फ़ील्ड 'full_details'
    $status = trim($_POST['status']);
    $category = 'result'; // फिक्स्ड कैटेगरी

    // इनपुट सत्यापन (कम से कम शीर्षक आवश्यक है)
    if (empty($title) || empty($post_date)) {
        header("Location: dashboard.php?error=missing_fields");
        exit;
    }
    
    // 2. डेटाबेस में डालने/अपडेट करने के लिए लॉजिक
    
    // UPDATE (संपादित करें)
    if (!empty($post_id)) {
        $sql = "UPDATE vacancies 
                SET title=?, full_details=?, post_date=?, status=?, result_link=?, official_website=? 
                WHERE id=? AND category='result'";
        
        $stmt = $conn->prepare($sql);
        // पैरामीटर: title, full_details, post_date, status, result_link, official_website, post_id
        $stmt->bind_param("ssssssi", 
                            $title, 
                            $full_details, 
                            $post_date, 
                            $status, 
                            $result_link, 
                            $official_website, 
                            $post_id);
        
        if ($stmt->execute()) {
            header("Location: manage_vacancies.php?category=result&success=updated");
        } else {
             // त्रुटि प्रबंधन
            header("Location: add_result.php?id=$post_id&error=db_update");
        }
        $stmt->close();
    } 
    // INSERT (नया जोड़ें)
    else {
        $sql = "INSERT INTO vacancies (title, full_details, category, post_date, status, result_link, official_website, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        // पैरामीटर: title, full_details, category, post_date, status, result_link, official_website
        $stmt->bind_param("sssssss", 
                            $title, 
                            $full_details, 
                            $category, 
                            $post_date, 
                            $status, 
                            $result_link, 
                            $official_website);
        
        if ($stmt->execute()) {
            header("Location: manage_vacancies.php?category=result&success=added");
        } else {
            // त्रुटि प्रबंधन
            header("Location: add_result.php?error=db_insert");
        }
        $stmt->close();
    }
} else {
    // यदि कोई POST रिक्वेस्ट नहीं है
    header("Location: dashboard.php");
}

// 3. कनेक्शन बंद करें
if (isset($conn)) {
    $conn->close();
}
exit;
?>