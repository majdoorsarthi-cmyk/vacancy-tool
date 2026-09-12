<?php
// 1. डेटाबेस कनेक्शन और पब्लिक हेडर शामिल करें
include 'includes/db_connect.php';
include 'includes/header.php'; 

// पेजिनेशन की सेटिंग
$limit = 20; 
$page = isset($_GET['page']) && filter_var($_GET['page'], FILTER_VALIDATE_INT) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$category = 'result';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Prepared Statements की मदद से SQL Query का निर्माण (SQL Injection Safety)
$where_clause = "WHERE category = ? AND status = 'live'";
$params = [$category];

if (!empty($search)) {
    $where_clause .= " AND title LIKE ?";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
}

// 1. Total Rows Count
$total_sql = "SELECT COUNT(*) FROM vacancies $where_clause";
$stmt_total = $conn->prepare($total_sql);

if (!empty($search)) {
    $stmt_total->bind_param("ss", $category, $params[1]);
} else {
    $stmt_total->bind_param("s", $category);
}

$stmt_total->execute();
$total_result = $stmt_total->get_result();
$total_rows = $total_result->fetch_row()[0];
$stmt_total->close();

$total_pages = ceil($total_rows / $limit);
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// 2. Main Data Fetching with Prepared Statement
$sql = "SELECT id, title, post_date, important_dates FROM vacancies 
        $where_clause 
        ORDER BY post_date DESC LIMIT ? OFFSET ?";

$stmt_main = $conn->prepare($sql);

if (!empty($search)) {
    $stmt_main->bind_param("ssii", $category, $params[1], $limit, $offset);
} else {
    $stmt_main->bind_param("sii", $category, $limit, $offset);
}

$stmt_main->execute();
$result = $stmt_main->get_result();

// AJAX Response हैंडलर
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    ob_start();
    include_rows_or_cards($result);
    $rows_html = ob_get_clean();

    ob_start();
    render_smart_pagination($page, $total_pages, $search);
    $pagination_html = ob_get_clean();

    header('Content-Type: application/json');
    echo json_encode([
        'rows' => $rows_html,
        'pagination' => $pagination_html,
        'total_rows' => $total_rows
    ]);
    exit;
}

function include_rows_or_cards($result) {
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $id = (int)$row["id"];
            $title = htmlspecialchars($row["title"], ENT_QUOTES, 'UTF-8');
            $formatted_date = date('d M, Y', strtotime($row["post_date"]));
            $is_upcoming = (isset($row['important_dates']) && (stripos($row['important_dates'], 'upcoming') !== false || stripos($row['important_dates'], 'जल्द') !== false));
            ?>
            <article class="result-row-item">
                <div class="col-title">
                    <?php if ($is_upcoming): ?>
                        <span class="badge-upcoming">UPCOMING</span>
                    <?php else: ?>
                        <span class="badge-new">NEW</span>
                    <?php endif; ?>
                    <a href="result_details.php?id=<?php echo $id; ?>" class="result-link"><?php echo $title; ?></a>
                </div>
                <div class="col-date">
                    <?php if ($is_upcoming): ?>
                        <span class="date-text-upcoming">जल्द घोषित</span>
                    <?php else: ?>
                        <span class="date-capsule"><i class="fa-regular fa-calendar-days"></i> <?php echo $formatted_date; ?></span>
                    <?php endif; ?>
                </div>
                <div class="col-action">
                    <?php if ($is_upcoming): ?>
                        <a href="result_details.php?id=<?php echo $id; ?>" class="btn-view-result btn-blue">सूचना देखें</a>
                    <?php else: ?>
                        <a href="result_details.php?id=<?php echo $id; ?>" class="btn-view-result btn-orange">🔥 देखें &gt;</a>
                    <?php endif; ?>
                </div>
            </article>
            <?php 
        }
    } else {
        echo '<div class="no-data" style="padding: 20px; text-align: center; color: #64748b; font-weight: 600;">कोई परिणाम रिकॉर्ड उपलब्ध नहीं है।</div>';
    }
}

// उन्नत पेजिनेशन (हजारों परिणाम होने पर भी सुरक्षित)
function render_smart_pagination($page, $total_pages, $search_term = '') {
    if ($total_pages <= 1) return;
    $search_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
    
    // Previous Button
    if($page > 1) {
        echo '<a href="?page='.($page-1).$search_param.'" class="pagination-link btn-real" data-page="'.($page-1).'">&laquo; पिछला</a>';
    }

    // First Page Link
    if ($page > 3) {
        echo '<a href="?page=1'.$search_param.'" class="pagination-link btn-real" data-page="1">1</a>';
        if ($page > 4) {
            echo '<span class="pagination-dots">...</span>';
        }
    }

    // Dynamic Middle Pages (Current +- 2)
    for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++) {
        $active_class = ($page === $i) ? 'active' : '';
        echo '<a href="?page='.$i.$search_param.'" class="pagination-link btn-real '.$active_class.'" data-page="'.$i.'">'.$i.'</a>';
    }

    // Last Page Link
    if ($page < $total_pages - 2) {
        if ($page < $total_pages - 3) {
            echo '<span class="pagination-dots">...</span>';
        }
        echo '<a href="?page='.$total_pages.$search_param.'" class="pagination-link btn-real" data-page="'.$total_pages.'">'.$total_pages.'</a>';
    }

    // Next Button
    if($page < $total_pages) {
        echo '<a href="?page='.($page+1).$search_param.'" class="pagination-link btn-real" data-page="'.($page+1).'">अगला &raquo;</a>';
    }
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Yantramanav:wght@400;500;700&display=swap');

    body {
        background: radial-gradient(circle at top right, rgba(238,242,255,0.8) 0%, rgba(244,247,254,1) 100%);
        font-family: 'Plus Jakarta Sans', 'Yantramanav', sans-serif;
        margin: 0;
        padding: 0;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .portal-outer-container {
        flex: 1;
        width: 100%;
        box-sizing: border-box;
        padding-top: 110px;
        padding-bottom: 80px;
    }

    .main-portal-layout {
        max-width: 1440px;
        margin: 0 auto;
        padding: 20px; 
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 24px;
        align-items: start;
    }

    .right-sidebar {
        position: sticky;
        top: 120px;
        display: flex;
        flex-direction: column;
        gap: 20px;
        z-index: 10;
    }
    
    .sidebar-card {
        background: #ffffff;
        border: 1px solid #edf2f7;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }
    
    .card-title-bar {
        background: #ffffff;
        padding: 16px 20px;
        font-size: 1.1rem;
        font-weight: 800;
        color: #1e3a8a;
        border-bottom: 2px solid #f8fafc;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        cursor: pointer;
        user-select: none;
    }

    .card-title-bar .toggle-icon {
        font-size: 0.85rem;
        transition: transform 0.3s ease;
        color: #64748b;
    }

    .sidebar-collapsible-body {
        max-height: 2000px;
        overflow: hidden;
        transition: max-height 0.3s ease-in-out;
    }

    .sidebar-collapsible-body.collapsed {
        max-height: 0 !important;
    }

    .sidebar-card.collapsed .toggle-icon {
        transform: rotate(-90deg);
    }

    .sidebar-menu-list { list-style: none; padding: 0; margin: 0; }
    .sidebar-menu-list li a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 20px;
        color: #475569;
        text-decoration: none;
        font-size: 0.95rem;
        font-weight: 600;
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.2s;
    }
    .sidebar-menu-list li a:hover { 
        background: #f8fafc; 
        color: #ff6a00; 
        padding-left: 24px; 
    }
    
    .btn-all-categories {
        display: block; text-align: center; 
        background: #2563eb;
        color: #fff !important; padding: 14px; font-weight: 700; font-size: 0.95rem; text-decoration: none;
        transition: background 0.2s;
    }
    .btn-all-categories:hover { background: #1d4ed8; }

    .center-content { display: flex; flex-direction: column; }

    .hero-flex-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: transparent;
        padding: 10px 0 25px 0;
        gap: 30px;
    }

    .hero-text-side {
        flex: 1;
        text-align: left;
    }

    .hero-image-side {
        width: 340px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .real-laptop-graphic {
        width: 100%;
        max-width: 320px;
        filter: drop-shadow(0 15px 25px rgba(0,0,0,0.1));
    }

    .hero-text-side h1 {
        font-size: 3rem;
        font-weight: 800;
        color: #0b1c4d;
        margin: 0 0 12px 0;
        letter-spacing: -0.5px;
    }
    .hero-text-side h1 span { color: #ff5100; }
    
    /* 4-5 Line Hindi Description Style */
    .hero-description-text {
        color: #475569;
        font-size: 1.05rem;
        line-height: 1.7;
        margin: 0 0 20px 0;
        font-weight: 500;
        background: #ffffff;
        padding: 16px 20px;
        border-left: 4px solid #ff5100;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    }

    .search-container-box {
        position: relative;
        max-width: 100%;
        width: 100%;
        margin-bottom: 15px;
    }
    .search-input-box {
        width: 100%;
        padding: 16px 130px 16px 50px;
        font-size: 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 50px;
        outline: none;
        background: #ffffff;
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.05);
        box-sizing: border-box;
        transition: all 0.3s ease;
    }
    .search-input-box:focus {
        border-color: #2563eb;
        box-shadow: 0 10px 30px rgba(37, 99, 235, 0.15);
    }
    .search-bar-icon { 
        position: absolute; 
        left: 20px; 
        top: 50%; 
        transform: translateY(-50%); 
        color: #2563eb; 
        font-size: 1.2rem; 
    }
    
    .btn-search-submit {
        position: absolute; right: 6px; top: 6px; bottom: 6px;
        background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
        color: white; border: none;
        padding: 0 30px; border-radius: 50px; font-weight: 700; cursor: pointer;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
        transition: all 0.2s ease;
    }
    .btn-search-submit:hover {
        background: linear-gradient(180deg, #1d4ed8 0%, #1e40af 100%);
        box-shadow: 0 6px 15px rgba(37, 99, 235, 0.3);
    }

    .search-tags-wrapper {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .search-tag {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 8px 18px;
        border-radius: 30px;
        font-size: 0.9rem;
        color: #475569;
        text-decoration: none;
        font-weight: 600;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        transition: all 0.2s;
        cursor: pointer;
    }
    .search-tag:hover { 
        background: #eff6ff; 
        border-color: #2563eb; 
        color: #2563eb;
        transform: translateY(-2px);
    }

    .table-with-sidebar-container {
        display: grid;
        grid-template-columns: 280px 1fr; 
        gap: 24px;
        align-items: start;
        margin-top: 10px;
    }

    .table-main-wrapper {
        background: #ffffff;
        border: 1px solid #edf2f7;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    }
    
    .table-custom-header {
        display: flex;
        background: #1e1b4b;
        color: white;
        padding: 16px 24px;
        font-weight: 700;
        font-size: 1rem;
    }
    
    .result-row-item {
        display: flex;
        align-items: center;
        padding: 16px 24px;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s;
    }
    .result-row-item:hover { background: #f8fafc; }

    .col-title { flex: 1; display: flex; align-items: center; gap: 12px; font-weight: 700; }
    .col-date { width: 150px; text-align: center; }
    .col-action { width: 130px; text-align: right; }

    .result-link { color: #1e293b; text-decoration: none; font-size: 1rem; line-height: 1.5; font-weight: 600; }
    .result-link:hover { color: #2563eb; }

    .badge-new { background: #ef4444; color: white; font-size: 0.7rem; font-weight: 800; padding: 4px 8px; border-radius: 6px; letter-spacing: 0.5px; }
    .badge-upcoming { background: #f97316; color: white; font-size: 0.7rem; font-weight: 800; padding: 4px 8px; border-radius: 6px; letter-spacing: 0.5px; }
    
    .date-capsule { background: #f0fdf4; color: #16a34a; font-weight: 700; font-size: 0.9rem; padding: 6px 14px; border-radius: 30px; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; gap: 6px; }
    .date-text-upcoming { color: #2563eb; font-weight: 700; font-size: 0.95rem; }

    .btn-view-result {
        color: white !important; text-decoration: none;
        padding: 9px 20px; border-radius: 8px; font-size: 0.9rem; font-weight: 700; 
        display: inline-flex; align-items: center; gap: 4px; cursor: pointer;
        transition: all 0.2s ease; border: none;
    }
    .btn-orange {
        background: linear-gradient(180deg, #ff6a00 0%, #ea580c 100%); 
        box-shadow: 0 4px 10px rgba(234, 88, 12, 0.25);
    }
    .btn-orange:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(234, 88, 12, 0.4);
    }
    .btn-blue { 
        background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%); 
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
    }
    .btn-blue:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(37, 99, 235, 0.4);
    }

    .see-all-results-footer { text-align: center; padding: 16px; background: #ffffff; border-top: 1px solid #f1f5f9; }
    .see-all-results-footer a { color: #2563eb; text-decoration: none; font-size: 1rem; font-weight: 700; }

    .quick-link-item {
        display: flex; justify-content: space-between; align-items: center;
        padding: 15px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; 
        text-decoration: none; font-size: 0.95rem; font-weight: 700; transition: all 0.2s;
    }
    .quick-link-item:hover { 
        background: #f8fafc; 
        color: #2563eb; 
        padding-left: 25px;
    }

    .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 20px; }
    .stat-box { padding: 16px; border-radius: 12px; text-align: center; }
    .stat-box h3 { margin: 0; font-size: 1.5rem; font-weight: 800; }
    .stat-box p { margin: 4px 0 0 0; font-size: 0.85rem; font-weight: 700; color: #64748b; }

    .why-choose-card {
        background: #ffffff; border: 1px solid #edf2f7; border-radius: 16px; padding: 20px; margin-top: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
    }
    .why-choose-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 15px; }
    .why-item { display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 700; color: #1e293b; }
    .why-item i { font-size: 1.3rem; }

    .social-join-bar {
        background: #1e1b4b; color: #ffffff; border-radius: 16px; padding: 24px; 
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;
    }
    .btn-group-join {
        padding: 12px 24px; border-radius: 30px; text-decoration: none; 
        font-size: 0.95rem; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;
        transition: all 0.2s; box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    .btn-whatsapp { background: linear-gradient(180deg, #22c55e 0%, #16a34a 100%); color: white !important; }
    .btn-whatsapp:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(22, 163, 74, 0.4); }
    .btn-telegram { background: linear-gradient(180deg, #3b82f6 0%, #1d4ed8 100%); color: white !important; }
    .btn-telegram:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(29, 78, 216, 0.4); }
    .btn-bookmark { background: #eab308; color: #000000 !important; }
    .btn-bookmark:hover { transform: translateY(-2px); }

    .btn-real {
        background: #ffffff; border: 1px solid #e2e8f0; color: #334155; padding: 10px 18px;
        font-weight: 700; font-size: 0.95rem; text-decoration: none; border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: all 0.2s;
    }
    .btn-real:hover { background: #f8fafc; border-color: #cbd5e1; transform: translateY(-1px); }
    .btn-real.active { background: #2563eb; border-color: #2563eb; color: white !important; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3); }
    .pagination-dots { padding: 10px 8px; color: #64748b; font-weight: 700; }

    @media (max-width: 1280px) {
        .main-portal-layout { grid-template-columns: 1fr; }
        .right-sidebar { position: static; display: flex; width: 100%; }
        .why-choose-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 991px) {
        .table-with-sidebar-container { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .hero-flex-container { flex-direction: column-reverse; text-align: center; }
        .hero-text-side h1 { font-size: 2.2rem; }
        .why-choose-grid { grid-template-columns: 1fr; }
        .table-custom-header .col-date, .result-row-item .col-date { display: none; }
    }
</style>

<div class="portal-outer-container">
    <div class="main-portal-layout">
        <main class="center-content">
            <section class="hero-flex-container">
                <div class="hero-text-side">
                    <h1>📜 सरकारी <span>परीक्षा परिणाम</span></h1>
                    
                    <!-- 4 से 5 लाइनों का विवरण (Hindi Description) -->
                    <p class="hero-description-text">
                        हमारे पोर्टल पर देश भर के सभी राज्य एवं केंद्र सरकार की प्रमुख परीक्षाओं के नवीनतम परिणाम (Results) की प्रामाणिक जानकारी उपलब्ध कराई जाती है। यहाँ आप SSC, UPSC, रेलवे, बैंकिंग, पुलिस भर्ती, एवं राज्य स्तरीय बोर्ड परीक्षाओं के परिणाम सबसे पहले और एक ही स्थान पर देख सकते हैं। अपनी परीक्षा का कट-ऑफ, मेरिट सूची और डायरेक्ट रिजल्ट लिंक पाने के लिए नीचे दिए गए सर्च विकल्प का प्रयोग करें।
                    </p>
                    
                    <div class="search-container-box">
                        <i class="fa-solid fa-magnifying-glass search-bar-icon"></i>
                        <input type="text" id="liveSearch" class="search-input-box" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="यहाँ परीक्षा या बोर्ड का नाम खोजें (जैसे: MP Police, REET)...">
                        <button class="btn-search-submit" onclick="fetchResults(1, document.getElementById('liveSearch').value.trim())">खोजें</button>
                    </div>

                    <div class="search-tags-wrapper">
                        <span class="search-tag" onclick="applyTagSearch('MP Police')">MP Police</span>
                        <span class="search-tag" onclick="applyTagSearch('REET')">REET</span>
                        <span class="search-tag" onclick="applyTagSearch('CTET')">CTET</span>
                        <span class="search-tag" onclick="applyTagSearch('SSC')">SSC</span>
                        <span class="search-tag" onclick="applyTagSearch('MP Board')">MP Board</span>
                        <span class="search-tag" onclick="applyTagSearch('UP Board')">UP Board</span>
                        <span class="search-tag" onclick="applyTagSearch('')" style="background:#f1f5f9;">Clear ➔</span>
                    </div>
                </div>

                <div class="hero-image-side">
                    <img src="banners/result.jpg" class="real-laptop-graphic" alt="Government Exam Result Portal Updates">
                </div>
            </section>

            <div class="table-with-sidebar-container">
                <aside class="sidebar-card" id="popularCatsCard">
                    <div class="card-title-bar" onclick="toggleSidebarCard('popularCatsCard')">
                        <span>🔥 लोकप्रिय श्रेणियाँ</span>
                        <i class="fa-solid fa-chevron-down toggle-icon"></i>
                    </div>
                    <div class="sidebar-collapsible-body">
                        <ul class="sidebar-menu-list">
                            <li><a href="#">💼 पुलिस भर्ती</a></li>
                            <li><a href="#">🧑‍🏫 शिक्षक भर्ती</a></li>
                            <li><a href="#">🚆 रेलवे भर्ती</a></li>
                            <li><a href="#">🏦 बैंकिंग भर्ती</a></li>
                            <li><a href="#">🛡️ डिफेंस भर्ती</a></li>
                            <li><a href="#">📝 बोर्ड रिजल्ट</a></li>
                            <li><a href="#">🎓 यूनिवर्सिटी रिजल्ट</a></li>
                            <li><a href="#">🏢 अन्य सरकारी भर्ती</a></li>
                        </ul>
                        <a href="#" class="btn-all-categories">सभी श्रेणियाँ देखें ➔</a>
                    </div>
                </aside>

                <div class="table-main-wrapper">
                    <div class="table-custom-header">
                        <div class="col-title">📌 परीक्षा का नाम (Exam Title)</div>
                        <div class="col-date">घोषित तिथि</div>
                        <div class="col-action">एक्शन</div>
                    </div>
                    <div id="cardsContainer">
                        <?php include_rows_or_cards($result); ?>
                    </div>
                    <div class="see-all-results-footer">
                        <a href="#">सभी परिणाम देखें ➔</a>
                    </div>
                </div>
            </div> 

            <section class="why-choose-card">
                <b style="font-size: 1.1rem; color: #1e3a8a;">क्यों चुनें Vacancy Portal?</b>
                <div class="why-choose-grid">
                    <div class="why-item"><i class="fa-solid fa-circle-check" style="color:#22c55e;"></i> <div>सटीक और विश्वसनीय<br><span style="font-size:0.75rem; color:#64748b; font-weight:500;">100% सत्यापित जानकारी</span></div></div>
                    <div class="why-item"><i class="fa-solid fa-bolt" style="color:#f59e0b;"></i> <div>सबसे तेज अपडेट<br><span style="font-size:0.75rem; color:#64748b; font-weight:500;">रियल टाइम नोटिफिकेशन</span></div></div>
                    <div class="why-item"><i class="fa-solid fa-mobile-screen-button" style="color:#2563eb;"></i> <div>मोबाइल फ्रेंडली<br><span style="font-size:0.75rem; color:#64748b; font-weight:500;">कहीं से भी, कभी भी</span></div></div>
                    <div class="why-item"><i class="fa-solid fa-shield-halved" style="color:#7c3aed;"></i> <div>सुरक्षित और सरल<br><span style="font-size:0.75rem; color:#64748b; font-weight:500;">आपकी गोपनीयता प्राथमिकता</span></div></div>
                </div>
            </section>

            <nav class="pagination-wrapper" id="paginationSection" style="display:flex; justify-content:center; align-items:center; margin-top:30px; gap:8px;" aria-label="Result Pagination">
                <?php render_smart_pagination($page, $total_pages, $search); ?>
            </nav>
        </main>

        <aside class="right-sidebar">
            <div class="sidebar-card" id="quickLinksCard">
                <div class="card-title-bar" style="background:#eff6ff; color:#1e3a8a;" onclick="toggleSidebarCard('quickLinksCard')">
                    <span><i class="fa-solid fa-bolt" style="color:#f59e0b;"></i> त्वरित पहुँच</span>
                    <i class="fa-solid fa-chevron-down toggle-icon"></i>
                </div>
                <div class="sidebar-collapsible-body">
                    <a href="#" class="quick-link-item"><span><i class="fa-solid fa-circle" style="color:#22c55e; font-size:10px; margin-right:8px;"></i> रिजल्ट देखें</span> <span>&gt;</span></a>
                    <a href="#" class="quick-link-item"><span><i class="fa-solid fa-circle" style="color:#3b82f6; font-size:10px; margin-right:8px;"></i> एडमिट कार्ड डाउनलोड</span> <span>&gt;</span></a>
                    <a href="#" class="quick-link-item"><span><i class="fa-solid fa-circle" style="color:#f59e0b; font-size:10px; margin-right:8px;"></i> आवेदन स्थिति देखें</span> <span>&gt;</span></a>
                    <a href="#" class="quick-link-item"><span><i class="fa-solid fa-circle" style="color:#e11d48; font-size:10px; margin-right:8px;"></i> आंसर की / कट ऑफ</span> <span>&gt;</span></a>
                </div>
            </div>

            <div class="sidebar-card" id="statsCard">
                <div class="card-title-bar" onclick="toggleSidebarCard('statsCard')">
                    <span>📊 आज के आंकड़े</span>
                    <i class="fa-solid fa-chevron-down toggle-icon"></i>
                </div>
                <div class="sidebar-collapsible-body">
                    <div class="stats-grid">
                        <div class="stat-box" style="background:#eef2ff;"><h3 style="color:#3b82f6;">1250+</h3><p>कुल भर्तियाँ</p></div>
                        <div class="stat-box" style="background:#f0fdf4;"><h3 style="color:#22c55e;">320+</h3><p>रिजल्ट घोषित</p></div>
                        <div class="stat-box" style="background:#eff6ff;"><h3 style="color:#2563eb;">85+</h3><p>एडमिट कार्ड</p></div>
                        <div class="stat-box" style="background:#fff7ed;"><h3 style="color:#ea580c;">45+</h3><p>नए अपडेट</p></div>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div style="max-width:1440px; margin:30px auto; padding:0 20px;">
        <div class="social-join-bar">
            <div style="display:flex; align-items:center; gap:15px;">
                <span style="font-size:2.2rem;">🔔</span>
                <div>
                    <b style="font-size:1.1rem; display:block; margin-bottom:4px;">किसी भी महत्वपूर्ण अपडेट को मिस न करें!</b>
                    <span style="font-size:0.9rem; color:#cbd5e1;">हमारे साथ जुड़ें और सभी सरकारी भर्तियों की जानकारी सबसे पहले पाएं।</span>
                </div>
            </div>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <a href="#" class="btn-group-join btn-whatsapp" rel="noopener noreferrer" target="_blank"><i class="fa-brands fa-whatsapp"></i> WhatsApp ग्रुप ज्वाइन करें</a>
                <a href="#" class="btn-group-join btn-telegram" rel="noopener noreferrer" target="_blank"><i class="fa-brands fa-telegram"></i> Telegram telegram ग्रुप ज्वाइन करें</a>
                <a href="#" class="btn-group-join btn-bookmark"><i class="fa-solid fa-star"></i> बुकमार्क करें</a>
            </div>
        </div>
    </div>
</div>

<script>
let searchTimer;
const searchInput = document.getElementById('liveSearch');
const cardsContainer = document.getElementById('cardsContainer');
const paginationSection = document.getElementById('paginationSection');

function toggleSidebarCard(cardId) {
    const card = document.getElementById(cardId);
    const body = card.querySelector('.sidebar-collapsible-body');
    if(card && body) {
        card.classList.toggle('collapsed');
        body.classList.toggle('collapsed');
    }
}

function applyTagSearch(tagName) {
    if(searchInput) {
        searchInput.value = tagName;
        fetchResults(1, tagName);
    }
}

function fetchResults(page = 1, searchQuery = '') {
    let url = `?ajax=1&page=${encodeURIComponent(page)}&search=${encodeURIComponent(searchQuery)}`;
    fetch(url)
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if(cardsContainer) cardsContainer.innerHTML = data.rows;
        if(paginationSection) paginationSection.innerHTML = data.pagination;
        initPaginationListeners();
    })
    .catch(error => console.error('Error fetching data:', error));
}

function initPaginationListeners() {
    document.querySelectorAll('.pagination-link').forEach(link => {
        link.removeEventListener('click', handlePaginationClick);
        link.addEventListener('click', handlePaginationClick);
    });
}

function handlePaginationClick(e) {
    e.preventDefault();
    const pageNum = this.getAttribute('data-page');
    if (pageNum) {
        fetchResults(pageNum, searchInput ? searchInput.value.trim() : '');
    }
}

if(searchInput) {
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => fetchResults(1, e.target.value.trim()), 350);
    });
}

document.addEventListener("DOMContentLoaded", initPaginationListeners);
</script>

<?php 
if(isset($stmt_main)) $stmt_main->close();
include 'includes/footer.php'; 
?>