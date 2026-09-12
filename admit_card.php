<?php
include 'includes/db_connect.php';
include 'includes/header.php'; 

// 🚀 AJAX Live Search Endpoint (Fast Server-Side Handler)
if (isset($_GET['ajax_search'])) {
    $search = trim($_GET['search'] ?? '');
    $search_param = "%" . $search . "%";
    
    if (!empty($search)) {
        $stmt = $conn->prepare("SELECT id, title, post_date, important_dates FROM vacancies WHERE category='admit_card' AND status='live' AND title LIKE ? ORDER BY post_date DESC LIMIT 15");
        $stmt->bind_param("s", $search_param);
    } else {
        $stmt = $conn->prepare("SELECT id, title, post_date, important_dates FROM vacancies WHERE category='admit_card' AND status='live' ORDER BY post_date DESC LIMIT 15");
    }
    
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    
    while ($row = $res->fetch_assoc()) {
        $raw_date = strip_tags($row['important_dates']);
        $short_date = mb_strlen($raw_date) > 30 ? mb_substr($raw_date, 0, 30) . '...' : $raw_date;

        $data[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'], 
            'post_date' => date('d M, Y', strtotime($row['post_date'])),
            'important_dates' => !empty($row['important_dates']) ? htmlspecialchars($short_date, ENT_QUOTES, 'UTF-8') : "जल्द उपलब्ध"
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// 🔢 Pagination Parameters
$limit = 15; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Optimized Count Query
$total_sql = "SELECT COUNT(*) FROM vacancies WHERE category='admit_card' AND status='live'";
$total_result = $conn->query($total_sql);
$total_rows = $total_result ? $total_result->fetch_row()[0] : 0;
$total_pages = ceil($total_rows / $limit);

// Main Page Query
$stmt = $conn->prepare("SELECT id, title, post_date, important_dates FROM vacancies WHERE category='admit_card' AND status='live' ORDER BY post_date DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!-- 🎨 Advanced Colorful & Dynamic Modern UI Stylesheet -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

    :root {
        --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
        --accent-gradient: linear-gradient(135deg, #3b82f6 0%, #2dd4bf 100%);
        --btn-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        --btn-hover-gradient: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
        --bg-glass: rgba(255, 255, 255, 0.85);
        --shadow-glow: 0 10px 30px -10px rgba(99, 102, 241, 0.3);
        --card-border: rgba(226, 232, 240, 0.8);
    }

    body {
        background: #f8fafc;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .admit-container {
        max-width: 1140px;
        margin: 30px auto;
        padding: 0 18px;
    }

    /* 🔥 Dynamic Neon Gradient Header */
    .hero-header {
        text-align: center;
        background: var(--primary-gradient);
        padding: 35px 20px;
        border-radius: 20px;
        color: #ffffff;
        box-shadow: var(--shadow-glow);
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .hero-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 60%);
        animation: rotateBg 15s linear infinite;
    }

    @keyframes rotateBg {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .hero-header-content {
        position: relative;
        z-index: 2;
    }

    .hero-badge {
        display: inline-block;
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(8px);
        padding: 6px 16px;
        border-radius: 50px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-bottom: 12px;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .hero-header h1 {
        font-family: 'Outfit', sans-serif;
        font-size: 2.3rem;
        font-weight: 800;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
    }

    .hero-header p {
        font-size: 1rem;
        opacity: 0.95;
        max-width: 650px;
        margin: 0 auto;
    }

    /* 📝 Glassmorphic SEO Box */
    .seo-content-box {
        background: #ffffff;
        border: 1px solid var(--card-border);
        border-left: 5px solid #6366f1;
        border-radius: 16px;
        padding: 22px 26px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }

    .seo-content-box h2 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.2rem;
        color: #1e293b;
        margin-bottom: 10px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .seo-content-box p {
        font-size: 0.92rem;
        color: #475569;
        line-height: 1.75;
        margin-bottom: 10px;
    }

    .seo-content-box p:last-child { margin-bottom: 0; }

    /* 🔍 Ultra Glowing Search Bar */
    .search-box-wrapper {
        max-width: 600px;
        margin: 0 auto 30px auto;
        position: relative;
    }

    .search-box-wrapper input {
        width: 100%;
        padding: 16px 48px 16px 52px;
        font-size: 0.98rem;
        border: 2px solid #e2e8f0;
        border-radius: 50px;
        outline: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: #ffffff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        box-sizing: border-box;
    }

    .search-box-wrapper input:focus {
        border-color: #8b5cf6;
        box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.2), 0 10px 25px rgba(139, 92, 246, 0.1);
    }

    .search-box-wrapper > i.fa-magnifying-glass {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #8b5cf6;
        font-size: 1.1rem;
    }

    .search-clear-btn {
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%);
        background: #f1f5f9;
        border: none;
        color: #64748b;
        cursor: pointer;
        font-size: 0.9rem;
        display: none;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        transition: all 0.2s ease;
    }

    .search-clear-btn:hover {
        background: #ef4444;
        color: #ffffff;
    }

    .search-loader {
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        border: 3px solid #e2e8f0;
        border-top-color: #8b5cf6;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
        display: none;
    }

    @keyframes spin {
        to { transform: translateY(-50%) rotate(360deg); }
    }

    .highlight {
        background: #fef08a;
        color: #854d0e;
        padding: 2px 4px;
        border-radius: 4px;
        font-weight: 700;
    }

    /* 📊 Dynamic Modern Data Table */
    .table-responsive {
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid var(--card-border);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        overflow: hidden;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .custom-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        padding: 16px 20px;
        border-bottom: 2px solid #edf2f7;
    }

    .custom-table td {
        padding: 18px 20px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.94rem;
        color: #1e293b;
        vertical-align: middle;
        transition: background 0.2s ease;
    }

    .custom-table tr:hover td {
        background-color: #f8fafc;
    }

    .title-cell {
        font-weight: 600;
    }

    .title-cell a {
        color: #0f172a;
        text-decoration: none;
        font-size: 0.98rem;
        font-family: 'Outfit', sans-serif;
        transition: color 0.2s ease;
        display: block;
        margin-bottom: 6px;
    }

    .title-cell a:hover {
        color: #6366f1;
    }

    /* Colorful Dynamic Badges */
    .date-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 0.78rem;
        font-weight: 700;
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
    }

    .date-badge.exam {
        background: #fff1f2;
        color: #e11d48;
        border-color: #fecdd3;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--btn-gradient);
        color: #ffffff !important;
        padding: 10px 18px;
        border-radius: 50px;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 700;
        white-space: nowrap;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .action-btn:hover {
        background: var(--btn-hover-gradient);
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
    }

    /* Dynamic Social Share Pill */
    .share-inline {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .share-inline span {
        font-size: 0.75rem;
        color: #94a3b8;
        font-weight: 600;
    }

    .share-inline a {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }

    .share-inline a.wa { background: #dcfce7; color: #16a34a; }
    .share-inline a.wa:hover { background: #25D366; color: #ffffff; transform: scale(1.1); }

    .share-inline a.tg { background: #e0f2fe; color: #0284c7; }
    .share-inline a.tg:hover { background: #0088cc; color: #ffffff; transform: scale(1.1); }

    /* 📢 Colorful Ad Container */
    .ad-slot {
        margin: 25px 0;
        text-align: center;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 2px dashed #cbd5e1;
        padding: 12px;
        border-radius: 12px;
    }

    .ad-slot span {
        display: block;
        font-size: 0.68rem;
        color: #94a3b8;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 4px;
    }

    /* 🔢 Modern Pagination */
    .pagination-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 35px;
        gap: 8px;
        flex-wrap: wrap;
    }

    .p-link {
        padding: 10px 16px;
        border-radius: 12px;
        background: #ffffff;
        color: #334155;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.88rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        transition: all 0.2s ease;
    }

    .p-link:hover {
        border-color: #8b5cf6;
        color: #8b5cf6;
        transform: translateY(-2px);
    }

    .p-link.active {
        background: var(--btn-gradient);
        color: #ffffff !important;
        border-color: transparent;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .p-disabled {
        padding: 10px 16px;
        border-radius: 12px;
        background: #f8fafc;
        color: #cbd5e1;
        border: 1px solid #e2e8f0;
        font-size: 0.88rem;
        cursor: not-allowed;
    }

    /* 💡 Interactive Accordion FAQ Section */
    .faq-section {
        background: #ffffff;
        border: 1px solid var(--card-border);
        border-radius: 20px;
        padding: 28px;
        margin-top: 45px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.03);
    }

    .faq-section h3 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.3rem;
        color: #0f172a;
        margin-bottom: 20px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .faq-accordion {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .faq-item {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        transition: border-color 0.2s ease;
    }

    .faq-item.active {
        border-color: #8b5cf6;
    }

    .faq-header {
        width: 100%;
        background: #f8fafc;
        padding: 16px 20px;
        text-align: left;
        border: none;
        outline: none;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.96rem;
        font-weight: 700;
        color: #1e293b;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background 0.2s ease;
    }

    .faq-header:hover {
        background: #f1f5f9;
    }

    .faq-header i {
        color: #8b5cf6;
        transition: transform 0.3s ease;
    }

    .faq-item.active .faq-header i {
        transform: rotate(180deg);
    }

    .faq-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s cubic-bezier(0, 1, 0, 1);
        background: #ffffff;
    }

    .faq-body {
        padding: 16px 20px;
        font-size: 0.9rem;
        color: #64748b;
        line-height: 1.7;
        border-top: 1px solid #f1f5f9;
    }

    /* 📱 Responsive Mobile View */
    @media (max-width: 768px) {
        .custom-table thead { display: none; }
        .custom-table, .custom-table tbody, .custom-table tr, .custom-table td {
            display: block;
            width: 100%;
        }
        .custom-table tr {
            margin-bottom: 16px;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 16px;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }
        .custom-table td {
            border: none;
            padding: 8px 0;
        }
        .action-btn {
            width: 100%;
            justify-content: center;
            margin-top: 8px;
        }
        .hero-header h1 { font-size: 1.8rem; }
    }
</style>

<div class="admit-container">

    <!-- Top Ad Placement -->
    <div class="ad-slot">
        <span>Advertisement</span>
    </div>

    <!-- 🔥 Ultra Dynamic Neon Gradient Hero Box -->
    <div class="hero-header">
        <div class="hero-header-content">
            <span class="hero-badge"><i class="fa-solid fa-bolt"></i> Live Updates</span>
            <h1>एडमिट कार्ड (Admit Cards Portal)</h1>
            <p>नवीनतम सरकारी परीक्षा प्रवेश पत्र, एग्जाम सिटी और डायरेक्ट हॉल टिकट डाउनलोड लिंक</p>
        </div>
    </div>

    <!-- 🌐 Dynamic SEO Content Box -->
    <div class="seo-content-box">
        <h2><i class="fa-solid fa-circle-info" style="color:#6366f1;"></i> सरकारी परीक्षा एडमिट कार्ड कैसे प्राप्त करें?</h2>
        <p>हमारे वेब पोर्टल पर आपको UPSC, SSC, Railway (RRB), Banking, Defense, और सभी राज्य स्तरीय परीक्षाओं (जैसे MP, UP, Rajasthan, Bihar Govt Exams) के नवीनतम हॉल टिकट और एग्जाम सिटी इंटिमेशन के डायरेक्ट लिंक्स सबसे तेज़ी से प्रदान किए जाते हैं।</p>
        <p>परीक्षा हॉल में प्रवेश के समय अभ्यर्थियों को प्रवेश पत्र के साथ एक वैलिड ओरिजिनल फोटो आईडी कार्ड (जैसे आधार कार्ड, वोटर आईडी, या ड्राइविंग लाइसेंस) अनिवार्य रूप से साथ ले जाना होता है।</p>
    </div>

    <!-- 🔍 Glowing Live Search Field -->
    <div class="search-box-wrapper">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="liveSearch" placeholder="परीक्षा का नाम टाइप करके तुरंत खोजें (उदा. SSC, Railway, Police)..." aria-label="Search Admit Cards" autocomplete="off">
        <button type="button" id="clearSearchBtn" class="search-clear-btn" title="सर्च साफ़ करें"><i class="fa-solid fa-xmark"></i></button>
        <div id="searchLoader" class="search-loader"></div>
    </div>

    <!-- 📊 Colorful Data Table Container -->
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 48%;">परीक्षा / भर्ती का नाम</th>
                    <th style="width: 18%;">जारी तिथि</th>
                    <th style="width: 20%;">परीक्षा तिथि / स्टेटस</th>
                    <th style="width: 14%; text-align: right;">डाउनलोड</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $id = (int)$row["id"];
                        $title = htmlspecialchars($row["title"], ENT_QUOTES, 'UTF-8');
                        $post_date = date('d M, Y', strtotime($row["post_date"]));
                        
                        $raw_date_val = strip_tags($row["important_dates"]);
                        $display_date = !empty($raw_date_val) ? (mb_strlen($raw_date_val) > 30 ? mb_substr($raw_date_val, 0, 30) . '...' : $raw_date_val) : "जल्द उपलब्ध";
                        $important_dates = htmlspecialchars($display_date, ENT_QUOTES, 'UTF-8');
                        
                        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
                        $actual_link = $scheme . "://" . $_SERVER['HTTP_HOST'] . "/admitdetails.php?id=" . $id;
                        $share_text = urlencode($title . " Admit Card: " . $actual_link);
                ?>
                        <tr>
                            <td class="title-cell">
                                <a href="admitdetails.php?id=<?php echo $id; ?>"><?php echo $title; ?></a>
                                <div class="share-inline">
                                    <span>Share:</span>
                                    <a href="https://api.whatsapp.com/send?text=<?php echo $share_text; ?>" target="_blank" class="wa" title="WhatsApp पर शेयर करें"><i class="fa-brands fa-whatsapp"></i></a>
                                    <a href="https://t.me/share/url?url=<?php echo urlencode($actual_link); ?>&text=<?php echo urlencode($title); ?>" target="_blank" class="tg" title="Telegram पर शेयर करें"><i class="fa-brands fa-telegram"></i></a>
                                </div>
                            </td>
                            <td><span class="date-badge"><i class="fa-regular fa-calendar-check"></i> <?php echo $post_date; ?></span></td>
                            <td><span class="date-badge exam"><i class="fa-regular fa-clock"></i> <?php echo $important_dates; ?></span></td>
                            <td style="text-align: right;">
                                <a href="admitdetails.php?id=<?php echo $id; ?>" class="action-btn">
                                    <i class="fa-solid fa-download"></i> हॉल टिकट
                                </a>
                            </td>
                        </tr>
                <?php 
                    }
                } else {
                    echo '<tr><td colspan="4" style="text-align:center; padding: 40px; color: #64748b; font-weight:600;">कोई एडमिट कार्ड उपलब्ध नहीं है।</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <div id="noResultMsg" style="display: none; text-align: center; padding: 40px; color: #64748b; background:#fff; border-radius:16px; margin-top:20px; border:1px solid #e2e8f0;">
        <i class="fa-solid fa-magnifying-glass-minus" style="font-size: 2.5rem; color:#cbd5e1; margin-bottom:10px;"></i>
        <p style="font-weight: 600; margin:0;">कोई परिणाम नहीं मिला! कृपया दूसरा नाम सर्च करें।</p>
    </div>

    <!-- 🔢 Dynamic Pagination Controls -->
    <?php if ($total_pages > 1): ?>
        <nav class="pagination-wrapper" id="paginationSection">
            <?php if($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>" class="p-link">&laquo; पिछला</a>
            <?php else: ?>
                <span class="p-disabled">&laquo; पिछला</span>
            <?php endif; ?>

            <?php
            $range = 2;
            if ($page > ($range + 1)) {
                echo '<a href="?page=1" class="p-link">1</a>';
                if ($page > ($range + 2)) echo '<span style="padding: 0 5px; color:#94a3b8;">...</span>';
            }

            for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++) {
                $activeClass = ($page === $i) ? 'active' : '';
                echo '<a href="?page='.$i.'" class="p-link '.$activeClass.'">'.$i.'</a>';
            }

            if ($page < ($total_pages - $range)) {
                if ($page < ($total_pages - $range - 1)) echo '<span style="padding: 0 5px; color:#94a3b8;">...</span>';
                echo '<a href="?page='.$total_pages.'" class="p-link">'.$total_pages.'</a>';
            }
            ?>

            <?php if($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>" class="p-link">अगला &raquo;</a>
            <?php else: ?>
                <span class="p-disabled">अगला &raquo;</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <!-- 💡 Dynamic Accordion FAQs Section (15 Complete FAQs) -->
    <div class="faq-section">
        <h3><i class="fa-solid fa-circle-question" style="color:#8b5cf6;"></i> अक्सर पूछे जाने वाले प्रश्न (FAQ)</h3>
        
        <div class="faq-accordion">
            
            <div class="faq-item">
                <button class="faq-header">
                    <span>प्रश्न 1: एडमिट कार्ड डाउनलोड करने के लिए किन क्रेडेंशियल्स की आवश्यकता होती है?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-content">
                    <div class="faq-body">सामान्यतः आपको अपना रजिस्ट्रेशन नंबर/रोल नंबर और पासवर्ड अथवा जन्म तिथि (Date of Birth) दर्ज करनी होती है।</div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-header">
                    <span>प्रश्न 2: यदि एडमिट कार्ड में विवरण या फोटो गलत हो तो क्या करना चाहिए?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-content">
                    <div class="faq-body">तुरंत संबंधित परीक्षा प्राधिकरण या बोर्ड की हेल्पडेस्क अथवा सपोर्ट मेल पर संपर्क करके सुधार का अनुरोध करें।</div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-header">
                    <span>प्रश्न 3: क्या स्मार्टफोन से एडमिट कार्ड डाउनलोड किया जा सकता है?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-content">
                    <div class="faq-body">हाँ, आप मोबाइल ब्राउज़र से पीडीएफ फाइल के रूप में हॉल टिकट सेव करके प्रिंटआउट निकाल सकते हैं।</div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-header">
                    <span>प्रश्न 4: परीक्षा केंद्र में प्रवेश हेतु कौन-सा पहचान पत्र मान्य है?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-content">
                    <div class="faq-body">आधार कार्ड, वोटर आईडी, पैन कार्ड, ड्राइविंग लाइसेंस या पासपोर्ट में से कोई एक ओरिजिनल फोटो पहचान पत्र ले जाना अनिवार्य है।</div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-header">
                    <span>प्रश्न 5: क्या धुंधले (Blur) एडमिट कार्ड से परीक्षा में प्रवेश मिलेगा?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-content">
                    <div class="faq-body">नहीं, एडमिट कार्ड और उस पर बनी फोटो तथा हस्ताक्षर पूर्णतः स्पष्ट होने चाहिए अन्यथा प्रवेश रोका जा सकता है।</div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-header">
                    <span>प्रश्न 6: क्या परीक्षा केंद्र पर एडमिट कार्ड की सॉफ्ट कॉपी/मोबाइल कॉपी मान्य है?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-content">
                    <div class="faq-body">नहीं, लगभग सभी परीक्षाओं में एडमिट कार्ड का प्रिंटेड हार्ड कॉपी होना आवश्यक है।</div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-header">
                    <span>प्रश्न 7: यदि रजिस्ट्रेशन नंबर या पासवर्ड भूल गए हों तो क्या करें?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-content">
                    <div class="faq-body">लॉगिन पेज पर दिए गए 'Forgot Registration No/Password' ऑप्शन पर क्लिक कर अपनी ईमेल या मोबाइल पर डिटेल्स दोबारा मंगा सकते हैं।</div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-header">
                    <span>प्रश्न 8: क्या एडमिट कार्ड बाय पोस्ट या डाक से घर भेजा जाता है?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-content">
                    <div class="faq-body">नहीं, अब सभी सरकारी भर्ती बोर्ड प्रवेश पत्र केवल ऑनलाइन पोर्टल पर ही उपलब्ध कराते हैं।</div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-header">
                    <span>प्रश्न 9: 'एग्जाम सिटी स्लिप' और 'एडमिट कार्ड' में क्या अंतर होता है?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-content">
                    <div class="faq-body">सिटी स्लिप केवल परीक्षा शहर की जानकारी देती है, जबकि एडमिट कार्ड में परीक्षा केंद्र का सटीक नाम, पता, रोल नंबर और शिफ्ट टाइम दर्ज होता है।</div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-header">
                    <span>प्रश्न 10: परीक्षा केंद्र पर रिपोर्टिंग टाइम से कितना पहले पहुंचना चाहिए?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-content">
                    <div class="faq-body">बायोमेट्रिक जांच और बायो-अटेंडेंस के लिए गेट बंद होने के समय से कम से कम 45 मिनट पहले अवश्य पहुंचें।</div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-header">
                    <span>प्रश्न 11: क्या एडमिट कार्ड के साथ एक्स्ट्रा पासपोर्ट फोटो ले जानी चाहिए?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-content">
                    <div class="faq-body">हाँ, अटेंडेंस शीट पर चिपकाने हेतु वही फोटो ले जाएं जो फॉर्म में अपलोड की गई थी।</div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-header">
                    <span>प्रश्न 12: यदि एडमिट कार्ड में एग्जाम डेट गलत प्रिंट हो तो क्या करें?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-content">
                    <div class="faq-body">ऐसी स्थिति में तुरंत संबंधित आयोग के हेल्प डेस्क नंबर या हेल्पलाइन ईमेल पर शिकायत दर्ज करें।</div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-header">
                    <span>प्रश्न 13: इस पोर्टल पर किन-किन भर्तियों के एडमिट कार्ड अपडेट होते हैं?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-content">
                    <div class="faq-body">यहाँ UPSC, SSC, Banking, Railways, Police, Defense और सभी राज्य स्तरीय परीक्षाओं के एडमिट कार्ड नियमित अपडेट होते हैं।</div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-header">
                    <span>प्रश्न 14: क्या इस पोर्टल से एडमिट कार्ड डाउनलोड करने पर कोई शुल्क लगता है?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-content">
                    <div class="faq-body">नहीं, यहाँ डाउनलोड लिंक देखना और एक्सेस करना पूरी तरह निःशुल्क है।</div>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-header">
                    <span>प्रश्न 15: क्या परीक्षा के बाद भी एडमिट कार्ड सुरक्षित रखना आवश्यक है?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-content">
                    <div class="faq-body">हाँ, काउंसलिंग, डॉक्युमेंट वेरिफिकेशन (DV) और मेडिकल के समय एडमिट कार्ड की कॉपी की मांग की जा सकती है।</div>
                </div>
            </div>

        </div>
    </div>

    <!-- Bottom Ad Placement -->
    <div class="ad-slot">
        <span>Advertisement</span>
    </div>

</div>

<!-- 🚀 Dynamic Real-time Debounced AJAX Search & Accordion Scripts -->
<script>
let debounceTimer;
const searchInput = document.getElementById('liveSearch');
const clearBtn = document.getElementById('clearSearchBtn');
const loader = document.getElementById('searchLoader');
const tableBody = document.getElementById('tableBody');
const pagination = document.getElementById('paginationSection');
const noResult = document.getElementById('noResultMsg');

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// ⚡ Live Instant AJAX Dynamic Search
searchInput.addEventListener('input', function(e) {
    clearTimeout(debounceTimer);
    const query = e.target.value.trim();

    if (query.length > 0) {
        clearBtn.style.display = 'block';
        loader.style.display = 'block';
    } else {
        clearBtn.style.display = 'none';
        loader.style.display = 'none';
    }

    debounceTimer = setTimeout(() => {
        if (query.length > 0) {
            if (pagination) pagination.style.display = 'none';

            fetch(`?ajax_search=1&search=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    loader.style.display = 'none';
                    tableBody.innerHTML = '';
                    
                    if (data.length > 0) {
                        noResult.style.display = 'none';
                        data.forEach(item => {
                            const domain = window.location.origin;
                            const link = `${domain}/admitdetails.php?id=${item.id}`;
                            const shareText = encodeURIComponent(`${item.title} Admit Card: ${link}`);
                            
                            let titleText = item.title;
                            let highlightedTitle = escapeHtml(titleText);
                            
                            if (query) {
                                try {
                                    const escapedQuery = escapeRegExp(query);
                                    const regex = new RegExp(`(${escapedQuery})`, 'gi');
                                    highlightedTitle = escapeHtml(titleText).replace(regex, '<span class="highlight">$1</span>');
                                } catch(err) {
                                    console.error(err);
                                }
                            }

                            tableBody.innerHTML += `
                                <tr>
                                    <td class="title-cell">
                                        <a href="admitdetails.php?id=${item.id}">${highlightedTitle}</a>
                                        <div class="share-inline">
                                            <span>Share:</span>
                                            <a href="https://api.whatsapp.com/send?text=${shareText}" target="_blank" class="wa" title="WhatsApp पर शेयर करें"><i class="fa-brands fa-whatsapp"></i></a>
                                            <a href="https://t.me/share/url?url=${encodeURIComponent(link)}&text=${encodeURIComponent(item.title)}" target="_blank" class="tg" title="Telegram पर शेयर करें"><i class="fa-brands fa-telegram"></i></a>
                                        </div>
                                    </td>
                                    <td><span class="date-badge"><i class="fa-regular fa-calendar-check"></i> ${item.post_date}</span></td>
                                    <td><span class="date-badge exam"><i class="fa-regular fa-clock"></i> ${item.important_dates}</span></td>
                                    <td style="text-align: right;">
                                        <a href="admitdetails.php?id=${item.id}" class="action-btn">
                                            <i class="fa-solid fa-download"></i> हॉल टिकट
                                        </a>
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        noResult.style.display = 'block';
                    }
                })
                .catch(err => {
                    loader.style.display = 'none';
                    console.error("Search Fetch Error:", err);
                });
        } else {
            loader.style.display = 'none';
            window.location.reload();
        }
    }, 300);
});

// Clear Button
clearBtn.addEventListener('click', function() {
    searchInput.value = '';
    clearBtn.style.display = 'none';
    loader.style.display = 'none';
    window.location.reload();
});

// 💡 Smooth Interactive Accordion FAQ Script
document.querySelectorAll('.faq-header').forEach(button => {
    button.addEventListener('click', () => {
        const faqItem = button.parentElement;
        const faqContent = button.nextElementSibling;

        document.querySelectorAll('.faq-item').forEach(item => {
            if (item !== faqItem) {
                item.classList.remove('active');
                item.querySelector('.faq-content').style.maxHeight = null;
            }
        });

        faqItem.classList.toggle('active');
        if (faqItem.classList.contains('active')) {
            faqContent.style.maxHeight = faqContent.scrollHeight + "px";
        } else {
            faqContent.style.maxHeight = null;
        }
    });
});
</script>

<?php 
$stmt->close();
include 'includes/footer.php'; 
?>