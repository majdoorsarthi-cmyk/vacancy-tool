<?php
error_reporting(0);
ini_set('display_errors', 0);

include '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action_type = $_POST['action_type'] ?? '';

    // ACTION 1: Password Reset Logic
    if ($action_type === 'reset_password') {
        $username     = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
        $whatsapp_no  = mysqli_real_escape_string($conn, trim($_POST['whatsapp_no'] ?? ''));
        $raw_password = $_POST['new_password'] ?? '';

        $check_sql = "SELECT id FROM operators WHERE LOWER(username) = '" . strtolower($username) . "' AND whatsapp_no = '$whatsapp_no' LIMIT 1";
        $res = mysqli_query($conn, $check_sql);

        if ($res && mysqli_num_rows($res) > 0) {
            $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE operators SET password = '$hashed_password' WHERE LOWER(username) = '" . strtolower($username) . "'";

            if (mysqli_query($conn, $update_sql)) {
                echo "<script>
                        alert('✅ आपका पासवर्ड सफलतापूर्वक बदल दिया गया है!');
                        window.location.href = 'login.php';
                      </script>";
                exit();
            }
        } else {
            echo "<script>
                    alert('⚠️ Username और WhatsApp Number मैच नहीं हुए!');
                    window.history.back();
                  </script>";
            exit();
        }
    }

    // ACTION 2: Find Username Logic
    if ($action_type === 'find_username') {
        $whatsapp_no  = mysqli_real_escape_string($conn, trim($_POST['whatsapp_no'] ?? ''));
        $identity_no  = mysqli_real_escape_string($conn, trim($_POST['identity_no'] ?? ''));

        $check_sql = "SELECT username FROM operators WHERE whatsapp_no = '$whatsapp_no' AND adhar_no = '$identity_no' LIMIT 1";
        $res = mysqli_query($conn, $check_sql);

        if ($res && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            $found_user = $row['username'];

            echo "<script>
                    alert('🔍 आपका Username है: [" . addslashes($found_user) . "]');
                    window.location.href = 'forgot_password.php';
                  </script>";
            exit();
        } else {
            echo "<script>
                    alert('⚠️ विवरण मेल नहीं खाया! WhatsApp No और Identity No पुनः जाँचें।');
                    window.history.back();
                  </script>";
            exit();
        }
    }
}
?>