<?php
// 1. आवश्यक फाइलें शामिल करें
session_start();
include '../includes/db_connect.php'; 
include 'includes/admin_header.php'; 

// 2. एक्सेस नियंत्रण
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// 3. फॉर्म सबमिशन हैंडल करें
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // लूप के माध्यम से सभी पोस्ट डेटा प्रोसेस करें
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'ad_code_') === 0) {
            $ad_slot_name = substr($key, 8); // 'ad_code_' के बाद का नाम
            $ad_code = $value;
            $is_active = isset($_POST['is_active_' . $ad_slot_name]) ? 1 : 0;
            
            // डेटाबेस में अपडेट या डालें (UPSERT)
            $stmt = $conn->prepare("INSERT INTO ads_settings (ad_slot_name, ad_code, is_active) 
                                    VALUES (?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE ad_code = VALUES(ad_code), is_active = VALUES(is_active)");
            $stmt->bind_param("ssi", $ad_slot_name, $ad_code, $is_active);
            
            if (!$stmt->execute()) {
                $error = "❌ " . htmlspecialchars($ad_slot_name) . " अपडेट करने में त्रुटि: " . $stmt->error;
                break;
            }
            $stmt->close();
        }
    }
    
    if (empty($error)) {
        $message = "✅ AdSense सेटिंग्स सफलतापूर्वक अपडेट कर दी गई हैं।";
    }
}

// 4. मौजूदा विज्ञापन सेटिंग्स फेच करें
$ads_settings = [];
$result = $conn->query("SELECT ad_slot_name, ad_code, is_active FROM ads_settings");
while ($row = $result->fetch_assoc()) {
    $ads_settings[$row['ad_slot_name']] = $row;
}

// डिफ़ॉल्ट स्लॉट सुनिश्चित करें
$default_slots = ['top_banner', 'bottom_banner', 'in_content_ad'];
foreach ($default_slots as $slot) {
    if (!isset($ads_settings[$slot])) {
        $ads_settings[$slot] = ['ad_slot_name' => $slot, 'ad_code' => '', 'is_active' => 0];
    }
}
?>

<div class="main-content">
    <div class="container">
        <h2 class="page-title">💰 AdSense / विज्ञापन प्रबंधन</h2>
        
        <?php if ($message): ?>
            <div class="alert success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-container wide">
            <p class="note info">अपने AdSense अकाउंट से विज्ञापन यूनिट कोड कॉपी करें और यहाँ पेस्ट करें।</p>
            
            <form action="ads_settings.php" method="POST">
            
            <?php foreach ($ads_settings as $slot_name => $data): ?>
                <div class="ad-slot-card">
                    <h3><?php echo strtoupper(str_replace('_', ' ', $slot_name)); ?> Slot</h3>
                    
                    <div class="form-group">
                        <label for="ad_code_<?php echo $slot_name; ?>">AdSense Code (HTML/JavaScript):</label>
                        <textarea id="ad_code_<?php echo $slot_name; ?>" name="ad_code_<?php echo $slot_name; ?>" rows="6"><?php echo htmlspecialchars($data['ad_code']); ?></textarea>
                        <small class="form-text">यह वह कोड है जो आपके Google AdSense खाते से उत्पन्न होता है।</small>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_active_<?php echo $slot_name; ?>" name="is_active_<?php echo $slot_name; ?>" value="1" <?php echo ($data['is_active'] == 1) ? 'checked' : ''; ?>>
                        <label for="is_active_<?php echo $slot_name; ?>" style="display: inline-block;">इस विज्ञापन स्लॉट को सक्षम करें (Enable Ad Slot)</label>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <button type="submit" class="btn btn-primary btn-full-width">AdSense सेटिंग्स सेव करें</button>
            </form>
        </div>
    </div>
</div>

<?php 
$conn->close();
include 'includes/admin_footer.php'; 
?>