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

$operator_id = intval($_SESSION['operator_id']);
$success_msg = "";
$error_msg = "";

// =========================================================================
// GLOBAL MIDDLEWARE CHECK: क्या ऑपरेटर सस्पेंडेड है?
// =========================================================================
$check_status_stmt = $conn->prepare("SELECT is_suspended FROM operators WHERE id = ?");
$check_status_stmt->bind_param("i", $operator_id);
$check_status_stmt->execute();
$status_result = $check_status_stmt->get_result();

if ($status_result->num_rows > 0) {
    $operator_data = $status_result->fetch_assoc();
    if (intval($operator_data['is_suspended']) === 1) {
        $error_msg = "🚫 आपका अकाउंट एडमिन द्वारा सस्पेंड कर दिया गया है। आप किसी भी पोस्ट में बदलाव नहीं कर सकते।";
    }
}
$check_status_stmt->close();

// 2. GET REQUEST: डेटाबेस से सभी फ़ील्ड्स फेच करना
if (empty($error_msg)) {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location: manage_vacancies.php");
        exit();
    }

    $vacancy_id = intval($_GET['id']);

    // सुरक्षा: ऑपरेटर केवल अपनी पोस्ट ही एडिट कर सके
    $stmt = $conn->prepare("SELECT * FROM vacancies WHERE id = ? AND operator_id = ?");
    $stmt->bind_param("ii", $vacancy_id, $operator_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: manage_vacancies.php");
        exit();
    }

    $vacancy = $result->fetch_assoc();
    $stmt->close();
}

// 3. POST REQUEST: सभी फ़ील्ड्स का डेटा अपडेट करना
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_msg)) {
    
    // सभी इनपुट्स को सैनिटाइज़ और सुरक्षित करना
    $category         = trim($_POST['category'] ?? '');
    $title            = trim($_POST['title'] ?? '');
    $state            = trim($_POST['state'] ?? '');
    $total_posts      = trim($_POST['total_posts'] ?? '');
    $last_date        = !empty($_POST['last_date']) ? $_POST['last_date'] : NULL;
    $short_desc       = trim($_POST['short_desc'] ?? '');
    $important_dates  = trim($_POST['important_dates'] ?? '');
    $official_website = trim($_POST['official_website'] ?? '');
    $result_link      = trim($_POST['result_link'] ?? '');
    $apply_link       = trim($_POST['apply_link'] ?? '');
    $long_details     = trim($_POST['long_details'] ?? '');
    $description      = trim($_POST['description'] ?? '');

    if (empty($title) || empty($category) || empty($short_desc)) {
        $error_msg = "⚠️ कृपया सभी अनिवार्य फ़ील्ड्स (श्रेणी, शीर्षक, और संक्षिप्त विवरण) भरें।";
    } else {
        // ऑपरेटर केवल 'pending' या 'rejected' पोस्ट को ही एडिट कर सकता है
        if ($vacancy['status'] === 'pending' || $vacancy['status'] === 'rejected') {
            
            // सुधार के बाद स्टेटस वापस 'pending' हो जाएगा
            $new_status = 'pending';

            $update_sql = "UPDATE vacancies SET 
                            category = ?, 
                            title = ?, 
                            state = ?, 
                            total_posts = ?, 
                            last_date = ?, 
                            short_desc = ?, 
                            important_dates = ?, 
                            official_website = ?, 
                            result_link = ?, 
                            apply_link = ?, 
                            long_details = ?, 
                            description = ?,
                            status = ? 
                           WHERE id = ? AND operator_id = ?";

            $update_stmt = $conn->prepare($update_sql);
            
            // 13 Strings (s) और 2 Integers (i) = sssssssssssssii
            $update_stmt->bind_param(
                "sssssssssssssii", 
                $category, 
                $title, 
                $state, 
                $total_posts, 
                $last_date, 
                $short_desc, 
                $important_dates, 
                $official_website, 
                $result_link, 
                $apply_link, 
                $long_details, 
                $description, 
                $new_status,
                $vacancy_id, 
                $operator_id
            );
            
            if ($update_stmt->execute()) {
                // ऑपरेटर एक्टिविटी लॉग दर्ज करना
                $action_log = "Post_Update_Resubmitted_ID_" . $vacancy_id;
                $page_name  = "edit_vacancy.php";
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                
                $log_stmt = $conn->prepare("INSERT INTO operator_activity_logs (operator_id, action, page_url, ip_address) VALUES (?, ?, ?, ?)");
                $log_stmt->bind_param("isss", $operator_id, $action_log, $page_name, $ip_address);
                $log_stmt->execute();
                $log_stmt->close();

                $_SESSION['success_msg'] = "🎉 पोस्ट (ID: #" . $vacancy_id . ") सफलतापूर्वक अपडेट कर दी गई है और एडमिन रिव्यु (Pending) के लिए दोबारा भेज दी गई है।";
                header("Location: manage_vacancies.php");
                exit();
            } else {
                $error_msg = "❌ त्रुटि: डेटाबेस अपडेट करने में विफल। " . $conn->error;
            }
            $update_stmt->close();
        } else {
            $error_msg = "⚠️ आप केवल 'Pending' या 'Rejected' पोस्ट में ही बदलाव कर सकते हैं। स्वीकृत (Approved/Live) पोस्ट लॉक रहती हैं।";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>पोस्ट संपादित करें | Vacancy Portal</title>
    
    <link rel="icon" type="image/png" href="vp.png">

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
            --card-bg: rgba(255, 255, 255, 0.98);
            --text-dark: #0f172a;
            --text-muted: #64748b;
        }

        body { 
            background: var(--bg-gradient); 
            color: var(--text-dark); 
            font-family: 'Inter', 'Hind', sans-serif; 
            min-height: 100vh;
        }

        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; bottom: 0; left: 0;
            z-index: 100;
        }
        
        .sidebar-brand { padding: 24px; border-bottom: 1px solid #f1f5f9; }
        .brand-text { background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .sidebar-menu { padding: 20px 15px; flex-grow: 1; }

        .nav-menu-link {
            display: flex; align-items: center;
            padding: 12px 15px; color: var(--text-muted);
            text-decoration: none; border-radius: 10px;
            font-weight: 500; margin-bottom: 5px;
            transition: all 0.2s;
        }
        .nav-menu-link i { width: 24px; font-size: 1.1rem; margin-right: 10px; }
        .nav-menu-link:hover { background: #f1f5f9; color: #2563eb; }
        .nav-menu-link.active { background: rgba(79, 70, 229, 0.1); color: #2563eb; font-weight: 600; }

        .main-content {
            flex-grow: 1;
            margin-left: 260px;
            padding: 30px;
        }

        .form-section-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
        }

        .section-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 18px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f1f5f9;
            display: flex; align-items: center; gap: 8px;
        }

        .form-label { font-weight: 600; color: #475569; font-size: 0.88rem; }
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .btn-save {
            background: var(--primary-gradient);
            color: white; font-weight: 600; border: none;
            padding: 12px 30px; border-radius: 10px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }
        
        .btn-cancel {
            background: #f1f5f9; color: #475569; font-weight: 600;
            border: 1px solid #e2e8f0; padding: 12px 25px; border-radius: 10px;
        }
        .btn-cancel:hover { background: #e2e8f0; }

        @media (max-width: 991px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 15px; }
        }
    </style>
</head>
<body>

<div class="app-wrapper">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
                <img src="vp.png" alt="Logo" width="30" height="30" class="me-2">
                <span class="brand-text fs-4">VACANCY</span>
            </a>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="nav-menu-link"><i class="fas fa-th-large"></i> डैशबोर्ड</a>
            <a href="manage_vacancies.php" class="nav-menu-link active"><i class="fas fa-folder-open"></i> वैकेंसी प्रबंधन</a>
            <a href="add_vacancy.php" class="nav-menu-link"><i class="fas fa-plus-circle"></i> नई वैकेंसी जोड़ें</a>
            <a href="support.php" class="nav-menu-link"><i class="fas fa-headset"></i> सहायता केंद्र</a>
        </div>
        <div class="p-3 border-top bg-light">
            <a href="logout.php" class="nav-menu-link text-danger p-0 m-0"><i class="fas fa-sign-out-alt"></i> लॉगआउट</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-dark mb-1">📝 पोस्ट संपादित करें (Edit Post)</h3>
                <p class="text-muted small mb-0">पोस्ट ID: #<?php echo isset($vacancy_id) ? $vacancy_id : '---'; ?> के सभी विवरण नीचे अपडेट करें।</p>
            </div>
            <a href="manage_vacancies.php" class="btn btn-cancel"><i class="fas fa-arrow-left me-1"></i> वापस सूची पर जाएं</a>
        </div>

        <!-- Alerts -->
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger py-2.5 mb-4 small fw-bold shadow-sm rounded-3"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <?php if (isset($vacancy)): ?>
            
            <?php 
            // क्या ऑपरेटर इस पोस्ट में एडिट कर सकता है?
            $can_edit = ($vacancy['status'] === 'pending' || $vacancy['status'] === 'rejected');
            ?>

            <!-- Status Alert Messages -->
            <?php if ($vacancy['status'] === 'rejected'): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-4">
                    <div class="d-flex align-items-center gap-2 fw-bold mb-1">
                        <i class="fas fa-times-circle fs-5"></i> यह पोस्ट एडमिन द्वारा रिजेक्ट (Rejected) कर दी गई है!
                    </div>
                    <div class="small">
                        <?php if (!empty($vacancy['rejection_reason'])): ?>
                            <strong>रिजेक्शन का कारण:</strong> <?php echo htmlspecialchars($vacancy['rejection_reason'], ENT_QUOTES, 'UTF-8'); ?><br>
                        <?php endif; ?>
                        <em>कृपया नीचे दी गई जानकारी में आवश्यक सुधार करें और "सुधार करके दोबारा सबमिट करें" बटन पर क्लिक करें।</em>
                    </div>
                </div>
            <?php elseif ($vacancy['status'] === 'approved'): ?>
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
                    <i class="fas fa-lock fs-5"></i>
                    <div>
                        <strong>यह पोस्ट स्वीकृति (Approved/Live) पा चुकी है!</strong> सुरक्षा और SEO कारणों से स्वीकृत या आर्काइव पोस्टों को संपादित नहीं किया जा सकता।
                    </div>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                
                <!-- 1. मुख्य श्रेणी एवं मूल जानकारी -->
                <div class="form-section-card">
                    <div class="section-title text-primary"><i class="fas fa-layer-group"></i> 1. मूल जानकारी एवं श्रेणी</div>
                    
                    <div class="row g-3">
                        <!-- Category -->
                        <div class="col-md-4">
                            <label for="category" class="form-label">श्रेणी (Category) <span class="text-danger">*</span></label>
                            <select class="form-select" id="category" name="category" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                <option value="latest_jobs" <?php echo ($vacancy['category'] == 'latest_jobs') ? 'selected' : ''; ?>>Latest Jobs (नवीनतम भर्तियां)</option>
                                <option value="result" <?php echo ($vacancy['category'] == 'result') ? 'selected' : ''; ?>>Result (परीक्षा परिणाम)</option>
                                <option value="admit_card" <?php echo ($vacancy['category'] == 'admit_card') ? 'selected' : ''; ?>>Admit Card (प्रवेश पत्र)</option>
                                <option value="answer_key" <?php echo ($vacancy['category'] == 'answer_key') ? 'selected' : ''; ?>>Answer Key (उत्तर कुंजी)</option>
                                <option value="admission" <?php echo ($vacancy['category'] == 'admission') ? 'selected' : ''; ?>>Admission (प्रवेश फॉर्म)</option>
                                <option value="syllabus" <?php echo ($vacancy['category'] == 'syllabus') ? 'selected' : ''; ?>>Syllabus (पाठ्यक्रम)</option>
                            </select>
                        </div>

                        <!-- Title -->
                        <div class="col-md-8">
                            <label for="title" class="form-label">शीर्षक / पद का नाम (Title) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($vacancy['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                        </div>

                        <!-- State -->
                        <div class="col-md-4">
                            <label for="state" class="form-label">राज्य / क्षेत्र (State)</label>
                            <input type="text" class="form-control" id="state" name="state" placeholder="उदा. All India, MP, UP" 
                                   value="<?php echo htmlspecialchars($vacancy['state'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo !$can_edit ? 'disabled' : ''; ?>>
                        </div>

                        <!-- Total Posts -->
                        <div class="col-md-4 field-group cat-latest_jobs cat-admission">
                            <label for="total_posts" class="form-label">कुल पद / सीटें (Total Posts/Seats)</label>
                            <input type="text" class="form-control" id="total_posts" name="total_posts" placeholder="उदा. 500 Posts" 
                                   value="<?php echo htmlspecialchars($vacancy['total_posts'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo !$can_edit ? 'disabled' : ''; ?>>
                        </div>

                        <!-- Last Date -->
                        <div class="col-md-4 field-group cat-latest_jobs cat-admission cat-admit_card">
                            <label for="last_date" class="form-label" id="lbl_last_date">अंतिम तिथि (Last Date)</label>
                            <input type="date" class="form-control" id="last_date" name="last_date" 
                                   value="<?php echo htmlspecialchars($vacancy['last_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo !$can_edit ? 'disabled' : ''; ?>>
                        </div>
                    </div>
                </div>

                <!-- 2. महत्वपूर्ण लिंक्स (Links Hub) -->
                <div class="form-section-card">
                    <div class="section-title text-success"><i class="fas fa-link"></i> 2. महत्वपूर्ण वेब लिंक्स (Links)</div>
                    
                    <div class="row g-3">
                        <!-- Apply Link -->
                        <div class="col-md-6 field-group cat-latest_jobs cat-admission">
                            <label for="apply_link" class="form-label">ऑनलाइन आवेदन लिंक (Apply Link)</label>
                            <input type="url" class="form-control" id="apply_link" name="apply_link" placeholder="https://..." 
                                   value="<?php echo htmlspecialchars($vacancy['apply_link'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo !$can_edit ? 'disabled' : ''; ?>>
                        </div>

                        <!-- Dynamic Download Link -->
                        <div class="col-md-6 field-group cat-result cat-admit_card cat-answer_key cat-syllabus cat-latest_jobs cat-admission">
                            <label for="result_link" class="form-label" id="lbl_result_link">रिजल्ट / मुख्य डायरेक्ट लिंक (Direct Download Link)</label>
                            <input type="url" class="form-control" id="result_link" name="result_link" placeholder="https://..." 
                                   value="<?php echo htmlspecialchars($vacancy['result_link'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo !$can_edit ? 'disabled' : ''; ?>>
                        </div>

                        <!-- Official Website -->
                        <div class="col-md-12">
                            <label for="official_website" class="form-label">आधिकारिक वेबसाइट (Official Website)</label>
                            <input type="url" class="form-control" id="official_website" name="official_website" placeholder="https://..." 
                                   value="<?php echo htmlspecialchars($vacancy['official_website'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo !$can_edit ? 'disabled' : ''; ?>>
                        </div>
                    </div>
                </div>

                <!-- 3. संक्षिप्त विवरण एवं तिथियाँ -->
                <div class="form-section-card">
                    <div class="section-title text-warning"><i class="fas fa-align-left"></i> 3. संक्षिप्त विवरण एवं तिथियाँ</div>
                    
                    <div class="row g-3">
                        <!-- Short Description -->
                        <div class="col-md-6">
                            <label for="short_desc" class="form-label">संक्षिप्त विवरण (Short Overview) <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="short_desc" name="short_desc" rows="4" required <?php echo !$can_edit ? 'disabled' : ''; ?>><?php echo htmlspecialchars($vacancy['short_desc'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <!-- Important Dates -->
                        <div class="col-md-6 field-group cat-latest_jobs cat-admission cat-result cat-admit_card cat-answer_key">
                            <label for="important_dates" class="form-label">महत्वपूर्ण तिथियाँ (Important Dates Text)</label>
                            <textarea class="form-control" id="important_dates" name="important_dates" rows="4" placeholder="आवेदन शुरू: 01/01/2026&#10;अंतिम तिथि: 31/01/2026" <?php echo !$can_edit ? 'disabled' : ''; ?>><?php echo htmlspecialchars($vacancy['important_dates'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- 4. विस्तृत विवरण एवं चरण (Steps) -->
                <div class="form-section-card">
                    <div class="section-title text-danger"><i class="fas fa-file-alt"></i> 4. विस्तृत विवरण एवं प्रक्रिया</div>
                    
                    <div class="row g-3">
                        <!-- Long Details -->
                        <div class="col-md-12 field-group cat-latest_jobs cat-admission cat-syllabus">
                            <label for="long_details" class="form-label" id="lbl_long_details">विस्तृत विवरण / योग्यता / फ़ीस (Long Details)</label>
                            <textarea class="form-control" id="long_details" name="long_details" rows="5" <?php echo !$can_edit ? 'disabled' : ''; ?>><?php echo htmlspecialchars($vacancy['long_details'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <!-- Step-by-Step Instructions -->
                        <div class="col-md-12">
                            <label for="description" class="form-label" id="lbl_description">प्रक्रिया / निर्देश (How to Check / Apply Steps)</label>
                            <textarea class="form-control" id="description" name="description" rows="5" <?php echo !$can_edit ? 'disabled' : ''; ?>><?php echo htmlspecialchars($vacancy['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-3 mb-5">
                    <?php if ($can_edit): ?>
                        <button type="submit" class="btn btn-save fs-6"><i class="fas fa-sync-alt me-1"></i> सुधार करके दोबारा सबमिट करें</button>
                    <?php endif; ?>
                    <a href="manage_vacancies.php" class="btn btn-cancel fs-6">रद्द करें</a>
                </div>

            </form>

        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Dynamic Field Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const categorySelect = document.getElementById('category');
    
    if (!categorySelect) return;

    function handleCategoryChange() {
        const selectedCat = categorySelect.value;
        const allDynamicFields = document.querySelectorAll('.field-group');

        allDynamicFields.forEach(field => {
            if (field.classList.contains('cat-' + selectedCat)) {
                field.style.display = 'block';
            } else {
                field.style.display = 'none';
            }
        });

        const lblResultLink = document.getElementById('lbl_result_link');
        const lblDescription = document.getElementById('lbl_description');
        const lblLastDate = document.getElementById('lbl_last_date');

        if (lblResultLink) {
            switch (selectedCat) {
                case 'result':
                    lblResultLink.innerText = 'रिजल्ट डाउनलोड लिंक (Result Download Link)';
                    break;
                case 'admit_card':
                    lblResultLink.innerText = 'एडमिट कार्ड डाउनलोड लिंक (Admit Card Link)';
                    break;
                case 'answer_key':
                    lblResultLink.innerText = 'उत्तर कुंजी लिंक (Answer Key Link)';
                    break;
                case 'syllabus':
                    lblResultLink.innerText = 'सिलेबस / पीडीएफ डाउनलोड लिंक (Syllabus PDF Link)';
                    break;
                case 'latest_jobs':
                    lblResultLink.innerText = 'नोटिफिकेशन पीडीएफ लिंक (Notification PDF Link)';
                    break;
                default:
                    lblResultLink.innerText = 'डायरेक्ट डाउनलोड लिंक (Direct Link)';
            }
        }

        if (lblDescription) {
            switch (selectedCat) {
                case 'result':
                    lblDescription.innerText = 'रिजल्ट देखने की प्रक्रिया (How to Check Result)';
                    break;
                case 'admit_card':
                    lblDescription.innerText = 'एडमिट कार्ड डाउनलोड करने की प्रक्रिया (How to Download Admit Card)';
                    break;
                case 'answer_key':
                    lblDescription.innerText = 'उत्तर कुंजी और आपत्ति दर्ज करने के चरण (Step-by-Step Instructions)';
                    break;
                case 'syllabus':
                    lblDescription.innerText = 'परीक्षा पैटर्न और विषय सूची (Exam Pattern Details)';
                    break;
                default:
                    lblDescription.innerText = 'आवेदन करने की प्रक्रिया (How to Fill Form)';
            }
        }

        if (lblLastDate) {
            if (selectedCat === 'admit_card') {
                lblLastDate.innerText = 'परीक्षा तिथि (Exam Date)';
            } else {
                lblLastDate.innerText = 'अंतिम तिथि (Last Date)';
            }
        }
    }

    handleCategoryChange();
    categorySelect.addEventListener('change', handleCategoryChange);
});
</script>

</body>
</html>