<?php
// 1. Session start aur authentication logic
session_start();

// Check karein ki operator logged in hai ya nahi
if (!isset($_SESSION['operator_logged_in']) || $_SESSION['operator_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// DB connection include karein
include '../includes/db_connect.php'; 

$success_msg = "";
$error_msg = "";

// 2. SUPPORT TICKET SUBMISSION LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'submit_ticket') {
    $operator_id = $_SESSION['operator_id'];
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);

    if (!empty($subject) && !empty($message)) {
        // Ticket insert karne ki query (Apne DB schema ke hisab se ise adjust kar sakte hain)
        $sql = "INSERT INTO support_tickets (operator_id, subject, message, priority, status, created_at) 
                VALUES ('$operator_id', '$subject', '$message', '$priority', 'pending', NOW())";
        
        if (mysqli_query($conn, $sql)) {
            $success_msg = "✔️ आपका सहायता अनुरोध (Ticket) सफलतापूर्वक दर्ज कर लिया गया है। जल्द ही एडमिन आपसे संपर्क करेंगे।";
        } else {
            $error_msg = "❌ डेटाबेस त्रुटि: अनुरोध सबमिट करने में विफल।";
        }
    } else {
        $error_msg = "⚠️ कृपया सभी फ़ील्ड सही-सही भरें।";
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>हेल्प डेस्क और सहायता | Vacancy Portal</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="vp.png">
    <link rel="apple-touch-icon" href="vp.png">

    <!-- Bootstrap 5.3.0 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #e2e8f0 100%);
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
            --accent-gradient: linear-gradient(135deg, #db2777 0%, #ea580c 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-dark: #0f172a;
            --text-muted: #64748b;
        }

        body { 
            background: var(--bg-gradient); 
            color: var(--text-dark); 
            font-family: 'Inter', 'Hind', sans-serif; 
            min-height: 100vh;
        }

        .navbar { 
            background: rgba(255, 255, 255, 0.8); 
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        }
        .brand-text {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .support-container { padding: 40px 0; }
        
        .info-card {
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            height: 100%;
        }

        .contact-method {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #2563eb;
            transition: all 0.3s ease;
        }
        .contact-method:hover {
            transform: translateX(5px);
            background: #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.04);
        }

        .faq-accordion .accordion-item {
            border: 1px solid #e2e8f0;
            border-radius: 12px !important;
            margin-bottom: 10px;
            overflow: hidden;
        }
        .faq-accordion .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
            box-shadow: none;
        }

        .form-control, .form-select {
            border: 1px solid #e2e8f0;
            padding: 12px;
            border-radius: 10px;
            font-weight: 500;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .btn-submit { 
            background: var(--primary-gradient); 
            color: #ffffff; 
            font-weight: 600; 
            border: none; 
            padding: 12px; 
            border-radius: 10px; 
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2);
            transition: 0.3s ease;
        }
        .btn-submit:hover { 
            transform: translateY(-1px); 
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.3);
            color: #fff;
        }
    </style>
</head>
<body>

<!-- Navigation Header -->
<nav class="navbar navbar-expand-lg sticky-top py-3">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <img src="vp.png" alt="Logo" width="32" height="32" class="me-2">
            <span class="brand-text">VACANCY</span>PORTAL
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small d-none d-sm-inline"><i class="fas fa-user-circle me-1"></i> ऑपरेटर: <strong><?php echo htmlspecialchars($_SESSION['operator_username']); ?></strong></span>
            <a href="dashboard.php" class="btn btn-outline-primary btn-sm rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> डैशबोर्ड</a>
        </div>
    </div>
</nav>

<div class="container support-container">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <span class="badge mb-2" style="background: rgba(79,70,229,0.1); color: #4f46e5; padding: 8px 16px; font-weight: 700; border-radius: 50px;">24/7 Technical Support</span>
            <h2 class="fw-bold text-dark">ऑपरेटर सहायता एवं सपोर्ट केंद्र</h2>
            <p class="text-muted">पोर्टल से संबंधित किसी भी समस्या, वॉलेट बैलेंस या तकनीकी गड़बड़ी के समाधान के लिए हमसे संपर्क करें।</p>
        </div>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success text-center py-3 mb-4 fw-bold shadow-sm rounded-3">
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger text-center py-3 mb-4 fw-bold shadow-sm rounded-3">
            <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Left Side: Contact Info & FAQs -->
        <div class="col-lg-5">
            <div class="info-card">
                <h5 class="fw-bold mb-4 text-dark"><i class="fas fa-headset text-primary me-2"></i>तुरंत संपर्क के माध्यम</h5>
                
                <div class="contact-method">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">आधिकारिक ईमेल</h6>
                            <p class="small text-muted mb-0">info@vacancyportal.co.in</p>
                        </div>
                    </div>
                </div>

                <div class="contact-method" style="border-left-color: #25d366;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">ऑपरेटर व्हाट्सएप सपोर्ट</h6>
                            <p class="small text-muted mb-0">सपोर्ट का समय: सुबह 10:00 से शाम 06:00 बजे तक</p>
                        </div>
                    </div>
                </div>

                <hr class="my-4 text-muted opacity-25">

                <!-- FAQ section -->
                <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-question-circle text-warning me-2"></i>अक्सर पूछे जाने वाले सवाल</h5>
                <div class="accordion faq-accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold small" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                मेरी सबमिट की गई पोस्ट लाइव कब होगी?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small text-muted">
                                ऑपरेटर द्वारा सबमिट किया गया डेटा एडमिन पैनल द्वारा 1 से 4 घंटे के भीतर वेरीफाई करके लाइव कर दिया जाता है।
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold small" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                पोस्ट रिजेक्ट होने का क्या कारण हो सकता है?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small text-muted">
                                यदि आपने गलत नोटिफिकेशन लिंक, अधूरी जानकारी या फ़र्ज़ी जॉब डेटा भरा है, तो एडमिन उसे रिजेक्ट कर सकता है।
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold small" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                वॉलेट बैलेंस बैंक खाते में कब क्रेडिट होता है?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small text-muted">
                                न्यूनतम विड्रॉल सीमा पूरी होने पर आप डैशबोर्ड से रिक्वेस्ट भेज सकते हैं, पेमेंट 24-48 घंटों में आपके पंजीकृत बैंक खाते में भेज दिया जाता है।
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Support Ticket Form -->
        <div class="col-lg-7">
            <div class="info-card">
                <h5 class="fw-bold mb-2 text-dark"><i class="fas fa-paper-plane text-success me-2"></i>सहायता टिकट (Support Ticket) दर्ज करें</h5>
                <p class="small text-muted mb-4">यदि आपको कोई समस्या आ रही है, तो नीचे दिए गए फॉर्म में विवरण लिखकर एडमिन को सीधे संदेश भेजें।</p>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <input type="hidden" name="action" value="submit_ticket">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">समस्या का विषय (Subject)</label>
                        <input type="text" name="subject" class="form-control" placeholder="उदा. वॉलेट बैलेंस ऐड नहीं हुआ या पोस्ट एरर" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">प्राथमिकता स्तर (Priority Level)</label>
                        <select name="priority" class="form-select" required>
                            <option value="low">सामान्य (Low)</option>
                            <option value="medium" selected>महत्वपूर्ण (Medium)</option>
                            <option value="high">अति आवश्यक (High)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark">समस्या का विस्तृत विवरण (Message)</label>
                        <textarea name="message" class="form-control" rows="5" placeholder="यहाँ अपनी समस्या को विस्तार से लिखें ताकि एडमिन उसे समझकर जल्दी हल कर सकें..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-submit w-100 py-2.5 text-uppercase">
                        <i class="fas fa-check-circle me-2"></i> सहायता अनुरोध सबमिट करें
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>