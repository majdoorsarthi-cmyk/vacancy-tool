<?php
session_start();

// Agar admin pehle se logged in hai, toh dashboard par bhejein
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === TRUE) {
    header("location: dashboard.php");
    exit;
}

include '../includes/db_connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Kripya username aur password dono bharein.";
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $hashed_password);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                session_regenerate_id(true);
                
                $_SESSION['admin_logged_in'] = TRUE;
                $_SESSION['admin_id'] = $id;
                $_SESSION['username'] = $username;
                
                header("location: dashboard.php");
                exit;
            } else {
                $error = "Galat username ya password.";
            }
        } else {
            $error = "Galat username ya password.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Sarkari Update Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Orbitron:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * { box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        
        body { 
            background: url('https://images.unsplash.com/photo-1497215728101-856f4ea42174?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80') no-repeat center center fixed;
            background-size: cover;
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0;
            padding: 20px;
            position: relative;
        }

        /* शानदार होम बटन */
        .home-link-top {
            position: absolute;
            top: 20px;
            left: 20px;
            text-decoration: none;
            color: #fff;
            background: rgba(255, 255, 255, 0.15);
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 500;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .home-link-top:hover {
            background: #fff;
            color: #1e3a8a;
            transform: scale(1.05);
        }

        .login-card { 
            background: rgba(255, 255, 255, 0.1); 
            backdrop-filter: blur(20px); 
            -webkit-backdrop-filter: blur(20px);
            padding: 40px; 
            border-radius: 25px; 
            border: 1px solid rgba(255, 255, 255, 0.2); 
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3); 
            width: 100%; 
            max-width: 420px; 
            color: #fff;
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card h2 { 
            margin: 0; 
            font-family: 'Orbitron', sans-serif; /* Futuristic Font */
            font-size: 26px; 
            font-weight: 700; 
            text-align: center; 
            letter-spacing: 2px;
            text-transform: uppercase;
            background: linear-gradient(to right, #fff, #a5b4fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-card p.subtitle {
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .admin-form .input-group {
            position: relative;
            margin-bottom: 25px;
        }

        .admin-form .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
        }

        .admin-form input { 
            width: 100%; 
            padding: 14px 14px 14px 45px; 
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2); 
            border-radius: 15px; 
            color: #fff;
            transition: 0.3s;
        }

        .admin-form input:focus {
            outline: none;
            border-color: #a5b4fc;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 15px rgba(165, 180, 252, 0.3);
        }

        .admin-form button { 
            width: 100%; 
            background: linear-gradient(135deg, #ffffff 0%, #e2e8f0 100%); 
            color: #1e3a8a; 
            border: none; 
            padding: 15px; 
            border-radius: 15px; 
            font-size: 16px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .admin-form button:hover { 
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }

        /* वापस जाने का लिंक स्टाइल */
        .back-to-site {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: 0.3s;
            opacity: 0.8;
        }

        .back-to-site:hover {
            opacity: 1;
            text-shadow: 0 0 10px rgba(255,255,255,0.8);
            letter-spacing: 1px;
        }

        .error-box { 
            background: rgba(255, 71, 87, 0.2); 
            color: #ff9f9f; 
            padding: 12px; 
            border-radius: 12px; 
            margin-bottom: 20px; 
            border: 1px solid rgba(255, 71, 87, 0.3);
            font-size: 13px;
            text-align: center;
        }

        .footer-text {
            text-align: center;
            margin-top: 25px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

    <a href="../index.php" class="home-link-top">
        <i class="fas fa-home"></i> Back to Home
    </a>

    <div class="login-card">
        <h2>Admin Login</h2>
        <p class="subtitle">Secure Command Center</p>
        
        <?php if (!empty($error)): ?>
            <div class="error-box">
                <i class="fas fa-shield-virus"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="admin-form" autocomplete="off">
            <div class="input-group">
                <i class="fas fa-user-shield"></i>
                <input type="text" id="username" name="username" required placeholder="Access ID">
            </div>
            
            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="password" id="password" name="password" required placeholder="Security Key">
            </div>
            
            <button type="submit">Unlock System <i class="fas fa-unlock-alt"></i></button>
        </form>

        <a href="../index.php" class="back-to-site">
            <i class="fas fa-arrow-left"></i> Go Back to Main Website
        </a>

        <div class="footer-text">
            &copy; <?php echo date('Y'); ?> SYSTEM ADMINISTRATION PANEL
        </div>
    </div>
</body>
</html>