<?php
// 1. Session start aur authentication logic
session_start();

// Agar operator pehle se logged in hai, toh direct dashboard par bheinjein
if (isset($_SESSION['operator_logged_in']) && $_SESSION['operator_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

// DB connection include karein
include '../includes/db_connect.php'; 

$login_error = "";
$active_tab = "login"; // By default login tab active rahega

// 2. LOGIN PROCESS LOGIC (100% Secure with Prepared Statements)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'login') {
    
    // Direct input check
    $username = trim($_POST['login_username']);
    $password = $_POST['login_password'];

    if (!empty($username) && !empty($password)) {
        
        // 🔒 SQL Injection Protection: Prepared Statement Query
        $sql = "SELECT id, username, password, is_active, is_suspended FROM operators WHERE username = ? LIMIT 1";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                
                // Password verification (Bcrypt check)
                if (password_verify($password, $row['password'])) {
                    
                    // 1. Check account suspended
                    if (intval($row['is_suspended']) === 1) {
                        $op_name = htmlspecialchars($row['username']);
                        $login_error = "🚫 <strong>अकाउंट सस्पेंड:</strong> ऑपरेटर <strong>{$op_name}</strong> की सभी सेवाएं तत्काल प्रभाव से रोक दी गई हैं।";
                    } 
                    // 2. Check account active
                    elseif (intval($row['is_active']) === 1) {
                        session_regenerate_id(true);
                        
                        $_SESSION['operator_logged_in'] = true;
                        $_SESSION['operator_id'] = $row['id'];
                        $_SESSION['operator_username'] = $row['username'];
                        
                        header("Location: dashboard.php");
                        exit();
                    } 
                    // 3. Account pending
                    else {
                        $login_error = "⏳ Aapka account abhi pending hai! Admin approval ka intezar karein.";
                    }
                    
                } else {
                    $login_error = "❌ Galat Password! Kripya sahi password dalein.";
                }
            } else {
                $login_error = "❌ Username nahi mila! Kripya sahi username dalein.";
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $login_error = "⚠️ Database server error! Kripya baad me koshish karein.";
        }
    } else {
        $login_error = "⚠️ Kripya saari fields bharein!";
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operator Gateway | Vacancy Portal</title>
    
    <!-- Favicon Integration -->
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
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --info-gradient: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        body { 
            background: var(--bg-gradient); 
            color: var(--text-dark); 
            font-family: 'Inter', 'Hind', sans-serif; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .top-bar {
            background: #0f172a;
            font-size: 0.85rem;
            color: #94a3b8;
            padding: 10px 0;
            border-bottom: 2px solid #2563eb;
        }
        .top-bar i { color: #3b82f6; }

        .navbar { 
            background: rgba(255, 255, 255, 0.8); 
            backdrop-filter: blur(20px);
            padding: 15px 0; 
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        }
        .navbar-brand { font-size: 1.5rem; letter-spacing: 0.5px; display: flex; align-items: center; }
        .brand-logo { width: 32px; height: 32px; object-fit: contain; margin-right: 10px; }
        .brand-text {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .btn-portal {
            background: var(--primary-gradient);
            color: #ffffff !important;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 50px;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }
        .btn-portal:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(79, 70, 229, 0.3);
        }

        .hero-section { flex: 1; padding: 40px 0; display: flex; align-items: center; }
        .hero-title { font-size: 2.6rem; font-weight: 800; line-height: 1.3; color: #1e1b4b; }
        .hero-highlight {
            background: linear-gradient(135deg, #4f46e5 0%, #db2777 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .instructions-panel {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            position: relative;
        }
        
        .step-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            border-left: 4px solid #4f46e5;
            transition: all 0.3s ease;
        }
        .step-card:hover {
            transform: translateX(5px);
            background: #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.04);
        }
        .step-card.step-verify { border-left-color: #ea580c; }
        .step-card.step-wallet { border-left-color: #10b981; }

        .obj-box {
            background: linear-gradient(to right, #ffffff, #fdf8f5);
            border: 1px dashed #fdba74;
            border-radius: 14px;
            padding: 18px;
            margin-top: 20px;
        }

        .portal-card { 
            background: var(--card-bg); 
            padding: 35px; 
            border-radius: 24px; 
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.8);
            margin-bottom: 25px;
        }
        
        .nav-pills { background: #f1f5f9; padding: 6px; border-radius: 14px; margin-bottom: 25px; }
        .nav-pills .nav-link { color: var(--text-muted); font-weight: 600; border-radius: 10px; transition: 0.3s; padding: 10px; border: none; }
        .nav-pills .nav-link.active { background: var(--primary-gradient); color: #ffffff; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2); }
        
        .input-group {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        .input-group:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        .input-group-text { 
            background-color: #f8fafc; 
            border: none;
            color: #4f46e5; 
            min-width: 45px; 
            justify-content: center; 
        }
        .form-control { 
            border: none;
            color: var(--text-dark); 
            padding: 11px;
            font-weight: 500;
            font-size: 0.95rem;
        }
        .form-control:focus { box-shadow: none; }
        
        .input-group-btn-custom {
            background-color: #f8fafc;
            border: none;
            color: var(--text-muted);
            padding: 0 15px;
            cursor: pointer;
        }

        .qr-box { 
            background: #fff; 
            padding: 20px; 
            border-radius: 16px; 
            text-align: center; 
            margin-bottom: 25px; 
            border: 2px dashed #db2777;
            display: none; 
            animation: fadeIn 0.5s ease forwards;
        }
        .qr-badge {
            background: var(--accent-gradient);
            color: white;
            padding: 3px 12px;
            font-size: 0.75rem;
            font-weight: bold;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 10px;
        }
        .qr-wrapper {
            background: #ffffff;
            display: inline-block;
            padding: 8px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .btn-submit { 
            background: var(--primary-gradient); 
            color: #ffffff; 
            font-weight: 600; 
            border: none; 
            transition: 0.3s ease; 
            padding: 12px; 
            border-radius: 10px; 
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2);
        }
        .btn-submit:hover:not(:disabled) { 
            transform: translateY(-1px); 
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.3);
        }
        .btn-submit:disabled { background: #cbd5e1; color: #94a3b8; cursor: not-allowed; box-shadow: none; }

        #screenshot-preview { max-width: 120px; display: none; border-radius: 6px; margin-top: 10px; border: 2px solid #10b981; }

        .side-updates-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #f8fafc;
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-top: 25px;
        }
        .stat-item {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 12px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            height: 0;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- Top Info Bar -->
<div class="top-bar d-none d-md-block">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="me-4"><i class="fas fa-envelope me-2"></i> info@vacancyportal.co.in</span>
            <span><i class="fas fa-server me-2"></i> सर्वर स्थिति: सुरक्षित (SSL Enforced)</span>
        </div>
        <div>
            <span class="badge bg-primary px-3 py-1 rounded-pill">ऑपरेटर नोड v2.0</span>
        </div>
    </div>
</div>

<!-- Navigation Header -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="login.php">
            <img src="vp.png" alt="Logo" class="brand-logo">
            <i class="fas fa-layer-group text-primary me-2"></i><span class="brand-text">VACANCY</span>PORTAL
        </a>
        <a href="login.php" class="btn-portal"><i class="fas fa-lock me-2"></i> सुरक्षित गेटवे</a>
    </div>
</nav>

<!-- Main Area -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-start">
            
            <!-- LEFT PANEL -->
            <div class="col-lg-5 mb-5 lg-mb-0">
                <span class="badge mb-3 text-uppercase" style="background: rgba(79,70,229,0.1); color: #4f46e5; padding: 8px 16px; font-weight: 700; border-radius: 50px;">Earning Matrix Active</span>
                <h1 class="hero-title">ऑपरेटर प्रणाली <br><span class="hero-highlight">कार्यप्रणाली व उद्देश्य</span></h1>
                <p class="mb-4 text-muted" style="line-height: 1.7;">पोर्टल से जुड़कर सरकारी व प्राइवेट नौकरियों की 100% सही व सटीक जानकारी लाइव करें और प्रत्येक सफल वेरिफिकेशन पर सीधे अपने ऑपरेटर वॉलेट में रियल-टाइम कैश कमाएं.</p>
                
                <!-- NEW BUTTONS FOR HELP MODAL & VIDEO -->
                <div class="d-flex gap-2 mb-4">
                    <button class="btn btn-warning text-dark fw-bold rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#registrationGuideModal">
                        <i class="fas fa-question-circle me-1"></i> रजिस्ट्रेशन कैसे करें? (निर्देश)
                    </button>
                    <a href="#video-guide-section" class="btn btn-outline-primary fw-bold rounded-pill px-3">
                        <i class="fas fa-play-circle me-1"></i> ट्रेनिंग वीडियो देखें
                    </a>
                </div>

                <div class="instructions-panel">
                    <div class="mb-4">
                        <h5 class="fw-bold mb-3" style="color: #1e1b4b;"><i class="fas fa-bullseye text-danger me-2"></i> हमारा मुख्य उद्देश्य:</h5>
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="d-flex align-items-start gap-2 bg-light p-2.5 rounded border-start border-3 border-danger shadow-sm mb-2">
                                    <i class="fas fa-check-circle text-success mt-1"></i>
                                    <div class="small"><strong>सटीक व त्वरित सूचनाएँ:</strong> ग्रामीण और शहरी क्षेत्रों के युवाओं तक बिना किसी देरी के वास्तविक नौकरियों, एडमिट कार्ड और रिज़ल्ट के नोटिफिकेशन पहुंचाना.</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-start gap-2 bg-light p-2.5 rounded border-start border-3 border-info shadow-sm mb-2">
                                    <i class="fas fa-users-cog text-info mt-1"></i>
                                    <div class="small"><strong>डिजिटल रोज़गार सृजन:</strong> देश भर के कंप्यूटर एक्सपर्ट्स और डेटा ENTRY ऑपरेटर्स को घर बैठे एक पारदर्शी डिजिटल अर्निंग प्लेटफॉर्म प्रदान करना.</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-start gap-2 bg-light p-2.5 rounded border-start border-3 border-success shadow-sm">
                                    <i class="fas fa-shield-halved text-success mt-1"></i>
                                    <div class="small"><strong>फेक न्यूज़ पर लगाम:</strong> इंटरनेट पर फैले फ़र्ज़ी जॉब नोटिफिकेशन्स को फ़िल्टर कर केवल आधिकारिक स्रोतों से प्रमाणित डेटा ही पब्लिश करना.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="text-muted opacity-25 my-3">

                    <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-circle-nodes text-primary me-2"></i> कार्य करने के 3 आसान चरण:</h5>
                    
                    <div class="step-card">
                        <div class="d-flex align-items-center mb-1">
                            <span class="badge bg-primary me-2">01</span>
                            <h6 class="fw-bold mb-0 text-dark">Category-Wise पोस्ट फिलिंग</h6>
                        </div>
                        <p class="small text-muted mb-0">सफलतापूर्वक लॉगिन के बाद, आपको अलग-अलग केटेगरी की लाइव वैकेंसियों की जानकारी भरकर फॉर्म सबमिट करना होगा.</p>
                    </div>
                    
                    <div class="step-card step-verify">
                        <div class="d-flex align-items-center mb-1">
                            <span class="badge bg-warning text-dark me-2">02</span>
                            <h6 class="fw-bold mb-0 text-dark">Admin Verification (रीव्यू)</h6>
                        </div>
                        <p class="small text-muted mb-0">आपके द्वारा सबमिट किया गया डेटा सीधे मुख्य एडमिन पैनल के पास डबल-चेक और ऑथेंटिकेशन के लिए जाएगा.</p>
                    </div>
                    
                    <div class="step-card step-wallet">
                        <div class="d-flex align-items-center mb-1">
                            <span class="badge bg-success me-2">03</span>
                            <h6 class="fw-bold mb-0 text-dark">लाइव पोस्ट & तुरंत वॉलेट अर्निंग</h6>
                        </div>
                        <p class="small text-muted mb-0">जैसे ही एडमिन पोस्ट को अप्रूव करके वेबसाइट पर लाइव करेगा, तुरंत आपके वॉलेट में तय फिक्स्ड कमीशन जोड़ दिया जाएगा.</p>
                    </div>

                    <div class="obj-box">
                        <div class="d-flex align-items-center mb-1 text-warning-emphasis">
                            <i class="fas fa-gift me-2 text-warning"></i><strong>ऑपरेटर विशेष लाभ:</strong>
                        </div>
                        <p class="small text-muted mb-0" style="font-size: 0.82rem;">24/7 टेक्निकल सपोर्ट बैकअप, पारदर्शी पेमेंट ट्रैकिंग सिस्टम, और हर महीने TOP ऑपरेटर को अतिरिक्त बोनस इंसेंटिव.</p>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANEL FORMS & LIVE UPDATES -->
            <div class="col-lg-7">
                <div class="portal-card">
                    
                    <?php if(!empty($login_error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <?php echo $login_error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <ul class="nav nav-pills nav-justified" id="portalTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="register-tab" data-bs-toggle="tab" data-bs-target="#register-panel" type="button" role="tab"><i class="fas fa-user-plus me-2"></i> नया पंजीकरण</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="login-tab" data-bs-toggle="tab" data-bs-target="#login-panel" type="button" role="tab"><i class="fas fa-sign-in-alt me-2"></i> ऑपरेटर लॉगिन</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="portalTabsContent">
                        <!-- REGISTRATION FORM -->
                        <div class="tab-pane fade show active" id="register-panel" role="tabpanel">
                            <form id="registrationForm" action="process_register.php" method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" name="username" class="form-control check-trigger" placeholder="यूज़रनेम बनाएं" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                                            <input type="password" id="reg-password" name="password" class="form-control check-trigger" placeholder="पासवर्ड बनाएं" required>
                                            <button type="button" class="input-group-btn-custom" onclick="togglePassword('reg-password', this)"><i class="fas fa-eye"></i></button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" name="email" class="form-control check-trigger" placeholder="ईमेल आईडी" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-whatsapp text-success"></i></span>
                                            <input type="tel" name="whatsapp_no" class="form-control check-trigger" placeholder="व्हाट्सएप नंबर" pattern="[0-9]{10}" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                            <input type="text" name="identity_no" class="form-control check-trigger" placeholder="पहचान पत्र संख्या" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-credit-card"></i></span>
                                            <input type="text" name="tax_no" class="form-control check-trigger" placeholder="टैक्स / पैन संख्या" style="text-transform: uppercase;" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-university"></i></span>
                                            <input type="text" name="bank_name" class="form-control check-trigger" placeholder="बैंक का नाम" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-file-invoice-dollar"></i></span>
                                            <input type="text" name="account_no" class="form-control check-trigger" placeholder="खाता संख्या" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-code"></i></span>
                                            <input type="text" name="routing_code" class="form-control check-trigger" placeholder="बैंक IFSC / रूटिंग कोड" style="text-transform: uppercase;" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dynamic Gateway QR -->
                                <div class="qr-box" id="dynamic-qr-box">
                                    <div class="qr-badge">वन-टाइम एक्टिवेशन शुल्क: ₹99</div>
                                    <p class="fw-bold mb-2 text-dark"><i class="fas fa-qrcode text-danger me-2"></i>स्कैन करके पेमेंट करें</p>
                                    <div class="qr-wrapper mb-3">
                                        <img id="dynamic-qr" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=upi://pay?pa=yourvpa@upi&pn=VacancyPortal&am=99" class="img-fluid rounded shadow-sm" width="140" height="140" alt="UPI QR">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="fas fa-receipt"></i></span>
                                            <input type="text" id="utr_number" name="utr_number" class="form-control" placeholder="12-अंकीय Transaction Ref (UTR) No. दर्ज करें" pattern="[0-9]{12}" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label text-muted small fw-bold mb-1"><i class="fas fa-upload me-1"></i> भुगतान स्क्रीनशॉट अपलोड करें:</label>
                                    <input type="file" id="screenshot_file" name="screenshot" class="form-control" accept="image/*" required>
                                    <div class="text-center">
                                        <img id="screenshot-preview" src="#" alt="Receipt Preview">
                                    </div>
                                </div>
                                
                                <button type="submit" id="submitRegisterBtn" class="btn btn-submit w-100 py-2.5 text-uppercase"><i class="fas fa-lock me-2"></i> डिटेल्स सुरक्षित भेजें</button>
                            </form>
                        </div>

                        <!-- LOGIN INTERFACE -->
                        <div class="tab-pane fade" id="login-panel" role="tabpanel">
                            <div class="text-center mb-4">
                                <i class="fas fa-user-shield text-primary display-6 mb-2"></i>
                                <h5 class="fw-bold text-dark">सत्यापित ऑपरेटर लॉगिन</h5>
                                <p class="small text-muted">डैशबोर्ड में प्रवेश करने के लिए क्रेडेंशियल्स दर्ज करें</p>
                            </div>
                            
                            <form action="" method="POST">
                                <input type="hidden" name="action" value="login">
                                
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                        <input type="text" name="login_username" class="form-control" placeholder="ऑपरेटर यूज़रनेम" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" id="login-password" name="login_password" class="form-control" placeholder="सुरक्षा पासवर्ड" required>
                                        <button type="button" class="input-group-btn-custom" onclick="togglePassword('login-password', this)"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-submit w-100 py-2.5 text-uppercase">अकाउंट लॉग इन करें &rarr;</button>
                            </form>
                        </div>
                    </div>
                </div>
<div style="margin-top: 20px; text-align: center;">
    <a href="forgot_password.php" class="forgot-btn">
        <i class="fa-solid fa-key"></i>
        <span>Password भूल गए? (Forgot Password)</span>
    </a>
</div>

<style>
/* Forgot Password Button Style */
.forgot-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 18px;
    background: rgba(78, 84, 200, 0.08);
    color: #4e54c8;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    border-radius: 8px;
    border: 1px solid rgba(78, 84, 200, 0.2);
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

/* Hover Effect */
.forgot-btn:hover {
    background: linear-gradient(135deg, #4e54c8, #8f94fb);
    color: #ffffff;
    border-color: transparent;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(78, 84, 200, 0.3);
}

.forgot-btn:active {
    transform: translateY(0);
}
</style>
                <!-- RIGHT SIDE LOWER WIDGET -->
                <div class="side-updates-card animate-box">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary pb-2">
                        <h5 class="m-0 fw-bold text-white" style="font-size: 14px;">
                            <i class="fas fa-bell text-warning me-2"></i>महत्वपूर्ण सूचना एवं लाइव स्टैट्स (नियमावली)
                        </h5>
                        <span class="badge bg-danger rounded-pill px-2 py-1" style="font-size: 10px;">LIVE TERMINAL</span>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-3">
                            <div class="stat-item px-1">
                                <div class="text-warning fw-bold" style="font-size: 13px;">1,240+</div>
                                <div style="font-size: 9px;" class="text-white-50">सक्रिय ऑपरेटर</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="stat-item px-1">
                                <div class="text-info fw-bold" style="font-size: 13px;">All Cat.</div>
                                <div style="font-size: 9px;" class="text-white-50">श्रेणी उद्देश्य</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="stat-item px-1">
                                <div class="text-success fw-bold" style="font-size: 13px;">₹10,000</div>
                                <div style="font-size: 9px;" class="text-white-50">न्यूनतम सीमा</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="stat-item px-1">
                                <div class="text-danger fw-bold" style="font-size: 13px;">Auto</div>
                                <div style="font-size: 9px;" class="text-white-50">रीव्यू मोड</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 p-2 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);">
                        <div class="d-flex justify-content-between text-white-50" style="font-size: 10px;">
                            <span>विड्रॉल लॉक प्रोग्रेस (न्यूनतम सीमा)</span>
                            <span class="text-success fw-bold">₹10,000 अनिवार्य</span>
                        </div>
                        <div class="progress mt-1" style="height: 6px; background: rgba(255,255,255,0.1);">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%;"></div>
                        </div>
                    </div>
                </div>

                <!-- NEW EMBEDDED VIDEO TUTORIAL SECTION -->
                <div id="video-guide-section" class="portal-card mt-4">
                    <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-video text-danger me-2"></i> ऑपरेटर रजिस्ट्रेशन ट्रेनिंग वीडियो</h5>
                    <p class="small text-muted mb-3">यदि आपको रजिस्ट्रेशन करने में कोई समस्या आ रही है, तो कृपया नीचे दिया गया ट्यूटोरियल वीडियो ध्यानपूर्वक देखें:</p>
                    <div class="video-container">
                        <!-- Yahan aap apni Youtube ya hosted video ka Embed URL daal sakte hain -->
                        <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Operator Registration Tutorial" allowfullscreen></iframe>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- ------------------------------------------------------------- -->
<!-- 1. POPUP MODAL: REGISTRATION HINDI INSTRUCTIONS -->
<!-- ------------------------------------------------------------- -->
<div class="modal fade" id="registrationGuideModal" tabindex="-1" aria-labelledby="registrationGuideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 18px; overflow: hidden;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="registrationGuideModalLabel">
                    <i class="fas fa-file-alt me-2"></i> ऑपरेटर पंजीकरण हेतु आवश्यक हिंदी निर्देश
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="font-size: 0.95rem; line-height: 1.6;">
                <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                    <i class="fas fa-info-circle fa-2x me-3 text-info"></i>
                    <div>
                        कृपया फॉर्म भरने से पहले सभी निर्देशों को ध्यान से पढ़ें ताकि आपका रजिस्ट्रेशन निरस्त (Reject) न हो।
                    </div>
                </div>

                <h6 class="fw-bold text-dark mt-3"><i class="fas fa-list-check text-primary me-2"></i> चरण-दर-चरण प्रक्रिया:</h6>
                <ol class="ps-3">
                    <li class="mb-2"><strong>यूज़रनेम और पासवर्ड:</strong> अपनी पसंद का यूज़रनेम और एक मजबूत पासवर्ड बनाएं जिसे आप याद रख सकें।</li>
                    <li class="mb-2"><strong>संपर्क विवरण:</strong> केवल अपना चालू <strong>ईमेल आईडी</strong> और 10-अंकों का सक्रिय <strong>व्हाट्सएप नंबर</strong> दर्ज करें।</li>
                    <li class="mb-2"><strong>पहचान पत्र:</strong> सुरक्षा कारणों से अपना सही पहचान पत्र एवं पैन/टैक्स संख्या दर्ज करना अनिवार्य है।</li>
                    <li class="mb-2"><strong>बैंक विवरण:</strong> सही बैंक नाम, खाता संख्या और IFSC कोड भरें। आपके द्वारा कमाए गए कमीशन की राशि सीधे इसी बैंक खाते में ट्रांसफर की जाएगी।</li>
                    <li class="mb-2"><strong>एक्टिवेशन शुल्क भुगतान:</strong> 
                        <ul class="ps-3 mt-1">
                            <li>स्क्रीन पर दिख रहे QR कोड को स्कैन करें और ₹99 का एक्टिवेशन शुल्क भुगतान करें।</li>
                            <li>भुगतान सफल होने के बाद मिलने वाला **12-अंकीय UTR / Transaction No.** दर्ज करें।</li>
                            <li>भुगतान की रसीद/स्क्रीनशॉट (Screenshot) अपलोड करें।</li>
                        </ul>
                    </li>
                </ol>

                <div class="alert alert-warning mb-0 mt-3" role="alert">
                    <i class="fas fa-exclamation-triangle me-1"></i> <strong>ध्यान दें:</strong> ग़लत UTR नंबर या फ़र्ज़ी स्क्रीनशॉट अपलोड करने पर आपका ऑपरेटर अकाउंट स्थायी रूप से सस्पेंड कर दिया जाएगा।
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">समझ गया / बंद करें</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Frontend Handling & Scripts -->
    <script>
        // 1. Password Visibility Toggle
        function togglePassword(inputId, button) {
            const passwordInput = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            // --- CONFIGURATION ---
            const upiId = "mahendra.thakur1994-2@okicici"; 
            const payeeName = "Vacancy Portal"; 
            const amount = "99.00";
            const currency = "INR";
            
            // डायनेमिक QR जनरेट करने के लिए लिंक स्ट्रिंग
            const upiLink = `upi://pay?pa=${upiId}&pn=${encodeURIComponent(payeeName)}&am=${amount}&cu=${currency}`;
            const qrImgElement = document.getElementById("dynamic-qr");
            if (qrImgElement) {
                qrImgElement.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(upiLink)}`;
            }
            // ------------------------------------

            const checkTriggers = document.querySelectorAll('.check-trigger');
            const dynamicQrBox = document.getElementById('dynamic-qr-box');
            const screenshotInput = document.getElementById('screenshot_file');
            const utrInput = document.getElementById('utr_number');
            const submitBtn = document.getElementById('submitRegisterBtn');
            const previewImg = document.getElementById('screenshot-preview');

            // फॉर्म फील्ड्स चेक करके QR बॉक्स दिखाने का फंक्शन
            function checkFieldsAndShowQR() {
                let allFilled = true;
                checkTriggers.forEach(element => {
                    if (!element.value.trim() || !element.checkValidity()) {
                        allFilled = false;
                    }
                });

                if (allFilled) {
                    if (dynamicQrBox) dynamicQrBox.style.display = 'block';
                } else {
                    if (dynamicQrBox) dynamicQrBox.style.display = 'none';
                }
            }

            // सभी इनपुट फील्ड्स पर इवेंट लिसनर
            checkTriggers.forEach(element => {
                element.addEventListener("input", checkFieldsAndShowQR);
                element.addEventListener("change", checkFieldsAndShowQR);
            });

            // पेज लोड होने पर भी एक बार चेक करें
            checkFieldsAndShowQR();

            // पेमेंट और UTR वैलिडेट करने का फंक्शन (सब्मिट बटन कंट्रोल करने के लिए)
            function validatePayment() {
                if (!utrInput || !screenshotInput || !submitBtn) return;
                
                const utrValue = utrInput.value.trim();
                // 12 अंकों का UTR और स्क्रीनशॉट होना अनिवार्य है
                if (screenshotInput.files.length > 0 && utrValue.length === 12 && /^\d+$/.test(utrValue)) {
                    submitBtn.removeAttribute("disabled");
                    submitBtn.innerHTML = '<i class="fas fa-circle-check me-2"></i> पंजीकरण अनुरोध सबमिट करें';
                    submitBtn.style.background = "var(--success-gradient)";
                } else {
                    submitBtn.setAttribute("disabled", "true");
                    submitBtn.innerHTML = '<i class="fas fa-lock me-2"></i> कृपया भुगतान रसीद अपलोड करें';
                    submitBtn.style.background = "var(--primary-gradient)";
                }
            }

            // स्क्रीनशॉट प्रीव्यू और वैलिडेशन
            if (screenshotInput) {
                screenshotInput.addEventListener("change", function () {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            if (previewImg) {
                                previewImg.setAttribute("src", e.target.result);
                                previewImg.style.display = "inline-block";
                            }
                        }
                        reader.readAsDataURL(file);
                    } else {
                        if (previewImg) previewImg.style.display = "none";
                    }
                    validatePayment();
                });
            }

            if (utrInput) {
                utrInput.addEventListener("input", validatePayment);
            }
        });
    </script>
</body>
</html>