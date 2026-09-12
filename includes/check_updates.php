<?php
include 'db_connect.php'; 
header('Content-Type: application/json');

// पिछले 24 घंटों में पोस्ट की गई नई भर्तियों की संख्या
$twenty_four_hours_ago = date('Y-m-d H:i:s', strtotime('-24 hours'));

$sql = "SELECT COUNT(id) AS new_count, MAX(post_date) AS latest_post_date 
        FROM vacancies 
        WHERE status='live' AND post_date > ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $twenty_four_hours_ago);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode($data);
$stmt->close();
$conn->close();
?>