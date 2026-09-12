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

$operator_id = $_SESSION['operator_id'];
$success_msg = "";
$error_msg = "";

// 2. FETCH CURRENT WALLET BALANCE FROM DATABASE
$balance_query = "SELECT wallet_balance FROM operators WHERE id = '$operator_id'"; // Apni table aur column name ke anusar check karein
$balance_result = mysqli_query($conn, $balance_query);
$balance_data = mysqli_fetch_assoc($balance_result);
$available_balance = $balance_data['wallet_balance'] ?? 0.00;

// 3. MINIMUM WITHDRAWAL LIMIT CONFIGURATION
$min_withdrawal_limit = 10000;

// 4. PROCESS WITHDRAWAL REQUEST (FORM SUBMISSION)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_withdrawal'])) {
    // Backend Security Check: Agar balance 10,000 se kam hai toh request bypass nahi honi chahiye
    if ($available_balance < $min_withdrawal_limit) {
        $error_msg = "❌ त्रुटि: आपका बैलेंस न्यूनतम निकासी सीमा (₹" . number_format($min_withdrawal_limit, 2) . ") से कम है।";
    } else {
        $amount = mysqli_real_escape_string($conn, $_POST['withdraw_amount']);
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        $payment_details = mysqli_real_escape_string($conn, $_POST['payment_details']);
        
        // Input Validations
        if (empty($amount) || empty($payment_method) || empty($payment_details)) {
            $error_msg = "⚠️ कृपया सभी फ़ील्ड्स को सही तरीके से भरें।";
        } elseif (!is_numeric($amount) || $amount < $min_withdrawal_limit) {
            $error_msg = "⚠️ निकासी राशि कम से कम ₹" . $min_withdrawal_limit . " होनी चाहिए।";
        } elseif ($amount > $available_balance) {
            $error_msg = "⚠️ आपके पास पर्याप्त बैलेंस नहीं है। अधिकतम उपलब्ध बैलेंस: ₹" . number_format($available_balance, 2);
        } else {
            // INSERT LOGIC (Withdrawal Requests Table me status 'pending' ke sath insert karein)
            // Note: Maan lete hain aapki table ka naam 'withdrawal_requests' hai
            $insert_query = "INSERT INTO withdrawal_requests (operator_id, amount, payment_method, payment_details, status, created_at) 
                             VALUES ('$operator_id', '$amount', '$payment_method', '$payment_details', 'pending', NOW())";
            
            if (mysqli_query($conn, $insert_query)) {
                // (Optional) Aap chahein toh yahan se turant balance minus kar sakte hain ya Admin ke approve karne ke baad debit kar sakte hain.
                $success_msg = "🎉 आपकी निकासी का अनुरोध सफलतापूर्वक दर्ज कर लिया गया है। एडमिन की स्वीकृति के बाद राशि ट्रांसफर कर दी जाएगी।";
                
                // Balance refresh karne ke liye fir se query chalayein
                $balance_result = mysqli_query($conn, $balance_query);
                $balance_data = mysqli_fetch_assoc($balance_result);
                $available_balance = $balance_data['wallet_balance'] ?? 0.00;
            } else {
                $error_msg = "❌ सर्वर त्रुटि: अनुरोध सबमिट नहीं किया जा सका। कृपया बाद में प्रयास करें।";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>निकासी अनुरोध | Vacancy Portal</title>
    
    <!-- Favicon Integration (vp.png Direct Path) -->
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
            --sidebar-bg: #ffffff;
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-dark: #0f172a;
            --text-muted: #64748b;
        }

        body { 
            background: var(--bg-gradient); 
            color: var(--text-dark); 
            font-family: 'Inter', 'Hind', sans-serif; 
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Layout Wrapper */
        .app-wrapper { display: flex; min-height: 100vh; }

        /* Sidebar Styling (Same as Wallet/Manage) */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            transition: all 0.3s ease;
        }
        
        .sidebar-brand { padding: 24px; border-bottom: 1px solid #f1f5f9; }
        .brand-text { background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .sidebar-menu { padding: 20px 15px; flex-grow: 1; }

        .nav-menu-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            margin-bottom: 5px;
            transition: all 0.2s;
        }

        .nav-menu-link i { width: 24px; font-size: 1.1rem; margin-right: 10px; }
        .nav-menu-link:hover { background: #f1f5f9; color: #2563eb; }
        .nav-menu-link.active {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(37, 99, 243, 0.1) 100%);
            color: #2563eb;
            font-weight: 600;
        }

        .sidebar-footer { padding: 20px; border-top: 1px solid #f1f5f9; background: #f8fafc; }

        /* Main Content Area */
        .main-content { flex-grow: 1; margin-left: 260px; padding: 30px; transition: all 0.3s ease; }

        /* Navbar inside Main Content */
        .content-navbar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid #e2e8f0;
            padding: 15px 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .data-card {
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            max-width: 650px;
        }

        .balance-badge-box {
            background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
        }

        .btn-submit-request {
            background: var(--primary-gradient);
            color: white !important;
            font-weight: 600;
            border-radius: 10px;
            padding: 12px 20px;
            border: none;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-submit-request:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37, 99, 243, 0.2); }

        @media (max-width: 991px) {
            .sidebar { left: -260px; }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0; padding: 15px; }
            .toggle-btn { display: block !important; }
        }
    </style>
</head>
<body>

<div class="app-wrapper">

    <!-- 1. SIDEBAR COMPONENT (Synced & Matched) -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
                <img src="vp.png" alt="Logo" width="30" height="30" class="me-2">
                <span class="brand-text fs-4">VACANCY</span>
            </a>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="nav-menu-link">
                <i class="fas fa-th-large"></i> डैशबोर्ड
            </a>
            <a href="manage_vacancies.php" class="nav-menu-link">
                <i class="fas fa-folder-open"></i> वैकेंसी प्रबंधन
            </a>
            <a href="add_vacancy.php" class="nav-menu-link">
                <i class="fas fa-plus-circle"></i> नई वैकेंसी जोड़ें
            </a>
            <a href="wallet.php" class="nav-menu-link active">
                <i class="fas fa-wallet"></i> वॉलेट लेज़र
            </a>
            <a href="support.php" class="nav-menu-link">
                <i class="fas fa-headset"></i> सहायता केंद्र
            </a>
        </div>
        
        <div class="sidebar-footer">
            <a href="logout.php" class="nav-menu-link text-danger p-0 m-0">
                <i class="fas fa-sign-out-alt"></i> लॉगआउट
            </a>
        </div>
    </div>

    <!-- 2. MAIN CONTENT AREA -->
    <div class="main-content">
        
        <!-- Synchronized Top Navbar -->
        <div class="content-navbar d-flex justify-content-between align-items-center">
            <button class="btn btn-light toggle-btn d-none" id="menuToggle" onclick="document.getElementById('sidebar').classList.toggle('active')">
                <i class="fas fa-bars"></i>
            </button>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-muted small"><i class="fas fa-user-circle me-1"></i> ऑपरेटर: <strong><?php echo htmlspecialchars($_SESSION['operator_username']); ?></strong></span>
            </div>
        </div>

        <!-- Heading Section -->
        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1"><i class="fas fa-money-check-alt text-primary me-2"></i>निकासी अनुरोध (Withdraw Request)</h3>
            <p class="text-muted mb-0 small">अपने वॉलेट बैलेंस को सीधे अपने बैंक खाते या UPI में ट्रांसफर करने के लिए नीचे फॉर्म भरें।</p>
        </div>

        <!-- Alert Notifications -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success py-3 mb-3 small fw-bold shadow-sm rounded-3"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger py-3 mb-3 small fw-bold shadow-sm rounded-3"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- HARD SECURITY CHECK IN FRONTEND DISPLAY -->
        <?php if ($available_balance >= $min_withdrawal_limit): ?>
            
            <!-- Safe Form Data Card -->
            <div class="data-card shadow-sm">
                <div class="balance-badge-box d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block">कुल उपलब्ध बैलेंस</span>
                        <h3 class="fw-bold mb-0">₹<?php echo number_format($available_balance, 2); ?></h3>
                    </div>
                    <div class="bg-white bg-opacity-25 p-3 rounded-circle">
                        <i class="fas fa-unlock-alt fs-4"></i>
                    </div>
                </div>

                <form action="withdraw_request.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">निकासी राशि (Amount to Withdraw) *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-dark fw-bold">₹</span>
                            <input type="number" name="withdraw_amount" class="form-control" min="<?php echo $min_withdrawal_limit; ?>" max="<?php echo $available_balance; ?>" value="<?php echo $min_withdrawal_limit; ?>" required>
                        </div>
                        <div class="form-text small">न्यूनतम निकासी ₹<?php echo number_format($min_withdrawal_limit, 0); ?> आवश्यक है।</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">भुगतान का माध्यम (Payment Method) *</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="">-- चुनें --</option>
                            <option value="UPI">UPI (Google Pay / PhonePe / Paytm)</option>
                            <option value="Bank Transfer">बैंक ट्रांसफर (Account Details)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-secondary">भुगतान विवरण (UPI ID या Bank A/c details) *</label>
                        <textarea name="payment_details" class="form-control" rows="3" placeholder="उदा: UPI ID: example@ybl या Bank A/c: 123456789, IFSC: SBIN0001234, Name: John Doe" required></textarea>
                    </div>

                    <button type="submit" name="submit_withdrawal" class="btn btn-submit-request">
                        <i class="fas fa-paper-plane me-2"></i> अनुरोध भेजें (Send Request)
                    </button>
                </form>
            </div>

        <?php else: ?>
            
            <!-- Direct Hard Redirection or Access Blocker Content if someone tries to visit manually -->
            <div class="card border-danger bg-light shadow-sm" style="border-radius: 15px; max-width: 650px;">
                <div class="card-body text-center p-5">
                    <i class="fas fa-lock text-danger display-4 mb-3"></i>
                    <h5 class="fw-bold text-danger">पहुंच प्रतिबंधित (Access Restricted)</h5>
                    <p class="text-muted small">
                        निकासी विकल्प का उपयोग करने के लिए आपके वॉलेट में कम से कम <strong>₹<?php echo number_format($min_withdrawal_limit, 0); ?></strong> होने चाहिए।
                    </p>
                    <hr class="text-muted opacity-25">
                    <div class="text-secondary small mb-3">आपका वर्तमान बैलेंस: <strong>₹<?php echo number_format($available_balance, 2); ?></strong></div>
                    <a href="wallet.php" class="btn btn-outline-primary btn-sm px-4 rounded-pill"><i class="fas fa-arrow-left me-1"></i> वॉलेट लेज़र पर वापस जाएं</a>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>