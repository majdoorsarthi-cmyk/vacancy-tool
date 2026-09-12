<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Recovery - Operator Portal</title>
    <!-- Google Fonts & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .recovery-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            padding: 35px 30px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 440px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .recovery-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .brand-header i {
            font-size: 42px;
            background: linear-gradient(135deg, #4e54c8, #8f94fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-header h2 {
            color: #2c3e50;
            font-size: 22px;
            font-weight: 700;
            margin-top: 5px;
        }

        /* Dynamic Tabs Style */
        .tab-container {
            display: flex;
            background: #eef2f5;
            border-radius: 10px;
            padding: 4px;
            margin-bottom: 25px;
        }

        .tab-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: transparent;
            font-size: 13px;
            font-weight: 600;
            color: #6c757d;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tab-btn.active {
            background: #ffffff;
            color: #4e54c8;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        /* Form Groups */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i.left-icon {
            position: absolute;
            left: 14px;
            color: #8f94fb;
            font-size: 15px;
        }

        .input-wrapper i.toggle-pwd {
            position: absolute;
            right: 14px;
            color: #adb5bd;
            cursor: pointer;
            font-size: 14px;
        }

        .input-wrapper i.toggle-pwd:hover {
            color: #4e54c8;
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 40px 12px 42px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .input-wrapper input:focus {
            border-color: #4e54c8;
            background: #ffffff;
            box-shadow: 0 0 8px rgba(78, 84, 200, 0.2);
        }

        .submit-btn {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #4e54c8, #8f94fb);
            border: none;
            border-radius: 10px;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.35);
            margin-top: 8px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(78, 84, 200, 0.5);
        }

        .back-to-login {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 22px;
            color: #4e54c8;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: color 0.3s;
        }

        .back-to-login:hover {
            color: #2c3e50;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="recovery-card">
    <div class="brand-header">
        <i class="fa-solid fa-user-shield"></i>
        <h2>Account Recovery</h2>
    </div>

    <!-- Dynamic Tabs -->
    <div class="tab-container">
        <button class="tab-btn active" onclick="switchTab('reset')">Reset Password</button>
        <button class="tab-btn" onclick="switchTab('find')">Find Username</button>
    </div>

    <!-- TAB 1: Password Reset Form -->
    <div id="tab-reset" class="tab-content active">
        <form action="process_forgot_password.php" method="POST">
            <input type="hidden" name="action_type" value="reset_password">

            <div class="form-group">
                <label>Username</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-user left-icon"></i>
                    <input type="text" name="username" required placeholder="अपना Username डालें">
                </div>
            </div>

            <div class="form-group">
                <label>WhatsApp Number</label>
                <div class="input-wrapper">
                    <i class="fa-brands fa-whatsapp left-icon"></i>
                    <input type="text" name="whatsapp_no" required placeholder="रजिस्टर्ड नंबर डालें">
                </div>
            </div>

            <div class="form-group">
                <label>New Password</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock left-icon"></i>
                    <input type="password" id="pwd1" name="new_password" required placeholder="नया पासवर्ड बनाएँ">
                    <i class="fa-solid fa-eye toggle-pwd" onclick="togglePassword('pwd1', this)"></i>
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fa-solid fa-key" style="margin-right: 6px;"></i> पासवर्ड अपडेट करें
            </button>
        </form>
    </div>

    <!-- TAB 2: Find Username Form -->
    <div id="tab-find" class="tab-content">
        <form action="process_forgot_password.php" method="POST">
            <input type="hidden" name="action_type" value="find_username">

            <div class="form-group">
                <label>WhatsApp Number</label>
                <div class="input-wrapper">
                    <i class="fa-brands fa-whatsapp left-icon"></i>
                    <input type="text" name="whatsapp_no" required placeholder="रजिस्टर्ड व्हाट्सएप नंबर">
                </div>
            </div>

            <div class="form-group">
                <label>Identity / Aadhaar No</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-id-card left-icon"></i>
                    <input type="text" name="identity_no" required placeholder="रजिस्टर्ड Identity या Aadhaar No">
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fa-solid fa-magnifying-glass" style="margin-right: 6px;"></i> Username खोजें
            </button>
        </form>
    </div>

    <a href="login.php" class="back-to-login">
        <i class="fa-solid fa-arrow-left"></i> वापस लॉगिन पेज पर जाएँ
    </a>
</div>

<script>
    // Tab Switcher Logic
    function switchTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

        if (tabName === 'reset') {
            document.querySelectorAll('.tab-btn')[0].classList.add('active');
            document.getElementById('tab-reset').classList.add('active');
        } else {
            document.querySelectorAll('.tab-btn')[1].classList.add('active');
            document.getElementById('tab-find').classList.add('active');
        }
    }

    // Password Show / Hide Toggle
    function togglePassword(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
</script>

</body>
</html>