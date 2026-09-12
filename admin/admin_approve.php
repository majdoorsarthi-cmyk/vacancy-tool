<?php
session_start();

// ─── PHP में भारतीय टाइमज़ोन सेट करें ───
date_default_timezone_set('Asia/Kolkata');

// ─── एडमिन सुरक्षा जाँच ───
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include '../includes/db_connect.php';

// वर्तमान URI निकालें (Sidebar एक्टिव स्टेटस के लिए)
$current_uri = basename($_SERVER['PHP_SELF']);

// URL वैलिडेटर हेल्पर फ़ंक्शन
function clean_or_default_link($input_url, $vacancy_id) {
    $trimmed = trim($input_url);
    if (empty($trimmed)) {
        return "https://vacancyportal.co.in/post.php?id=" . $vacancy_id;
    }
    if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
        return $trimmed;
    }
    return "https://vacancyportal.co.in/post.php?id=" . $vacancy_id;
}

// ─── 1. वन-सिग्नल नोटिफिकेशन ───
function send_new_update_notification($title, $vacancy_id) { 
    /* आपका वन सिग्नल कोड */ 
}

// ─── 2. टेलीग्राम ब्रॉडकास्ट फ़ंक्शन ───
function send_to_n8n_telegram($title, $vacancy_id, $category, $eligibility, $total_posts, $last_date) {
    $bot_token = "YOUR_TELEGRAM_BOT_TOKEN"; 
    $chat_id = "@YOUR_TELEGRAM_CHANNEL";    

    $post_link = "https://vacancyportal.co.in/post.php?id=" . $vacancy_id;

    $message = "📢 *नई भर्ती अपडेट!* 📢\n\n";
    $message .= "📌 *शीर्षक:* " . $title . "\n";
    $message .= "📁 *श्रेणी:* " . strtoupper($category) . "\n";
    if(!empty($total_posts)) $message .= "👥 *कुल पद:* " . $total_posts . "\n";
    if(!empty($eligibility)) $message .= "🎓 *पात्रता:* " . $eligibility . "\n";
    if(!empty($last_date)) $message .= "⏰ *अंतिम तिथि:* " . $last_date . "\n\n";
    $message .= "🔗 *ऑनलाइन फॉर्म एवं विवरण लिंक:* " . $post_link;

    $url = "https://api.telegram.org/bot" . $bot_token . "/sendMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => false
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    @curl_exec($ch);
    curl_close($ch);
}

// ─── 3. व्हाट्सएप चैनल / ग्रुप ऑटो-शेयर फ़ंक्शन ───
function send_to_whatsapp_channel($title, $vacancy_id, $category, $total_posts, $last_date) {
    $instance_id = "instanceXXXXX"; 
    $token = "YOUR_API_TOKEN";       
    
    $post_link = "https://vacancyportal.co.in/post.php?id=" . $vacancy_id;
    $whatsapp_channel_link = "https://whatsapp.com/channel/0029Vb7Cpuy4SpkPg9U35c2x";

    $message = "🔥 *नई सरकारी भर्ती / योजना अपडेट* 🔥\n\n";
    $message .= "📌 *{$title}*\n";
    $message .= "📁 *श्रेणी:* " . strtoupper($category) . "\n";
    if(!empty($total_posts)) $message .= "👥 *कुल पद:* {$total_posts}\n";
    if(!empty($last_date)) $message .= "⏰ *अंतिम तिथि:* {$last_date}\n\n";
    $message .= "👉 *पूरी जानकारी और ऑनलाइन फॉर्म भरें:* \n" . $post_link . "\n\n";
    $message .= "📲 *ताजा अपडेट्स के लिए व्हाट्सएप चैनल ज्वाइन करें:* \n" . $whatsapp_channel_link;

    $url = "https://api.ultramsg.com/{$instance_id}/messages/chat";
    
    $data = [
        'token' => $token,
        'to' => 'YOUR_WHATSAPP_GROUP_OR_CHANNEL_ID', 
        'body' => $message
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    @curl_exec($ch);
    curl_close($ch);
}

// एक्टिविटी लॉग सेव करने का हेल्पर फ़ंक्शन
function log_admin_activity($conn, $vacancy_id, $operator_id, $action_type, $message) {
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (vacancy_id, operator_id, action_type, action_message) VALUES (?, ?, ?, ?)");
    $log_stmt->bind_param("iiss", $vacancy_id, $operator_id, $action_type, $message);
    $log_stmt->execute();
    $log_stmt->close();
}

$message = "";

// ─── 1. जब एडमिन APPROVE बटन दबाए ───
if (isset($_POST['approve_post'])) {
    $vacancy_id = intval($_POST['vacancy_id']);
    $op_id = intval($_POST['operator_id']);
    $current_datetime = date('Y-m-d H:i:s');

    $v_sql = "SELECT * FROM vacancies WHERE id = ?";
    $v_stmt = $conn->prepare($v_sql);
    $v_stmt->bind_param("i", $vacancy_id);
    $v_stmt->execute();
    $vacancy = $v_stmt->get_result()->fetch_assoc();
    $v_stmt->close();

    if ($vacancy && $vacancy['status'] == 'pending') {
        $conn->begin_transaction();
        try {
            $update_v = $conn->prepare("UPDATE vacancies SET status = 'live' WHERE id = ?");
            $update_v->bind_param("i", $vacancy_id);
            $update_v->execute();
            $update_v->close();

            $pay_amount = 0.50;
            $new_score = 0;

            if ($op_id > 0) {
                $op_q = $conn->prepare("SELECT performance_score, designation, commission_rate FROM operators WHERE id = ?");
                $op_q->bind_param("i", $op_id);
                $op_q->execute();
                $op_data = $op_q->get_result()->fetch_assoc();
                $op_q->close();

                if ($op_data) {
                    $new_score = intval($op_data['performance_score']) + 1;
                    $custom_desig = trim($op_data['designation'] ?? '');
                    $db_comm_rate = floatval($op_data['commission_rate'] ?? 0);

                    if ($db_comm_rate > 0) {
                        $pay_amount = $db_comm_rate;
                    } else {
                        if ($new_score >= 501) {
                            $pay_amount = 2.00;
                            $calculated_desig = 'Diamond Elite';
                        } elseif ($new_score >= 201) {
                            $pay_amount = 1.50;
                            $calculated_desig = 'Gold Master';
                        } elseif ($new_score >= 51) {
                            $pay_amount = 1.00;
                            $calculated_desig = 'Silver Exec';
                        } else {
                            $pay_amount = 0.50;
                            $calculated_desig = 'Bronze Starter';
                        }
                    }

                    $desig_to_set = !empty($custom_desig) ? $custom_desig : ($calculated_desig ?? 'Bronze Starter');

                    // FIX: Types changed from "ddsi" to "didsi" to match 5 bind variables
                    $update_op = $conn->prepare("UPDATE operators SET wallet_balance = wallet_balance + ?, performance_score = ?, commission_rate = ?, designation = ? WHERE id = ?");
                    $update_op->bind_param("didsi", $pay_amount, $new_score, $pay_amount, $desig_to_set, $op_id);
                    $update_op->execute();
                    $update_op->close();

                    $insert_w = $conn->prepare("INSERT INTO operator_wallet (operator_id, vacancy_id, amount, status, created_at) VALUES (?, ?, ?, 'credited', ?)");
                    $insert_w->bind_param("iids", $op_id, $vacancy_id, $pay_amount, $current_datetime);
                    $insert_w->execute();
                    $insert_w->close();
                }
            }

            $log_msg = "एडमिन ने आपकी पोस्ट '{$vacancy['title']}' को Approve किया। ₹" . number_format($pay_amount, 2) . " क्रेडिट हुए एवं Performance Score बढ़कर {$new_score} हुआ।";
            log_admin_activity($conn, $vacancy_id, $op_id, 'Approved', $log_msg);

            $conn->commit();
            $message = "
            <div class='flex items-center p-4 mb-6 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm'>
                <i class='fa-solid fa-circle-check text-lg mr-2.5 text-emerald-600'></i>
                <div><span class='font-bold'>सफलता!</span> पोस्ट लाइव हो गई है। ऑपरेटर को पद/रैंक अनुसार <strong>₹" . number_format($pay_amount, 2) . "</strong> क्रेडिट कर दिए गए हैं।</div>
            </div>";

            send_new_update_notification($vacancy['title'], $vacancy_id);
            send_to_n8n_telegram($vacancy['title'], $vacancy_id, $vacancy['category'], $vacancy['eligibility'], $vacancy['total_posts'], $vacancy['last_date']);
            send_to_whatsapp_channel($vacancy['title'], $vacancy_id, $vacancy['category'], $vacancy['total_posts'], $vacancy['last_date']);

        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='p-4 mb-6 text-sm text-rose-800 rounded-xl bg-rose-50 border border-rose-200'>❌ त्रुटि: " . $e->getMessage() . "</div>";
        }
    }
}

// ─── 2. जब एडमिन REJECT बटन दबाए (Reason के साथ) ───
if (isset($_POST['reject_post'])) {
    $vacancy_id = intval($_POST['vacancy_id']);
    $op_id = intval($_POST['operator_id']);
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
    
    if(empty($rejection_reason)) {
        $rejection_reason = "लिंक या विवरण अमान्य है, कृपया सही लिंक डालकर पुनः सबमिट करें।";
    }

    if($op_id <= 0) {
        $check_op = $conn->query("SELECT operator_id FROM vacancies WHERE id = $vacancy_id")->fetch_assoc();
        $op_id = intval($check_op['operator_id'] ?? 0);
    }
    
    // Status rejected सेट करें
    $update_r = $conn->prepare("UPDATE vacancies SET status = 'rejected' WHERE id = ?");
    $update_r->bind_param("i", $vacancy_id);
    
    if ($update_r->execute()) {
        $log_msg = "आपकी पोस्ट (ID: #$vacancy_id) को एडमिन द्वारा रिजेक्ट किया गया है। कारण: " . $rejection_reason;
        log_admin_activity($conn, $vacancy_id, $op_id, 'Rejected', $log_msg);

        $message = "
        <div class='flex items-center p-4 mb-6 text-sm text-amber-800 rounded-xl bg-amber-50 border border-amber-200 shadow-sm'>
            <i class='fa-solid fa-circle-exclamation text-lg mr-2.5 text-amber-600'></i>
            <div>वैकेंसी को सफलतापूर्वक <span class='font-bold'>Rejected</span> लिस्ट में भेज दिया गया है। (कारण ऑपरेटर लॉग में दर्ज हो गया है)</div>
        </div>";
    }
    $update_r->close();
}

// ─── 3. जब एडमिन DELETE बटन दबाए ───
if (isset($_POST['delete_post'])) {
    $vacancy_id = intval($_POST['vacancy_id']);
    
    $check_v = $conn->query("SELECT operator_id, title FROM vacancies WHERE id = $vacancy_id")->fetch_assoc();
    $op_id = intval($check_v['operator_id'] ?? 0);
    $v_title = $check_v['title'] ?? 'Unknown';

    $delete_v = $conn->prepare("DELETE FROM vacancies WHERE id = ?");
    $delete_v->bind_param("i", $vacancy_id);
    if ($delete_v->execute()) {
        $log_msg = "एडमिन ने आपकी पोस्ट '$v_title' (ID: #$vacancy_id) को डिलीट कर दिया है।";
        log_admin_activity($conn, $vacancy_id, $op_id, 'Deleted', $log_msg);

        $message = "
        <div class='flex items-center p-4 mb-6 text-sm text-rose-800 rounded-xl bg-rose-50 border border-rose-200 shadow-sm'>
            <i class='fa-solid fa-trash-can text-lg mr-2.5 text-rose-600'></i>
            <div>वैकेंसी डेटाबेस से हटा दी गई है।</div>
        </div>";
    }
    $delete_v->close();
}

// ─── 4. जब एडमिन EDIT करके फॉर्म सबमिट करे (UPDATE) ───
if (isset($_POST['update_post'])) {
    $vacancy_id = intval($_POST['vacancy_id']);
    $op_id = intval($_POST['operator_id']);
    $title = $_POST['title'];
    $category = strtolower(trim($_POST['category']));
    $state = $_POST['state'];
    $total_posts = intval($_POST['total_posts']);
    $last_date = $_POST['last_date'];
    $short_desc = $_POST['short_desc'];
    $eligibility = $_POST['eligibility'];
    $important_dates = $_POST['important_dates'];
    
    $apply_link = clean_or_default_link($_POST['apply_link'], $vacancy_id);
    $official_website = clean_or_default_link($_POST['official_website'], $vacancy_id);
    $result_link = clean_or_default_link($_POST['result_link'], $vacancy_id);
    
    $long_details = $_POST['long_details']; 
    $description = $_POST['description']; 
    $full_details = isset($_POST['full_details']) ? $_POST['full_details'] : ''; 

    $edit_sql = "UPDATE vacancies SET title=?, category=?, state=?, total_posts=?, last_date=?, short_desc=?, eligibility=?, important_dates=?, apply_link=?, official_website=?, result_link=?, long_details=?, description=?, full_details=? WHERE id=?";
    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->bind_param("sssissssssssssi", $title, $category, $state, $total_posts, $last_date, $short_desc, $eligibility, $important_dates, $apply_link, $official_website, $result_link, $long_details, $description, $full_details, $vacancy_id);
    
    if($edit_stmt->execute()) {
        $log_msg = "एडमिन ने आपकी पोस्ट '$title' में कुछ सुधार (Edit/Update) किए हैं।";
        log_admin_activity($conn, $vacancy_id, $op_id, 'Edited', $log_msg);

        $message = "
        <div class='flex items-center p-4 mb-6 text-sm text-blue-800 rounded-xl bg-blue-50 border border-blue-200 shadow-sm'>
            <i class='fa-solid fa-pen-to-square text-lg mr-2.5 text-blue-600'></i>
            <div>वैकेंसी डेटा में सफलतापूर्वक सुधार (Edit) कर दिया गया है।</div>
        </div>";
    }
    $edit_stmt->close();
}

// ─── टैब एवं कैटेगरी फ़िल्टर लॉजिक ───
$current_view = isset($_GET['view']) ? $_GET['view'] : 'pending';
if (!in_array($current_view, ['pending', 'live', 'rejected'])) {
    $current_view = 'pending';
}

$selected_category = isset($_GET['cat']) ? strtolower(trim($_GET['cat'])) : 'all';

$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$conditions = ["v.status = ?"];
$params = [$current_view];
$types = "s";

if ($selected_category !== 'all' && $selected_category !== '') {
    $conditions[] = "LOWER(v.category) = ?";
    $params[] = $selected_category;
    $types .= "s";
}

if ($search !== "") {
    $conditions[] = "v.title LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

$where_clause = " WHERE " . implode(" AND ", $conditions);

$count_sql = "SELECT COUNT(*) as total FROM vacancies v" . $where_clause;
$c_stmt = $conn->prepare($count_sql);
$c_stmt->bind_param($types, ...$params);
$c_stmt->execute();
$total_rows = $c_stmt->get_result()->fetch_assoc()['total'];
$c_stmt->close();

$total_pages = ceil($total_rows / $limit);

$count_pending = $conn->query("SELECT COUNT(*) as total FROM vacancies WHERE status='pending'")->fetch_assoc()['total'];
$count_live = $conn->query("SELECT COUNT(*) as total FROM vacancies WHERE status='live'")->fetch_assoc()['total'];
$count_rejected = $conn->query("SELECT COUNT(*) as total FROM vacancies WHERE status='rejected'")->fetch_assoc()['total'];

$wallet_q = $conn->query("SELECT SUM(wallet_balance) as total_bal FROM operators");
$wallet_assoc = $wallet_q ? $wallet_q->fetch_assoc() : [];
$wallet_balance = floatval($wallet_assoc['total_bal'] ?? 0.00);

$query = "SELECT v.*, o.username as operator_name, o.designation, o.commission_rate, o.performance_score 
          FROM vacancies v 
          LEFT JOIN operators o ON v.operator_id = o.id 
          $where_clause 
          ORDER BY v.id DESC LIMIT ? OFFSET ?";

$params_main = $params;
$params_main[] = $limit;
$params_main[] = $offset;
$types_main = $types . "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types_main, ...$params_main);
$stmt->execute();
$result = $stmt->get_result();

$all_categories = [
    'all' => 'सभी (All Categories)',
    'job' => '💼 Latest Job',
    'admit_card' => '🎟️ Admit Card',
    'result' => '🏆 Result',
    'scheme' => '📜 Sarkari Yojana',
    'answer_key' => '🔑 Answer Key',
    'syllabus' => '📚 Syllabus',
    'admission' => '🎓 Admission'
];
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>वैकेंसी मंजूरी व मैनेजमेंट पैनल - Admin Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { background-color: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="flex min-h-screen overflow-hidden">

    <!-- Sidebar Container -->
    <div class="hidden md:flex flex-col w-64 bg-slate-950 text-slate-300 min-h-screen border-r border-slate-800/50 shrink-0 shadow-[4px_0_24px_rgba(0,0,0,0.3)]">
        
        <div class="h-20 flex items-center px-6 border-b border-white/5 bg-gradient-to-b from-slate-900 to-transparent gap-3">
            <div class="h-11 w-11 rounded-2xl bg-white/5 flex items-center justify-center border border-white/10 shadow-lg overflow-hidden">
                <img src="serve_logo.php" 
                     onerror="this.style.display='none'; this.parentElement.querySelector('.fallback-icon').style.display='flex';" 
                     alt="Logo" class="h-8 w-8 object-contain">
                <div class="fallback-icon h-8 w-8 hidden items-center justify-center">
                    <i class="fas fa-briefcase text-indigo-400"></i>
                </div>
            </div>
            <div>
                <h1 class="text-[13px] font-bold text-white tracking-wide">Vacancy Portal</h1>
                <span class="text-[9px] text-indigo-400 font-bold uppercase tracking-[0.2em]">Admin Node</span>
            </div>
        </div>

        <div class="flex-1 flex flex-col justify-between p-4 overflow-y-auto">
            <nav class="space-y-2">
                <p class="px-4 text-[10px] font-black text-slate-600 uppercase tracking-widest mb-3">Main Menu</p>
                
                <a href="dashboard.php" class="relative flex items-center gap-4 px-4 py-3 rounded-2xl text-xs font-bold transition-all duration-500 group <?php echo ($current_uri == 'dashboard.php') ? 'bg-indigo-600 text-white shadow-[0_4px_20px_rgba(79,70,229,0.3)]' : 'hover:bg-slate-900 hover:text-white'; ?>">
                    <i class="fas fa-chart-pie text-sm <?php echo ($current_uri == 'dashboard.php') ? 'text-white' : 'text-indigo-400'; ?>"></i>
                    <span>मुख्य डैशबोर्ड</span>
                </a>

                <a href="<?php echo $current_uri; ?>" class="flex items-center gap-4 px-4 py-3 rounded-2xl text-xs font-bold transition-all duration-500 group bg-indigo-600 text-white shadow-[0_4px_20px_rgba(79,70,229,0.3)]">
                    <i class="fas fa-plus-circle text-sm text-white"></i>
                    <span>नई पोस्ट्स / अप्रूवल</span>
                </a>

                <a href="manage_promotions.php" class="flex items-center gap-4 px-4 py-3 rounded-2xl text-xs font-bold transition-all duration-500 group <?php echo ($current_uri == 'manage_promotions.php') ? 'bg-indigo-600 text-white shadow-[0_4px_20px_rgba(79,70,229,0.3)]' : 'hover:bg-slate-900 hover:text-white'; ?>">
                    <i class="fas fa-trophy text-sm text-amber-400"></i>
                    <span>प्रमोशन व पद प्रबंधन</span>
                </a>

                <a href="wallet.php" class="flex items-center gap-4 px-4 py-3 rounded-2xl text-xs font-bold transition-all duration-500 group <?php echo ($current_uri == 'wallet.php') ? 'bg-indigo-600 text-white shadow-[0_4px_20px_rgba(79,70,229,0.3)]' : 'hover:bg-slate-900 hover:text-white'; ?>">
                    <i class="fas fa-wallet text-sm <?php echo ($current_uri == 'wallet.php') ? 'text-white' : 'text-emerald-400'; ?>"></i>
                    <span class="flex-1">वॉलेट लेजर</span>
                    <?php if(isset($wallet_balance) && $wallet_balance > 0): ?>
                        <span class="bg-white/10 backdrop-blur-md px-2.5 py-1 rounded-lg border border-white/10 font-mono text-[10px]">
                            ₹<?php echo number_format((float)$wallet_balance, 2); ?>
                        </span>
                    <?php endif; ?>
                </a>

                <p class="pt-8 px-4 text-[10px] font-black text-slate-600 uppercase tracking-widest mb-3">Settings</p>

                <a href="profile.php" class="flex items-center gap-4 px-4 py-3 rounded-2xl text-xs font-bold transition-all duration-500 group <?php echo ($current_uri == 'profile.php') ? 'bg-indigo-600 text-white shadow-[0_4px_20px_rgba(79,70,229,0.3)]' : 'hover:bg-slate-900 hover:text-white'; ?>">
                    <i class="fas fa-user-shield text-sm <?php echo ($current_uri == 'profile.php') ? 'text-white' : 'text-purple-400'; ?>"></i>
                    <span>प्रोफ़ाइल सेटिंग</span>
                </a>
            </nav>

            <div class="pt-6 border-t border-white/5">
                <a href="logout.php" class="flex items-center gap-4 px-4 py-3 rounded-2xl text-xs font-bold text-rose-400 hover:bg-rose-500/10 transition-all duration-500">
                    <i class="fas fa-sign-out-alt text-sm"></i>
                    <span>सुरक्षित लॉग आउट</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 p-4 md:p-8 overflow-y-auto">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <div class="flex flex-col md:flex-row md:items-center md:justify-between bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 rounded-2xl shadow-xl border border-slate-800 gap-4">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-xl bg-indigo-500/20 flex items-center justify-center border border-indigo-500/30 text-indigo-400 shadow-inner">
                        <i class="fa-solid fa-shield-halved text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-extrabold tracking-tight text-white">वैकेंसी अप्रूवल एवं ऑल-कैटेगरी कंट्रोल</h1>
                        <p class="text-xs text-slate-400 mt-0.5">ऑपरेटर पोस्ट्स रिव्यू करें — पद व रैंक अनुसार ऑटो-कमीशन जनरेट होगा</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 bg-white/5 px-4 py-2.5 rounded-xl border border-white/10 text-xs font-medium text-slate-300">
                    <i class="fa-solid fa-layer-group text-indigo-400 text-sm"></i>
                    <span>चयनित श्रेणी: <strong class="text-white uppercase"><?php echo htmlspecialchars($selected_category); ?></strong></span>
                </div>
            </div>

            <?php echo $message; ?>

            <!-- फ़िल्टर बार -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex flex-wrap gap-2 bg-slate-100 p-1 rounded-xl border border-slate-200 flex-1">
                        <a href="?view=pending&cat=<?php echo urlencode($selected_category); ?>" class="flex-1 text-center px-4 py-2.5 rounded-lg font-bold text-xs transition-all flex items-center justify-center gap-2 <?php echo $current_view == 'pending' ? 'bg-amber-500 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70' ?>">
                            <i class="fa-solid fa-clock"></i> लंबित (Pending)
                            <span class="px-2 py-0.5 rounded-full bg-black/20 text-[10px]"><?php echo $count_pending; ?></span>
                        </a>
                        <a href="?view=live&cat=<?php echo urlencode($selected_category); ?>" class="flex-1 text-center px-4 py-2.5 rounded-lg font-bold text-xs transition-all flex items-center justify-center gap-2 <?php echo $current_view == 'live' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70' ?>">
                            <i class="fa-solid fa-circle-check"></i> स्वीकृत (Live)
                            <span class="px-2 py-0.5 rounded-full bg-black/20 text-[10px]"><?php echo $count_live; ?></span>
                        </a>
                        <a href="?view=rejected&cat=<?php echo urlencode($selected_category); ?>" class="flex-1 text-center px-4 py-2.5 rounded-lg font-bold text-xs transition-all flex items-center justify-center gap-2 <?php echo $current_view == 'rejected' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70' ?>">
                            <i class="fa-solid fa-circle-xmark"></i> अस्वीकृत (Rejected)
                            <span class="px-2 py-0.5 rounded-full bg-black/20 text-[10px]"><?php echo $count_rejected; ?></span>
                        </a>
                    </div>
                    
                    <form method="GET" class="relative w-full lg:w-80 shrink-0 flex items-center gap-2">
                        <input type="hidden" name="view" value="<?php echo htmlspecialchars($current_view); ?>">
                        <input type="hidden" name="cat" value="<?php echo htmlspecialchars($selected_category); ?>">
                        
                        <div class="relative w-full">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            </span>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="शीर्षक से खोजें..." class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-2xs transition-all">
                        </div>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition">खोजें</button>
                    </form>
                </div>

                <div class="border-t border-slate-100 pt-3">
                    <span class="text-[11px] font-extrabold uppercase text-slate-400 tracking-wider block mb-2">श्रेणी अनुसार फ़िल्टर करें (Category Switcher):</span>
                    <div class="flex flex-wrap gap-1.5 overflow-x-auto pb-1">
                        <?php foreach($all_categories as $cat_key => $cat_label): ?>
                            <a href="?view=<?php echo urlencode($current_view); ?>&cat=<?php echo urlencode($cat_key); ?>&search=<?php echo urlencode($search); ?>" 
                               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border flex items-center gap-1.5 <?php echo $selected_category === $cat_key ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'; ?>">
                                <?php echo $cat_label; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Table Container -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="vacancyTable">
                        <thead>
                            <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                                <th class="p-4">ऑपरेटर विवरण व पद</th>
                                <th class="p-4">वैकेंसी शीर्षक (Title)</th>
                                <th class="p-4">कैटेगरी</th>
                                <th class="p-4">लास्ट डेट</th>
                                <th class="p-4">स्थिति</th>
                                <th class="p-4 text-center">एक्शन</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $clean_row = $row;
                                    $clean_row['long_details'] = $row['long_details'] ?? '';
                                    $clean_row['description'] = $row['description'] ?? '';
                                    $clean_row['full_details'] = $row['full_details'] ?? '';
                                    $json_data = htmlspecialchars(json_encode($clean_row), ENT_QUOTES, 'UTF-8');

                                    $op_desig = !empty($row['designation']) ? $row['designation'] : 'Bronze Starter';
                                    $op_comm = floatval($row['commission_rate'] ?? 0.50);
                                ?>
                                    <tr class="text-xs hover:bg-slate-50/80 transition-colors">
                                        <td class="p-4">
                                            <div class="flex items-center gap-2.5">
                                                <div class="h-8 w-8 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-700 font-bold uppercase text-xs">
                                                    <?php echo substr($row['operator_name'] ?? 'A', 0, 1); ?>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-slate-900 text-sm"><?php echo htmlspecialchars($row['operator_name'] ?? 'अज्ञात'); ?></div>
                                                    <div class="flex items-center gap-1.5 mt-0.5">
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                            👑 <?php echo htmlspecialchars($op_desig); ?>
                                                        </span>
                                                        <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100">
                                                            ₹<?php echo number_format($op_comm, 2); ?>/post
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4 font-semibold text-slate-800 max-w-xs leading-snug">
                                            <div class="line-clamp-2"><?php echo htmlspecialchars($row['title']); ?></div>
                                            <div class="text-[10px] text-slate-400 font-normal mt-1">ID: #<?php echo $row['id']; ?> | राज्य: <?php echo htmlspecialchars($row['state'] ?? 'All'); ?></div>
                                        </td>
                                        <td class="p-4 font-bold text-slate-700 uppercase">
                                            <span class="px-2.5 py-1 bg-slate-100 rounded-lg text-[10px] border border-slate-200">
                                                📁 <?php echo htmlspecialchars($row['category']); ?>
                                            </span>
                                        </td>
                                        <td class="p-4 font-bold text-rose-600">
                                            <?php echo htmlspecialchars($row['last_date'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="p-4">
                                            <?php if($row['status'] == 'pending'): ?>
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">Pending</span>
                                            <?php elseif($row['status'] == 'live'): ?>
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">Live</span>
                                            <?php else: ?>
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">Rejected</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button onclick="openVerifyModal(this)" data-vacancy='<?php echo $json_data; ?>' class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs flex items-center gap-1.5 transition">
                                                    <i class="fa-solid fa-eye"></i> देखें/बदलें
                                                </button>
                                                <form method="POST" onsubmit="return confirm('क्या आप इस पोस्ट को पूरी तरह हटाना चाहते हैं?');" class="inline">
                                                    <input type="hidden" name="vacancy_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="operator_id" value="<?php echo $row['operator_id']; ?>">
                                                    <button type="submit" name="delete_post" formnovalidate class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs border border-rose-200 transition">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="p-12 text-center text-slate-400 font-medium">
                                        <i class="fa-solid fa-folder-open text-3xl text-slate-300 block mb-2"></i>
                                        चुनी गई श्रेणी या स्थिति में कोई पोस्ट उपलब्ध नहीं है।
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <?php if($total_pages > 1): ?>
                    <div class="bg-slate-50 border-t border-slate-200 px-5 py-3 flex items-center justify-between gap-4 select-none">
                        <div class="text-xs text-slate-500 font-semibold">
                            पेज <?php echo $page; ?> का <?php echo $total_pages; ?> (कुल पोस्ट्स: <?php echo $total_rows; ?>)
                        </div>
                        <div class="flex items-center gap-1.5">
                            <?php if($page > 1): ?>
                                <a href="?view=<?php echo urlencode($current_view); ?>&cat=<?php echo urlencode($selected_category); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo ($page - 1); ?>" class="px-3.5 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 transition shadow-2xs">पिछला</a>
                            <?php endif; ?>

                            <?php if($page < $total_pages): ?>
                                <a href="?view=<?php echo urlencode($current_view); ?>&cat=<?php echo urlencode($selected_category); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo ($page + 1); ?>" class="px-3.5 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 transition shadow-2xs">अगला</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- ─── 5. समीक्षा एवं संपादन पॉपअप (Modal Window) ─── -->
    <div id="verifyModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeVerifyModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <form method="POST" id="reviewForm" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-slate-200">
                <input type="hidden" name="vacancy_id" id="form_vacancy_id">
                <input type="hidden" name="operator_id" id="form_operator_id">

                <div class="bg-slate-900 p-4 text-white flex items-center justify-between">
                    <div>
                        <span id="m_operator" class="text-xs font-bold text-amber-400 uppercase tracking-wide"></span>
                        <h3 class="text-base font-bold text-white mt-0.5">समीक्षा और संपादन (Review Mode)</h3>
                    </div>
                    <button type="button" onclick="closeVerifyModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition"><i class="fa-solid fa-xmark text-base"></i></button>
                </div>

                <div class="p-6 max-h-[70vh] overflow-y-auto bg-slate-50 space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">वैकेंसी का मुख्य शीर्षक (Title):</label>
                        <input type="text" name="title" id="edit_title" class="w-full p-2.5 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">कैटेगरी (Category):</label>
                            <select name="category" id="edit_category" class="w-full p-2.5 border border-slate-200 rounded-xl font-bold uppercase focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none">
                                <option value="job">JOB</option>
                                <option value="admit_card">ADMIT CARD</option>
                                <option value="result">RESULT</option>
                                <option value="scheme">SCHEME</option>
                                <option value="answer_key">ANSWER KEY</option>
                                <option value="syllabus">SYLLABUS</option>
                                <option value="admission">ADMISSION</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">राज्य (State):</label>
                            <input type="text" name="state" id="edit_state" class="w-full p-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">कुल पद (Total Posts):</label>
                            <input type="number" name="total_posts" id="edit_posts" class="w-full p-2.5 border border-slate-200 rounded-xl font-bold text-emerald-600 focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">अंतिम तिथि (Last Date):</label>
                            <input type="text" name="last_date" id="edit_lastdate" class="w-full p-2.5 border border-slate-200 rounded-xl font-bold text-rose-600 focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">शॉर्ट डिस्क्रिप्शन (Short Description):</label>
                        <textarea name="short_desc" id="edit_shortdesc" rows="2" class="w-full p-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none"></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">भर्ती की पूरी जानकारी (Long Details):</label>
                        <textarea name="long_details" id="edit_longdetails"></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">आवेदन कैसे करें / निर्देश (Description):</label>
                        <textarea name="description" id="edit_instructions"></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">फुल डिटेल्स टेबल/कंटेंट (Full Details):</label>
                        <textarea name="full_details" id="edit_fulldetails" rows="3" class="w-full p-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">योग्यता / पात्रता (Eligibility):</label>
                            <textarea name="eligibility" id="edit_eligibility" rows="3" class="w-full p-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none"></textarea>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">महत्वपूर्ण तिथियां (Important Dates):</label>
                            <textarea name="important_dates" id="edit_dates" rows="3" class="w-full p-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none"></textarea>
                        </div>
                    </div>

                    <!-- टाइप text रखा है ताकि HTML5 URL का एरर न दे -->
                    <div class="bg-white p-4 rounded-xl border border-slate-200 space-y-3">
                        <h4 class="font-bold text-slate-800 border-b pb-1.5"><i class="fa-solid fa-link text-indigo-500"></i> आधिकारिक लिंक्स (Important Links)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-slate-500 mb-0.5">Apply Online Link:</label>
                                <input type="text" name="apply_link" id="edit_link_apply" class="w-full p-2 border border-slate-200 rounded-lg text-xs text-blue-600 focus:ring-1 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-slate-500 mb-0.5">Official Website:</label>
                                <input type="text" name="official_website" id="edit_link_official" class="w-full p-2 border border-slate-200 rounded-lg text-xs text-blue-600 focus:ring-1 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-slate-500 mb-0.5">Result / Notification Link:</label>
                                <input type="text" name="result_link" id="edit_link_result" class="w-full p-2 border border-slate-200 rounded-lg text-xs text-blue-600 focus:ring-1 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- 🌟 अस्वीकृति का कारण (Rejection Reason Input Box) -->
                    <div class="bg-amber-50 p-4 rounded-xl border border-amber-200">
                        <label class="block font-bold text-amber-900 mb-1">
                            <i class="fa-solid fa-comment-dots text-amber-600 mr-1"></i> अस्वीकृति का कारण (Rejection Reason):
                        </label>
                        <input type="text" name="rejection_reason" id="rejection_reason" placeholder="जैसे: Apply Link में URL की जगह गलत टेक्स्ट लिखा है, कृपया सही Link दर्ज करें।" class="w-full p-2.5 border border-amber-300 rounded-xl text-xs bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <p class="text-[10px] text-amber-700 mt-1">यह कारण ऑपरेटर के Activity Log में दिखाई देगा ताकि वह इसे सुधार सके।</p>
                    </div>
                </div>

                <div class="bg-slate-100 p-4 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200">
                    <div class="flex items-center gap-2">
                        <button type="submit" name="reject_post" formnovalidate onclick="return confirm('क्या आप इस पोस्ट को Rejection Reason के साथ ऑपरेटर को वापस भेजना चाहते हैं?');" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl transition-all shadow-xs">
                            ⚠️ Reject & Send Reason
                        </button>
                        <button type="submit" name="delete_post" formnovalidate onclick="return confirm('सावधान! यह हमेशा के लिए डिलीट हो जाएगी।');" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl transition-all shadow-xs">
                            🗑️ Delete
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="closeVerifyModal()" class="px-4 py-2 bg-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-300 transition">बंद करें</button>
                        <button type="submit" name="update_post" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-xs transition-all">
                            💾 Save Edits
                        </button>
                        <button type="submit" name="approve_post" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-xs transition-all">
                            ✔ Approve & Live
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── JAVASCRIPT ENGINE ─── -->
    <script>
        let editorLong = CKEDITOR.replace('long_details');
        let editorDesc = CKEDITOR.replace('description');

        function openVerifyModal(button) {
            const data = JSON.parse(button.getAttribute('data-vacancy'));
            
            document.getElementById('form_vacancy_id').value = data.id;
            document.getElementById('form_operator_id').value = data.operator_id;
            
            let desigText = data.designation ? data.designation : 'Bronze Starter';
            let commText = data.commission_rate ? parseFloat(data.commission_rate).toFixed(2) : '0.50';
            document.getElementById('m_operator').innerText = "ऑपरेटर: " + (data.operator_name ? data.operator_name : "अज्ञात") + " | पद: " + desigText + " (₹" + commText + "/post)";
            
            document.getElementById('edit_title').value = data.title || '';
            
            let catSelect = document.getElementById('edit_category');
            if (data.category) {
                catSelect.value = data.category.toLowerCase();
            }
            
            document.getElementById('edit_state').value = data.state || '';
            document.getElementById('edit_posts').value = data.total_posts || '';
            document.getElementById('edit_lastdate').value = data.last_date || '';
            document.getElementById('edit_shortdesc').value = data.short_desc || '';
            
            document.getElementById('edit_eligibility').value = data.eligibility || '';
            document.getElementById('edit_dates').value = data.important_dates || '';
            
            document.getElementById('edit_link_apply').value = data.apply_link || '';
            document.getElementById('edit_link_official').value = data.official_website || '';
            document.getElementById('edit_link_result').value = data.result_link || '';
            
            document.getElementById('edit_fulldetails').value = data.full_details || '';
            document.getElementById('rejection_reason').value = ''; // Reset reason
            
            if(editorLong) editorLong.setData(data.long_details || '');
            if(editorDesc) editorDesc.setData(data.description || '');
            
            document.getElementById('verifyModal').classList.remove('hidden');
        }
        
        function closeVerifyModal() {
            document.getElementById('verifyModal').classList.add('hidden');
        }
    </script>
</body>
</html>