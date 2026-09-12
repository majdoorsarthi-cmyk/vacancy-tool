<?php
// 1. आवश्यक फाइलें शामिल करें
session_start();
include '../includes/db_connect.php'; 
include 'includes/admin_header.php'; 

// 2. एक्सेस नियंत्रण: सुनिश्चित करें कि केवल लॉग इन एडमिन ही एक्सेस कर सकता है
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$admin_id = $_GET['id'] ?? null;
$message = '';
$error = '';
$admin_data = null;

// 3. ID सत्यापन और मौजूदा डेटा लोड करना
if (empty($admin_id) || !is_numeric($admin_id)) {
    $error = "❌ अमान्य एडमिन ID।";
} else {
    $stmt_fetch = $conn->prepare("SELECT id, username FROM admins WHERE id = ?");
    $stmt_fetch->bind_param("i", $admin_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();

    if ($result_fetch->num_rows === 0) {
        $error = "❌ एडमिन यूजर नहीं मिला।";
    } else {
        $admin_data = $result_fetch->fetch_assoc();
    }
    $stmt_fetch->close();
}


// 4. फॉर्म सबमिशन हैंडल करें
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $admin_data) {
    $new_username = trim($_POST['username'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $update_fields = [];
    $params = [];
    $types = '';

    // A. यूजरनेम अपडेट लॉजिक
    if ($new_username != $admin_data['username']) {
        // चेक करें कि नया यूजरनेम पहले से मौजूद तो नहीं है (खुद को छोड़कर)
        $stmt_check = $conn->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
        $stmt_check->bind_param("si", $new_username, $admin_id);
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if ($stmt_check->num_rows > 0) {
            $error = "यह यूजरनेम पहले से किसी अन्य एडमिन द्वारा उपयोग किया जा रहा है।";
        } else {
            $update_fields[] = "username = ?";
            $params[] = $new_username;
            $types .= 's';
        }
        $stmt_check->close();
    }

    // B. पासवर्ड अपडेट लॉजिक
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            $error = "पासवर्ड और पुष्टि पासवर्ड मेल नहीं खाते।";
        } elseif (strlen($new_password) < 6) {
            $error = "पासवर्ड कम से कम 6 वर्णों का होना चाहिए।";
        } else {
            // पासवर्ड को हैश करें
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_fields[] = "password = ?";
            $params[] = $hashed_password;
            $types .= 's';
        }
    }
    
    // C. डेटाबेस में अपडेट करें
    if (empty($error) && !empty($update_fields)) {
        $sql_update = "UPDATE admins SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $params[] = $admin_id; // अंतिम पैरामीटर ID है
        $types .= 'i';

        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param($types, ...$params);

        if ($stmt_update->execute()) {
            $message = "✅ एडमिन अकाउंट सफलतापूर्वक अपडेट कर दिया गया।";
            // अपडेट के बाद नया डेटा फिर से लोड करें
            $admin_data['username'] = $new_username ?: $admin_data['username'];
        } else {
            $error = "❌ अपडेट करने में त्रुटि: " . $stmt_update->error;
        }
        $stmt_update->close();
    } elseif (empty($error)) {
        $message = "📝 कोई बदलाव नहीं किया गया।";
    }
}
?>

<div class="main-content">
    <div class="container">
        <h2 class="page-title">⚙️ एडमिन अकाउंट एडिट करें</h2>
        
        <?php if ($admin_data): ?>

            <?php if ($message): ?>
                <div class="alert success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="form-container wide">
                <form action="edit_admin.php?id=<?php echo $admin_data['id']; ?>" method="POST">
                    
                    <p class="note">सिर्फ वही फ़ील्ड भरें जिसे आप बदलना चाहते हैं। पासवर्ड फ़ील्ड खाली छोड़ने पर पासवर्ड नहीं बदलेगा।</p>
                    
                    <div class="form-group">
                        <label for="username">यूजरनेम:</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($admin_data['username']); ?>" required>
                    </div>
                    
                    <hr>
                    
                    <h3>पासवर्ड बदलें (वैकल्पिक)</h3>
                    
                    <div class="form-group">
                        <label for="new_password">नया पासवर्ड:</label>
                        <input type="password" id="new_password" name="new_password" placeholder="नया पासवर्ड दर्ज करें">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">नए पासवर्ड की पुष्टि करें:</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="पुष्टि करें">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">अपडेट करें</button>
                    <a href="admin_list.php" class="btn btn-secondary">वापस सूची में जाएँ</a>

                </form>
            </div>
            
        <?php else: ?>
            <div class="alert error"><?php echo $error; ?></div>
            <a href="admin_list.php" class="btn btn-secondary">वापस सूची में जाएँ</a>
        <?php endif; ?>
    </div>
</div>

<?php 
if (isset($conn)) {
    $conn->close();
}
include 'includes/admin_footer.php'; 
?>