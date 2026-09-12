<?php
// includes/db_connect.php (यदि आप संदेश डेटाबेस में सेव करना चाहते हैं)
// include 'includes/db_connect.php'; 
include 'includes/header.php'; 

$message_status = '';
$admin_email = 'your_admin_email@example.com'; // **इसे अपने वास्तविक ईमेल से बदलें**

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message_body = trim($_POST['message']);

    // साधारण वैलिडेशन
    if (empty($name) || empty($email) || empty($subject) || empty($message_body) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Red color style के बजाय 'alert error' क्लास का उपयोग करें
        $message_status = '<div class="alert error">कृपया सभी फ़ील्ड सही ढंग से भरें और एक वैध ईमेल दर्ज करें।</div>';
    } else {
        // ईमेल भेजने की तैयारी
        $to = $admin_email;
        $email_subject = "पोर्टल संपर्क फ़ॉर्म से: " . $subject;
        $email_body = "नाम: " . $name . "\n" .
                      "ईमेल: " . $email . "\n" .
                      "विषय: " . $subject . "\n\n" .
                      "संदेश:\n" . $message_body;
        
        $headers = "From: " . $email . "\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // mail() फ़ंक्शन का उपयोग करके ईमेल भेजें
        if (mail($to, $email_subject, $email_body, $headers)) {
            // Green color style के बजाय 'alert success' क्लास का उपयोग करें
            $message_status = '<div class="alert success">✅ आपका संदेश सफलतापूर्वक भेज दिया गया है। हम जल्द ही आपसे संपर्क करेंगे।</div>';
            // फ़ॉर्म फ़ील्ड को साफ़ करें
            $_POST = array(); 
        } else {
            $message_status = '<div class="alert error">❌ संदेश भेजने में त्रुटि हुई। कृपया बाद में पुनः प्रयास करें। (नोट: लोकलहोस्ट पर mail() काम नहीं करता है)</div>';
        }
    }
}
?>

<div class="contact-hero">
    <div class="container">
        <h1>📞 हमसे संपर्क करें (Get in Touch)</h1>
        <p class="tagline">यदि आपके कोई प्रश्न, सुझाव या विज्ञापन संबंधी पूछताछ है, तो हमें लिखें।</p>
    </div>
</div>

<div class="container main-content-padding">
    
    <div class="contact-grid">
        
        <div class="contact-info-box">
            <h3>💬 सीधे संपर्क करें</h3>
            <p><i class="fas fa-envelope"></i> ईमेल: <a href="mailto:<?php echo htmlspecialchars($admin_email); ?>"><?php echo htmlspecialchars($admin_email); ?></a></p>
            <p><i class="fas fa-map-marker-alt"></i> पता: [आपका पता यहाँ जोड़ें/केवल ऑनलाइन]</p>
            <p>हमारा लक्ष्य 24-48 घंटों के भीतर सभी प्रश्नों का उत्तर देना है।</p>
        </div>

        <div class="contact-form-wrapper">
            <h2>✉️ हमें एक संदेश भेजें</h2>
            <?php echo $message_status; ?>

            <form method="POST" class="contact-form">
                <div class="form-group">
                    <label for="name">आपका नाम:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">आपका ईमेल:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="subject">विषय (Inquiry Type):</label>
                    <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? 'Website Inquiry'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="message">संदेश:</label>
                    <textarea id="message" name="message" rows="6" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full-width">संदेश भेजें</button>
            </form>
        </div>
        
    </div>

    <div style="margin-top: 30px; text-align: center;">
        [Google AdSense Unit - Bottom]
    </div>

</div>
<?php include 'includes/footer.php'; ?>