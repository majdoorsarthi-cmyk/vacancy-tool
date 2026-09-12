<?php
// Session start aur Operator login check
session_start();
if (!isset($_SESSION['operator_logged_in']) || !isset($_SESSION['operator_id'])) {
    header("Location: login.php");
    exit();
}

// DB connection include
include '../includes/db_connect.php'; 

$operator_id = intval($_SESSION['operator_id']);
$success_msg = "";
$error_msg = "";

// --- PROFILE UPDATE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $whatsapp_no = mysqli_real_escape_string($conn, $_POST['whatsapp_no']);
    
    // Basic verification ki fields khali na hon
    if (!empty($email) && !empty($whatsapp_no)) {
        $update_query = "UPDATE operators SET email = '$email', whatsapp_no = '$whatsapp_no' WHERE id = $operator_id";
        if (mysqli_query($conn, $update_query)) {
            $success_msg = "Profile details successfully update ho gayi hain!";
        } else {
            $error_msg = "Profile update karne me dikkat aayi. Kripya dobara prayas karein.";
        }
    } else {
        $error_msg = "Sabhi fields ko bharna anivarya hai.";
    }
}

// --- PASSWORD CHANGE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (!empty($old_password) && !empty($new_password) && !empty($confirm_password)) {
        // Operator ka purana password fetch karein
        $pass_check_query = "SELECT password FROM operators WHERE id = $operator_id";
        $pass_res = mysqli_query($conn, $pass_check_query);
        $pass_row = mysqli_fetch_assoc($pass_res);
        
        // Agar aap password_hash use kar rahe hain (Recommended)
        if (password_verify($old_password, $pass_row['password'])) {
            if ($new_password === $confirm_password) {
                $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $pass_update_query = "UPDATE operators SET password = '$new_hashed_password' WHERE id = $operator_id";
                
                if (mysqli_query($conn, $pass_update_query)) {
                    $success_msg = "Password safaltapoorvak badal diya gaya hai!";
                } else {
                    $error_msg = "Password update karne me error aaya.";
                }
            } else {
                $error_msg = "Naya password aur Confirm Password match nahi kar rahe hain.";
            }
        } else {
            // NOTE: Agar aap simple/plain text password use kar rahe hain, toh is line ko uncomment karein:
            // if ($old_password === $pass_row['password']) { ... }
            $error_msg = "Purana password galat hai.";
        }
    } else {
        $error_msg = "Password badalane ke liye sabhi fields bharna zaroori hai.";
    }
}

// --- CURRENT OPERATOR DETAILS FETCH ---
$op_query = "SELECT username, email, whatsapp_no, is_active, created_at FROM operators WHERE id = $operator_id";
$op_result = mysqli_query($conn, $op_query);
$operator = mysqli_fetch_assoc($op_result);

// Personal contributions count karne ke liye
$my_count_query = "SELECT COUNT(*) as my_count FROM vacancies WHERE operator_id = $operator_id";
$my_count_result = mysqli_query($conn, $my_count_query);
$my_count_row = mysqli_fetch_assoc($my_count_result);
$my_total_posts = $my_count_row['my_count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="icon" type="image/png" href="vp.png">
    <link rel="apple-touch-icon" href="vp.png">
    <title>Operator Profile | Vacancy Portal</title>
    <!-- Bootstrap 5.3.0 & FontAwesome Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: #334155;
        }
        .op-header {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            color: white;
            padding: 25px 0;
            border-bottom: 4px solid #6366f1;
        }
        .profile-card {
            background: #ffffff;
            border-radius: 16px;
            border: none;
            box-shadow: 0 6px 24px rgba(0,0,0,0.04);
            padding: 30px;
        }
        .avatar-box {
            width: 90px;
            height: 90px;
            background: #4f46e5;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 15px;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2);
        }
        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25);
        }
        .btn-custom {
            border-radius: 10px;
            font-weight: 600;
            padding: 10px 20px;
        }
    </style>
</head>
<body>

<!-- Top Navigation Header -->
<div class="op-header shadow-sm">
    <div class="container px-4 d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-user-gear text-indigo-400 me-2"></i> Account Settings</h4>
            <p class="small mb-0 opacity-75">Manage your profile information and credentials</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="dashboard.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
            <a href="logout.php" class="btn btn-sm btn-danger rounded-pill px-3" onclick="return confirm('क्या आप लॉगआउट करना चाहते हैं?')"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
        </div>
    </div>
</div>

<div class="container px-4 py-5">
    
    <!-- Status Messages -->
    <?php if(!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="fas fa-circle-check me-2"></i> <?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if(!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="fas fa-circle-exclamation me-2"></i> <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Sidebar Detail Card -->
        <div class="col-12 col-lg-4">
            <div class="profile-card text-center">
                <div class="avatar-box">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($operator['username'] ?? 'Operator'); ?></h4>
                <p class="text-muted small mb-3">Operator ID: #<?php echo $operator_id; ?></p>
                
                <div class="mb-3">
                    <?php if(($operator['is_active'] ?? 0) == 1): ?>
                        <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fas fa-circle-check me-1"></i> Active Status</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fas fa-clock me-1"></i> Verification Pending</span>
                    <?php endif; ?>
                </div>
                
                <hr class="my-4 text-muted opacity-25">
                
                <div class="text-start">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Total Contributions:</span>
                        <span class="fw-bold text-indigo-600"><?php echo $my_total_posts; ?> Jobs</span>
                    </div>
                    <div class="d-flex justify-content-between mb-0">
                        <span class="text-muted small">Member Since:</span>
                        <span class="fw-semibold text-dark"><?php echo !empty($operator['created_at']) ? date('d-M-Y', strtotime($operator['created_at'])) : 'N/A'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Form Settings -->
        <div class="col-12 col-lg-8">
            <div class="d-flex flex-column gap-4">
                
                <!-- Account Details Form -->
                <div class="profile-card">
                    <h5 class="fw-bold mb-4 text-dark"><i class="fas fa-id-card text-primary me-2"></i> Profile Details Edit</h5>
                    <form action="" method="POST">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-secondary">Username (Non-editable)</label>
                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($operator['username'] ?? ''); ?>" readonly>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-secondary">WhatsApp Number</label>
                                <input type="text" name="whatsapp_no" class="form-control" value="<?php echo htmlspecialchars($operator['whatsapp_no'] ?? ''); ?>" placeholder="91XXXXXXXX" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-secondary">Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($operator['email'] ?? ''); ?>" placeholder="example@domain.com" required>
                            </div>
                            <div class="col-12 text-end mt-4">
                                <button type="submit" name="update_profile" class="btn btn-primary btn-custom px-4">
                                    <i class="fas fa-save me-2"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Password Settings Form -->
                <div class="profile-card">
                    <h5 class="fw-bold mb-4 text-danger"><i class="fas fa-shield-halved text-danger me-2"></i> Security & Password Update</h5>
                    <form action="" method="POST">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-secondary">Current Password</label>
                                <input type="password" name="old_password" class="form-control" placeholder="••••••••" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-secondary">New Password</label>
                                <input type="password" name="new_password" class="form-control" placeholder="••••••••" minlength="6" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-secondary">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" minlength="6" required>
                            </div>
                            <div class="col-12 text-end mt-4">
                                <button type="submit" name="change_password" class="btn btn-danger btn-custom px-4">
                                    <i class="fas fa-key me-2"></i> Update Password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>