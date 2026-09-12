<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: login.php"); 
    exit(); 
}
include '../includes/db_connect.php';

// MySQL mode relax (Safe Execution)
if ($conn) {
    $conn->query("SET sql_mode=''");
}

$message = "";
$msg_type = "";

// ─── ऑपरेटर की सभी डिटेल्स और रेट अपडेट करने का लॉजिक ───
if (isset($_POST['update_operator_master'])) {
    $op_id = intval($_POST['operator_id']);
    $new_rate = floatval($_POST['commission_rate']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $whatsapp_no = mysqli_real_escape_string($conn, $_POST['whatsapp_no']);
    $pan_no = mysqli_real_escape_string($conn, $_POST['pan_no']);
    $bank_name = mysqli_real_escape_string($conn, $_POST['bank_name']);
    $account_no = mysqli_real_escape_string($conn, $_POST['account_no']);
    $ifsc_code = mysqli_real_escape_string($conn, $_POST['ifsc_code']);

    // मास्टर अपडेट क्वेरी
    $update_sql = "UPDATE operators SET 
                    username = '$username', 
                    email = '$email', 
                    whatsapp_no = '$whatsapp_no', 
                    pan_no = '$pan_no', 
                    bank_name = '$bank_name', 
                    account_no = '$account_no', 
                    ifsc_code = '$ifsc_code', 
                    commission_rate = $new_rate 
                   WHERE id = $op_id";

    if (mysqli_query($conn, $update_sql)) {
        $log_action = "Profile Details and Rate (₹$new_rate) modified by Admin";
        $log_sql = "INSERT INTO operator_activity_logs (operator_id, action, created_at) VALUES ($op_id, '$log_action', NOW())";
        mysqli_query($conn, $log_sql);

        $message = "📝 <strong>सफलतापूर्वक अपडेटेड:</strong> ऑपरेटर <strong>{$username}</strong> की प्रोफाइल डिटेल्स सुरक्षित कर दी गई है।";
        $msg_type = "success";
    } else {
        $message = "<strong>❌ डेटाबेस त्रुटि:</strong> " . mysqli_error($conn);
        $msg_type = "error";
    }
}

$query = mysqli_query($conn, "SELECT id, username, email, whatsapp_no, pan_no, bank_name, account_no, ifsc_code, commission_rate FROM operators ORDER BY id DESC");
$total_rows = ($query) ? mysqli_num_rows($query) : 0;
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Operator Profile & Rates Editor | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: 'Segoe UI', sans-serif; color: #1e293b; margin: 0; padding: 0; }
        
        /* Layout Grid for Perfect Sidebar Alignment */
        .app-container { display: flex; width: 100%; min-height: 100vh; }
        .main-content { flex: 1; min-width: 0; display: flex; flex-direction: column; background: #f8fafc; }
        
        .admin-header { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; padding: 20px 25px; border-bottom: 4px solid #6366f1; }
        .main-card { background: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03); padding: 25px; margin: 25px; }
        
        /* Professional Layout Table Fixes */
        .table-responsive { border: 1px solid #e2e8f0; border-radius: 8px; background: white; }
        .table { table-layout: fixed; width: 100%; margin-bottom: 0; vertical-align: top; border-collapse: collapse; }
        .table thead { background: #f1f5f9; color: #475569; border-bottom: 2px solid #e2e8f0; }
        .table th { font-weight: 600; font-size: 0.8rem; text-transform: uppercase; padding: 12px 10px; letter-spacing: 0.5px; }
        .table td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        
        /* Official & Clean Input Controls */
        .input-group-container { display: flex; flex-direction: column; gap: 8px; }
        .input-wrapper { display: flex; flex-direction: column; }
        
        .master-input { 
            border: 1px solid #cbd5e1; 
            padding: 7px 10px; 
            border-radius: 6px; 
            font-size: 0.85rem; 
            font-weight: 500; 
            background-color: #ffffff; 
            transition: all 0.15s ease-in-out;
            width: 100%;
            color: #334155;
        }
        .master-input:focus { 
            border-color: #6366f1; 
            outline: none; 
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15); 
        }
        .section-label { font-size: 0.72rem; font-weight: 600; color: #64748b; margin-bottom: 3px; display: flex; align-items: center; gap: 4px; }
        .rate-box { max-width: 90px; font-weight: 700; border: 1px solid #6366f1; background-color: #f5f3ff; text-align: center; font-size: 0.9rem; color: #4338ca; border-radius: 6px; padding: 6px; }
        
        /* Modern Buttons */
        .btn-master-save { 
            background: #4f46e5; 
            color: white; border: none; padding: 8px 12px; border-radius: 6px; 
            font-size: 0.85rem; font-weight: 600; transition: all 0.2s; width: 100%;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .btn-master-save:hover { background: #4338ca; }
        .btn-back { background-color: #64748b; color: white; font-weight: 600; border-radius: 6px; padding: 8px 16px; font-size: 0.85rem; }
        .btn-back:hover { background-color: #475569; color: white; }
    </style>
</head>
<body>

    <div class="app-container">
        
        <!-- SIDEBAR -->
        <?php include 'includes/admin_header.php'; ?>

        <!-- MAIN CONTENT AREA -->
        <div class="main-content">
            
            <!-- HEADER -->
            <div class="admin-header">
                <div class="container-fluid d-flex justify-content-between align-items-center-custom p-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="fw-bold mb-1" style="font-size: 1.35rem;"><i class="fas fa-user-edit me-2" style="color:#818cf8;"></i> Master Operator Profile & Commission Manager</h4>
                        <p class="small mb-0" style="color: #94a3b8;">पंजीकरण विवरण संपादित करें एवं लाइव कमीशन दरों को नियंत्रित करें</p>
                    </div>
                    <div>
                        <a href="manage_operators.php" class="btn btn-back shadow-sm"><i class="fas fa-arrow-left me-2"></i> मास्टर लिस्ट पर जाएँ</a>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTAINER -->
            <div class="main-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0 text-dark" style="font-size: 1.1rem;"><i class="fas fa-id-card text-primary me-2"></i> ऑपरेटर पूर्ण पंजीकरण रिकॉर्ड संपादन</h5>
                    <span class="badge px-3 py-2 rounded-pill" style="background-color: #6366f1; font-weight: 500; font-size: 0.8rem;"><?php echo $total_rows; ?> Operators Configured</span>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center">ID</th>
                                <th style="width: 24%;">मूल विवरण (Basic Info)</th>
                                <th style="width: 24%;">सत्यापन विवरण (Verification)</th>
                                <th style="width: 28%;">बैंक खाता विवरण (Bank Details)</th>
                                <th style="width: 12%;" class="text-center">कमीशन रेट</th>
                                <th style="width: 12%;" class="text-center">कार्रवाई</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_rows > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($query)): ?>
                                    <tr>
                                        <!-- वैध तरीका: <form> को <tr> के ठीक बाहर या अंदर रखने के बजाय, हम इसे पहले TD में रखकर बंद किए बिना सबमिट के लिए पूरे ब्लॉक का इस्तेमाल कर सकते हैं, या इसे TD के अंदर ही रैप कर सकते हैं। सुरक्षा के लिए यहाँ साफ स्ट्रक्चर है। -->
                                        <td class="fw-bold text-secondary text-center pt-3">
                                            #<?php echo $row['id']; ?>
                                        </td>
                                        
                                        <!-- BASIC DETAILS -->
                                        <td>
                                            <form id="form-<?php echo $row['id']; ?>" method="POST" action="">
                                            <input type="hidden" name="operator_id" value="<?php echo $row['id']; ?>">
                                            <div class="input-group-container">
                                                <div class="input-wrapper">
                                                    <span class="section-label"><i class="fas fa-user text-muted"></i> यूज़रनेम</span>
                                                    <input type="text" name="username" class="master-input" value="<?php echo htmlspecialchars($row['username'] ?? ''); ?>" required>
                                                </div>
                                                <div class="input-wrapper">
                                                    <span class="section-label"><i class="fas fa-envelope text-muted"></i> ईमेल पता</span>
                                                    <input type="email" name="email" class="master-input" value="<?php echo htmlspecialchars($row['email'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- VERIFICATION -->
                                        <td>
                                            <div class="input-group-container">
                                                <div class="input-wrapper">
                                                    <span class="section-label"><i class="fab fa-whatsapp text-success"></i> व्हाट्सएप</span>
                                                    <input type="text" name="whatsapp_no" class="master-input" value="<?php echo htmlspecialchars($row['whatsapp_no'] ?? ''); ?>">
                                                </div>
                                                <div class="input-wrapper">
                                                    <span class="section-label"><i class="fas fa-id-card-alt text-muted"></i> पैन कार्ड (PAN)</span>
                                                    <input type="text" name="pan_no" class="master-input text-uppercase" value="<?php echo htmlspecialchars($row['pan_no'] ?? ''); ?>">
                                                </div>
                                            </div>
                                        </td>

                                        <!-- BANK DETAILS -->
                                        <td>
                                            <div class="input-group-container">
                                                <div class="input-wrapper">
                                                    <span class="section-label"><i class="fas fa-university text-muted"></i> बैंक का नाम</span>
                                                    <input type="text" name="bank_name" class="master-input" value="<?php echo htmlspecialchars($row['bank_name'] ?? ''); ?>">
                                                </div>
                                                <div class="row g-2">
                                                    <div class="col-7">
                                                        <span class="section-label">खाता संख्या</span>
                                                        <input type="text" name="account_no" class="master-input" value="<?php echo htmlspecialchars($row['account_no'] ?? ''); ?>">
                                                    </div>
                                                    <div class="col-5">
                                                        <span class="section-label">IFSC</span>
                                                        <input type="text" name="ifsc_code" class="master-input text-uppercase" value="<?php echo htmlspecialchars($row['ifsc_code'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- COMMISSION RATE -->
                                        <td>
                                            <div class="d-flex justify-content-center align-items-center pt-3">
                                                <span class="me-1 fw-bold text-secondary">₹</span>
                                                <input type="number" step="0.01" min="0" name="commission_rate" class="rate-box" value="<?php echo number_format((float)($row['commission_rate'] ?? 0.00), 2); ?>" required>
                                            </div>
                                        </td>

                                        <!-- SAVE BUTTON -->
                                        <td class="text-center">
                                            <div class="pt-3">
                                                <button type="submit" name="update_operator_master" class="btn-master-save">
                                                    <i class="fas fa-save me-1"></i> Save
                                                </button>
                                            </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-users-slash display-4 mb-3 text-secondary"></i>
                                        <h5>कोई भी ऑपरेटर प्रोफाइल रिकॉर्ड नहीं मिला!</h5>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
                title: 'मास्टर रिकॉर्ड अपडेट',
                html: `<?php echo $message; ?>`,
                background: '#1e293b',
                color: '#ffffff',
                confirmButtonColor: '#6366f1',
                confirmButtonText: 'डेटा सुरक्षित हो गया'
            }).then(() => {
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        <?php endif; ?>
    });
    </script>
</body>
</html>