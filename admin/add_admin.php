<?php
// 1. आवश्यक फाइलें शामिल करें
session_start();
include '../includes/db_connect.php'; 
include 'includes/admin_header.php'; // एडमिन हेडर शामिल करें

// 2. एक्सेस नियंत्रण: सुनिश्चित करें कि केवल लॉग इन एडमिन ही एक्सेस कर सकता है
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// 3. फॉर्म सबमिशन हैंडल करें
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // इनपुट सत्यापन
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "सभी फ़ील्ड भरना आवश्यक है।";
    } elseif ($password !== $confirm_password) {
        $error = "पासवर्ड और पुष्टि पासवर्ड मेल नहीं खाते।";
    } elseif (strlen($password) < 6) {
        $error = "पासवर्ड कम से कम 6 वर्णों का होना चाहिए।";
    } else {
        // 4. यूजरनेम की उपलब्धता जांचें
        $stmt_check = $conn->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error = "यह यूजरनेम पहले से मौजूद है। कृपया दूसरा चुनें।";
        } else {
            // 5. पासवर्ड को सुरक्षित रूप से हैश (एन्क्रिप्ट) करें
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // 6. डेटाबेस में नया एडमिन डालें
            $stmt_insert = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
            $stmt_insert->bind_param("ss", $username, $hashed_password);

            if ($stmt_insert->execute()) {
                $message = "✅ नया एडमिन '" . htmlspecialchars($username) . "' सफलतापूर्वक जोड़ दिया गया है।";
            } else {
                $error = "❌ एडमिन जोड़ने में त्रुटि: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
?>

<div class="main-content">
    <div class="container">
        <h2>👥 नया एडमिन जोड़ें</h2>
        
        <?php if ($message): ?>
            <div class="alert success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form action="add_admin.php" method="POST">
                
                <div class="form-group">
                    <label for="username">यूजरनेम (Username):</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">पासवर्ड (Password):</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">पासवर्ड की पुष्टि करें:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">एडमिन जोड़ें</button>
            </form>
        </div>
    </div>
</div>

<?php 
$conn->close();
include 'includes/admin_footer.php'; 
?>