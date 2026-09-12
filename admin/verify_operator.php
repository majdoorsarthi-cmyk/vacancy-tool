<?php
// Session start aur Admin login check
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// DB connection include
include '../includes/db_connect.php'; 

// MySQL mode relax (Safe Execution)
if ($conn) {
    $conn->query("SET sql_mode=''");
}

// ─── एक्टिव स्टेट लॉजिक (SIDEBAR SYNC) ───
$current_page = basename($_SERVER['PHP_SELF']);
$current_category = $_GET['category'] ?? '';

// वह फ़ाइलें जिन्हें हमेशा एक्टिव रहना चाहिए
$active_files = ['add_vacancy.php', 'add_result.php', 'admin_approve.php', 'manage_vacancies.php', 'manage_categories.php', 'verify_operator.php', 'wallet.php'];

// जाँच करें कि क्या वर्तमान पेज एक्टिव है
function is_active_file($file_name) {
    global $current_page;
    return ($current_page == $file_name) ? 'active' : '';
}

// जाँच करें कि क्या वर्तमान पेज किसी ड्रॉपडाउन का हिस्सा है
function is_dropdown_open($files, $categories = []) {
    global $current_page, $current_category;
    
    if (in_array($current_page, $files)) {
        if ($current_page == 'manage_vacancies.php' && !empty($categories)) {
            if (in_array($current_category, $categories)) {
                return ' active';
            }
        }
        elseif (empty($categories)) {
            return ' active';
        }
    }
    return '';
}

$message = "";
$msg_class = "";

// ─── REAL-SYNC: OPERATOR ACTION PROCESSING ───
if (isset($_GET['action']) && isset($_GET['id'])) {
    $operator_id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action == 'approve') {
        $update_sql = "UPDATE operators SET is_active = 1, payment_verified = 1 WHERE id = $operator_id";
        if (mysqli_query($conn, $update_sql)) {
            $message = "✅ Operator account kamyabi se approved aur activate kar diya gaya hai!";
            $msg_class = "alert-success";
        } else {
            $message = "❌ Error: Account approve nahi ho paya: " . mysqli_error($conn);
            $msg_class = "alert-danger";
        }
    } elseif ($action == 'reject') {
        $delete_sql = "DELETE FROM operators WHERE id = $operator_id";
        if (mysqli_query($conn, $delete_sql)) {
            $message = "🗑️ Operator request ko reject aur table se remove kar diya gaya hai.";
            $msg_class = "alert-warning";
        } else {
            $message = "❌ Error: Request reject nahi ho payi: " . mysqli_error($conn);
            $msg_class = "alert-danger";
        }
    }
}

// ─── PENDING OPERATORS QUERY ───
$sql = "SELECT id, username, email, whatsapp_no, adhar_no, pan_no, bank_name, account_no, ifsc_code, utr_number, payment_screenshot, created_at FROM operators WHERE is_active = 0 ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
$total_requests = ($result) ? mysqli_num_rows($result) : 0;
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operator Verification Panel | Admin</title>
    <!-- Bootstrap 5.3.0 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link class="sub-css" rel="stylesheet" href="assets/css/admin_style.css?v=<?php echo time(); ?>"> 
    
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: #1e293b;
        }
        /* लेआउट रैपर सीएसएस */
        #wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }
        #sidebar-wrapper {
            min-width: 250px;
            max-width: 250px;
            background: #0f172a;
            color: #fff;
            min-height: 100vh;
            transition: all 0.3s;
        }
        #page-content-wrapper {
            width: 100%;
            flex-grow: 1;
        }
        .admin-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 20px 0;
            border-bottom: 4px solid #3b82f6;
            margin-bottom: 4px;
        }
        .main-card {
            background: #ffffff;
            border-radius: 16px;
            border: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            padding: 25px;
        }
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }
        .table thead {
            background: #4f46e5;
            color: white;
        }
        .table th {
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px;
        }
        .table td {
            padding: 15px;
            vertical-align: middle;
            font-size: 0.95rem;
        }
        .utr-badge {
            background-color: #fef3c7;
            color: #d97706;
            font-family: monospace;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #fde68a;
        }
        .btn-action {
            padding: 8px 16px;
            font-weight: 600;
            border-radius: 8px;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        .preview-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid #cbd5e1;
            transition: transform 0.2s;
        }
        .preview-thumb:hover {
            transform: scale(1.1);
            border-color: #3b82f6;
        }
        #modalScreenshot {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        /* PHP द्वारा खुले हुए ड्रॉपडाउन के लिए स्टाइल */
        .sidebar-dropdown.active > .submenu {
            display: block !important;
        }
    </style>
</head>
<body>
<div id="wrapper">
    
    <!-- ─── SIDEBAR START (COMPLETELY SYNCED) ─── -->
    <div id="sidebar-wrapper">
        <ul class="sidebar-nav">
            <li class="sidebar-brand">
                <a href="dashboard.php">⚙️ Admin Panel</a>
            </li>
            
            <li class="<?php echo is_active_file('dashboard.php'); ?>">
                <a href="dashboard.php">🏠 डैशबोर्ड</a>
            </li>

            <?php 
                $post_management_files = ['add_vacancy.php', 'add_result.php', 'admin_approve.php', 'manage_vacancies.php'];
                $post_management_is_open = is_dropdown_open($post_management_files);
            ?>
            <li class="sidebar-dropdown<?php echo $post_management_is_open; ?>">
                <a href="#">📢 पोस्ट प्रबंधन</a>
                <ul class="submenu" style="<?php echo $post_management_is_open ? 'display: block;' : ''; ?>">
                    <li class="<?php echo is_active_file('admin_approve.php'); ?>"><a href="admin_approve.php">✔️ वैकेंसी अप्रूवल पैनल</a></li>
                    <li class="<?php echo is_active_file('add_vacancy.php'); ?>"><a href="add_vacancy.php">➕ नई जॉब रिक्ति जोड़ें</a></li>
                    <li class="<?php echo is_active_file('add_result.php'); ?>"><a href="add_result.php">➕ नई परिणाम पोस्ट जोड़ें</a></li> 
                    <li class="<?php echo is_active_file('manage_vacancies.php') && empty($current_category) ? 'active' : ''; ?>"><a href="manage_vacancies.php">📝 सभी पोस्ट प्रबंधित करें</a></li>
                </ul>
            </li>
            
            <?php 
                $category_management_files = ['manage_categories.php', 'manage_vacancies.php'];
                $category_management_categories = ['job', 'result', 'admit_card', 'other'];
                $category_management_is_open = is_dropdown_open($category_management_files, $category_management_categories) || is_active_file('manage_categories.php');
            ?>
            <li class="sidebar-dropdown<?php echo $category_management_is_open; ?>">
                <a href="#">🏷️ श्रेणी के अनुसार प्रबंधन</a>
                <ul class="submenu" style="<?php echo $category_management_is_open ? 'display: block;' : ''; ?>">
                    <li class="<?php echo is_active_file('manage_categories.php'); ?>"><a href="manage_categories.php">🛠️ श्रेणियाँ सेटिंग्स</a></li> 
                    <li class="<?php echo ($current_page == 'manage_vacancies.php' && $current_category == 'job') ? 'active' : ''; ?>"><a href="manage_vacancies.php?category=job">💼 रिक्ति (Job)</a></li>
                    <li class="<?php echo ($current_page == 'manage_vacancies.php' && $current_category == 'result') ? 'active' : ''; ?>"><a href="manage_vacancies.php?category=result">📋 परिणाम (Result)</a></li>
                    <li class="<?php echo ($current_page == 'manage_vacancies.php' && $current_category == 'admit_card') ? 'active' : ''; ?>"><a href="manage_vacancies.php?category=admit_card">🎟️ एडमिट कार्ड</a></li>
                    <li class="<?php echo ($current_page == 'manage_vacancies.php' && $current_category == 'other') ? 'active' : ''; ?>"><a href="manage_vacancies.php?category=other">📂 अन्य पोस्ट</a></li>
                </ul>
            </li>

            <!-- वॉलेट लेजर मेनू ऑप्शन -->
            <li class="<?php echo is_active_file('wallet.php'); ?>">
                <a href="wallet.php">💳 वॉलेट लेजर</a>
            </li>

            <!-- ऑपरेटर सत्यापन विकल्प (ACTIVE STATE HIGHLIGHTED FOR THIS FILE) -->
            <li class="<?php echo is_active_file('verify_operator.php'); ?>">
                <a href="verify_operator.php">🧑‍💻 ऑपरेटर सत्यापन पैनल</a>
            </li>

            <li>
                <a href="logout.php">➡️ लॉग आउट</a>
            </li>
        </ul>
    </div>
    <!-- ─── SIDEBAR END ─── -->

    <!-- ─── MAIN CONTENT BLOCK ─── -->
    <div id="page-content-wrapper">
        <!-- Admin Top Navbar/Header -->
        <div class="admin-header shadow-sm">
            <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1"><i class="fas fa-shield-halved text-primary me-2"></i> Vacancy Portal Control Panel</h4>
                    <p class="small text-muted mb-0" style="color: #94a3b8 !important;">नया ऑपरेटर वेरिफिकेशन गेटवे</p>
                </div>
                <div>
                    <span class="badge bg-danger p-2"><i class="fas fa-circle-dot me-1 shadow-sm"></i> लाइव वेरिफिकेशन मोड</span>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">
            
            <!-- Toast/Alert System Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $msg_class; ?> alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <span class="fs-6 me-2"><?php echo $message; ?></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Main Datatable Card -->
            <div class="main-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0 text-indigo"><i class="fas fa-hourglass-half text-warning me-2"></i> लंबित ऑपरेटर पंजीकरण अनुरोध (Pending Operators)</h5>
                    <span class="badge bg-secondary px-3 py-2 rounded-pill"><?php echo $total_requests; ?> Request(s) Found</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ऑपरेटर क्रेडेंशियल्स</th>
                                <th>संपर्क विवरण</th>
                                <th>आधार / पैन</th>
                                <th>बैंक केवाईसी विवरण</th>
                                <th>भुगतान UTR नंबर</th>
                                <th>रसीद (Screenshot)</th>
                                <th class="text-center">एक्शन</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_requests > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <!-- Username & Date -->
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['username'] ?? ''); ?></div>
                                            <small class="text-muted" style="font-size:0.75rem;"><i class="far fa-clock me-1"></i> <?php echo isset($row['created_at']) ? date('d M Y, h:i A', strtotime($row['created_at'])) : ''; ?></small>
                                        </td>
                                        
                                        <!-- Contact Info -->
                                        <td>
                                            <div class="small"><i class="fas fa-envelope text-muted me-1"></i> <?php echo htmlspecialchars($row['email'] ?? ''); ?></div>
                                            <div class="small fw-semibold mt-1"><i class="fab fa-whatsapp text-success me-1"></i> <?php echo htmlspecialchars($row['whatsapp_no'] ?? ''); ?></div>
                                        </td>
                                        
                                        <!-- Aadhaar & PAN -->
                                        <td>
                                            <div class="small">Aadhar: <span class="fw-bold text-dark"><?php echo !empty($row['adhar_no']) ? '[Aadhaar Redacted]' : ''; ?></span></div>
                                            <div class="small mt-1">PAN: <span class="badge bg-light text-dark border fw-bold"><?php echo htmlspecialchars($row['pan_no'] ?? ''); ?></span></div>
                                        </td>
                                        
                                        <!-- Bank KYC Details -->
                                        <td>
                                            <?php if(!empty($row['bank_name'])): ?>
                                                <div class="small fw-bold text-indigo"><i class="fas fa-university me-1 text-muted"></i> <?php echo htmlspecialchars($row['bank_name'] ?? ''); ?></div>
                                                <div class="small text-muted">A/C: <span class="text-dark fw-semibold"><?php echo htmlspecialchars($row['account_no'] ?? ''); ?></span></div>
                                                <div class="small text-muted">IFSC: <span class="text-dark fw-semibold"><?php echo htmlspecialchars($row['ifsc_code'] ?? ''); ?></span></div>
                                            <?php else: ?>
                                                <span class="text-muted small">No Bank Details</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- UTR Number -->
                                        <td>
                                            <?php if(!empty($row['utr_number'])): ?>
                                                <span class="utr-badge"><i class="fas fa-receipt me-1"></i> <?php echo htmlspecialchars($row['utr_number'] ?? ''); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Smart Route Checker logic for images -->
                                        <td>
                                            <?php 
                                            $screenshot = trim($row['payment_screenshot'] ?? '');
                                            $display_img = "";

                                            if (!empty($screenshot)) {
                                                if (file_exists($screenshot)) {
                                                    $display_img = $screenshot;
                                                } elseif (file_exists("../" . $screenshot)) {
                                                    $display_img = "../" . $screenshot;
                                                } elseif (file_exists("../../" . $screenshot)) {
                                                    $display_img = "../../" . $screenshot;
                                                } elseif (file_exists("uploads/" . basename($screenshot))) {
                                                    $display_img = "uploads/" . basename($screenshot);
                                                } elseif (file_exists("../uploads/" . basename($screenshot))) {
                                                    $display_img = "../uploads/" . basename($screenshot);
                                                }
                                            }

                                            if (!empty($display_img)): 
                                            ?>
                                                <img src="<?php echo htmlspecialchars($display_img); ?>" class="preview-thumb" alt="Payment Receipt" onclick="openScreenshotModal('<?php echo htmlspecialchars($display_img); ?>', '<?php echo htmlspecialchars($row['utr_number'] ?? 'N/A'); ?>')">
                                            <?php else: ?>
                                                <?php if(!empty($screenshot)): ?>
                                                    <a href="<?php echo htmlspecialchars("../" . $screenshot); ?>" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size: 0.75rem;">
                                                        <i class="fas fa-external-link-alt me-1"></i> View File
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-danger small fw-bold"><i class="fas fa-circle-xmark me-1"></i> No Image</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Direct Actions Buttons -->
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="verify_operator.php?action=approve&id=<?php echo $row['id']; ?>" class="btn btn-success btn-action" onclick="return confirm('क्या आप वाकई इस ऑपरेटर को स्वीकृत (Approve) करना चाहते हैं?')">
                                                    <i class="fas fa-user-check me-1"></i> Approve
                                                </a>
                                                <a href="verify_operator.php?action=reject&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-action" onclick="return confirm('क्या आप इस अनुरोध को अस्वीकार (Reject) और डिलीट करना चाहते हैं?')">
                                                    <i class="fas fa-user-xmark me-1"></i> Reject
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-folder-open display-4 mb-3 text-secondary"></i>
                                        <h5>कोई भी लंबित ऑपरेटर पंजीकरण अनुरोध नहीं मिला!</h5>
                                        <p class="small">सभी ऑपरेटर सत्यापित और एक्टिव मोड में हैं।</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- ─── MAIN CONTENT BLOCK END ─── -->

</div>

<!-- SCREENSHOT VIEW MODAL -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius:20px;">
            <div class="modal-header bg-dark text-white" style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <h6 class="modal-title fw-bold" id="receiptModalLabel"><i class="fas fa-receipt text-warning me-2"></i> भुगतान रसीद सत्यापन</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center bg-light p-4">
                <div class="mb-3">
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">UTR Ref: <span id="modalUtrText" class="fw-bold"></span></span>
                </div>
                <div class="shadow-sm p-2 bg-white rounded">
                    <img id="modalScreenshot" src="" alt="Full Payment Receipt">
                </div>
            </div>
            <div class="modal-footer justify-content-center border-0 bg-light pb-4" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal" style="border-radius:10px;">बंद करें</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS and Modal Handler -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function openScreenshotModal(imageSrc, utrNumber) {
        document.getElementById('modalScreenshot').src = imageSrc;
        document.getElementById('modalUtrText').innerText = utrNumber;
        var myModal = new bootstrap.Modal(document.getElementById('receiptModal'));
        myModal.show();
    }
</script>
</body>
</html>