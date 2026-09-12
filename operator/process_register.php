<?php
// किसी भी प्रकार की PHP Warning को स्क्रीन पर रोकने के लिए
error_reporting(0);
ini_set('display_errors', 0);

// DB connection का path
include '../includes/db_connect.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Inputs Catch Karein
    $raw_username     = $_POST['username'] ?? '';
    $raw_password     = $_POST['password'] ?? '';
    $raw_email        = $_POST['email'] ?? '';
    $raw_whatsapp_no  = $_POST['whatsapp_no'] ?? '';
    
    $raw_identity_no  = $_POST['identity_no'] ?? $_POST['adhar_no'] ?? '';
    $raw_tax_no       = $_POST['tax_no'] ?? $_POST['pan_no'] ?? '';
    
    $raw_bank_name    = $_POST['bank_name'] ?? '';
    $raw_account_no   = $_POST['account_no'] ?? '';
    $raw_routing_code = $_POST['routing_code'] ?? $_POST['ifsc_code'] ?? '';
    $raw_utr_number   = $_POST['utr_number'] ?? '';

    // 2. Formatting & Escaping
    $username     = mysqli_real_escape_string($conn, trim($raw_username));
    $password     = password_hash($raw_password, PASSWORD_DEFAULT);
    $email        = mysqli_real_escape_string($conn, trim(strtolower($raw_email)));
    $whatsapp_no  = mysqli_real_escape_string($conn, trim($raw_whatsapp_no));
    
    $identity_no  = mysqli_real_escape_string($conn, trim($raw_identity_no));
    $tax_no       = mysqli_real_escape_string($conn, strtoupper(trim($raw_tax_no)));
    
    $bank_name    = mysqli_real_escape_string($conn, trim($raw_bank_name));
    $account_no   = mysqli_real_escape_string($conn, trim($raw_account_no));
    $routing_code = mysqli_real_escape_string($conn, strtoupper(trim($raw_routing_code)));
    $utr_number   = mysqli_real_escape_string($conn, trim($raw_utr_number));

    // 3. Duplicate Checking
    $conditions = array();

    if ($username !== '')     $conditions[] = "LOWER(username) = '" . strtolower($username) . "'";
    if ($email !== '')        $conditions[] = "LOWER(email) = '" . strtolower($email) . "'";
    if ($whatsapp_no !== '')  $conditions[] = "whatsapp_no = '$whatsapp_no'";
    if ($identity_no !== '')  $conditions[] = "adhar_no = '$identity_no'";
    if ($tax_no !== '')       $conditions[] = "pan_no = '$tax_no'";
    if ($account_no !== '')   $conditions[] = "account_no = '$account_no'";
    if ($utr_number !== '')   $conditions[] = "utr_number = '$utr_number'";

    if (count($conditions) > 0) {
        $check_sql = "SELECT username, email, whatsapp_no, adhar_no, pan_no, account_no, utr_number 
                      FROM operators 
                      WHERE " . implode(' OR ', $conditions) . " 
                      LIMIT 1";

        $check_result = mysqli_query($conn, $check_sql);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            echo "<script>
                    alert('⚠️ पंजीकरण अस्वीकृत! आपकी दी गई जानकारी में से कुछ विवरण (Username, Phone, Aadhaar, PAN या Account) पहले से दर्ज है।');
                    window.history.back();
                  </script>";
            exit();
        }
    }

    // 4. File Upload Check
    $target_dir = "../uploads/operators/";

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    if (isset($_FILES["screenshot"]) && $_FILES["screenshot"]["error"] == 0) {
        $ext = pathinfo($_FILES["screenshot"]["name"], PATHINFO_EXTENSION);
        $clean_file_name = time() . "_" . uniqid() . "." . $ext; 
        $target_file = $target_dir . $clean_file_name;

        if (move_uploaded_file($_FILES["screenshot"]["tmp_name"], $target_file)) {
            
            $sql = "INSERT INTO operators (
                username, 
                password, 
                payment_screenshot, 
                payment_verified, 
                is_active, 
                email, 
                whatsapp_no, 
                adhar_no, 
                pan_no, 
                bank_name, 
                account_no, 
                ifsc_code, 
                utr_number
            ) VALUES (
                '$username', 
                '$password', 
                '$target_file', 
                0, 
                0, 
                '$email', 
                '$whatsapp_no', 
                '$identity_no', 
                '$tax_no', 
                '$bank_name', 
                '$account_no', 
                '$routing_code', 
                '$utr_number'
            )";
            
            if (mysqli_query($conn, $sql)) {
                echo "<script>
                        alert('✅ Registration Successful! Admin verification ka intezar karein.');
                        window.location.href = 'login.php';
                      </script>";
                exit();
            } else {
                echo "<script>
                        alert('❌ Database Error! Kripya details check karein.');
                        window.history.back();
                      </script>";
                exit();
            }
        } else {
            echo "<script>
                    alert('❌ File upload fail ho gaya! Permission check karein.');
                    window.history.back();
                  </script>";
            exit();
        }
    } else {
        echo "<script>
                alert('❌ Please payment screenshot upload karein!');
                window.history.back();
              </script>";
        exit();
    }
}
?>