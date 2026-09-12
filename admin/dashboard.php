<?php 
// Session start aur login check
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("location: login.php");
    exit;
}

// Database connection aur Header
include '../includes/db_connect.php'; 

// MySQL mode ko relax karna
if ($conn) {
    $conn->query("SET sql_mode=''");
}

// --- 🛡️ OPERATOR ACTIONS SYNC (APPROVE / DELETE) ---
$action_message = "";
$action_status = "";

if (isset($_GET['action']) && isset($_GET['id']) && ($_SESSION['user_role'] ?? '') === 'admin') {
    $action_id = intval($_GET['id']);
    $action_type = $_GET['action'];
    
    if ($action_type === 'approve') {
        $update_stmt = $conn->prepare("UPDATE operators SET is_active = 1, payment_verified = 1 WHERE id = ?");
        $update_stmt->bind_param("i", $action_id);
        if ($update_stmt->execute()) {
            $action_message = "ऑपरेटर को सफलतापूर्वक एक्टिवेट कर दिया गया है।";
            $action_status = "success";
        }
        $update_stmt->close();
    } elseif ($action_type === 'delete') {
        $delete_stmt = $conn->prepare("DELETE FROM operators WHERE id = ?");
        $delete_stmt->bind_param("i", $action_id);
        if ($delete_stmt->execute()) {
            $action_message = "ऑपरेटर का रिकॉर्ड सफलतापूर्वक हटा दिया गया है।";
            $action_status = "danger";
        }
        $delete_stmt->close();
    }
}

include 'includes/admin_header.php'; 

// --- REAL DATABASE SYNC LOGIC START ---
$user_role   = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'admin'; 
$user_status = isset($_SESSION['admin_status']) ? $_SESSION['admin_status'] : 'approved'; 

$pending_operators_count = 0;
$total_operators_count = 0;
$operators_list = [];

if ($user_role === 'admin') {
    try {
        // Pending operators count
        $op_query = "SELECT COUNT(*) as total_pending FROM operators WHERE is_active = 0 OR payment_verified = 0";
        $op_res = $conn->query($op_query);
        if ($op_res) {
            $op_row = $op_res->fetch_assoc();
            $pending_operators_count = isset($op_row['total_pending']) ? $op_row['total_pending'] : 0;
        }

        // Total active operators count for stats grid
        $total_op_query = "SELECT COUNT(*) as total_ops FROM operators WHERE is_active = 1 AND payment_verified = 1";
        $total_op_res = $conn->query($total_op_query);
        if ($total_op_res) {
            $total_op_row = $total_op_res->fetch_assoc();
            $total_operators_count = isset($total_op_row['total_ops']) ? $total_op_row['total_ops'] : 0;
        }
        
        // Fixed Query for Operators Grid
        $grid_query = "SELECT o.id, o.username, o.is_active, o.payment_verified, o.created_at, o.email,
                       (SELECT COUNT(*) FROM vacancies v WHERE v.username = o.username) + 
                       (SELECT COUNT(*) FROM expired_vacancies ev WHERE ev.username = o.username) as total_posts
                       FROM operators o ORDER BY o.id DESC";
        
        $grid_res = $conn->query($grid_query);
        if ($grid_res) {
            while ($grid_row = $grid_res->fetch_assoc()) {
                $operators_list[] = $grid_row;
            }
        }
    } catch (Exception $e) {
        $pending_operators_count = 0;
    }
}

// Vacancies data sync
$query = "SELECT status, category, CAST(last_date AS CHAR) as last_date, created_at, state FROM vacancies
          UNION ALL
          SELECT status, category, CAST(last_date AS CHAR) as last_date, created_at, state FROM expired_vacancies";

$result = $conn->query($query);

$total = 0; $live = 0; $results = 0; $admit_cards = 0; $recent = 0; $expired = 0;
$states_list = [];
$today = date('Y-m-d');
$yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $total++;
        if ($row['category'] == 'result') $results++;
        if ($row['category'] == 'admit_card') $admit_cards++;
        if ($row['created_at'] >= $yesterday) $recent++;
        if (!empty($row['state'])) $states_list[$row['state']] = true;
        
        $l_date = $row['last_date'];
        $is_expired_date = (!empty($l_date) && $l_date != '0000-00-00' && $l_date < $today);
        
        if ($row['status'] == 'expired' || $is_expired_date) {
            $expired++;
        } else if ($row['status'] == 'live') {
            $live++;
        }
    }
}
$total_states = count($states_list);
?>

<!-- Futuristic Google Fonts, FontAwesome & DataTables -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<style>
    :root {
        --global-bg: linear-gradient(135deg, #eef2ff 0%, #f0fdf4 35%, #fdf2f8 70%, #fff7ed 100%);
        --card-white: rgba(255, 255, 255, 0.85);
        --glass-border: rgba(255, 255, 255, 0.9);
        --text-dark: #0f172a;
        --text-sub: #475569;
        
        /* Power Colorful Accents */
        --grad-blue: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        --grad-emerald: linear-gradient(135deg, #059669 0%, #10b981 100%);
        --grad-purple: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
        --grad-rose: linear-gradient(135deg, #e11d48 0%, #f43f5e 100%);
        --grad-amber: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
        --grad-cyan: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
        --grad-pink: linear-gradient(135deg, #db2777 0%, #ec4899 100%);
        --grad-orange: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
    }

    #page-content-wrapper {
        background: var(--global-bg);
        background-attachment: fixed;
        min-height: 100vh;
        padding: 32px 24px;
        color: var(--text-dark);
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* 🌍 GLOBAL LIVE TICKER BAR */
    .global-ticker {
        background: #0f172a;
        color: #fff;
        border-radius: 20px;
        padding: 10px 20px;
        margin-bottom: 32px;
        display: flex;
        align-items: center;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .ticker-globe-badge {
        background: var(--grad-rose);
        color: #fff;
        padding: 6px 16px;
        border-radius: 12px;
        font-size: 0.78rem;
        font-weight: 900;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-right: 18px;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 15px rgba(225, 29, 72, 0.4);
    }

    .pulse-earth {
        width: 10px;
        height: 10px;
        background: #fff;
        border-radius: 50%;
        animation: earthPulse 1.5s infinite;
    }

    @keyframes earthPulse {
        0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.8); }
        70% { transform: scale(1.2); box-shadow: 0 0 0 12px rgba(255, 255, 255, 0); }
        100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); }
    }

    marquee {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.9rem;
        font-weight: 600;
        color: #f8fafc;
    }

    /* 🚀 TOP GLOBAL COMMAND BAR */
    .top-command-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 36px;
        flex-wrap: wrap;
        gap: 20px;
    }

    .master-title h2 {
        font-weight: 900;
        font-size: 2.1rem;
        margin: 0;
        letter-spacing: -0.5px;
        background: linear-gradient(135deg, #1e1b4b 0%, #4338ca 50%, #6366f1 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .master-title p {
        color: var(--text-sub);
        font-size: 0.9rem;
        margin: 6px 0 0 0;
        font-weight: 600;
    }

    .supreme-status-card {
        background: var(--card-white);
        border: 2px solid #ffffff;
        border-radius: 22px;
        padding: 14px 26px;
        display: flex;
        align-items: center;
        gap: 20px;
        backdrop-filter: blur(20px);
        box-shadow: 0 12px 35px rgba(99, 102, 241, 0.12);
    }

    .btn-supreme-action {
        background: var(--grad-purple);
        color: #fff;
        padding: 10px 22px;
        border-radius: 14px;
        font-size: 0.85rem;
        font-weight: 800;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 8px 20px rgba(124, 58, 237, 0.35);
        border: none;
    }

    .btn-supreme-action:hover {
        color: #fff;
        transform: translateY(-3px) scale(1.03);
        box-shadow: 0 12px 28px rgba(124, 58, 237, 0.5);
    }

    /* 📊 VIBRANT COLORFUL HUD GRID */
    .hud-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 22px;
        margin-bottom: 40px;
    }

    .hud-card {
        background: var(--card-white);
        border: 2px solid #ffffff;
        border-radius: 24px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(16px);
        transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.05);
    }

    .hud-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }

    .hud-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
    }

    .hud-header span {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: var(--text-sub);
        font-weight: 800;
    }

    .hud-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        color: #fff;
        box-shadow: 0 8px 18px rgba(0,0,0,0.15);
    }

    .hud-value {
        font-family: 'JetBrains Mono', monospace;
        font-size: 2.4rem;
        font-weight: 800;
        line-height: 1;
        color: var(--text-dark);
    }

    /* Interactive Sync Button on Google Card */
    .btn-sync-indexing {
        background: none;
        border: none;
        color: var(--text-sub);
        font-size: 0.9rem;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .btn-sync-indexing:hover {
        color: #2563eb;
        background: rgba(37, 99, 235, 0.1);
    }

    /* Gradient Themes for Cards */
    .c-blue .hud-icon-box { background: var(--grad-blue); }
    .c-emerald .hud-icon-box { background: var(--grad-emerald); }
    .c-purple .hud-icon-box { background: var(--grad-purple); }
    .c-rose .hud-icon-box { background: var(--grad-rose); }
    .c-amber .hud-icon-box { background: var(--grad-amber); }
    .c-cyan .hud-icon-box { background: var(--grad-cyan); }
    .c-pink .hud-icon-box { background: var(--grad-pink); }
    .c-orange .hud-icon-box { background: var(--grad-orange); }

    /* ⚡ COLORFUL EXECUTION CONSOLE */
    .power-console {
        background: rgba(255, 255, 255, 0.9);
        border: 2px solid #ffffff;
        padding: 28px;
        border-radius: 26px;
        margin-bottom: 40px;
        backdrop-filter: blur(20px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.04);
    }

    .power-console h4 {
        margin: 0 0 22px 0;
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .action-button-group { display: flex; gap: 18px; flex-wrap: wrap; }

    .btn-power-cmd {
        padding: 14px 28px;
        border-radius: 16px;
        text-decoration: none;
        font-weight: 800;
        font-size: 0.88rem;
        color: #fff;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        border: none;
    }

    .btn-power-cmd:hover { color: #fff; transform: translateY(-4px); }
    .btn-cmd-green { background: var(--grad-emerald); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.35); }
    .btn-cmd-amber { background: var(--grad-amber); box-shadow: 0 8px 20px rgba(245, 158, 11, 0.35); }
    .btn-cmd-blue { background: var(--grad-blue); box-shadow: 0 8px 20px rgba(59, 130, 246, 0.35); }

    /* 🖥️ OPERATOR GLOBAL TERMINAL */
    .operator-global-terminal {
        background: rgba(255, 255, 255, 0.95);
        border: 2px solid #ffffff;
        border-radius: 28px;
        padding: 32px;
        backdrop-filter: blur(20px);
        box-shadow: 0 20px 45px rgba(0,0,0,0.06);
    }

    .operator-global-terminal h3 {
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 26px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .colorful-table {
        border-collapse: separate;
        border-spacing: 0 10px;
        width: 100% !important;
    }

    .colorful-table th {
        background: #f1f5f9 !important;
        color: var(--text-sub) !important;
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 14px 20px;
        border: none !important;
    }

    .colorful-table tbody tr {
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        border-radius: 16px;
        transition: all 0.25s ease;
    }

    .colorful-table tbody tr:hover {
        transform: translateY(-2px) scale(1.002);
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    }

    .colorful-table td {
        padding: 16px 20px;
        border: none;
        vertical-align: middle;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-dark);
    }

    .colorful-table td:first-child { border-top-left-radius: 16px; border-bottom-left-radius: 16px; }
    .colorful-table td:last-child { border-top-right-radius: 16px; border-bottom-right-radius: 16px; }

    .status-pill-vibrant {
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .status-pill-vibrant.approved { background: rgba(16, 185, 129, 0.15); color: #047857; border: 1px solid #10b981; }
    .status-pill-vibrant.pending { background: rgba(245, 158, 11, 0.15); color: #b45309; border: 1px solid #f59e0b; }

    .action-btn-vibrant {
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 0.78rem;
        font-weight: 800;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        color: #fff;
    }

    .action-approve-vibrant { background: var(--grad-emerald); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
    .action-approve-vibrant:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(16, 185, 129, 0.5); }

    .action-delete-vibrant { background: var(--grad-rose); box-shadow: 0 4px 12px rgba(244, 63, 94, 0.3); }
    .action-delete-vibrant:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(244, 63, 94, 0.5); }
</style>

<div id="page-content-wrapper">
    <div class="container-fluid p-0">
        
        <!-- 🌍 GLOBAL LIVE TICKER BAR -->
        <div class="global-ticker">
            <div class="ticker-globe-badge">
                <div class="pulse-earth"></div> GLOBAL LIVE RADAR
            </div>
            <marquee id="liveMarquee" behavior="scroll" direction="left" scrollamount="5" onmouseover="this.stop();" onmouseout="this.start();">
                🌐 ग्लोबल नेटवर्क लाइव... डेटाबेस सिंक एक्टिवेट है।
            </marquee>
        </div>
        
        <!-- 🚀 TOP GLOBAL COMMAND BAR -->
        <div class="top-command-bar">
            <div class="master-title">
                <h2><i class="fa-solid fa-earth-americas text-primary"></i> ग्लोबल एडमिन कमांड सेंटर</h2>
                <p>Supreme Global Enterprise Console // Supreme Power Node</p>
            </div>

            <?php if ($user_role === 'admin'): ?>
                <div class="supreme-status-card">
                    <div style="font-size: 0.88rem; color: var(--text-dark);">
                        <span style="color: #4338ca; font-weight: 800;">पेंडिंग ऑपरेटर:</span> 
                        <strong style="font-family:'JetBrains Mono'; font-size: 1.1rem; color: #e11d48;"><?php echo $pending_operators_count; ?></strong>
                    </div>
                    <a href="#operator-grid" class="btn-supreme-action">
                        <i class="fa-solid fa-users-gear"></i> ऑपरेटर कंट्रोल
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- 📊 VIBRANT COLORFUL HUD GRID -->
        <div class="hud-grid">
            <!-- Google Indexing Status Card with Refresh & Search Console Link -->
            <div class="hud-card c-blue">
                <div class="hud-header">
                    <span>
                        Google Indexing Status 
                        <button id="refreshIndexingBtn" class="btn-sync-indexing" title="Re-check Indexing Status">
                            <i class="fa-solid fa-rotate-right" id="syncIcon"></i>
                        </button>
                    </span>
                    <a href="https://search.google.com/search-console" target="_blank" title="Open Google Search Console" style="text-decoration:none;">
                        <div class="hud-icon-box" style="background: var(--grad-blue);"><i class="fa-brands fa-google"></i></div>
                    </a>
                </div>
                <div class="hud-value" id="indexingStatus" style="font-size: 1.35rem; color: #2563eb; line-height: 1.2;">Checking...</div>
            </div>

            <div class="hud-card c-blue">
                <div class="hud-header">
                    <span>कुल डेटाबेस रिकॉर्ड</span>
                    <div class="hud-icon-box"><i class="fa-solid fa-database"></i></div>
                </div>
                <div class="hud-value"><?php echo number_format($total); ?></div>
            </div>

            <?php if ($user_role === 'admin'): ?>
            <div class="hud-card c-purple">
                <div class="hud-header">
                    <span>कुल एक्टिव ऑपरेटर</span>
                    <div class="hud-icon-box"><i class="fa-solid fa-users"></i></div>
                </div>
                <div class="hud-value" style="color: #7c3aed;"><?php echo number_format($total_operators_count); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="hud-card c-emerald">
                <div class="hud-header">
                    <span>लाइव नौकरियां</span>
                    <div class="hud-icon-box"><i class="fa-solid fa-tower-broadcast"></i></div>
                </div>
                <div class="hud-value" style="color: #059669;"><?php echo number_format($live); ?></div>
            </div>

            <div class="hud-card c-rose">
                <div class="hud-header">
                    <span>एक्सपायर्ड वैकेंसी</span>
                    <div class="hud-icon-box"><i class="fa-solid fa-calendar-xmark"></i></div>
                </div>
                <div class="hud-value" style="color: #e11d48;"><?php echo number_format($expired); ?></div>
            </div>

            <div class="hud-card c-orange">
                <div class="hud-header">
                    <span>कवर्ड राज्य</span>
                    <div class="hud-icon-box"><i class="fa-solid fa-map-location-dot"></i></div>
                </div>
                <div class="hud-value" style="color: #ea580c;"><?php echo number_format($total_states); ?></div>
            </div>
            
            <div class="hud-card c-amber">
                <div class="hud-header">
                    <span>जारी रिजल्ट्स</span>
                    <div class="hud-icon-box"><i class="fa-solid fa-file-circle-check"></i></div>
                </div>
                <div class="hud-value" style="color: #d97706;"><?php echo number_format($results); ?></div>
            </div>

            <div class="hud-card c-pink">
                <div class="hud-header">
                    <span>जारी एडमिट कार्ड</span>
                    <div class="hud-icon-box"><i class="fa-solid fa-id-card"></i></div>
                </div>
                <div class="hud-value" style="color: #db2777;"><?php echo number_format($admit_cards); ?></div>
            </div>
            
            <div class="hud-card c-cyan">
                <div class="hud-header">
                    <span>24 घंटे के अपडेट्स</span>
                    <div class="hud-icon-box"><i class="fa-solid fa-bolt"></i></div>
                </div>
                <div class="hud-value" style="color: #0891b2;"><?php echo number_format($recent); ?></div>
            </div>
        </div>
        
        <!-- ⚡ COLORFUL EXECUTION CONSOLE -->
        <div class="power-console">
            <h4><i class="fa-solid fa-bolt-lightning text-warning"></i> सुप्रीम एक्जीक्यूशन कमांड्स (Quick Actions)</h4>
            <div class="action-button-group">
                <a href="add_vacancy.php" class="btn-power-cmd btn-cmd-green">
                    <i class="fa-solid fa-plus-circle"></i> नई जॉब प्रविष्टि
                </a>
                <a href="add_result.php" class="btn-power-cmd btn-cmd-amber">
                    <i class="fa-solid fa-clipboard-check"></i> रिजल्ट अपलोड
                </a>
                <a href="manage_vacancies.php" class="btn-power-cmd btn-cmd-blue">
                    <i class="fa-solid fa-sliders"></i> सिस्टम कोर मैनेज करें
                </a>
            </div>
        </div>

        <!-- 🖥️ OPERATOR GLOBAL TERMINAL -->
        <?php if ($user_role === 'admin'): ?>
            <div class="operator-global-terminal" id="operator-grid">
                <h3>
                    <span><i class="fa-solid fa-users-gear text-primary me-2"></i> ऑपरेटर ग्लोबल नेटवर्क मैनेजमेंट</span>
                    <span class="badge bg-primary rounded-pill px-3 py-2 fs-6" style="font-family:'JetBrains Mono';"><?php echo count($operators_list); ?> OPERATORS</span>
                </h3>
                
                <?php if (!empty($action_message)): ?>
                    <div class="alert alert-<?php echo $action_status === 'danger' ? 'danger' : 'success'; ?> alert-dismissible fade show rounded-4 shadow-sm" role="alert">
                        <i class="fa-solid <?php echo $action_status === 'success' ? 'fa-circle-check text-success' : 'fa-trash-can text-danger'; ?> me-2"></i>
                        <?php echo $action_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table colorful-table" id="operatorsDataTable">
                        <thead>
                            <tr>
                                <th>UID</th>
                                <th>ऑपरेटर नाम</th>
                                <th>ईमेल</th>
                                <th>पोस्ट संख्या</th>
                                <th>पंजीकरण समय</th>
                                <th>स्थिति</th>
                                <th>कंट्रोल एक्शन्स</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($operators_list) > 0): ?>
                                <?php foreach ($operators_list as $op): 
                                    $is_approved = ($op['is_active'] == 1 && $op['payment_verified'] == 1);
                                ?>
                                    <tr>
                                        <td style="font-family:'JetBrains Mono'; color:#2563eb; font-weight:800;">#<?php echo $op['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($op['username']); ?></strong></td>
                                        <td style="color:var(--text-sub);"><?php echo htmlspecialchars($op['email']); ?></td>
                                        <td>
                                            <span class="badge bg-primary px-3 py-2 rounded-pill" style="font-family:'JetBrains Mono'; font-size:0.85rem;">
                                                <i class="fa-solid fa-file-lines me-1"></i> <?php echo isset($op['total_posts']) ? $op['total_posts'] : 0; ?>
                                            </span>
                                        </td>
                                        <td><small style="color:var(--text-sub); font-weight:600;"><?php echo date('d M Y, h:i A', strtotime($op['created_at'])); ?></small></td>
                                        <td>
                                            <span class="status-pill-vibrant <?php echo $is_approved ? 'approved' : 'pending'; ?>">
                                                <i class="fa-solid <?php echo $is_approved ? 'fa-circle-check' : 'fa-clock'; ?>"></i>
                                                <?php echo $is_approved ? 'Active' : 'Pending'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display:flex; gap:8px;">
                                                <?php if (!$is_approved): ?>
                                                    <a href="?action=approve&id=<?php echo $op['id']; ?>#operator-grid" class="action-btn-vibrant action-approve-vibrant" onclick="return confirm('क्या आप इस ऑपरेटर को एक्टिवेट करना चाहते हैं?');">
                                                        <i class="fa-solid fa-check"></i> Approve
                                                    </a>
                                                <?php endif; ?>
                                                <a href="?action=delete&id=<?php echo $op['id']; ?>#operator-grid" class="action-btn-vibrant action-delete-vibrant" onclick="return confirm('सुरक्षा चेतावनी: क्या आप निश्चित रूप से इस ऑपरेटर को हटाना चाहते हैं?');">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // DataTables Initialization
    $('#operatorsDataTable').DataTable({
        "order": [[ 0, "desc" ]], 
        "pageLength": 10,         
        "language": {
            "search": "खोजें:",
            "lengthMenu": "_MENU_ ऑपरेटर दिखाएं",
            "info": "_TOTAL_ में से _START_ से _END_ ऑपरेटर दर्ज हैं",
            "paginate": { "next": "आगे", "previous": "पीछे" },
            "emptyTable": "कोई रिकॉर्ड मौजूद नहीं है।"
        }
    });

    // Real-time Indexing Status Check via AJAX
    function checkIndexingStatus() {
        $('#syncIcon').addClass('fa-spin');
        $.ajax({
            url: 'check_indexing.php',
            type: 'GET',
            success: function(data) {
                $('#indexingStatus').html(data);
            },
            error: function() {
                $('#indexingStatus').html('<span style="color:#e11d48; font-size:1rem;">Offline</span>');
            },
            complete: function() {
                setTimeout(function() {
                    $('#syncIcon').removeClass('fa-spin');
                }, 500);
            }
        });
    }

    // Manual Refresh On Click
    $('#refreshIndexingBtn').on('click', function(e) {
        e.preventDefault();
        checkIndexingStatus();
    });

    checkIndexingStatus();
    setInterval(checkIndexingStatus, 30000); // Har 30 sec me auto check

    // Real-time Marquee Terminal Sync via AJAX
    function loadLiveAlerts() {
        $.ajax({
            url: 'fetch_live_alerts.php',
            type: 'GET',
            success: function(response) {
                if(response.trim() !== "") {
                    $('#liveMarquee').html(response);
                }
            },
            error: function() {
                $('#liveMarquee').html('🌐 ग्लोबल सिंक एरर। कनेक्टिविटी चेक करें।');
            }
        });
    }

    loadLiveAlerts();
    setInterval(loadLiveAlerts, 10000); 

    // Smooth Scroll Anchor
    if(window.location.hash) {
        $('html, body').animate({
            scrollTop: $(window.location.hash).offset().top - 80
        }, 600);
    }
});
</script>

<?php 
include 'includes/admin_footer.php'; 
?>