<?php
// सेशन की शुरुआत और सुरक्षा जांच
session_start();
if (!isset($_SESSION['operator_logged_in']) || !isset($_SESSION['operator_id'])) {
    header("Location: login.php");
    exit();
}

// डेटाबेस कनेक्शन फ़ाइल
include '../includes/db_connect.php'; 

// लॉग-इन ऑपरेटर की जानकारी प्राप्त करना
$operator_id = intval($_SESSION['operator_id']);

// --- AJAX API ENDPOINT FOR MILLIONS OF POSTS ---
if (isset($_GET['action']) && $_GET['action'] === 'fetch_vacancies') {
    header('Content-Type: application/json');
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $department = isset($_GET['department']) ? trim($_GET['department']) : '';
    
    // सुरक्षित बेस क्वेरी मेकिंग
    $where_clauses = ["operator_id = ?"];
    $params = [$operator_id];
    $types = "i";
    
    if (!empty($search)) {
        $where_clauses[] = "(title LIKE ? OR category LIKE ?)";
        $search_param = "%" . $search . "%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    
    if (!empty($department)) {
        $where_clauses[] = "category = ?";
        $params[] = $department;
        $types .= "s";
    }
    
    $where_sql = implode(" AND ", $where_clauses);
    
    // 1. कुल डेटा काउंट प्राप्त करना
    $count_sql = "SELECT COUNT(*) as total FROM vacancies WHERE $where_sql";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
    mysqli_stmt_execute($count_stmt);
    $count_res = mysqli_stmt_get_result($count_stmt);
    $total_rows = mysqli_fetch_assoc($count_res)['total'];
    mysqli_stmt_close($count_stmt);
    
    // 2. लिमिट के साथ मुख्य डेटा स्ट्रीम निकालना
    $data_sql = "SELECT id, title AS job_title, category AS department, last_date, apply_link, created_at 
                 FROM vacancies 
                 WHERE $where_sql 
                 ORDER BY id DESC LIMIT ? OFFSET ?";
    
    $data_stmt = mysqli_prepare($conn, $data_sql);
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    mysqli_stmt_bind_param($data_stmt, $types, ...$params);
    mysqli_stmt_execute($data_stmt);
    $data_res = mysqli_stmt_get_result($data_stmt);
    
    $vacancies = [];
    while ($row = mysqli_fetch_assoc($data_res)) {
        $row['formatted_created_at'] = date('d M, Y', strtotime($row['created_at']));
        $row['formatted_last_date'] = !empty($row['last_date']) ? date('d-m-Y', strtotime($row['last_date'])) : 'असीमित';
        $vacancies[] = $row;
    }
    mysqli_stmt_close($data_stmt);
    
    echo json_encode([
        'status' => 'success',
        'data' => $vacancies,
        'total_rows' => $total_rows,
        'total_pages' => max(1, ceil($total_rows / $limit)),
        'current_page' => $page
    ]);
    exit();
}

// --- ऑपरेटर प्रोफ़ाइल एवं लाइव वॉलेट बैलेंस (Direct Database Sync) ---
$op_query = "SELECT username, email, whatsapp_no, is_active, wallet_balance FROM operators WHERE id = ?";
$stmt = mysqli_prepare($conn, $op_query);
mysqli_stmt_bind_param($stmt, "i", $operator_id);
mysqli_stmt_execute($stmt);
$op_result = mysqli_stmt_get_result($stmt);
$operator = mysqli_fetch_assoc($op_result);
mysqli_stmt_close($stmt);

$is_active = isset($operator['is_active']) ? intval($operator['is_active']) : 0;

// डेटाबेस से सीधे सिंक किया गया रियल-टाइम बैलेंस
$wallet_balance = floatval($operator['wallet_balance'] ?? 0.00);

// --- 🔄 कुल कमाई की लाइव गणना ---
$total_earned = 0.00;
$earn_query = "SELECT SUM(amount) as total_earn FROM operator_wallet WHERE operator_id = ? AND status = 'credited'";
$earn_stmt = mysqli_prepare($conn, $earn_query);
if ($earn_stmt) {
    mysqli_stmt_bind_param($earn_stmt, "i", $operator_id);
    mysqli_stmt_execute($earn_stmt);
    $earn_res = mysqli_stmt_get_result($earn_stmt);
    if ($earn_row = mysqli_fetch_assoc($earn_res)) {
        $total_earned = floatval($earn_row['total_earn'] ?? 0.00);
    }
    mysqli_stmt_close($earn_stmt);
}

// --- सुरक्षा गतिविधि लॉग (Audit Trail) ---
$current_page = mysqli_real_escape_string($conn, $_SERVER['PHP_SELF']);
$ip_address = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR']);
$action_detail = "ऑपरेटर ने अपना मुख्य डैशबोर्ड सफलतापूर्वक खोला।";

$log_query = "INSERT INTO operator_activity_logs (operator_id, action, page_url, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())";
$log_stmt = mysqli_prepare($conn, $log_query);
if ($log_stmt) {
    mysqli_stmt_bind_param($log_stmt, "isss", $operator_id, $action_detail, $current_page, $ip_address);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
}

// --- एडमिन लाइव लॉग्स (अंतिम 5 अपडेट) ---
$admin_log_query = "SELECT action_type, action_message, created_at FROM activity_logs WHERE operator_id = ? ORDER BY id DESC LIMIT 5";
$admin_log_stmt = mysqli_prepare($conn, $admin_log_query);
mysqli_stmt_bind_param($admin_log_stmt, "i", $operator_id);
mysqli_stmt_execute($admin_log_stmt);
$admin_logs_result = mysqli_stmt_get_result($admin_log_stmt);

$logs_list = [];
if ($admin_logs_result) {
    while ($row = mysqli_fetch_assoc($admin_logs_result)) {
        $logs_list[] = $row;
    }
}
$total_logs = count($logs_list);
mysqli_stmt_close($admin_log_stmt);

// --- ग्लोबल काउंटर्स ---
$count_query = "SELECT COUNT(*) as live_count FROM vacancies WHERE status = 'live'";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_live_global = intval($count_row['live_count'] ?? 0);

$my_count_query = "SELECT COUNT(*) as my_count FROM vacancies WHERE operator_id = ?";
$my_stmt = mysqli_prepare($conn, $my_count_query);
mysqli_stmt_bind_param($my_stmt, "i", $operator_id);
mysqli_stmt_execute($my_stmt);
$my_res = mysqli_stmt_get_result($my_stmt);
$my_count_row = mysqli_fetch_assoc($my_res);
$my_total_posts = intval($my_count_row['my_count'] ?? 0);
mysqli_stmt_close($my_stmt);

// यूनिक विभागों की सूची
$dept_query = "SELECT DISTINCT category FROM vacancies WHERE operator_id = ? AND category IS NOT NULL AND category != ''";
$dept_stmt = mysqli_prepare($conn, $dept_query);
mysqli_stmt_bind_param($dept_stmt, "i", $operator_id);
mysqli_stmt_execute($dept_stmt);
$dept_res = mysqli_stmt_get_result($dept_stmt);
$departments = [];
while($d_row = mysqli_fetch_assoc($dept_res)) {
    $departments[] = $d_row['category'];
}
mysqli_stmt_close($dept_stmt);

$active_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="vp.png">
    <link rel="apple-touch-icon" href="vp.png">
    <title>ऑपरेटर कार्यक्षेत्र डैशबोर्ड (Ultra Scaling)</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f6f9; }
        .sidebar-scrollbar::-webkit-scrollbar { width: 5px; }
        .sidebar-scrollbar::-webkit-scrollbar-track { background: #0f172a; }
        .sidebar-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        .sidebar-scrollbar::-webkit-scrollbar-thumb:hover { background: #475569; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="text-slate-800 antialiased selection:bg-indigo-600 selection:text-white flex flex-col md:flex-row min-h-screen">

    <!-- 📱 मोबाइल नेविगेशन बार (Header) -->
    <header class="md:hidden w-full border-b border-slate-800 bg-slate-900 text-white p-4 shadow-lg flex justify-between items-center sticky top-0 z-50">
        <div class="flex items-center gap-2.5">
            <div class="h-9 w-9 bg-indigo-600 rounded-xl flex items-center justify-center shadow-md shadow-indigo-500/30 ring-2 ring-indigo-400/20">
                <i class="fas fa-user-tie text-white text-base"></i>
            </div>
            <div>
                <span class="font-extrabold text-sm tracking-tight text-white block">ऑपरेटर कार्यक्षेत्र</span>
                <span class="text-[9px] text-emerald-400 font-bold tracking-wide flex items-center gap-1">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span> ऑनलाइन
                </span>
            </div>
        </div>
        <button id="menu-toggle" class="text-white focus:outline-none p-2 bg-slate-800 hover:bg-slate-700 rounded-xl transition border border-slate-700 flex items-center justify-center">
            <i class="fas fa-bars text-lg"></i>
        </button>
    </header>

    <!-- ⚡ ADVANCED ULTRA-DYNAMIC SIDEBAR PANEL -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-72 md:w-64 border-r border-slate-800/80 bg-slate-900 text-slate-200 flex flex-col justify-between transform -translate-x-full md:translate-x-0 md:sticky md:top-0 md:h-screen transition-transform duration-300 ease-in-out shadow-2xl md:shadow-none">
        
        <!-- साइडबार टॉप कंटेनर -->
        <div class="flex flex-col h-full overflow-hidden">
            
            <!-- 1. हेडर सेक्शन -->
            <div class="p-5 border-b border-slate-800/80 bg-slate-950/40 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-gradient-to-tr from-indigo-700 to-indigo-500 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-600/30 ring-2 ring-indigo-400/20">
                        <i class="fas fa-user-tie text-lg text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-sm font-extrabold text-white tracking-tight leading-tight">कार्यक्षेत्र Pro</h1>
                        <span class="text-[10px] text-slate-400 font-medium flex items-center gap-1 mt-0.5">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span> सुरक्षित लाइव सत्र
                        </span>
                    </div>
                </div>
                <!-- मोबाइल क्लोज बटन -->
                <button id="close-sidebar" class="md:hidden text-slate-400 hover:text-white p-1 rounded-lg">
                    <i class="fas fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- 2. ऑपरेटर प्रोफ़ाइल विजेट -->
            <div class="p-4 mx-3 my-3 rounded-2xl bg-gradient-to-r from-slate-950/80 to-slate-900 border border-slate-800 shadow-inner shrink-0">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-full bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 font-black text-sm shrink-0">
                        <?php echo strtoupper(substr($operator['username'] ?? 'U', 0, 1)); ?>
                    </div>
                    <div class="overflow-hidden min-w-0 flex-1">
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-extrabold">लॉग-इन ऑपरेटर</p>
                        <h2 class="text-xs font-bold text-amber-400 tracking-wide truncate uppercase">
                            <?php echo htmlspecialchars($operator['username'] ?? 'यूज़र'); ?>
                        </h2>
                        <p class="text-[11px] text-slate-400 truncate font-mono mt-0.5"><?php echo htmlspecialchars($operator['email'] ?? ''); ?></p>
                    </div>
                </div>
            </div>

            <!-- 3. नेविगेशन लिंक्स (Scrollable Dynamic Area) -->
            <nav class="px-3 py-2 flex-1 overflow-y-auto sidebar-scrollbar space-y-1">
                
                <div class="px-3 pt-2 pb-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">मुख्य नेविगेशन</div>

                <a href="dashboard.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-xs transition duration-200 group <?php echo ($active_page == 'dashboard.php') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'; ?>">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-chart-pie w-4 text-center text-indigo-400 group-hover:scale-110 transition <?php echo ($active_page == 'dashboard.php') ? 'text-white' : ''; ?>"></i> 
                        <span class="truncate">डैशबोर्ड मुख्य</span>
                    </span>
                </a>

                <div class="px-3 pt-3 pb-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">डेटा फ़ीड प्रबंधन</div>

                <a href="add_vacancy.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-xs transition duration-200 group <?php echo ($active_page == 'add_vacancy.php') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'; ?> <?php echo ($is_active === 0) ? 'pointer-events-none opacity-30' : ''; ?>">
                    <span class="flex items-center gap-3 min-w-0">
                        <i class="fas fa-plus-circle w-4 text-center text-emerald-400 shrink-0 group-hover:scale-110 transition"></i> 
                        <span class="truncate">नई वेकेंसी जोड़ें</span>
                    </span>
                    <span class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[9px] font-extrabold px-1.5 py-0.5 rounded uppercase shrink-0">लाइव</span>
                </a>

                <a href="add_result.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-xs transition duration-200 group <?php echo ($active_page == 'add_result.php') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'; ?> <?php echo ($is_active === 0) ? 'pointer-events-none opacity-30' : ''; ?>">
                    <span class="flex items-center gap-3 min-w-0">
                        <i class="fas fa-trophy w-4 text-center text-amber-400 shrink-0 group-hover:scale-110 transition"></i> 
                        <span class="truncate">सभी रिजल्ट जोड़ें</span>
                    </span>
                    <span class="bg-amber-400 text-slate-950 font-black text-[9px] px-1.5 py-0.5 rounded-full uppercase animate-pulse shrink-0">NEW</span>
                </a>

                <a href="add_admit_card.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-xs transition duration-200 group <?php echo ($active_page == 'add_admit_card.php') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'; ?> <?php echo ($is_active === 0) ? 'pointer-events-none opacity-30' : ''; ?>">
                    <span class="flex items-center gap-3 min-w-0">
                        <i class="fas fa-id-card w-4 text-center text-sky-400 shrink-0 group-hover:scale-110 transition"></i> 
                        <span class="truncate">एडमिट कार्ड जोड़ें</span>
                    </span>
                    <span class="bg-sky-400 text-slate-950 font-black text-[9px] px-1.5 py-0.5 rounded-full uppercase shrink-0">NEW</span>
                </a>

                <a href="add_scheme.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-xs transition duration-200 group <?php echo ($active_page == 'add_scheme.php') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'; ?> <?php echo ($is_active === 0) ? 'pointer-events-none opacity-30' : ''; ?>">
                    <span class="flex items-center gap-3 min-w-0">
                        <i class="fas fa-hand-holding-heart w-4 text-center text-emerald-400 shrink-0 group-hover:scale-110 transition"></i> 
                        <span class="truncate">नई योजना जोड़ें</span>
                    </span>
                    <span class="bg-emerald-400 text-slate-950 font-black text-[9px] px-1.5 py-0.5 rounded-full uppercase shrink-0">NEW</span>
                </a>

                <a href="add_education.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-xs transition duration-200 group <?php echo ($active_page == 'add_education.php') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'; ?> <?php echo ($is_active === 0) ? 'pointer-events-none opacity-30' : ''; ?>">
                    <span class="flex items-center gap-3 min-w-0">
                        <i class="fas fa-graduation-cap w-4 text-center text-blue-400 shrink-0 group-hover:scale-110 transition"></i> 
                        <span class="truncate">शिक्षा व प्रवेश जोड़ें</span>
                    </span>
                    <span class="bg-blue-400 text-slate-950 font-black text-[9px] px-1.5 py-0.5 rounded-full uppercase shrink-0">NEW</span>
                </a>

                <a href="pdf.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-xs transition duration-200 group <?php echo ($active_page == 'pdf.php') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'; ?> <?php echo ($is_active === 0) ? 'pointer-events-none opacity-30' : ''; ?>">
                    <span class="flex items-center gap-3 min-w-0">
                        <i class="fas fa-file-pdf w-4 text-center text-rose-500 shrink-0 group-hover:scale-110 transition"></i> 
                        <span class="truncate">Pdf फ़ॉर्म्स जोड़ें</span>
                    </span>
                    <span class="bg-rose-500 text-white font-black text-[9px] px-1.5 py-0.5 rounded-full uppercase shrink-0">NEW</span>
                </a>

                <a href="other_updates.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-xs transition duration-200 group <?php echo ($active_page == 'other_updates.php') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'; ?> <?php echo ($is_active === 0) ? 'pointer-events-none opacity-30' : ''; ?>">
                    <span class="flex items-center gap-3 min-w-0">
                        <i class="fas fa-bell w-4 text-center text-purple-400 shrink-0 group-hover:scale-110 transition"></i> 
                        <span class="truncate">अन्य अपडेट जोड़ें</span>
                    </span>
                    <span class="bg-purple-500 text-white font-black text-[9px] px-1.5 py-0.5 rounded-full uppercase shrink-0">NEW</span>
                </a>

                <div class="px-3 pt-3 pb-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">खाता एवं प्रबंधन</div>

                <a href="rank_rewards.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-xs transition duration-200 group <?php echo ($active_page == 'rank_rewards.php') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'; ?> <?php echo ($is_active === 0) ? 'pointer-events-none opacity-30' : ''; ?>">
                    <span class="flex items-center gap-3 min-w-0">
                        <i class="fas fa-award w-4 text-center text-amber-400 shrink-0 group-hover:scale-110 transition"></i> 
                        <span class="truncate">रैंक व प्रमोशन</span>
                    </span>
                    <span class="bg-amber-400 text-slate-950 font-black text-[9px] px-1.5 py-0.5 rounded-full uppercase shrink-0">RANK</span>
                </a>

                <a href="manage_vacancies.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-xs transition duration-200 group <?php echo ($active_page == 'manage_vacancies.php') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'; ?> <?php echo ($is_active === 0) ? 'pointer-events-none opacity-30' : ''; ?>">
                    <span class="flex items-center gap-3 min-w-0">
                        <i class="fas fa-tasks w-4 text-center text-indigo-400 shrink-0 group-hover:scale-110 transition"></i> 
                        <span class="truncate">वेकेंसी प्रबंधित करें</span>
                    </span>
                </a>

                <a href="wallet.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-xs transition duration-200 group <?php echo ($active_page == 'wallet.php') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'; ?>">
                    <span class="flex items-center gap-3 min-w-0">
                        <i class="fas fa-wallet w-4 text-center text-emerald-300 shrink-0 group-hover:scale-110 transition"></i> 
                        <span class="truncate">वॉलेट और आय</span>
                    </span>
                </a>

                <a href="profile.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-xs transition duration-200 group <?php echo ($active_page == 'profile.php') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'; ?>">
                    <span class="flex items-center gap-3 min-w-0">
                        <i class="fas fa-sliders w-4 text-center text-slate-400 shrink-0 group-hover:scale-110 transition"></i> 
                        <span class="truncate">प्रोफ़ाइल सेटिंग्स</span>
                    </span>
                </a>

                <a href="support.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-xs transition duration-200 group <?php echo ($active_page == 'support.php') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'; ?>">
                    <span class="flex items-center gap-3 min-w-0">
                        <i class="fas fa-circle-question w-4 text-center text-sky-400 shrink-0 group-hover:scale-110 transition"></i> 
                        <span class="truncate">सहायता केंद्र</span>
                    </span>
                </a>
            </nav>
        </div>

        <!-- 4. साइडबार फुटर -->
        <div class="p-4 border-t border-slate-800/80 bg-slate-950/60 shrink-0">
            <a href="logout.php" onclick="return confirm('क्या आप वाकई इस सुरक्षित सत्र को बंद करना चाहते हैं?')" class="w-full bg-rose-600/10 hover:bg-rose-600 text-rose-400 hover:text-white font-bold text-xs py-2.5 px-4 rounded-xl transition-all duration-200 flex items-center justify-center gap-2 border border-rose-500/20 shadow-sm">
                <i class="fas fa-power-off"></i> <span>लॉगआउट करें</span>
            </a>
        </div>
    </aside>

    <!-- मोबाइल ओवरले backdrop -->
    <div id="sidebar-overlay" class="fixed inset-0 z-40 bg-slate-950/80 backdrop-blur-sm hidden md:hidden transition-opacity duration-300"></div>

    <!-- मुख्य सामग्री क्षेत्र -->
    <main class="flex-1 overflow-x-hidden overflow-y-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10">
        
        <!-- खाता सत्यापन स्थिति मैट्रिक्स -->
        <div class="mb-6">
            <?php if($is_active === 1): ?>
                <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/50 p-6 shadow-sm ring-1 ring-slate-100">
                    <div class="absolute top-0 left-0 right-0 h-[3px] bg-gradient-to-r from-emerald-500 via-teal-500 to-indigo-500"></div>
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="flex items-start gap-4.5">
                            <div class="flex h-13 w-13 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-emerald-400 shadow-lg shadow-slate-950/10 border border-slate-800 ring-4 ring-slate-100">
                                <i class="fas fa-fingerprint text-xl animate-pulse"></i>
                            </div>
                            
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-bold text-slate-800 tracking-tight">ऑपरेटर कंसोल प्रमाणीकरण</h3>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[11px] font-extrabold text-emerald-700 uppercase tracking-wider">
                                        <i class="fas fa-check-double text-[9px]"></i> Secure Node
                                    </span>
                                </div>
                                <p class="text-xs font-medium leading-relaxed text-slate-500 max-w-2xl">
                                    सिस्टम ने आपकी डिजिटल पहचान को सफलतापूर्वक सत्यापित कर लिया है। आपके पास <span class="text-slate-800 font-semibold">TC Academy Vacancy Portal</span> को प्रबंधित करने, नई विज्ञप्तियां लाइव करने और डेटाबेस संचालन के पूर्ण विशेषाधिकार हैं।
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0 flex flex-wrap sm:flex-nowrap items-center gap-4 border-t border-slate-100 pt-4 lg:border-t-0 lg:pt-0">
                            <div class="bg-slate-100/60 rounded-xl px-3.5 py-2 border border-slate-200/60 text-left min-w-[110px]">
                                <span class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider">सुरक्षा स्तर</span>
                                <span class="text-xs font-bold text-slate-700 flex items-center gap-1 mt-0.5">
                                    <i class="fas fa-shield-alt text-indigo-500 text-[10px]"></i> ऑपरेटर मोड
                                </span>
                            </div>

                            <div class="bg-slate-100/60 rounded-xl px-3.5 py-2 border border-slate-200/60 text-left min-w-[130px]">
                                <span class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider">सिस्टम स्थिति</span>
                                <span class="text-xs font-bold text-emerald-700 flex items-center gap-2 mt-0.5">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    सक्रिय ऑपरेटर
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-sm">
                    <div class="flex gap-4">
                        <div class="h-12 w-12 shrink-0 bg-amber-500/10 text-amber-600 rounded-xl flex items-center justify-center text-xl">
                            <i class="fas fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-amber-900 text-base">सत्यापन प्रक्रिया लंबित है</h3>
                            <p class="text-sm text-amber-700/90 mt-0.5">मुख्य व्यवस्थापक (Admin) द्वारा आपके खाते की समीक्षा की जा रही है।</p>
                        </div>
                    </div>
                    <span class="shrink-0 inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                        <i class="fas fa-clock text-[10px]"></i> समीक्षा चरण
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <!-- ⚡ ऑपरेटर त्वरित कार्य शॉर्टकट बार -->
        <div class="mb-8 bg-slate-900 text-white rounded-2xl p-4 shadow-md flex flex-col md:flex-row items-center justify-between gap-4 border border-slate-800">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 bg-amber-400/20 border border-amber-400/40 rounded-xl flex items-center justify-center text-amber-400">
                    <i class="fa-solid fa-bolt text-base animate-pulse"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-amber-400">ऑपरेटर त्वरित कार्य शॉर्टकट्स</h4>
                    <p class="text-xs text-slate-300">साइटबार के मुख्य फीचर्स तक सीधे पहुँचें और काम तुरंत शुरू करें:</p>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                <a href="add_vacancy.php" class="flex-1 md:flex-none bg-emerald-600 hover:bg-emerald-500 text-white px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-plus-circle"></i> नई वेकेंसी फ़ीड करें
                </a>
                <a href="add_result.php" class="flex-1 md:flex-none bg-amber-500 hover:bg-amber-400 text-slate-950 px-3.5 py-2 rounded-xl text-xs font-extrabold transition flex items-center justify-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-trophy"></i> परीक्षा परिणाम फ़ीड करें
                </a>
                <a href="manage_vacancies.php" class="flex-1 md:flex-none bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-list-check"></i> मेरी लिस्ट
                </a>
            </div>
        </div>

        <!-- मुख्य सांख्यिकी कार्ड्स -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 to-indigo-900 p-6 text-white shadow-xl flex flex-col justify-between group">
                <div class="absolute -right-6 -top-6 h-28 w-28 rounded-full bg-indigo-500/20 blur-xl"></div>
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <p class="text-xs font-semibold tracking-wider text-indigo-200/80 uppercase">उपलब्ध वॉलेट बैलेंस</p>
                        <!-- लाइव डेटाबेस बैलेंस यहाँ दिखेगा -->
                        <h3 class="text-3xl font-extrabold mt-1 tracking-tight">₹<?php echo number_format($wallet_balance, 2); ?></h3>
                    </div>
                    <div class="h-11 w-11 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-lg shadow-inner">
                        <i class="fas fa-wallet text-indigo-200"></i>
                    </div>
                </div>
                <div class="pt-4 border-t border-white/10 flex items-center justify-between text-xs">
                    <a href="wallet.php" class="bg-white text-indigo-900 font-semibold px-3 py-1.5 rounded-lg hover:bg-indigo-50 transition duration-150 flex items-center gap-1.5 shadow-sm">
                        passbook देखें <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex items-center justify-between group hover:border-emerald-300 transition duration-300">
                <div>
                    <p class="text-xs font-semibold tracking-wider text-slate-400 uppercase">आपके द्वारा जोड़ी गई रिक्तियां</p>
                    <h3 class="text-3xl font-bold mt-1 text-slate-800 tracking-tight"><?php echo $my_total_posts; ?></h3>
                    <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 inline-block"></span> आपके व्यक्तिगत योगदान
                    </p>
                </div>
                <div class="h-12 w-12 bg-emerald-50 border border-emerald-100 rounded-xl flex items-center justify-center text-lg text-emerald-600">
                    <i class="fas fa-folder-plus"></i>
                </div>
            </div>

            <!-- 🔔 नोटिफिकेशन सिगनलर कार्ड -->
            <div onclick="toggleNotifModal()" class="rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-50/50 to-white p-6 shadow-sm flex items-center justify-between group hover:border-indigo-400 transition duration-300 cursor-pointer relative">
                <div>
                    <p class="text-xs font-semibold tracking-wider text-indigo-600 uppercase">एडमिन लाइव अपडेट व निर्देश</p>
                    <h3 class="text-3xl font-bold mt-1 text-slate-800 tracking-tight flex items-center gap-2">
                        <span><?php echo $total_logs; ?></span>
                        <?php if($total_logs > 0): ?>
                            <span class="inline-flex items-center rounded-md bg-indigo-600 px-2.5 py-0.5 text-xs font-bold text-white shadow-xs animate-bounce">नया अपडेट</span>
                        <?php endif; ?>
                    </h3>
                    <p class="text-xs text-indigo-600 mt-1 font-medium hover:underline">नए फीचर्स और निर्देश देखने के लिए क्लिक करें &rarr;</p>
                </div>
                <div class="h-12 w-12 bg-indigo-600 text-white border border-indigo-500 rounded-xl flex items-center justify-center text-lg relative shadow-md shadow-indigo-500/20">
                    <i class="fa-solid fa-bullhorn <?php echo ($total_logs > 0) ? 'animate-pulse' : ''; ?>"></i>
                    <?php if($total_logs > 0): ?>
                        <span class="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-amber-400 text-[10px] font-extrabold text-slate-900 border-2 border-white shadow-sm">
                            <?php echo $total_logs; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 🔔 एडमिन एक्शन एवं न्यू साइटबार गाइडेंस पॉप-अप विंडो -->
        <div id="notif-modal" class="fixed inset-0 z-50 hidden bg-slate-950/75 backdrop-blur-md flex items-center justify-center p-3 sm:p-4 transition-all duration-300">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden transform transition-all flex flex-col max-h-[92vh] border border-indigo-100">
                
                <!-- पॉप-अप हेडर -->
                <div class="p-5 sm:p-6 bg-gradient-to-r from-indigo-950 via-slate-900 to-indigo-900 text-white flex items-center justify-between relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-indigo-500/10 rounded-full blur-xl"></div>
                    
                    <div class="flex items-center gap-3.5 z-10">
                        <div class="h-12 w-12 bg-indigo-600/30 border border-indigo-400/30 rounded-2xl flex items-center justify-center text-amber-400 text-xl shadow-inner shrink-0">
                            <i class="fa-solid fa-bullhorn animate-bounce"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-extrabold bg-amber-400 text-slate-950 px-2 py-0.5 rounded-md uppercase tracking-wider">एडमिन लाइव गाइडेंस बोर्ड</span>
                            <h4 class="text-base font-bold text-white tracking-tight mt-0.5">साइटबार में नए फीचर्स, अपडेट और जरूरी निर्देश</h4>
                        </div>
                    </div>
                    <button onclick="toggleNotifModal()" class="text-slate-400 hover:text-white text-2xl font-bold focus:outline-none z-10 h-8 w-8 rounded-full flex items-center justify-center hover:bg-white/10 transition">&times;</button>
                </div>
                
                <!-- 🚀 न्यू फ़ीचर्स व साइडबार गाइडेंस अलर्ट बॉक्स -->
                <div class="bg-indigo-50/90 border-b border-indigo-100 p-4 sm:p-5">
                    <h5 class="text-xs font-bold text-indigo-950 uppercase tracking-wider flex items-center gap-2 mb-2">
                        <i class="fa-solid fa-compass text-indigo-600"></i> आपके लिए एक्टिव साइडबार लिंक्स और कार्य क्षेत्र:
                    </h5>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                        <div class="bg-white border border-indigo-200/80 p-2.5 rounded-xl flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-plus-circle text-emerald-600"></i>
                                <span class="font-bold text-slate-800">नई वेकेंसी</span>
                            </div>
                            <a href="add_vacancy.php" class="bg-emerald-600 text-white px-2 py-1 rounded-lg text-[10px] font-bold hover:bg-emerald-700 transition">फ़ीड करें &rarr;</a>
                        </div>

                        <div class="bg-white border border-amber-300 p-2.5 rounded-xl flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-trophy text-amber-500"></i>
                                <span class="font-bold text-slate-800">परीक्षा रिजल्ट</span>
                            </div>
                            <a href="add_result.php" class="bg-amber-500 text-slate-950 px-2 py-1 rounded-lg text-[10px] font-extrabold hover:bg-amber-400 transition">फ़ीड करें &rarr;</a>
                        </div>

                        <div class="bg-white border border-indigo-200/80 p-2.5 rounded-xl flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-tasks text-indigo-600"></i>
                                <span class="font-bold text-slate-800">वेकेंसी लिस्ट</span>
                            </div>
                            <a href="manage_vacancies.php" class="bg-indigo-600 text-white px-2 py-1 rounded-lg text-[10px] font-bold hover:bg-indigo-700 transition">सूची देखें &rarr;</a>
                        </div>
                    </div>
                </div>

                <!-- पॉप-अप बॉडी -->
                <div class="p-4 sm:p-6 space-y-3.5 overflow-y-auto flex-1 bg-slate-50/50 custom-scrollbar">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">हाल ही में किए गए एडमिन एक्शन संदेश:</p>

                    <?php if($total_logs > 0): ?>
                        <?php foreach($logs_list as $log): 
                            $badge_text = "अपडेट";
                            $badge_class = "bg-slate-100 text-slate-700 border-slate-200";
                            $border_class = "border-l-4 border-l-slate-500";
                            $icon_class = "fa-info-circle text-slate-500";
                            $action_link = "manage_vacancies.php";
                            $btn_text = "लिस्ट देखें";

                            $type = strtolower(trim($log['action_type']));
                            $msg = strtolower(trim($log['action_message']));

                            if(strpos($type, 'approved') !== false || strpos($type, 'स्वीकृत') !== false) {
                                $badge_text = "✅ पोस्ट स्वीकृत (Approved)";
                                $badge_class = "bg-emerald-50 text-emerald-800 border-emerald-200 font-extrabold";
                                $border_class = "border-l-4 border-l-emerald-500";
                                $icon_class = "fa-circle-check text-emerald-600";
                                $action_link = "manage_vacancies.php";
                                $btn_text = "अपनी स्वीकृत पोस्ट देखें";
                            } elseif(strpos($type, 'rejected') !== false || strpos($type, 'अस्वीकृत') !== false) {
                                $badge_text = "❌ पोस्ट अस्वीकृत (Rejected)";
                                $badge_class = "bg-amber-50 text-amber-800 border-amber-200 font-extrabold";
                                $border_class = "border-l-4 border-l-amber-500";
                                $icon_class = "fa-triangle-exclamation text-amber-600";
                                $action_link = "manage_vacancies.php";
                                $btn_text = "सुधार हेतु लिस्ट खोलें";
                            } elseif(strpos($type, 'result') !== false || strpos($msg, 'result') !== false || strpos($msg, 'परिणाम') !== false) {
                                $badge_text = "🏆 परीक्षा परिणाम फ़ीचर";
                                $badge_class = "bg-amber-100 text-amber-950 border-amber-300 font-extrabold";
                                $border_class = "border-l-4 border-l-amber-500";
                                $icon_class = "fa-trophy text-amber-600";
                                $action_link = "add_result.php";
                                $btn_text = "परीक्षा परिणाम फॉर्म खोलें";
                            } elseif(strpos($type, 'deleted') !== false || strpos($type, 'हटाया') !== false) {
                                $badge_text = "🗑️ पोस्ट हटाई गई (Deleted)";
                                $badge_class = "bg-rose-50 text-rose-800 border-rose-200 font-extrabold";
                                $border_class = "border-l-4 border-l-rose-500";
                                $icon_class = "fa-trash-can text-rose-600";
                                $action_link = "manage_vacancies.php";
                                $btn_text = "अपडेटेड स्थिति देखें";
                            } elseif(strpos($type, 'edited') !== false || strpos($type, 'संशोधित') !== false) {
                                $badge_text = "✏️ पोस्ट में संशोधन (Edited)";
                                $badge_class = "bg-sky-50 text-sky-800 border-sky-200 font-extrabold";
                                $border_class = "border-l-4 border-l-sky-500";
                                $icon_class = "fa-pen-to-square text-sky-600";
                                $action_link = "manage_vacancies.php";
                                $btn_text = "संशोधित पोस्ट देखें";
                            } elseif(strpos($type, 'bonus') !== false || strpos($type, 'credit') !== false) {
                                $badge_text = "🎁 वॉलेट जमा / बोनस क्रेडिट";
                                $badge_class = "bg-purple-50 text-purple-800 border-purple-200 font-extrabold";
                                $border_class = "border-l-4 border-l-purple-500";
                                $icon_class = "fa-gift text-purple-600";
                                $action_link = "wallet.php";
                                $btn_text = "अपना वॉलेट बैलेंस देखें";
                            } else {
                                $badge_text = "📢 " . htmlspecialchars($log['action_type']);
                            }
                        ?>
                            <div class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs <?php echo $border_class; ?> hover:shadow-md transition-all space-y-2.5">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] rounded-lg border <?php echo $badge_class; ?>">
                                        <i class="fa-solid <?php echo $icon_class; ?> text-[10px]"></i> <?php echo $badge_text; ?>
                                    </span>
                                    <span class="text-[11px] text-slate-400 font-semibold flex items-center gap-1 bg-slate-100 px-2 py-0.5 rounded-md">
                                        <i class="fa-regular fa-clock text-[10px]"></i> <?php echo date('d M Y, h:i A', strtotime($log['created_at'])); ?>
                                    </span>
                                </div>
                                <p class="text-xs sm:text-sm text-slate-800 font-medium leading-relaxed pl-1">
                                    <?php echo htmlspecialchars($log['action_message']); ?>
                                </p>
                                
                                <div class="pt-2 border-t border-slate-100 flex justify-end">
                                    <a href="<?php echo $action_link; ?>" class="inline-flex items-center gap-1.5 bg-slate-900 hover:bg-indigo-600 text-white text-xs px-3.5 py-1.5 rounded-xl font-bold transition shadow-xs">
                                        <?php echo $btn_text; ?> <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-12 text-slate-400">
                            <i class="fa-solid fa-bell-slash text-4xl block mb-3 text-slate-300"></i>
                            <p class="text-sm font-semibold text-slate-600">कोई नया एडमिन निर्देश दर्ज नहीं है।</p>
                            <p class="text-xs text-slate-400 mt-1">आप साइडबार के सभी विकल्पों का उपयोग करके डेटा भरना जारी रख सकते हैं।</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- पॉप-अप फुटर -->
                <div class="p-4 bg-slate-100 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-[11px] text-slate-500 font-medium">TC Academy Vacancy Portal &bull; ऑपरेटर कार्यक्षेत्र निर्देश</p>
                    <button onclick="toggleNotifModal()" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-6 py-2.5 rounded-xl shadow-md transition duration-150 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-check"></i> ठीक है, मैं समझ गया
                    </button>
                </div>

            </div>
        </div>

        <!-- 🔥 SEARCH & FILTER LIVE STREAM CONTAINER -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/60 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-list-check text-amber-500"></i> आपकी वेकेंसी मैनजमेंट (फीडिंग)
                    </h4>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">करोड़ों डेटा होने पर भी सुपर-फास्ट रिस्पॉन्सिव फ़िल्टरिंग</p>
                </div>
                
                <!-- रियल-टाइम फ़िल्टर्स कंट्रोल बार -->
                <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                    <div class="relative flex-1 sm:flex-initial">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </span>
                        <input type="text" id="table-search" placeholder="शीर्षक या विभाग खोजें..." class="w-full sm:w-64 pl-9 pr-4 py-2 border border-slate-200 bg-white rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                    </div>
                    
                    <select id="table-dept-filter" class="bg-white border border-slate-200 text-xs font-medium rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition cursor-pointer">
                        <option value="">सभी विभाग (Categories)</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- डेटा रेंडरिंग टेबल -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-bold text-xs uppercase border-b border-slate-200/80">
                            <th class="py-4 px-6">वेकेंसी विवरण (शीर्षक)</th>
                            <th class="py-4 px-6">श्रेणी (विभाग)</th>
                            <th class="py-4 px-6">अंतिम तिथि</th>
                            <th class="py-4 px-6 text-center">कार्रवाई लिंक</th>
                        </tr>
                    </thead>
                    <tbody id="vacancy-data-rows" class="divide-y divide-slate-100 text-sm font-medium text-slate-700">
                        <!-- AJAX रिस्पॉन्स डेटा यहाँ रेंडर होगा -->
                    </tbody>
                </table>
            </div>
            
            <!-- पैजिनेशन फुटर -->
            <div class="p-4 bg-slate-50/50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div id="table-info-counter" class="text-xs text-slate-400 font-medium">
                    डेटा लोड किया जा रहा है...
                </div>
                <div id="table-pagination-controls" class="inline-flex items-center -space-x-px rounded-md shadow-2xs bg-white border border-slate-200 overflow-hidden">
                    <!-- पैजिनेशन बटन -->
                </div>
            </div>
        </div>

    </main>

<!-- 🚀 डैशबोर्ड ऑटो-पॉपअप व AJAX स्ट्रीमिंग स्क्रिप्ट -->
<script>
    // --- 1. एडमिन लॉग्स पॉप-अप विंडो कंट्रोल ---
    function toggleNotifModal() {
        const modal = document.getElementById('notif-modal');
        if (modal.classList.contains('hidden')) {
            modal.classList.remove('hidden');
        } else {
            modal.classList.add('hidden');
        }
    }

    // पॉप-अप के बाहर क्लिक करने पर बंद होना
    window.onclick = function(event) {
        const modal = document.getElementById('notif-modal');
        if (event.target == modal) {
            modal.classList.add('hidden');
        }
    }

    // --- 2. मोबाइल मेनू टॉगल लॉजिक (Smooth Off-Canvas) ---
    const menuToggle = document.getElementById('menu-toggle');
    const closeSidebar = document.getElementById('close-sidebar');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    function openMenu() {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden', 'md:overflow-auto');
    }

    function closeMenu() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden', 'md:overflow-auto');
    }

    if(menuToggle) menuToggle.addEventListener('click', openMenu);
    if(closeSidebar) closeSidebar.addEventListener('click', closeMenu);
    if(overlay) overlay.addEventListener('click', closeMenu);

    // स्क्रीन रिसाइज़ पर मोबाइल ओवरले कंट्रोल
    window.addEventListener('resize', () => {
        if(window.innerWidth >= 768) {
            closeMenu();
        }
    });

    // --- 3. HIGH PERFORMANCE AJAX STREAMING LOGIC ---
    let currentLivePage = 1;
    const itemsPerRequest = 5;
    
    const searchInput = document.getElementById('table-search');
    const deptFilter = document.getElementById('table-dept-filter');
    const dataRowsContainer = document.getElementById('vacancy-data-rows');
    const infoCounter = document.getElementById('table-info-counter');
    const paginationControls = document.getElementById('table-pagination-controls');
    
    function escapeHTML(str) {
        if (!str) return '';
        return str.replace(/[&<>'"]/g, 
            tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
        );
    }

    // स्केलेटन लोडर
    function renderSkeleton() {
        let html = '';
        for(let i=0; i < itemsPerRequest; i++) {
            html += `
            <tr class="animate-pulse">
                <td class="py-4 px-6">
                    <div class="h-4 bg-slate-200 rounded w-3/4 mb-2"></div>
                    <div class="h-3 bg-slate-200 rounded w-1/2"></div>
                </td>
                <td class="py-4 px-6"><div class="h-6 bg-slate-200 rounded-md w-16"></div></td>
                <td class="py-4 px-6"><div class="h-4 bg-slate-200 rounded w-20"></div></td>
                <td class="py-4 px-6 text-center"><div class="h-8 bg-slate-200 rounded-lg w-24 mx-auto"></div></td>
            </tr>`;
        }
        if(dataRowsContainer) dataRowsContainer.innerHTML = html;
    }

    // सर्वर से डेटा फेच करने का मुख्य फंक्शन
    async function fetchLiveStreamData() {
        if(!dataRowsContainer) return;
        renderSkeleton();
        
        const searchVal = encodeURIComponent(searchInput ? searchInput.value : '');
        const deptVal = encodeURIComponent(deptFilter ? deptFilter.value : '');
        
        try {
            const response = await fetch(`dashboard.php?action=fetch_vacancies&page=${currentLivePage}&limit=${itemsPerRequest}&search=${searchVal}&department=${deptVal}`);
            const result = await response.json();
            
            if(result.status === 'success') {
                renderTableRows(result.data);
                renderPagination(result.total_pages, result.current_page, result.total_rows);
            }
        } catch (error) {
            console.error("Data fetch error: ", error);
            dataRowsContainer.innerHTML = `<tr><td colspan="4" class="py-8 text-center text-rose-500 font-semibold">डेटा स्ट्रीम त्रुटि! कृपया पुनः प्रयास करें।</td></tr>`;
        }
    }

    // डेटा टेबल रेंडरिंग
    function renderTableRows(items) {
        if(!dataRowsContainer) return;
        if(items.length === 0) {
            dataRowsContainer.innerHTML = `
            <tr>
                <td colspan="4" class="py-12 px-6 text-center text-slate-400">
                    <div class="text-3xl mb-3 text-slate-300"><i class="fas fa-folder-open"></i></div>
                    <p class="text-sm font-medium">कोई डेटा रिकॉर्ड नहीं मिला।</p>
                </td>
            </tr>`;
            return;
        }
        
        let html = '';
        items.forEach(row => {
            html += `
            <tr class="hover:bg-slate-50/70 transition duration-150">
                <td class="py-4 px-6">
                    <span class="block text-slate-900 font-semibold truncate max-w-xs">${escapeHTML(row.job_title)}</span>
                    <span class="text-[11px] text-slate-400 font-medium block mt-0.5">
                        <i class="far fa-clock mr-1"></i>जोड़ने की तिथि: ${row.formatted_created_at}
                    </span>
                </td>
                <td class="py-4 px-6">
                    <span class="inline-flex items-center rounded-md bg-indigo-50 border border-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                        ${escapeHTML(row.department || 'सामान्य')}
                    </span>
                </td>
                <td class="py-4 px-6 text-rose-600 font-semibold">
                    <i class="far fa-calendar-xmark mr-1 text-xs"></i> ${row.formatted_last_date}
                </td>
                <td class="py-4 px-6 text-center">
                    <a href="view_vacancy.php?id=${parseInt(row.id)}" class="inline-flex items-center gap-1.5 bg-slate-900 hover:bg-indigo-600 text-white text-xs px-3 py-1.5 rounded-lg font-semibold transition duration-150 shadow-sm">
                        विवरण देखें <i class="fas fa-eye text-[10px]"></i>
                    </a>
                </td>
            </tr>`;
        });
        dataRowsContainer.innerHTML = html;
    }

    // पैजिनेशन रेंडरिंग
    function renderPagination(totalPages, currentPage, totalRows) {
        currentLivePage = currentPage;
        if(!infoCounter || !paginationControls) return;
        
        const startItem = totalRows === 0 ? 0 : (currentPage - 1) * itemsPerRequest + 1;
        const textEnd = currentPage * itemsPerRequest;
        const endItem = textEnd > totalRows ? totalRows : textEnd;
        infoCounter.textContent = `दिखाए जा रहे हैं ${startItem} से ${endItem} कुल ${totalRows} रिकॉर्ड्स में से`;

        let paginationHtml = '';
        
        paginationHtml += `
        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="px-3 py-2 border-r border-slate-200 text-xs font-semibold bg-white text-slate-500 hover:bg-slate-50 disabled:opacity-50 disabled:pointer-events-none transition">
            <i class="fas fa-chevron-left"></i>
        </button>`;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                const isActive = i === currentPage;
                paginationHtml += `
                <button onclick="changePage(${i})" class="px-3 py-2 border-r border-slate-200 text-xs font-bold ${isActive ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-white text-slate-700 hover:bg-slate-50'} transition">
                    ${i}
                </button>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                paginationHtml += `<span class="px-3 py-2 border-r border-slate-200 text-xs font-medium bg-white text-slate-400">...</span>`;
            }
        }

        paginationHtml += `
        <button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} class="px-3 py-2 text-xs font-semibold bg-white text-slate-500 hover:bg-slate-50 disabled:opacity-50 disabled:pointer-events-none transition">
            <i class="fas fa-chevron-right"></i>
        </button>`;

        paginationControls.innerHTML = paginationHtml;
    }

    function changePage(page) {
        currentLivePage = page;
        fetchLiveStreamData();
    }

    let searchTimeout;
    if(searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentLivePage = 1;
                fetchLiveStreamData();
            }, 300); 
        });
    }

    if(deptFilter) {
        deptFilter.addEventListener('change', () => {
            currentLivePage = 1;
            fetchLiveStreamData();
        });
    }

    // ऑटो-पॉपअप व लाइव फेच
    document.addEventListener('DOMContentLoaded', () => {
        fetchLiveStreamData();
        
        <?php if($total_logs > 0): ?>
            setTimeout(() => {
                toggleNotifModal();
            }, 300);
        <?php endif; ?>
    });
</script>
</body>
</html>