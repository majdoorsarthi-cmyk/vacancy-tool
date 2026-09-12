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

// ऑपरेटर की रीयल-टाइम स्थिति डेटाबेस से फैच करें
$is_suspended = 0;
$op_rank = 'Junior Operator';
$op_score = 0;
$operator_username = isset($_SESSION['operator_username']) ? $_SESSION['operator_username'] : 'User';

$check_op = $conn->prepare("SELECT is_suspended, designation, performance_score FROM operators WHERE id = ?");
$check_op->bind_param("i", $operator_id);
$check_op->execute();
$check_op->bind_result($is_suspended, $op_rank, $op_score);
$check_op->fetch();
$check_op->close();

// Session-based messages handle karna
if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
if (isset($_SESSION['error_msg'])) {
    $error_msg = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

// 2. DELETE VACANCY LOGIC (सस्पेंशन चेक और Prepared Statements)
if (isset($_GET['delete_id'])) {
    if ($is_suspended == 1) {
        $_SESSION['error_msg'] = "❌ आपका अकाउंट सस्पेंडेड है। आप कोई वैकेंसी डिलीट नहीं कर सकते।";
    } else {
        $delete_id = intval($_GET['delete_id']);
        
        $check_stmt = $conn->prepare("SELECT status FROM vacancies WHERE id = ? AND operator_id = ?");
        $check_stmt->bind_param("ii", $delete_id, $operator_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result && $check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            // ऑपरेटर केवल Pending या Rejected वैकेंसियों को डिलीट कर सकता है
            if ($row['status'] == 'pending' || $row['status'] == 'rejected') {
                $delete_stmt = $conn->prepare("DELETE FROM vacancies WHERE id = ?");
                $delete_stmt->bind_param("i", $delete_id);
                if ($delete_stmt->execute()) {
                    $_SESSION['success_msg'] = "🗑️ वैकेंसी सफलतापूर्वक हटा दी गई है।";
                } else {
                    $_SESSION['error_msg'] = "❌ त्रुटि: वैकेंसी को हटाया नहीं जा सका।";
                }
                $delete_stmt->close();
            } else {
                $_SESSION['error_msg'] = "⚠️ आप केवल 'Pending' या 'Rejected' वैकेंसियों को ही डिलीट कर सकते हैं।";
            }
        } else {
            $_SESSION['error_msg'] = "❌ अनधिकृत अनुरोध या अवैध आईडी।";
        }
        $check_stmt->close();
    }
    
    header("Location: manage_vacancies.php");
    exit();
}

// 3. FILTER & SEARCH PARAMETERS
$filter_category = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$filter_status   = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$search_query    = isset($_GET['search']) ? trim($_GET['search']) : '';

// 4. DYNAMIC FETCH VACANCIES QUERY BUILDING
$sql = "SELECT * FROM vacancies WHERE operator_id = ?";
$params = [$operator_id];
$types = "i";

// श्रेणी (Category) फ़िल्टर
if (!empty($filter_category) && $filter_category !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $filter_category;
    $types .= "s";
}

// स्थिति (Status) फ़िल्टर
if (!empty($filter_status) && $filter_status !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

// खोज (Search Keyword) फ़िल्टर
if (!empty($search_query)) {
    $sql .= " AND (title LIKE ? OR short_desc LIKE ?)";
    $searchTerm = '%' . $search_query . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$sql .= " ORDER BY id DESC";

$fetch_stmt = $conn->prepare($sql);
if ($fetch_stmt) {
    $fetch_stmt->bind_param($types, ...$params);
    $fetch_stmt->execute();
    $result = $fetch_stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>वैकेंसी प्रबंधन | Vacancy Portal</title>
    
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
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            transition: all 0.3s ease;
        }
        
        .sidebar-brand {
            padding: 24px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .brand-text {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-menu {
            padding: 15px 15px;
            flex-grow: 1;
        }

        .op-profile-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 15px;
        }

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

        .nav-menu-link i {
            width: 24px;
            font-size: 1.1rem;
            margin-right: 10px;
        }

        .nav-menu-link:hover {
            background: #f1f5f9;
            color: #2563eb;
        }

        .nav-menu-link.active {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(37, 99, 243, 0.1) 100%);
            color: #2563eb;
            font-weight: 600;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #f1f5f9;
            background: #f8fafc;
        }

        .main-content {
            flex-grow: 1;
            margin-left: 260px;
            padding: 30px;
            transition: all 0.3s ease;
        }

        .content-navbar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid #e2e8f0;
            padding: 15px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        }
        
        .filter-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.015);
        }

        .data-card {
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        }

        .table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.82rem;
            padding: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .table td { padding: 15px; font-size: 0.92rem; }

        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.78rem;
            font-weight: 700;
            display: inline-block;
        }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.2); }
        .status-approved { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); }
        .status-rejected { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.2); }

        .cat-badge {
            background: #e0e7ff;
            color: #3730a3;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .btn-add-new {
            background: var(--primary-gradient);
            color: white !important;
            font-weight: 600;
            border-radius: 10px;
            padding: 10px 20px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
            border: none;
        }
        .btn-add-new:hover { transform: translateY(-1px); }

        .action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        .rejection-box {
            background-color: #fef2f2;
            border-left: 3px solid #ef4444;
            color: #991b1b;
            font-size: 0.82rem;
            padding: 5px 8px;
            border-radius: 4px;
            margin-top: 6px;
        }

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

    <!-- 1. SIDEBAR COMPONENT -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
                <img src="vp.png" alt="Logo" width="30" height="30" class="me-2">
                <span class="brand-text fs-4">VACANCY</span>
            </a>
        </div>
        
        <div class="sidebar-menu">
            <div class="op-profile-card">
                <div class="small text-muted mb-1 text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">ऑपरेटर अकाउंट</div>
                <div class="fw-bold text-dark text-truncate" style="font-size: 13px;"><?php echo htmlspecialchars($operator_username); ?></div>
                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top" style="font-size: 11px;">
                    <span class="text-secondary"><i class="fas fa-award text-warning me-1"></i> <?php echo htmlspecialchars($op_rank); ?></span>
                    <span class="text-success fw-bold"><?php echo $op_score; ?> Pts</span>
                </div>
            </div>

            <a href="dashboard.php" class="nav-menu-link">
                <i class="fas fa-th-large"></i> डैशबोर्ड
            </a>
            <a href="manage_vacancies.php" class="nav-menu-link active">
                <i class="fas fa-folder-open"></i> वैकेंसी प्रबंधन
            </a>
            <a href="add_vacancy.php" class="nav-menu-link">
                <i class="fas fa-plus-circle"></i> नई वैकेंसी जोड़ें
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
        
        <!-- Top Navbar -->
        <div class="content-navbar d-flex justify-content-between align-items-center">
            <button class="btn btn-light toggle-btn d-none" id="menuToggle" onclick="document.getElementById('sidebar').classList.toggle('active')">
                <i class="fas fa-bars"></i>
            </button>
            <div class="ms-auto d-flex align-items-center gap-3">
                <?php if ($is_suspended == 1): ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 small fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> आपका अकाउंट सस्पेंडेड है</span>
                <?php else: ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 small fw-bold"><i class="fas fa-shield-alt me-1"></i> सत्र सुरक्षित है</span>
                <?php endif; ?>
                <span class="text-muted small"><i class="fas fa-user-circle me-1"></i> ऑपरेटर: <strong><?php echo htmlspecialchars($operator_username); ?></strong></span>
            </div>
        </div>

        <!-- Headings -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">आपकी सबमिट की गई वैकेंसियां</h3>
                <p class="text-muted mb-0 small">यहाँ अपनी पोस्ट को श्रेणी और स्थिति अनुसार फ़िल्टर या सर्च करें।</p>
            </div>
            <a href="add_vacancy.php" class="btn btn-add-new"><i class="fas fa-plus-circle me-1"></i> नई वैकेंसी जोड़ें</a>
        </div>

        <!-- Messages -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success py-2.5 mb-3 small fw-bold shadow-sm rounded-3"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger py-2.5 mb-3 small fw-bold shadow-sm rounded-3"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- 🔍 MULTI-CATEGORY & STATUS FILTER BAR -->
        <div class="filter-card">
            <form method="GET" action="manage_vacancies.php" class="row g-2 align-items-center">
                
                <!-- 1. Category Filter -->
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="fas fa-tags me-1"></i> श्रेणी (Category)</label>
                    <select name="category" class="form-select form-select-sm fw-semibold" onchange="this.form.submit()">
                        <option value="all" <?php echo ($filter_category == 'all') ? 'selected' : ''; ?>>-- समस्त श्रेणियाँ (All) --</option>
                        <option value="latest_jobs" <?php echo ($filter_category == 'latest_jobs') ? 'selected' : ''; ?>>Latest Jobs (नवीनतम नौकरियां)</option>
                        <option value="result" <?php echo ($filter_category == 'result') ? 'selected' : ''; ?>>Result (परीक्षा परिणाम)</option>
                        <option value="admit_card" <?php echo ($filter_category == 'admit_card') ? 'selected' : ''; ?>>Admit Card (प्रवेश पत्र)</option>
                        <option value="answer_key" <?php echo ($filter_category == 'answer_key') ? 'selected' : ''; ?>>Answer Key (उत्तर कुंजी)</option>
                        <option value="admission" <?php echo ($filter_category == 'admission') ? 'selected' : ''; ?>>Admission (प्रवेश फॉर्म)</option>
                        <option value="syllabus" <?php echo ($filter_category == 'syllabus') ? 'selected' : ''; ?>>Syllabus (पाठ्यक्रम)</option>
                    </select>
                </div>

                <!-- 2. Status Filter -->
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="fas fa-toggle-on me-1"></i> स्थिति (Status)</label>
                    <select name="status" class="form-select form-select-sm fw-semibold" onchange="this.form.submit()">
                        <option value="all" <?php echo ($filter_status == 'all') ? 'selected' : ''; ?>>-- सभी स्थितियाँ (All) --</option>
                        <option value="pending" <?php echo ($filter_status == 'pending') ? 'selected' : ''; ?>>⏳ Pending (लंबित)</option>
                        <option value="approved" <?php echo ($filter_status == 'approved' || $filter_status == 'live') ? 'selected' : ''; ?>>✔️ Approved / Live (स्वीकृत)</option>
                        <option value="rejected" <?php echo ($filter_status == 'rejected') ? 'selected' : ''; ?>>❌ Rejected (अस्वीकृत)</option>
                        <option value="archived" <?php echo ($filter_status == 'archived') ? 'selected' : ''; ?>>📦 Archived (आर्काइव्ड)</option>
                    </select>
                </div>

                <!-- 3. Search Keyword -->
                <div class="col-md-4 col-sm-8">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="fas fa-search me-1"></i> खोजें (Search Title)</label>
                    <input type="text" name="search" class="form-select-sm form-control form-control-sm" placeholder="टाइटल या विवरण खोजें..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>

                <!-- 4. Action Buttons -->
                <div class="col-md-2 col-sm-4 d-flex gap-1 align-items-end mt-sm-auto mt-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold"><i class="fas fa-filter me-1"></i> फ़िल्टर</button>
                    <?php if ($filter_category !== 'all' || $filter_status !== 'all' || !empty($search_query)): ?>
                        <a href="manage_vacancies.php" class="btn btn-sm btn-outline-secondary" title="फ़िल्टर हटाएं"><i class="fas fa-redo"></i></a>
                    <?php endif; ?>
                </div>

            </form>
        </div>

        <!-- Data Table Card -->
        <div class="data-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>आईडी</th>
                            <th>श्रेणी (Category)</th>
                            <th>पद का नाम (Job Title)</th>
                            <th>अंतिम तिथि</th>
                            <th>स्थिति (Status)</th>
                            <th class="text-center">कार्रवाई (Action)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $status_class = "status-pending";
                                $status_text = "⏳ Pending";
                                
                                if ($row['status'] == 'live' || $row['status'] == 'approved') { 
                                    $status_class = "status-approved";
                                    $status_text = "✔️ Approved";
                                } elseif ($row['status'] == 'rejected') {
                                    $status_class = "status-rejected";
                                    $status_text = "❌ Rejected";
                                } elseif ($row['status'] == 'archived') {
                                    $status_class = "status-rejected";
                                    $status_text = "📦 Archived";
                                }
                                
                                $cat_display = !empty($row['category']) ? str_replace('_', ' ', $row['category']) : 'General';
                                $last_date = ($row['last_date']) ? date('d-m-Y', strtotime($row['last_date'])) : 'N/A';
                                
                                echo "<tr>";
                                echo "<td class='fw-bold text-secondary'>#" . intval($row['id']) . "</td>";
                                echo "<td><span class='cat-badge'>" . htmlspecialchars($cat_display) . "</span></td>";
                                
                                echo "<td>
                                        <strong class='text-dark d-block'>" . htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8') . "</strong>
                                        <small class='text-muted'>" . htmlspecialchars(mb_strimwidth($row['short_desc'] ?? '', 0, 50, '...')) . "</small>";
                                
                                // अगर पोस्ट Reject हुई है तो Rejection Reason दिखाएं
                                if ($row['status'] == 'rejected' && !empty($row['rejection_reason'])) {
                                    echo "<div class='rejection-box'><i class='fas fa-exclamation-circle me-1'></i> <strong>रिजेक्शन कारण:</strong> " . htmlspecialchars($row['rejection_reason']) . "</div>";
                                }

                                echo "</td>";
                                
                                echo "<td><i class='far fa-calendar-alt text-muted me-1'></i> " . $last_date . "</td>";
                                echo "<td><span class='status-badge " . $status_class . "'>" . $status_text . "</span></td>";
                                echo "<td class='text-center'>";
                                
                                // Edit Action (Pending या Rejected दोनों स्थिति में अनुमति दें)
                                if ($row['status'] == 'pending' || $row['status'] == 'rejected') {
                                    echo "<a href='edit_vacancy.php?id=" . intval($row['id']) . "' class='btn btn-sm btn-outline-primary action-btn me-1' title='संपादित करें / सुधारें'><i class='fas fa-edit'></i></a>";
                                } else {
                                    echo "<a href='edit_vacancy.php?id=" . intval($row['id']) . "' class='btn btn-sm btn-outline-secondary action-btn me-1' title='केवल देखें'><i class='fas fa-eye'></i></a>";
                                }
                                
                                // Delete Action
                                if ($row['status'] == 'pending' || $row['status'] == 'rejected') {
                                    echo "<a href='manage_vacancies.php?delete_id=" . intval($row['id']) . "' class='btn btn-sm btn-outline-danger action-btn' title='हटाएं' onclick='return confirm(\"क्या आप निश्चित रूप से इस वैकेंसी को हटाना चाहते हैं?\");'><i class='fas fa-trash-alt'></i></a>";
                                } else {
                                    echo "<button class='btn btn-sm btn-light action-btn text-muted border-0' disabled title='लाइव या आर्काइव पोस्ट डिलीट नहीं हो सकती'><i class='fas fa-ban'></i></button>";
                                }
                                
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center py-5 text-muted'>";
                            echo "<i class='fas fa-search display-6 mb-3 d-block text-secondary'></i>";
                            echo "चयनित फ़िल्टर के अनुसार कोई परिणाम नहीं मिला।<br><a href='manage_vacancies.php' class='btn btn-sm btn-link text-decoration-none mt-2'>सभी वैकेंसियां देखें</a>";
                            echo "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>