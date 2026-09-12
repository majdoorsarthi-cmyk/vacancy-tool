<?php
// डेटाबेस कनेक्शन और हेडर
include 'includes/db_connect.php'; 
include 'includes/header.php'; 

$message_sent = false;
$error_message = '';

// आपका ऑफिशियल डोमेन ईमेल
$admin_email = "info@vacancyportal.co.in"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // स्पैम रोकने के लिए हनीपोट (Honeypot) Check
    if (!empty($_POST['website'])) {
        die("Spam detected!");
    }

    // 1. इनपुट डेटा प्राप्त करें और सुरक्षित (Sanitize) करें
    $name = isset($_POST['name']) ? htmlspecialchars(strip_tags(trim($_POST['name']))) : '';
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $subject_input = isset($_POST['subject']) ? htmlspecialchars(strip_tags(trim($_POST['subject']))) : '';
    $subject = !empty($subject_input) ? $subject_input : "Website Contact Form";
    $user_message = isset($_POST['message']) ? htmlspecialchars(strip_tags(trim($_POST['message']))) : '';

    // 2. सत्यापन (Validation)
    if (empty($name) || empty($email) || empty($user_message)) {
        $error_message = "कृपया सभी अनिवार्य फ़ील्ड्स भरें।";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "कृपया एक वैध ईमेल पता दर्ज करें।";
    } else {
        // 3. ईमेल सेटिंग्स (Google Rejection Avoidance Protocol)
        $to = $admin_email;
        $email_subject = "Vacancy Portal Contact: " . $subject;

        $headers  = "From: " . $name . " <" . $admin_email . ">\r\n";
        $headers .= "Reply-To: " . $name . " <" . $email . ">\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        // HTML ईमेल बॉडी
        $email_body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='background: #f4f4f4; padding: 20px;'>
                <h2 style='color: #2563eb;'>Vacancy Portal: नया संदेश</h2>
                <p><strong>नाम:</strong> {$name}</p>
                <p><strong>ईमेल:</strong> {$email}</p>
                <p><strong>विषय:</strong> {$subject}</p>
                <hr>
                <p><strong>संदेश:</strong></p>
                <p style='background: #fff; padding: 15px; border-radius: 5px;'>".nl2br($user_message)."</p>
            </div>
        </body>
        </html>";
        
        // 4. ईमेल भेजें
        if (mail($to, $email_subject, $email_body, $headers)) {
            $message_sent = true;
        } else {
            $error_message = "सर्वर पर ईमेल फ़ंक्शन सक्षम नहीं है। कृपया हमें " . $admin_email . " पर सीधे लिखें।";
        }
    }
}
?>

<style>
    :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8;
        --secondary: #64748b;
        --success: #16a34a;
        --danger: #dc2626;
        --bg-light: #f8fafc;
        --card-bg: #ffffff;
    }

    .contact-wrapper {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Hero Banner Section */
    .contact-hero {
        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        color: white;
        padding: 40px 30px;
        border-radius: 16px;
        text-align: center;
        margin-bottom: 30px;
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2);
    }

    .contact-hero h1 {
        font-size: 2.3rem;
        margin-bottom: 10px;
        font-weight: 800;
    }

    .contact-hero p {
        font-size: 1.1rem;
        opacity: 0.9;
        max-width: 700px;
        margin: 0 auto;
    }

    /* Grid Layout */
    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }

    @media (max-width: 900px) {
        .contact-grid { grid-template-columns: 1fr; }
    }

    .card {
        background: var(--card-bg);
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid #e2e8f0;
    }

    .card-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 20px;
        border-bottom: 3px solid var(--primary);
        display: inline-block;
        padding-bottom: 5px;
    }

    /* Direct Contact Options Cards */
    .direct-contacts {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 25px;
    }

    .contact-box {
        background: #f1f5f9;
        padding: 18px;
        border-radius: 12px;
        border-left: 4px solid var(--primary);
    }

    .contact-box h4 {
        margin: 0 0 5px 0;
        color: #1e293b;
        font-size: 1.05rem;
    }

    .contact-box p, .contact-box a {
        margin: 0;
        color: var(--primary);
        font-weight: 600;
        text-decoration: none;
        word-break: break-all;
    }

    /* Form Styles */
    .form-group { margin-bottom: 18px; }

    label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
        color: #475569;
        font-size: 0.95rem;
    }

    input, textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        transition: border-color 0.3s;
        font-size: 1rem;
        box-sizing: border-box;
    }

    input:focus, textarea:focus {
        border-color: var(--primary);
        outline: none;
    }

    .btn-submit {
        background: var(--primary);
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
        width: 100%;
        font-size: 1rem;
        transition: background 0.3s;
    }

    .btn-submit:hover { background: var(--primary-dark); }

    /* FAQ Accordion Section */
    .faq-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 15px 20px;
        margin-bottom: 12px;
    }

    .faq-item h4 {
        margin: 0 0 8px 0;
        color: #0f172a;
        font-size: 1.05rem;
    }

    .faq-item p {
        margin: 0;
        color: #475569;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }

    .hidden-field { display: none; }

    .disclaimer-box {
        background: #fffbeeb0;
        border: 1px solid #fef3c7;
        border-left: 4px solid #f59e0b;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
        color: #92400e;
        font-size: 0.9rem;
    }
</style>

<div class="contact-wrapper">
    
    <!-- Hero Banner -->
    <div class="contact-hero">
        <h1>👋 Vacancy Portal सहायता केंद्र</h1>
        <p>क्या आपका कोई सवाल है या आपको किसी सरकारी भर्ती की जानकारी चाहिए? हमारी टीम आपकी सहायता के लिए हमेशा तैयार है।</p>
    </div>

    <div class="contact-grid">
        
        <!-- Left Side: Direct Contact & FAQs (बिना फॉर्म के जानकारी) -->
        <div>
            <div class="card" style="margin-bottom: 30px;">
                <h2 class="card-title">📌 सीधा संपर्क करें</h2>
                <p style="color: #64748b; margin-bottom: 20px;">आप हमें फॉर्म भरे बिना भी सीधे ईमेल के माध्यम से संपर्क कर सकते हैं:</p>
                
                <div class="direct-contacts">
                    <div class="contact-box">
                        <h4>📧 आधिकारिक ईमेल</h4>
                        <a href="mailto:<?php echo $admin_email; ?>"><?php echo $admin_email; ?></a>
                    </div>
                    <div class="contact-box">
                        <h4>⏰ जवाब का समय</h4>
                        <p style="color:#334155;">24 से 48 घंटे</p>
                    </div>
                </div>

                <div class="direct-contacts">
                    <div class="contact-box">
                        <h4>📍 मुख्य कार्यालय</h4>
                        <p style="color:#334155;">नई दिल्ली, भारत</p>
                    </div>
                    <div class="contact-box">
                        <h4>💼 कार्य दिवस</h4>
                        <p style="color:#334155;">सोम - शनि (10 AM - 6 PM)</p>
                    </div>
                </div>

                <div class="disclaimer-box">
                    <strong>⚠️ महत्वपूर्ण सूचना:</strong> Vacancy Portal एक सूचनात्मक जॉब पोर्टल है। हम किसी भी जॉब के लिए कोई शुल्क या पैसे नहीं मांगते हैं। कृपया फर्जी कॉल से सावधान रहें।
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="card">
                <h2 class="card-title">❓ अक्सर पूछे जाने वाले सवाल (FAQ)</h2>
                
                <div class="faq-item">
                    <h4>1. क्या आप जॉब दिलाने के पैसे लेते हैं?</h4>
                    <p>नहीं, हमारी वेबसाइट पर दी गई सभी नौकरियां और जानकारी 100% मुफ्त हैं।</p>
                </div>

                <div class="faq-item">
                    <h4>2. एडमिट कार्ड या रिजल्ट डाउनलोड नहीं हो रहा?</h4>
                    <p>कृपया संबंधित विभाग (जैसे SSC, UPSC, Railway) की आधिकारिक वेबसाइट पर सर्वर डाउन की जांच करें।</p>
                </div>

                <div class="faq-item">
                    <h4>3. गलत जानकारी की शिकायत कैसे करें?</h4>
                    <p>यदि आपको किसी पोस्ट में कोई गलती मिलती है, तो आप हमें सीधे <strong><?php echo $admin_email; ?></strong> पर मेल कर सकते हैं।</p>
                </div>
            </div>
        </div>

        <!-- Right Side: Quick Contact Form -->
        <div>
            <div class="card">
                <h2 class="card-title">✉️ संदेश भेजें</h2>
                <p style="color: #64748b; margin-bottom: 20px;">यदि आपके पास कोई विशेष सवाल या सुझाव है, तो नीचे फॉर्म भरें:</p>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">⚠️ <?php echo $error_message; ?></div>
                <?php endif; ?>

                <?php if ($message_sent): ?>
                    <div class="alert alert-success">
                        ✅ <strong>सफलता!</strong> आपका संदेश भेज दिया गया है। हम जल्द ही आपसे संपर्क करेंगे।
                    </div>
                    <div style="text-align:center;">
                        <a href="contact.php" class="btn-submit" style="display:inline-block; text-decoration:none; text-align:center;">नया संदेश भेजें</a>
                    </div>
                <?php else: ?>
                    
                    <form action="contact.php" method="POST">
                        <input type="text" name="website" class="hidden-field">

                        <div class="form-group">
                            <label for="name">आपका नाम *</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                                   placeholder="उदा. राहुल शर्मा">
                        </div>

                        <div class="form-group">
                            <label for="email">ईमेल पता *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   placeholder="rahul@example.com">
                        </div>

                        <div class="form-group">
                            <label for="subject">विषय</label>
                            <input type="text" id="subject" name="subject"
                                   value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                                   placeholder="उदा. जॉब अपडेट या सुझाव">
                        </div>

                        <div class="form-group">
                            <label for="message">संदेश *</label>
                            <textarea id="message" name="message" rows="5" required 
                                      placeholder="अपना सवाल या संदेश विस्तार से लिखें..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn-submit">संदेश सबमिट करें 🚀</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>