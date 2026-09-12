<?php
// 1. Session start karein backend authentication ke liye
session_start();

// Agar operator pehle se logged in hai, toh direct dashboard par bheinjein
if (isset($_SESSION['operator_logged_in']) && $_SESSION['operator_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

// DB connection include karein
include '../includes/db_connect.php'; 

$login_error = "";
$active_tab = "register"; // Default tab register rahega

// 2. LOGIN PROCESS LOGIC (Secure with Prepared Statements)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'login') {
    $active_tab = "login"; // Agar login fail hota hai, toh login tab hi khula rahega
    
    // Prepared Statements use ho raha hai isliye direct value check safe hai
    $username = trim($_POST['login_username']);
    $password = $_POST['login_password'];

    if (!empty($username) && !empty($password)) {
        
        // 🔒 SQL Injection Protection: Fetching required status columns safely
        $sql = "SELECT id, username, password, is_active, is_suspended FROM operators WHERE username = ? LIMIT 1";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                
                // Password verification
                if (password_verify($password, $row['password'])) {
                    
                    // 🔄 Real Status Verification Logic
                    
                    // 1. Sabse pehle check karein ki account suspended (is_suspended = 1) toh nahi hai
                    if (intval($row['is_suspended']) === 1) {
                        
                        // Aapka bataya gaya exact Hindi custom message configuration
                        $op_name = htmlspecialchars($row['username']);
                        $login_error = "🚫 <strong>अकाउंट सस्पेंड:</strong> ऑपरेटर <strong>{$op_name}</strong> की सभी सेवाएं तत्काल प्रभाव से रोक दी गई हैं।";
                        
                    } 
                    // 2. Agar suspend nahi hai, toh check karein ki account active (is_active = 1) hai ya nahi
                    elseif (intval($row['is_active']) === 1) {
                        
                        // Security Layer: Fix Session Fixation
                        session_regenerate_id(true);
                        
                        // Active Operator Login Successful
                        $_SESSION['operator_logged_in'] = true;
                        $_SESSION['operator_id'] = $row['id'];
                        $_SESSION['operator_username'] = $row['username'];
                        
                        header("Location: dashboard.php");
                        exit();
                    } 
                    // 3. Agar suspend bhi nahi hai (0) aur active bhi nahi hai (0), toh pending hai
                    else {
                        $login_error = "⏳ Aapka account abhi pending hai! Admin approval ka intezar karein.";
                    }
                    
                } else {
                    $login_error = "❌ Galat Password! Kripya sahi password dalein.";
                }
            } else {
                $login_error = "❌ Username nahi mila! Kripya sahi username dalein.";
            }
            
            // Statement resource close karein
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
  <link rel="icon" type="image/png" href="vp.png">
    <link rel="apple-touch-icon" href="vp.png">
    <title>Operator Gateway | Vacancy Portal</title>
    <!-- Bootstrap 5.3.0 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #f5f7ff 0%, #e4ecfa 50%, #fdeef4 100%);
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            --accent-gradient: linear-gradient(135deg, #ff007f 0%, #ff7b00 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-dark: #0f172a;
            --text-muted: #475569;
            --border-color: #cbd5e1;
        }

        body { 
            background: var(--bg-gradient); 
            color: var(--text-dark); 
            font-family: 'Segoe UI', system-ui, sans-serif; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .top-bar {
            background: linear-gradient(90deg, #0f172a 0%, #1e293b 100%);
            font-size: 0.85rem;
            color: #cbd5e1;
            padding: 12px 0;
            border-bottom: 2px solid #3b82f6;
        }
        .top-bar i {
            color: #3b82f6;
            animation: pulse 2s infinite;
        }

        .navbar { 
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(16px);
            padding: 15px 0; 
            border-bottom: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03);
        }
        .navbar-brand { font-size: 1.6rem; letter-spacing: 0.5px; }
        .brand-text {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .btn-portal {
            background: var(--accent-gradient);
            color: #ffffff !important;
            font-weight: 700;
            padding: 10px 24px;
            border-radius: 50px;
            transition: all 0.4s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(255, 0, 127, 0.2);
        }
        .btn-portal:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 0, 127, 0.4);
        }

        .hero-section { flex: 1; padding: 40px 0; display: flex; align-items: center; }
        .hero-title { font-size: 2.8rem; font-weight: 800; line-height: 1.2; color: #1e1b4b; }
        .hero-highlight {
            background: linear-gradient(135deg, #3b82f6 0%, #ff007f 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .instructions-panel {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 20px 40px rgba(0,0,0,0.04);
            position: relative;
            overflow: hidden;
        }
        .instructions-panel::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 6px; height: 100%;
            background: var(--primary-gradient);
        }
        
        .portal-card { 
            background: var(--card-bg); 
            padding: 35px; 
            border-radius: 24px; 
            box-shadow: 0 30px 60px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }
        
        .nav-pills { background: #e2e8f0; padding: 6px; border-radius: 14px; margin-bottom: 25px; }
        .nav-pills .nav-link { color: var(--text-muted); font-weight: 700; border-radius: 10px; transition: 0.3s; padding: 12px; }
        .nav-pills .nav-link.active { background: var(--primary-gradient); color: #ffffff; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); }
        
        .input-group {
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            border-radius: 10px;
            overflow: hidden;
        }
        .input-group-text { 
            background-color: #f8fafc; 
            border: 1px solid var(--border-color); 
            color: #4f46e5; 
            min-width: 48px; 
            justify-content: center; 
        }
        .form-control { 
            border: 1px solid var(--border-color); 
            color: var(--text-dark); 
            padding: 12px;
            font-weight: 500;
        }
        .form-control:focus { 
            border-color: #3b82f6; 
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); 
        }
        .input-group-btn-custom {
            background-color: #f8fafc;
            border: 1px solid var(--border-color);
            border-left: none;
            color: var(--text-muted);
            padding: 0 15px;
            cursor: pointer;
        }

        .qr-box { 
            background: linear-gradient(180deg, #ffffff 0%, #fff5f8 100%); 
            padding: 25px; 
            border-radius: 16px; 
            text-align: center; 
            margin-bottom: 25px; 
            border: 2px dashed #ff007f;
            box-shadow: 0 10px 25px rgba(255, 0, 127, 0.05);
            position: relative;
            display: none; 
            animation: fadeIn 0.6s ease forwards;
        }
        .qr-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--accent-gradient);
            color: white;
            padding: 4px 15px;
            font-size: 0.75rem;
            font-weight: bold;
            border-radius: 50px;
            letter-spacing: 1px;
        }
        .qr-wrapper {
            background: #ffffff;
            display: inline-block;
            padding: 10px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            min-height: 145px;
            min-width: 145px;
        }
        
        .btn-submit { 
            background: var(--primary-gradient); 
            color: #ffffff; 
            font-weight: 700; 
            border: none; 
            transition: 0.4s ease; 
            padding: 14px; 
            letter-spacing: 0.5px; 
            border-radius: 12px; 
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.25);
        }
        .btn-submit:hover:not(:disabled) { 
            background: linear-gradient(135deg, #3b82f6 0%, #4f46e5 100%);
            transform: translateY(-2px); 
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4); 
        }
        .btn-submit:disabled {
            background: #cbd5e1;
            color: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
        }

        #screenshot-preview {
            max-width: 140px;
            display: none;
            border-radius: 8px;
            margin-top: 12px;
            border: 2px solid #10b981;
            animation: fadeIn 0.5s ease;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- Top Info Bar -->
<div class="top-bar d-none d-md-block">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="me-4"><i class="fas fa-envelope-open me-2"></i> info@vacancyportal.co.in</span>
            <span><i class="fas fa-bolt me-2"></i> सिस्टम स्टेटस: ऑनलाइन</span>
        </div>
        <div>
            <span class="badge bg-primary px-3 py-1.5 rounded-pill">सुरक्षित ऑपरेटर कोर</span>
        </div>
    </div>
</div>

<!-- Navigation Header -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">
            <i class="fas fa-circle-nodes text-primary"></i> <span class="brand-text">VACANCY</span>PORTAL
        </a>
        <a href="#" class="btn-portal"><i class="fas fa-fingerprint me-2"></i> पोर्टल हब एक्सेस</a>
    </div>
</nav>

<!-- Main Area -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            
            <!-- Left Panel Info (Hindi Instructions) -->
            <div class="col-lg-5 mb-5 lg-mb-0">
                <span class="badge mb-3 text-uppercase" style="background: linear-gradient(90deg, rgba(79,70,229,0.1) 0%, rgba(255,0,127,0.1) 100%); color: #4f46e5; padding: 10px 20px; font-weight: 700; border-radius: 50px;">फ्रेमवर्क एक्टिवेशन v2.0</span>
                <h1 class="hero-title">बनें एक सत्यापित <br><span class="hero-highlight">पोर्टल ऑपरेटर</span></h1>
                <p class="mb-4" style="color: var(--text-muted); line-height: 1.8; font-size: 1.05rem;">Vacancy Portal के प्रीमियम नेटवर्क से जुड़कर अपने सेंटर को लाइव हाई-स्पीड डेटा एक्सेस डैशबोर्ड में बदलें।</p>
                
                <div class="instructions-panel">
                    <h5 class="fw-bold mb-4" style="color: #4f46e5;"><i class="fas fa-wand-magic-sparkles me-2 text-warning"></i> आवश्यक पंजीकरण निर्देश:</h5>
                    <ul class="list-unstyled mb-0" style="font-size: 0.95rem; line-height: 1.7;">
                        <li class="mb-3 d-flex align-items-start"><i class="fas fa-circle-check text-success mt-1 me-3 fs-5"></i> <span><b>चरण 1:</b> दाईं ओर दिए गए फॉर्म में अपनी सटीक व्यक्तिगत और बैंक केवाईसी (KYC) डिटेल्स दर्ज करें।</span></li>
                        <li class="mb-3 d-flex align-items-start"><i class="fas fa-circle-check text-success mt-1 me-3 fs-5"></i> <span><b>चरण 2:</b> सभी इनपुट बॉक्स भरने के बाद नीचे <b>ऑटोमैटिक QR कोड</b> लोड हो जाएगा।</span></li>
                        <li class="mb-3 d-flex align-items-start"><i class="fas fa-circle-check text-success mt-1 me-3 fs-5"></i> <span><b>चरण 3:</b> QR कोड स्कैन कर ₹99 गेटवे शुल्क का भुगतान पूरा करें।</span></li>
                        <li class="mb-3 d-flex align-items-start"><i class="fas fa-circle-check text-success mt-1 me-3 fs-5"></i> <span><b>चरण 4:</b> 12-अंकीय UPI ट्रांजैक्शन ID दर्ज करें और स्क्रीनशॉट अटैच करें। <span class="text-danger fw-bold">(अनिवार्य)</span></span></li>
                        <li class="mb-0 d-flex align-items-start"><i class="fas fa-circle-check text-success mt-1 me-3 fs-5"></i> <span><b>चरण 5:</b> सुरक्षा वेरिफिकेशन सफल होते ही आपका ऑपरेटर पैनल तुरंत लाइव कर दिया जाएगा।</span></li>
                    </ul>
                </div>
            </div>

            <!-- Right Panel Form Cards -->
            <div class="col-lg-7">
                <div class="portal-card">
                    
                    <ul class="nav nav-pills nav-justified" id="portalTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo ($active_tab == 'register') ? 'active' : ''; ?>" id="register-tab" data-bs-toggle="tab" data-bs-target="#register-panel" type="button" role="tab"><i class="fas fa-rocket me-2"></i> नया पंजीकरण</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo ($active_tab == 'login') ? 'active' : ''; ?>" id="login-tab" data-bs-toggle="tab" data-bs-target="#login-panel" type="button" role="tab"><i class="fas fa-key me-2"></i> ऑपरेटर लॉगिन</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="portalTabsContent">
                        
                        <!-- REGISTRATION FORM -->
                        <div class="tab-pane fade <?php echo ($active_tab == 'register') ? 'show active' : ''; ?>" id="register-panel" role="tabpanel">
                            
                            <form id="registrationForm" action="process_register.php" method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user-gear"></i></span>
                                            <input type="text" name="username" class="form-control check-trigger" placeholder="यूज़रनेम बनाएं" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-vault"></i></span>
                                            <input type="password" id="reg-password" name="password" class="form-control check-trigger" placeholder="पासवर्ड बनाएं" required>
                                            <button type="button" class="input-group-btn-custom" onclick="togglePassword('reg-password', this)"><i class="fas fa-eye"></i></button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope-open-text"></i></span>
                                            <input type="email" name="email" class="form-control check-trigger" placeholder="आधिकारिक ईमेल आईडी" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-whatsapp text-success"></i></span>
                                            <input type="tel" name="whatsapp_no" class="form-control check-trigger" placeholder="सक्रिय व्हाट्सएप नंबर" pattern="[0-9]{10}" title="कृपया 10-digit का मोबाइल number डालें" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-passport text-warning"></i></span>
                                            <input type="text" name="adhar_no" class="form-control check-trigger" placeholder="आधार कार्ड नंबर" pattern="[0-9]{12}" title="कृपया 12-digit का नंबर डालें" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-id-card-clip text-info"></i></span>
                                            <input type="text" name="pan_no" class="form-control check-trigger" placeholder="पैन कार्ड नंबर" style="text-transform: uppercase;" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-building-columns text-danger"></i></span>
                                            <input type="text" name="bank_name" class="form-control check-trigger" placeholder="बैंक का नाम" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-money-check-dollar"></i></span>
                                            <input type="text" name="account_no" class="form-control check-trigger" placeholder="बैंक खाता संख्या" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                            <input type="text" name="ifsc_code" class="form-control check-trigger" placeholder="बैंक IFSC कोड" style="text-transform: uppercase;" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- DYNAMIC QR BOX -->
                                <div class="qr-box" id="dynamic-qr-box">
                                    <div class="qr-badge">केवल ₹99</div>
                                    <p class="fw-bold mb-2" style="color: #1e1b4b;"><i class="fas fa-qrcode text-danger me-2"></i> एक्टिवेशन शुल्क गेटवे</p>
                                    <p class="small text-muted mb-3">कृपया इस QR को स्कैन करके भुगतान पूरा करें और नीचे डिटेल्स भरें।</p>
                                    
                                    <div class="qr-wrapper mb-3">
                                        <img id="dynamic-qr" src="" class="img-fluid rounded shadow-sm" width="140" height="140" alt="UPI QR">
                                    </div>
                                    
                                    <div class="small text-danger fw-bold"><i class="fas fa-circle-exclamation me-1"></i> भुगतान के बाद नीचे Ref No. दर्ज करें और स्क्रीनशॉट अपलोड करें।</div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text bg-warning-subtle"><i class="fas fa-receipt text-dark"></i></span>
                                            <input type="text" id="utr_number" name="utr_number" class="form-control" placeholder="12-अंकीय UPI Ref No. / Transaction ID डालें" pattern="[0-9]{12}" title="कृपया सटीक 12-Digit UPI Ref ID दर्ज करें" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label text-danger small fw-bold mb-2"><i class="fas fa-cloud-arrow-up me-2"></i> भुगतान स्क्रीनशॉट अपलोड करें (अनिवार्य):</label>
                                    <input type="file" id="screenshot_file" name="screenshot" class="form-control" accept="image/*" required>
                                    <div class="text-center">
                                        <img id="screenshot-preview" src="#" alt="Receipt Preview">
                                    </div>
                                </div>
                                
                                <button type="submit" id="submitRegisterBtn" class="btn btn-submit w-100 py-3 text-uppercase" disabled><i class="fas fa-lock me-2"></i> कृपया भुगतान रसीद अपलोड करें</button>
                            </form>
                        </div>

                        <!-- LOGIN INTERFACE -->
                        <div class="tab-pane fade <?php echo ($active_tab == 'login') ? 'show active' : ''; ?>" id="login-panel" role="tabpanel">
                            <div class="text-center mb-4">
                                <i class="fas fa-fingerprint text-primary display-4 mb-3"></i>
                                <h4 class="fw-bold" style="color: #1e1b4b;">अधिकृत सिस्टम गेटवे</h4>
                                <p class="small text-muted">अपने Vacancy Portal अकाउंट से सत्र अधिकृत करें।</p>
                            </div>
                            
                            <!-- Action indicator to catch errors -->
                            <?php if (!empty($login_error)): ?>
                                <div class="alert alert-danger text-center py-2 mb-3 small fw-bold">
                                    <?php echo $login_error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Action self target form -->
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                                <input type="hidden" name="action" value="login">
                                
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-shield-halved"></i></span>
                                        <input type="text" name="login_username" class="form-control" placeholder="ऑपरेटर एक्सेस आईडी" required value="<?php echo isset($_POST['login_username']) ? htmlspecialchars($_POST['login_username']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" id="login-password" name="login_password" class="form-control" placeholder="सुरक्षा पासवर्ड" required>
                                        <button type="button" class="input-group-btn-custom" onclick="togglePassword('login-password', this)"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-submit w-100 py-3 text-uppercase">सत्र अधिकृत करें &rarr;</button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');
        if (input.type === "password") {
            input.type = "text";
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = "password";
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        // --- CONFIGURATION ---
        const upiId = "mahendra.thakur1994-2@okicici"; 
        const payeeName = "Vacancy Portal"; 
        const amount = "99.00";
        const currency = "INR";
        
        const upiLink = `upi://pay?pa=${upiId}&pn=${encodeURIComponent(payeeName)}&am=${amount}&cu=${currency}`;
        const qrImgElement = document.getElementById("dynamic-qr");
        qrImgElement.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(upiLink)}`;
        // ------------------------------------

        const formElements = document.querySelectorAll(".check-trigger");
        const qrBox = document.getElementById("dynamic-qr-box");
        const screenshotInput = document.getElementById("screenshot_file");
        const utrInput = document.getElementById("utr_number");
        const submitBtn = document.getElementById("submitRegisterBtn");
        const previewImg = document.getElementById("screenshot-preview");

        function checkFieldsAndShowQR() {
            let allFilled = true;
            formElements.forEach(element => {
                if (!element.value.trim() || !element.checkValidity()) {
                    allFilled = false;
                }
            });

            if (allFilled) {
                if (qrBox.style.display !== "block") {
                    qrBox.style.display = "block";
                }
            } else {
                qrBox.style.display = "none";
            }
        }

        formElements.forEach(element => {
            element.addEventListener("input", checkFieldsAndShowQR);
            element.addEventListener("change", checkFieldsAndShowQR);
        });

        // Check fields on load if pre-filled
        checkFieldsAndShowQR();

        function validatePayment() {
            const utrValue = utrInput.value.trim();
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

        screenshotInput.addEventListener("change", function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.setAttribute("src", e.target.result);
                    previewImg.style.display = "inline-block";
                }
                reader.readAsDataURL(file);
            } else {
                previewImg.style.display = "none";
            }
            validatePayment();
        });

        utrInput.addEventListener("input", validatePayment);
    });
</script>
</body>
</html>