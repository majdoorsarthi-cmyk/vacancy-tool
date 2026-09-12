<?php
session_start();

// ─── 1. ऑपरेटर सुरक्षा जाँच ───
if (!isset($_SESSION['operator_logged_in']) || $_SESSION['operator_logged_in'] !== true) {
    if (isset($_GET['ajax_sync'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    header("Location: login.php");
    exit;
}

include '../includes/db_connect.php';

$operator_id = $_SESSION['operator_id'];

// ─── 🏆 2. डिफॉल्ट रैंक और रेट कॉन्फ़िगरेशन ───
$ranks = [
    1 => ['name' => 'Bronze Starter', 'min_score' => 0,   'max_score' => 50,  'rate' => 0.50, 'badge' => '🥉', 'bg' => 'from-amber-900 via-amber-950 to-slate-950', 'border' => 'border-amber-700/60', 'tag' => 'शुरुआती दौर', 'daily_target' => 20],
    2 => ['name' => 'Silver Executive', 'min_score' => 51,  'max_score' => 200, 'rate' => 1.00, 'badge' => '🥈', 'bg' => 'from-slate-700 via-slate-900 to-slate-950', 'border' => 'border-slate-500/60', 'tag' => '2X कमाई की शुरुआत', 'daily_target' => 35],
    3 => ['name' => 'Gold Master Pro',   'min_score' => 201, 'max_score' => 500, 'rate' => 1.50, 'badge' => '🥇', 'bg' => 'from-yellow-700 via-amber-900 to-slate-950', 'border' => 'border-yellow-500/60', 'tag' => '3X प्रॉफिट ज़ोन', 'daily_target' => 50],
    4 => ['name' => 'Diamond Elite',    'min_score' => 501, 'max_score' => 9999,'rate' => 2.00, 'badge' => '💎', 'bg' => 'from-cyan-700 via-blue-950 to-slate-950',    'border' => 'border-cyan-400/60', 'tag' => '4X रॉयल पैसिव इनकम', 'daily_target' => 100]
];

// ─── 3. इनलाइन AJAX सिंक एंडपॉइंट (Admin Sync Fix Enabled) ───
if (isset($_GET['ajax_sync']) && $_GET['ajax_sync'] == '1') {
    header('Content-Type: application/json');
    
    // DB से designation और commission_rate भी फ़ेच करें
    $op_stmt = $conn->prepare("SELECT username, wallet_balance, performance_score, designation, commission_rate FROM operators WHERE id = ?");
    $op_stmt->bind_param("i", $operator_id);
    $op_stmt->execute();
    $op_data = $op_stmt->get_result()->fetch_assoc();
    $op_stmt->close();

    $score = intval($op_data['performance_score'] ?? 0);
    $wallet = floatval($op_data['wallet_balance'] ?? 0.00);
    $db_designation = trim($op_data['designation'] ?? '');
    $db_comm_rate = isset($op_data['commission_rate']) ? floatval($op_data['commission_rate']) : null;

    $current_rank = $ranks[1];
    $current_level = 1;
    foreach ($ranks as $level => $r) {
        if ($score >= $r['min_score']) {
            $current_rank = $r;
            $current_level = $level;
        }
    }

    // एडमिन ओवरराइड लागू करें (यदि Admin ने DB में पद या रेट बदला हो)
    if (!empty($db_designation)) {
        $current_rank['name'] = $db_designation;
    }
    if ($db_comm_rate !== null && $db_comm_rate > 0) {
        $current_rank['rate'] = $db_comm_rate;
    }

    $next_level = $current_level < 4 ? $current_level + 1 : 4;
    $next_rank = $ranks[$next_level];

    if ($current_level < 4) {
        $needed_points = $next_rank['min_score'] - $score;
        $total_range = $next_rank['min_score'] - $current_rank['min_score'];
        $current_progress_points = $score - $current_rank['min_score'];
        $progress_percentage = ($total_range > 0) ? min(100, max(0, round(($current_progress_points / $total_range) * 100))) : 0;
    } else {
        $needed_points = 0;
        $progress_percentage = 100;
    }

    $p_stmt = $conn->prepare("SELECT COUNT(*) as total_approved FROM vacancies WHERE operator_id = ? AND status = 'live'");
    $p_stmt->bind_param("i", $operator_id);
    $p_stmt->execute();
    $approved_posts = $p_stmt->get_result()->fetch_assoc()['total_approved'] ?? 0;
    $p_stmt->close();

    $today_stmt = $conn->prepare("SELECT COUNT(*) as today_approved FROM vacancies WHERE operator_id = ? AND status = 'live' AND DATE(created_at) = CURDATE()");
    $today_stmt->bind_param("i", $operator_id);
    $today_stmt->execute();
    $today_posts = $today_stmt->get_result()->fetch_assoc()['today_approved'] ?? 0;
    $today_stmt->close();

    $daily_target = $current_rank['daily_target'];
    $daily_progress = min(100, round(($today_posts / $daily_target) * 100));

    echo json_encode([
        'status' => 'success',
        'score' => $score,
        'wallet' => $wallet,
        'approved_posts' => $approved_posts,
        'today_posts' => $today_posts,
        'daily_progress' => $daily_progress,
        'current_level' => $current_level,
        'current_rank' => $current_rank,
        'next_rank' => $next_rank,
        'needed_points' => $needed_points,
        'progress_percentage' => $progress_percentage
    ]);
    exit;
}

// ─── 4. सामान्य पृष्ठ लोड (PHP डेटाबेस फ़ेच) ───
$op_stmt = $conn->prepare("SELECT username, wallet_balance, performance_score, designation, commission_rate FROM operators WHERE id = ?");
$op_stmt->bind_param("i", $operator_id);
$op_stmt->execute();
$op_data = $op_stmt->get_result()->fetch_assoc();
$op_stmt->close();

$score = intval($op_data['performance_score'] ?? 0);
$wallet = floatval($op_data['wallet_balance'] ?? 0.00);
$username = htmlspecialchars($op_data['username'] ?? 'ऑपरेटर');
$db_designation = trim($op_data['designation'] ?? '');
$db_comm_rate = isset($op_data['commission_rate']) ? floatval($op_data['commission_rate']) : null;

$current_rank = $ranks[1];
$current_level = 1;

foreach ($ranks as $level => $r) {
    if ($score >= $r['min_score']) {
        $current_rank = $r;
        $current_level = $level;
    }
}

// 🌟 एडमिन द्वारा सेट पद और कमीशन रेट प्राथमिकता से दिखाएं
if (!empty($db_designation)) {
    $current_rank['name'] = $db_designation;
}
if ($db_comm_rate !== null && $db_comm_rate > 0) {
    $current_rank['rate'] = $db_comm_rate;
}

$next_level = $current_level < 4 ? $current_level + 1 : 4;
$next_rank = $ranks[$next_level];

if ($current_level < 4) {
    $needed_points = $next_rank['min_score'] - $score;
    $total_range = $next_rank['min_score'] - $current_rank['min_score'];
    $current_progress_points = $score - $current_rank['min_score'];
    $progress_percentage = ($total_range > 0) ? min(100, max(0, round(($current_progress_points / $total_range) * 100))) : 0;
} else {
    $needed_points = 0;
    $progress_percentage = 100;
}

$p_stmt = $conn->prepare("SELECT COUNT(*) as total_approved FROM vacancies WHERE operator_id = ? AND status = 'live'");
$p_stmt->bind_param("i", $operator_id);
$p_stmt->execute();
$approved_posts = $p_stmt->get_result()->fetch_assoc()['total_approved'] ?? 0;
$p_stmt->close();

$today_stmt = $conn->prepare("SELECT COUNT(*) as today_approved FROM vacancies WHERE operator_id = ? AND status = 'live' AND DATE(created_at) = CURDATE()");
$today_stmt->bind_param("i", $operator_id);
$today_stmt->execute();
$today_posts = $today_stmt->get_result()->fetch_assoc()['today_approved'] ?? 0;
$today_stmt->close();

$daily_target = $current_rank['daily_target'];
$daily_progress = min(100, round(($today_posts / $daily_target) * 100));
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>रैंक एवं अनलिमिटेड अर्निंग हब - Operator Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 15px rgba(234, 179, 8, 0.3); }
            50% { box-shadow: 0 0 30px rgba(234, 179, 8, 0.7); }
        }
        .glow-card { animation: pulse-glow 3.5s infinite; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 font-sans min-h-screen flex flex-col md:flex-row relative">

    <!-- SIDEBAR INCLUDE -->
    <?php 
        if (file_exists('sidebar.php')) {
            include 'sidebar.php';
        } elseif (file_exists('../includes/sidebar.php')) {
            include '../includes/sidebar.php';
        } elseif (file_exists('includes/sidebar.php')) {
            include 'includes/sidebar.php';
        }
    ?>

    <!-- MAIN CONTENT CONTAINER -->
    <main class="flex-1 p-4 md:p-8 overflow-y-auto">
        <div class="max-w-6xl mx-auto space-y-8">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-800/80">
                <div>
                    <a href="dashboard.php" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 transition mb-2 inline-flex items-center gap-1">
                        <i class="fa-solid fa-arrow-left"></i> डैशबोर्ड पर वापस जाएं
                    </a>
                    <h1 class="text-3xl font-black text-white tracking-tight flex items-center gap-3">
                        ⚡ रैंक प्रमोशन व इनकम बूस्टर केंद्र
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">
                        नमस्कार <span class="text-yellow-400 font-bold"><?php echo $username; ?></span>! एडमिन द्वारा सेट किया गया वर्तमान पद: <span class="text-emerald-400 font-bold underline"><?php echo htmlspecialchars($current_rank['name']); ?></span>
                    </p>
                </div>
                <div class="bg-slate-900/90 border border-slate-800 p-4 rounded-2xl flex items-center gap-4 shadow-lg">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 text-2xl">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block font-medium">लाइव वॉलेट बैलेंस</span>
                        <span class="text-2xl font-black text-emerald-400" id="live_wallet">₹<?php echo number_format($wallet, 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Motivational Call to Action Banner -->
            <div class="bg-gradient-to-r from-red-600 via-orange-600 to-amber-600 rounded-2xl p-4 shadow-xl flex items-center justify-between gap-4 text-white">
                <div class="flex items-center gap-3">
                    <span class="text-3xl animate-bounce">🔥</span>
                    <div>
                        <h4 class="font-extrabold text-base md:text-lg">मेहनत आज करोगे, तो कल की पीढ़ी याद रखेगी!</h4>
                        <p class="text-xs md:text-sm text-amber-100">जितनी ज्यादा स्वीकृत पोस्ट्स, उतनी तेजी से रैंक बढ़ेगी और एडमिन द्वारा प्रमोशन मिलेगा!</p>
                    </div>
                </div>
                <a href="add_vacancy.php" class="hidden lg:inline-flex items-center gap-2 bg-black hover:bg-slate-900 text-yellow-300 font-black text-xs px-5 py-3 rounded-xl shadow-lg hover:scale-105 transition transform uppercase tracking-wider">
                    अभी पोस्ट जोड़ें 🚀
                </a>
            </div>

            <!-- Hero Card: Current Status (Shows Admin Updated Designation & Rate) -->
            <div id="hero_card" class="bg-gradient-to-br <?php echo $current_rank['bg']; ?> p-6 md:p-8 rounded-3xl shadow-2xl border <?php echo $current_rank['border']; ?> relative overflow-hidden glow-card">
                <div class="absolute -right-8 -bottom-8 opacity-15 text-9xl select-none pointer-events-none" id="rank_badge_bg">
                    <?php echo $current_rank['badge']; ?>
                </div>

                <div class="relative z-10 space-y-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 bg-black/50 backdrop-blur-md text-amber-300 rounded-full text-xs font-black uppercase tracking-wider border border-amber-400/30">
                                <span id="rank_tag"><?php echo $current_rank['tag']; ?></span>
                            </div>
                            <h2 class="text-3xl md:text-5xl font-black text-white mt-3 flex items-center gap-3">
                                <span id="rank_badge"><?php echo $current_rank['badge']; ?></span>
                                <span id="rank_name"><?php echo htmlspecialchars($current_rank['name']); ?></span>
                            </h2>
                            <p class="text-sm md:text-base text-slate-200 mt-2">
                                आपकी वर्तमान प्रति पोस्ट कमाई: <strong class="text-yellow-300 text-xl font-black bg-black/40 px-3 py-1 rounded-lg border border-yellow-400/30" id="current_rate">₹<?php echo number_format($current_rank['rate'], 2); ?> / Post</strong>
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 min-w-[260px]">
                            <div class="bg-black/40 backdrop-blur-md p-4 rounded-2xl border border-white/10 text-center">
                                <span class="text-[10px] text-slate-300 uppercase font-black tracking-wider block">Performance Score</span>
                                <span class="text-3xl font-black text-yellow-300" id="live_score"><?php echo $score; ?></span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">कुल अर्जित अंक</span>
                            </div>
                            <div class="bg-black/40 backdrop-blur-md p-4 rounded-2xl border border-white/10 text-center">
                                <span class="text-[10px] text-slate-300 uppercase font-black tracking-wider block">Approved Posts</span>
                                <span class="text-3xl font-black text-emerald-400" id="approved_posts"><?php echo $approved_posts; ?></span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">स्वीकृत वैकेंसियां</span>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Target Widget -->
                    <div class="bg-black/30 backdrop-blur-md p-4 rounded-2xl border border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-yellow-500/20 text-yellow-400 flex items-center justify-center font-bold">
                                🎯
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-200">आज का दैनिक लक्ष्य (Daily Target)</h4>
                                <p class="text-xs text-slate-400">आज स्वीकृत पोस्ट्स: <span class="text-white font-bold" id="today_posts"><?php echo $today_posts; ?></span> / <span id="daily_target"><?php echo $daily_target; ?></span></p>
                            </div>
                        </div>
                        <div class="w-full sm:w-1/3 space-y-1">
                            <div class="flex justify-between text-[11px] font-bold text-slate-300">
                                <span>लक्ष्य प्रोग्रेस</span>
                                <span class="text-yellow-400" id="daily_progress_text"><?php echo $daily_progress; ?>%</span>
                            </div>
                            <div class="w-full bg-slate-900 rounded-full h-2 overflow-hidden border border-white/10">
                                <div class="bg-yellow-400 h-full rounded-full transition-all duration-500" id="daily_progress_bar" style="width: <?php echo $daily_progress; ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress to Next Level -->
                    <div id="progress_section">
                        <?php if ($current_level < 4): ?>
                            <div class="bg-black/40 backdrop-blur-md p-5 rounded-2xl border border-white/10 space-y-3">
                                <div class="flex flex-col sm:flex-row justify-between sm:items-center text-xs md:text-sm font-bold text-white gap-1">
                                    <span class="flex items-center gap-2">
                                        <i class="fa-solid fa-bullseye text-yellow-400"></i>
                                        अगला लक्ष्य: <strong class="text-yellow-300" id="next_rank_name"><?php echo $next_rank['badge'] . ' ' . $next_rank['name']; ?></strong> 
                                        (रेट: ₹<span id="next_rank_rate"><?php echo number_format($next_rank['rate'], 2); ?></span>/post)
                                    </span>
                                    <span class="text-emerald-400 font-extrabold" id="progress_percentage_text"><?php echo $progress_percentage; ?>% पूरा हुआ</span>
                                </div>

                                <div class="w-full bg-slate-900 rounded-full h-4 p-0.5 border border-white/20 overflow-hidden">
                                    <div class="bg-gradient-to-r from-amber-400 via-yellow-300 to-emerald-400 h-full rounded-full transition-all duration-1000 shadow-lg" id="progress_bar" style="width: <?php echo $progress_percentage; ?>%"></div>
                                </div>

                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center text-xs text-slate-200 font-medium gap-2">
                                    <p>
                                        🚀 अगली रैंक अनलॉक करने के लिए मात्र <strong class="text-yellow-300 text-sm font-black underline" id="needed_points"><?php echo $needed_points; ?> पॉइंट्स (पोस्ट्स)</strong> दूर हैं!
                                    </p>
                                    <span class="text-[11px] bg-indigo-900/80 text-indigo-200 px-2.5 py-1 rounded-md border border-indigo-700">1 Approved Post = +1 Point</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="bg-black/50 backdrop-blur-md p-5 rounded-2xl border border-cyan-400/50 text-center space-y-2">
                                <h3 class="text-lg font-black text-cyan-300">🎉 आप उच्चतम स्तर पर विराजमान हैं!</h3>
                                <p class="text-xs text-slate-300">आप सिस्टम के शीर्ष ऑपरेटर हैं। बिना रुके काम जारी रखें!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Interactive Live Earnings Calculator -->
            <div class="bg-slate-900 rounded-3xl p-6 border border-slate-800 space-y-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 border-b border-slate-800 pb-4">
                    <div>
                        <h3 class="text-xl font-extrabold text-white flex items-center gap-2">
                            <i class="fa-solid fa-calculator text-yellow-400"></i> लाइव संभावित कमाई कैलकुलेटर
                        </h3>
                        <p class="text-xs text-slate-400">नीचे दिए गए स्लाइडर से देखें कि आप रोज़ कितनी पोस्ट करके कितना कमा सकते हैं!</p>
                    </div>
                    <span class="text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 py-1.5 rounded-full font-bold">
                        ⚡ Interactive Estimator
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                    <div class="lg:col-span-7 space-y-4">
                        <label class="flex justify-between text-sm font-bold text-slate-200">
                            <span>प्रतिदिन अपेक्षित पोस्ट्स संख्या:</span>
                            <span class="text-yellow-400 text-base font-extrabold" id="calcPostValue">50 पोस्ट/दिन</span>
                        </label>
                        <input type="range" id="calcSlider" min="10" max="200" step="5" value="50" class="w-full h-3 bg-slate-950 rounded-lg appearance-none cursor-pointer accent-yellow-400 border border-slate-800">
                        <div class="flex justify-between text-[10px] text-slate-500 font-bold">
                            <span>10 Post</span>
                            <span>50 Post</span>
                            <span>100 Post</span>
                            <span>200 Post</span>
                        </div>
                    </div>

                    <div class="lg:col-span-5 bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-3">
                        <div class="text-xs text-slate-400 font-bold uppercase tracking-wider">आपकी वर्तमान पद/रैंक (<span id="calc_rank_name"><?php echo htmlspecialchars($current_rank['name']); ?></span>) पर अनुमानित कमाई:</div>
                        <div class="flex justify-between items-center border-b border-slate-800 pb-2">
                            <span class="text-xs text-slate-300">दैनिक कमाई:</span>
                            <span class="text-lg font-black text-yellow-400" id="dailyEarning">₹0.00</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-300">मासिक कमाई (30 दिन):</span>
                            <span class="text-2xl font-black text-emerald-400" id="monthlyEarning">₹0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Levels Breakdown Grid -->
            <div>
                <h3 class="text-xl font-extrabold text-white mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-layer-group text-indigo-400"></i> मानक सिस्टम लेवल्स व दरें
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <?php foreach ($ranks as $lvl => $r): ?>
                        <?php 
                            $is_current = ($lvl == $current_level); 
                            $is_unlocked = ($score >= $r['min_score']);
                        ?>
                        <div id="rank_card_<?php echo $lvl; ?>" class="p-5 rounded-2xl border transition-all relative flex flex-col justify-between space-y-4 <?php echo $is_current ? 'bg-slate-800/90 border-indigo-500 ring-2 ring-indigo-500/50 shadow-xl' : ($is_unlocked ? 'bg-slate-900/80 border-slate-700' : 'bg-slate-950/60 border-slate-900 opacity-60'); ?>">
                            
                            <div>
                                <div class="flex justify-between items-start mb-3">
                                    <div class="text-4xl"><?php echo $r['badge']; ?></div>
                                    <div class="status-badge-container">
                                        <?php if ($is_current): ?>
                                            <span class="bg-indigo-600 text-white text-[9px] font-black px-2.5 py-1 rounded-full uppercase tracking-wider shadow">
                                                एक्टिव
                                            </span>
                                        <?php elseif ($is_unlocked): ?>
                                            <span class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[9px] font-black px-2 py-0.5 rounded-full uppercase">
                                                अनलॉकड
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-slate-800 text-slate-500 text-[9px] font-black px-2 py-0.5 rounded-full uppercase">
                                                लॉकड 🔒
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <h4 class="font-extrabold text-base text-white"><?php echo $r['name']; ?></h4>
                                <span class="text-[10px] text-slate-400 font-medium block mt-0.5"><?php echo $r['tag']; ?></span>

                                <div class="my-4 py-3 px-3 bg-slate-950/80 rounded-xl border border-slate-800 text-center">
                                    <span class="text-[10px] text-slate-400 block uppercase font-bold">मानक दर</span>
                                    <span class="text-2xl font-black text-emerald-400">₹<?php echo number_format($r['rate'], 2); ?></span>
                                </div>

                                <div class="text-xs space-y-2 text-slate-300">
                                    <div class="flex justify-between border-b border-slate-800 pb-1">
                                        <span class="text-slate-400">आवश्यक स्कोर:</span>
                                        <strong class="text-white"><?php echo $r['min_score']; ?> - <?php echo $r['max_score']; ?> pts</strong>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">दैनिक लक्ष्य:</span>
                                        <strong class="text-yellow-400"><?php echo $r['daily_target']; ?> पोस्ट/दिन</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="level-status-footer">
                                <?php if (!$is_unlocked): ?>
                                    <div class="text-[11px] text-amber-400/90 bg-amber-950/30 border border-amber-800/40 p-2 rounded-lg text-center font-medium">
                                        🎯 केवल <?php echo ($r['min_score'] - $score); ?> पॉइंट्स और चाहिए!
                                    </div>
                                <?php else: ?>
                                    <div class="text-[11px] text-emerald-400/90 bg-emerald-950/30 border border-emerald-800/40 p-2 rounded-lg text-center font-medium">
                                        ✓ योग्य स्तर
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Motivational Strategy & Rules Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-slate-900/90 rounded-3xl p-6 border border-slate-800 space-y-4">
                    <h4 class="font-extrabold text-base text-white flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-cyan-400"></i> प्रमोशन के नियम एवं गाइडलाइंस
                    </h4>
                    <ul class="text-xs text-slate-300 space-y-2.5 list-disc list-inside leading-relaxed">
                        <li>प्रत्येक <strong>Approved वैकेंसी पोस्ट</strong> पर आपको <strong>+1 Performance Point</strong> मिलता है।</li>
                        <li>एडमिन आपकी परफॉर्मेंस देखकर आपका <strong>पद/रैंक (Designation) व रेट (Commission Rate)</strong> सीधे अपडेट कर सकता है।</li>
                        <li>गलत जानकारी या कॉपी-पेस्ट कंटेंट की वजह से पोस्ट रीजेक्ट होने पर स्कोर नहीं मिलेगा।</li>
                    </ul>
                </div>

                <div class="bg-gradient-to-br from-indigo-950 via-slate-900 to-slate-950 rounded-3xl p-6 border border-indigo-800/50 space-y-4">
                    <h4 class="font-extrabold text-base text-yellow-300 flex items-center gap-2">
                        <i class="fa-solid fa-rocket text-amber-400"></i> शीर्ष ऑपरेटर बनने के टिप्स
                    </h4>
                    <div class="space-y-3 text-xs text-slate-300">
                        <div class="flex items-start gap-2.5">
                            <span class="bg-indigo-600 text-white w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">1</span>
                            <p><strong class="text-white">नियमित पोस्ट जोड़ें:</strong> daily target पूरा करें ताकि एडमिन आपकी रैंक अपग्रेड करे।</p>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="bg-indigo-600 text-white w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">2</span>
                            <p><strong class="text-white">सटीक जानकारी:</strong> जॉब की तारीख, फीस व लिंक सही भरें।</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom CTA Button -->
            <div class="text-center pt-4 pb-8">
                <a href="add_vacancy.php" class="inline-flex items-center gap-3 bg-gradient-to-r from-yellow-400 via-amber-500 to-orange-500 hover:from-yellow-300 hover:to-orange-400 text-slate-950 font-black text-base px-8 py-4 rounded-2xl shadow-2xl hover:scale-105 transition transform uppercase tracking-wider">
                    <span><i class="fa-solid fa-plus-circle"></i> नई वैकेंसी पोस्ट करें</span>
                </a>
            </div>

        </div>
    </main>

    <!-- LEVEL UP NOTIFICATION POPUP MODAL -->
    <div id="levelUpModal" class="fixed inset-0 bg-black/80 backdrop-blur-md hidden flex items-center justify-center z-50 p-4">
        <div class="bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 border-2 border-yellow-400/80 rounded-3xl p-6 md:p-8 max-w-md w-full text-center space-y-6 shadow-2xl relative">
            <div class="text-6xl animate-bounce" id="modal_badge">🏆</div>
            <div>
                <span class="text-xs font-black uppercase tracking-widest text-yellow-400 bg-yellow-400/10 border border-yellow-400/30 px-3 py-1 rounded-full">बधाई हो! पद/रैंक अपडेट</span>
                <h3 class="text-2xl md:text-3xl font-black text-white mt-3" id="modal_title">आपकी रैंक अपडेट हुई है!</h3>
                <p class="text-sm text-slate-300 mt-2">आपकी मेहनत और एडमिन द्वारा आपकी रैंक को प्रमोट किया गया है!</p>
            </div>
            <div class="bg-slate-950/90 border border-slate-800 p-4 rounded-2xl text-left space-y-2">
                <div class="flex justify-between text-xs text-slate-400">
                    <span>नया पद / रैंक:</span>
                    <strong class="text-white text-sm" id="modal_rank_name">Executive</strong>
                </div>
                <div class="flex justify-between text-xs text-slate-400">
                    <span>नया पेआउट रेट:</span>
                    <strong class="text-emerald-400 font-bold text-sm" id="modal_rate">₹1.00 / Post</strong>
                </div>
            </div>
            <button onclick="closeLevelUpModal()" class="w-full bg-gradient-to-r from-yellow-400 to-amber-500 text-slate-950 font-black py-3.5 rounded-xl uppercase tracking-wider">
                शानदार! आगे बढ़ें 🚀
            </button>
        </div>
    </div>

    <!-- JAVASCRIPT FOR CALCULATOR & REAL-TIME SYNC -->
    <script>
        let currentRate = <?php echo floatval($current_rank['rate']); ?>;
        let currentLevel = <?php echo intval($current_level); ?>;
        let currentDesignation = "<?php echo addslashes($current_rank['name']); ?>";

        document.addEventListener('DOMContentLoaded', function() {
            const slider = document.getElementById('calcSlider');
            if (slider) {
                slider.addEventListener('input', updateEarnings);
                updateEarnings();
            }

            // हर 5 सेकंड में ऑटो सिंक
            setInterval(syncDatabaseData, 5000);
        });

        function updateEarnings() {
            const slider = document.getElementById('calcSlider');
            const postVal = document.getElementById('calcPostValue');
            const dailyEarning = document.getElementById('dailyEarning');
            const monthlyEarning = document.getElementById('monthlyEarning');

            if (!slider) return;

            const posts = parseInt(slider.value);
            postVal.textContent = posts + ' पोस्ट/दिन';
            
            const daily = posts * currentRate;
            const monthly = daily * 30;

            dailyEarning.textContent = '₹' + daily.toFixed(2);
            monthlyEarning.textContent = '₹' + monthly.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function syncDatabaseData() {
            fetch('?ajax_sync=1')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('live_wallet').textContent = '₹' + parseFloat(data.wallet).toFixed(2);
                        document.getElementById('live_score').textContent = data.score;
                        document.getElementById('approved_posts').textContent = data.approved_posts;
                        document.getElementById('today_posts').textContent = data.today_posts;
                        document.getElementById('daily_target').textContent = data.current_rank.daily_target;
                        document.getElementById('daily_progress_text').textContent = data.daily_progress + '%';
                        document.getElementById('daily_progress_bar').style.width = data.daily_progress + '%';

                        // एडमिन द्वारा अपडेट किया गया पदनाम/रैंक सिंक करें
                        document.getElementById('rank_badge').textContent = data.current_rank.badge;
                        document.getElementById('rank_badge_bg').textContent = data.current_rank.badge;
                        document.getElementById('rank_name').textContent = data.current_rank.name;
                        document.getElementById('rank_tag').textContent = data.current_rank.tag;
                        document.getElementById('current_rate').textContent = '₹' + parseFloat(data.current_rank.rate).toFixed(2) + ' / Post';
                        document.getElementById('calc_rank_name').textContent = data.current_rank.name;

                        // अगर एडमिन ने पद या रेट बदला है तो पॉपअप दिखाएं
                        if (data.current_rank.name !== currentDesignation || parseFloat(data.current_rank.rate) !== currentRate) {
                            showLevelUpModal(data.current_rank);
                            currentDesignation = data.current_rank.name;
                        }

                        currentRate = parseFloat(data.current_rank.rate);
                        updateEarnings();
                    }
                })
                .catch(err => console.error("Sync issue:", err));
        }

        function showLevelUpModal(rank) {
            document.getElementById('modal_badge').textContent = rank.badge;
            document.getElementById('modal_rank_name').textContent = rank.name;
            document.getElementById('modal_rate').textContent = '₹' + parseFloat(rank.rate).toFixed(2) + ' / Post';
            document.getElementById('levelUpModal').classList.remove('hidden');
        }

        function closeLevelUpModal() {
            document.getElementById('levelUpModal').classList.add('hidden');
        }
    </script>
</body>
</html>