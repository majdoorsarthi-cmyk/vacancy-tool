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

function is_active_file($file_name) {
    global $current_page;
    return ($current_page == $file_name) ? 'active' : '';
}

$message = "";
$msg_type = ""; 

// 🌟 ─── NEW POWER FEATURE: UPDATE OPERATOR RATE & DETAILS (POST REQUEST) ───
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_rate_action'])) {
    $operator_id = intval($_POST['operator_id']);
    $new_rate = floatval($_POST['commission_rate']);
    $new_username = mysqli_real_escape_string($conn, $_POST['username']);

    // डिटेल्स अपडेट करने की SQL क्वेरी
    $rate_update_sql = "UPDATE operators SET username = '$new_username', commission_rate = $new_rate WHERE id = $operator_id";
    
    if (mysqli_query($conn, $rate_update_sql)) {
        // लॉग्स में एंट्री
        $log_action = "Operator Rate updated to Rs. $new_rate and details edited by Admin";
        $log_sql = "INSERT INTO operator_activity_logs (operator_id, action, created_at) VALUES ($operator_id, '$log_action', NOW())";
        mysqli_query($conn, $log_sql);
        
        $message = "🔄 <strong>अपडेट सफल:</strong> ऑपरेटर का डेटा और नया कमीशन रेट (<strong>₹$new_rate</strong>) सुरक्षित कर दिया गया है।";
        $msg_type = "success";
    } else {
        $message = "<strong>❌ त्रुटि:</strong> " . mysqli_error($conn);
        $msg_type = "error";
    }
}

// ─── OPERATOR ACTIONS PROCESSING (SUSPEND, UNSUSPEND, VERIFY, DELETE) ───
if (isset($_GET['action']) && isset($_GET['id'])) {
    $operator_id = intval($_GET['id']);
    $action = $_GET['action'];

    // ऑपरेटर का नाम (Username) फैच कर रहे हैं
    $name_query = "SELECT username FROM operators WHERE id = $operator_id LIMIT 1";
    $name_result = mysqli_query($conn, $name_query);
    $op_name = "Operator"; 
    if ($name_result && mysqli_num_rows($name_result) > 0) {
        $name_row = mysqli_fetch_assoc($name_result);
        $op_name = htmlspecialchars($name_row['username']);
    }

    // 1. SUSPEND ACTION
    if ($action == 'suspend') {
        $suspend_sql = "UPDATE operators SET is_active = 0, is_suspended = 1 WHERE id = $operator_id";
        if (mysqli_query($conn, $suspend_sql)) {
            $log_action = "Operator Suspended by Admin";
            $log_sql = "INSERT INTO operator_activity_logs (operator_id, action, created_at) VALUES ($operator_id, '$log_action', NOW())";
            mysqli_query($conn, $log_sql);
            
            $message = "🚫 <strong>अकाउंट सस्पेंड:</strong> ऑपरेटर <strong>{$op_name}</strong> की सेवाएं रोक दी गई हैं।";
            $msg_type = "warning";
        } else {
            $message = "<strong>❌ त्रुटि:</strong> " . mysqli_error($conn);
            $msg_type = "error";
        }
    } 
    // 2. UNSUSPEND ACTION
    elseif ($action == 'unsuspend') {
        $unsuspend_sql = "UPDATE operators SET is_active = 1, is_suspended = 0 WHERE id = $operator_id";
        if (mysqli_query($conn, $unsuspend_sql)) {
            $log_action = "Operator Unsuspended / Activated by Admin";
            $log_sql = "INSERT INTO operator_activity_logs (operator_id, action, created_at) VALUES ($operator_id, '$log_action', NOW())";
            mysqli_query($conn, $log_sql);
            
            $message = "✅ <strong>अकाउंट बहाल:</strong> ऑपरेटर <strong>{$op_name}</strong> को सफलतापूर्वक वापस एक्टिव कर दिया गया है।";
            $msg_type = "success";
        } else {
            $message = "<strong>❌ त्रुटि:</strong> " . mysqli_error($conn);
            $msg_type = "error";
        }
    }
    // 3. VERIFY ACTION
    elseif ($action == 'verify') {
        $verify_sql = "UPDATE operators SET is_active = 1, is_suspended = 0 WHERE id = $operator_id"; 
        if (mysqli_query($conn, $verify_sql)) {
            $log_action = "Operator Identity Verified & Approved by Admin";
            $log_sql = "INSERT INTO operator_activity_logs (operator_id, action, created_at) VALUES ($operator_id, '$log_action', NOW())";
            mysqli_query($conn, $log_sql);
            
            $message = "🧑‍💻 <strong>सत्यापन सफल:</strong> ऑपरेटर <strong>{$op_name}</strong> का प्रोफ़ाइल वेरिफाई और अप्रूव कर दिया गया है।";
            $msg_type = "success";
        } else {
            $message = "<strong>❌ त्रुटि:</strong> " . mysqli_error($conn);
            $msg_type = "error";
        }
    }
    // 4. DELETE ACTION
    elseif ($action == 'delete') {
        $log_action = "Operator (@{$op_name}) Data Permanently Removed";
        $log_sql = "INSERT INTO operator_activity_logs (operator_id, action, created_at) VALUES ($operator_id, '$log_action', NOW())";
        mysqli_query($conn, $log_sql);

        $delete_sql = "DELETE FROM operators WHERE id = $operator_id";
        if (mysqli_query($conn, $delete_sql)) {
            $message = "🧹 ऑपरेटर <strong>{$op_name}</strong> का डेटा पोर्टल से डिलीट कर दिया गया है।";
            $msg_type = "success";
        } else {
            $message = "<strong>❌ त्रुटि:</strong> " . mysqli_error($conn);
            $msg_type = "error";
        }
    }
}

// 🌟 डेटाबेस से ऑपरेटर्स फेच करना (SQL में commission_rate भी जोड़ दिया है)
$sql = "SELECT id, username, email, whatsapp_no, pan_no, bank_name, account_no, ifsc_code, created_at, is_suspended, commission_rate FROM operators ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
$total_operators = ($result) ? mysqli_num_rows($result) : 0;
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Active Operators | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body { background-color: #f1f5f9; font-family: 'Segoe UI', sans-serif; color: #1e293b; }
        #wrapper { display: flex; width: 100%; align-items: stretch; }
        #sidebar-wrapper { min-width: 250px; max-width: 250px; background: #0f172a; color: #fff; min-height: 100vh; transition: all 0.3s; }
        .sidebar-nav { list-style: none; padding: 20px 0; }
        .sidebar-nav li a { color: #94a3b8; display: block; padding: 12px 20px; text-decoration: none; }
        .sidebar-nav li.active a, .sidebar-nav li a:hover { color: #fff; background: #1e293b; border-left: 4px solid #3b82f6; }
        #page-content-wrapper { width: 100%; flex-grow: 1; }
        .admin-header { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; padding: 20px 0; border-bottom: 4px solid #3b82f6; }
        .main-card { background: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); padding: 25px; }
        .table-responsive { border-radius: 12px; overflow: hidden; }
        .table thead { background: #0f172a; color: white; }
        .table th { font-weight: 600; font-size: 0.9rem; text-transform: uppercase; padding: 15px; }
        .table td { padding: 15px; vertical-align: middle; }
        .btn-action { padding: 6px 12px; font-weight: 600; border-radius: 8px; font-size: 0.85rem; }
        
        /* 🌟 इनपुट बॉक्स स्टाइलिंग */
        .editable-input { border: 1px solid #cbd5e1; padding: 4px 8px; border-radius: 6px; font-size: 0.9rem; font-weight: 500; background-color: #f8fafc; transition: all 0.2s; }
        .editable-input:focus { border-color: #3b82f6; background-color: #fff; outline: none; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
        .btn-save-inline { background: #6366f1; color: white; border: none; padding: 5px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; transition: 0.2s; }
        .btn-save-inline:hover { background: #4f46e5; }
    </style>
</head>
<body>
<div id="wrapper">
    
    <!-- SIDEBAR -->
    <div id="sidebar-wrapper">
        <ul class="sidebar-nav">
            <li class="sidebar-brand" style="padding: 20px; font-size: 1.2rem; font-weight: bold;">⚙️ Admin Panel</li>
            <li class="<?php echo is_active_file('dashboard.php'); ?>"><a href="dashboard.php">🏠 डैशबोर्ड</a></li>
            <li class="<?php echo is_active_file('manage_operators.php'); ?>"><a href="manage_operators.php">👥 ऑपरेटर लिस्ट</a></li>
            <li class="<?php echo is_active_file('verify_operator.php'); ?>"><a href="verify_operator.php">🧑‍💻 सत्यापन पैनल</a></li>
            <li><a href="logout.php">➡️ लॉग आउट</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div id="page-content-wrapper">
        <div class="admin-header shadow-sm">
            <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1"><i class="fas fa-users text-primary me-2"></i> Operators Management</h4>
                    <p class="small text-muted mb-0" style="color: #94a3b8 !important;">पोर्टल के सभी ऑपरेटरों की वास्तविक स्थिति एवं रेट कंट्रोल</p>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">
            <div class="main-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0 text-dark"><i class="fas fa-list text-primary me-2"></i> पंजीकृत ऑपरेटर मास्टर सूची</h5>
                    <span class="badge bg-primary px-3 py-2 rounded-pill"><?php echo $total_operators; ?> Total</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ऑपरेटर क्रेडेंशियल्स</th>
                                <th>कमीशन रेट (₹/Post)</th>
                                <th>संपर्क विवरण</th>
                                <th>स्थिति (Status)</th>
                                <th>बैंक डिटेल्स</th>
                                <th class="text-center">एक्शन</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_operators > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr style="<?php echo ($row['is_suspended'] == 1) ? 'background-color: #fef2f2;' : ''; ?>">
                                        
                                        <!-- फॉर्म की शुरुआत: हर ऑपरेटर के रो को इंडिविजुअल सबमिट करने के लिए -->
                                        <form method="POST" action="">
                                            <input type="hidden" name="operator_id" value="<?php echo $row['id']; ?>">
                                            
                                            <td>
                                                <!-- 🌟 एडिटेबल यूजरनेम इनपुट -->
                                                <input type="text" name="username" class="editable-input fw-bold text-dark w-75 mb-1" value="<?php echo htmlspecialchars($row['username'] ?? ''); ?>" required>
                                                <br>
                                                <small class="text-muted" style="font-size:0.75rem;"><i class="far fa-calendar-alt me-1"></i> <?php echo isset($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : ''; ?></small>
                                            </td>
                                            
                                            <td>
                                                <!-- 🌟 एडिटेबल रेट इनपुट (0.10, 0.50, 1.00 आदि भरने के लिए) -->
                                                <div class="d-flex align-items-center gap-1">
                                                    <input type="number" step="0.01" min="0" name="commission_rate" class="editable-input text-center" style="width: 80px;" value="<?php echo number_format((float)($row['commission_rate'] ?? 0.00), 2); ?>">
                                                    <button type="submit" name="update_rate_action" class="btn-save-inline" title="डिटेल्स और रेट अपडेट करें">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </form>

                                        <td>
                                            <div class="small"><i class="fas fa-envelope text-muted me-1"></i> <?php echo htmlspecialchars($row['email'] ?? ''); ?></div>
                                            <div class="small fw-semibold mt-1"><i class="fab fa-whatsapp text-success me-1"></i> <?php echo htmlspecialchars($row['whatsapp_no'] ?? ''); ?></div>
                                        </td>
                                        <td>
                                            <?php if($row['is_suspended'] == 1): ?>
                                                <span class="badge bg-danger"><i class="fas fa-ban me-1"></i> सस्पेंडेड</span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> एक्टिव</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(!empty($row['bank_name'])): ?>
                                                <div class="small fw-bold"><i class="fas fa-university me-1 text-muted"></i> <?php echo htmlspecialchars($row['bank_name'] ?? ''); ?></div>
                                                <div class="small text-muted">A/C: <span class="text-dark font-monospace"><?php echo htmlspecialchars($row['account_no'] ?? ''); ?></span></div>
                                            <?php else: ?>
                                                <span class="text-muted small">No Bank Details</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <!-- Dynamic Suspend / Unsuspend Button Logic -->
                                                <?php if($row['is_suspended'] == 0): ?>
                                                    <a href="javascript:void(0);" class="btn btn-warning btn-action text-dark btn-suspend" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['username']); ?>">
                                                        <i class="fas fa-user-slash me-1"></i> Suspend
                                                    </a>
                                                    <a href="javascript:void(0);" class="btn btn-success btn-action text-white btn-verify" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['username']); ?>">
                                                        <i class="fas fa-user-check me-1"></i> Verify
                                                    </a>
                                                <?php else: ?>
                                                    <a href="javascript:void(0);" class="btn btn-info btn-action text-dark btn-unsuspend" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['username']); ?>">
                                                        <i class="fas fa-user-plus me-1"></i> Unsuspend
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="javascript:void(0);" class="btn btn-danger btn-action btn-delete" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['username']); ?>">
                                                    <i class="fas fa-trash-can me-1"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-users-slash display-4 mb-3"></i>
                                        <h5>कोई ऑपरेटर डेटाबेस में नहीं मिला!</h5>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    <?php if (!empty($message)): ?>
        Swal.fire({
            icon: '<?php echo $msg_type; ?>',
            title: 'सिस्टम अपडेट',
            html: `<?php echo $message; ?>`,
            background: '#1e293b',
            color: '#ffffff',
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'ठीक है'
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    <?php endif; ?>

    // 1. Suspend Trigger
    document.querySelectorAll('.btn-suspend').forEach(button => {
        button.addEventListener('click', function() {
            const opId = this.getAttribute('data-id');
            const opName = this.getAttribute('data-name');
            Swal.fire({
                title: 'सस्पेंड करना चाहते हैं?',
                text: `ऑपरेटर "${opName}" अस्थायी रूप से ब्लॉक हो जाएगा।`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#eab308',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'हाँ, सस्पेंड करें',
                cancelButtonText: 'रद्द करें'
            }).then((result) => { if (result.isConfirmed) { window.location.href = `manage_operators.php?action=suspend&id=${opId}`; } });
        });
    });

    // 2. Unsuspend Trigger
    document.querySelectorAll('.btn-unsuspend').forEach(button => {
        button.addEventListener('click', function() {
            const opId = this.getAttribute('data-id');
            const opName = this.getAttribute('data-name');
            Swal.fire({
                title: 'अकाउंट वापस बहाल करें?',
                text: `ऑपरेटर "${opName}" पुनः एक्टिव होकर लॉगिन कर पायेगा।`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#06b6d4',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'हाँ, एक्टिव करें',
                cancelButtonText: 'रद्द करें'
            }).then((result) => { if (result.isConfirmed) { window.location.href = `manage_operators.php?action=unsuspend&id=${opId}`; } });
        });
    });

    // 3. Verify Trigger
    document.querySelectorAll('.btn-verify').forEach(button => {
        button.addEventListener('click', function() {
            const opId = this.getAttribute('data-id');
            const opName = this.getAttribute('data-name');
            Swal.fire({
                title: 'ऑपरेटर सत्यापित करें?',
                text: `क्या आपने "${opName}" के सभी दस्तावेज़ जांच लिए हैं?`,
                icon: 'success',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'हाँ, वेरिफाई करें',
                cancelButtonText: 'रद्द करें'
            }).then((result) => { if (result.isConfirmed) { window.location.href = `manage_operators.php?action=verify&id=${opId}`; } });
        });
    });

    // 4. Delete Trigger
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function() {
            const opId = this.getAttribute('data-id');
            const opName = this.getAttribute('data-name');
            Swal.fire({
                title: 'गंभीर चेतावनी!',
                text: `क्या आप वाकई ऑपरेटर "${opName}" को हमेशा के लिए डिलीट करना चाहते हैं?`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'हाँ, डिलीट करें',
                cancelButtonText: 'रद्द करें'
            }).then((result) => { if (result.isConfirmed) { window.location.href = `manage_operators.php?action=delete&id=${opId}`; } });
        });
    });
});
</script>
</body>
</html>