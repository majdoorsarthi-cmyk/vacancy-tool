<?php
// डेटाबेस कनेक्शन इम्पोर्ट करें
include('../db_connect.php'); 

$response = [
    'pending_withdrawals' => 0,
    'pending_operators' => 0,
    'pending_vacancies' => 0 // 🌟 यह नया जोड़ें
];

if ($conn) {
    // विड्रॉल काउंट
    $q1 = mysqli_query($conn, "SELECT COUNT(*) as total FROM withdrawal_requests WHERE status = 'pending'");
    if($q1) { $d1 = mysqli_fetch_assoc($q1); $response['pending_withdrawals'] = intval($d1['total']); }

    // ऑपरेटर काउंट
    $q2 = mysqli_query($conn, "SELECT COUNT(*) as total FROM operators WHERE is_active = 0");
    if($q2) { $d2 = mysqli_fetch_assoc($q2); $response['pending_operators'] = intval($d2['total']); }

    // 🌟 वैकेंसी काउंट (यह क्वेरी सुनिश्चित करें)
    $q3 = mysqli_query($conn, "SELECT COUNT(*) as total FROM vacancies WHERE status = 'pending'");
    if($q3) { $d3 = mysqli_fetch_assoc($q3); $response['pending_vacancies'] = intval($d3['total']); }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>