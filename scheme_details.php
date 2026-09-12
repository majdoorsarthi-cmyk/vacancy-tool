<?php 
/**
 * File: scheme_details.php
 * उद्देश्य: सरकारी योजनाओं (Government Schemes) का विस्तृत विवरण - PhonePe App Experience UI
 * (Updated with all Database Fields)
 */
include 'includes/db_connect.php'; 
include 'includes/header.php'; 

// 1. URL से ID प्राप्त करना
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo "<script>window.location.href='schemes.php';</script>";
    exit;
}

// 2. डेटाबेस से योजना का पूरा डेटा प्राप्त करना
$stmt = $conn->prepare("SELECT * FROM vacancies WHERE id = ? AND (category = 'scheme' OR category = 'schemes') AND status = 'live' LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    ?>
    <div style="max-width:500px; margin: 40px auto; text-align:center; padding:30px 20px; background:#fff; border-radius:24px; box-shadow:0 10px 30px rgba(0,0,0,0.06);">
        <div style="width:70px; height:70px; background:#fee2e2; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #ef4444;"></i>
        </div>
        <h2 style="color:#0f172a; font-weight:800; font-size:1.3rem; margin-bottom:8px;">योजना उपलब्ध नहीं है!</h2>
        <p style="color:#64748b; margin-bottom:20px; font-size:0.9rem; line-height:1.5;">आप जिस योजना की जानकारी खोज रहे हैं, वह हटा दी गई है या अभी सक्रिय नहीं है।</p>
        <a href="schemes.php" style="background:#5f259f; color:#fff; padding:12px 28px; border-radius:50px; text-decoration:none; font-weight:700; display:inline-block; font-size:0.9rem;">
            <i class="fas fa-arrow-left"></i> सभी योजनाएं देखें
        </a>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

$scheme = $result->fetch_assoc();

// 3. डेटाबेस के सभी 24 फ़ील्ड्स को सैनिटाइज़ और सेट करना
$title             = htmlspecialchars($scheme['title'] ?? '', ENT_QUOTES, 'UTF-8');
$state             = htmlspecialchars(!empty($scheme['location']) ? $scheme['location'] : ($scheme['state'] ?? 'सभी राज्य (All India)'), ENT_QUOTES, 'UTF-8');
$post_date         = !empty($scheme['post_date']) ? date('d M, Y', strtotime($scheme['post_date'])) : 'N/A';
$last_date         = !empty($scheme['last_date']) ? date('d M, Y', strtotime($scheme['last_date'])) : 'N/A';
$short_desc        = htmlspecialchars($scheme['short_desc'] ?? '', ENT_QUOTES, 'UTF-8');

// मुख्य विवरण एवं अतिरिक्त फ़ील्ड्स
$description       = $scheme['description'] ?? '';
$long_details      = $scheme['long_details'] ?? '';
$full_details      = $scheme['full_details'] ?? '';
$extra_details     = $scheme['extra_details'] ?? '';
$admission_process = $scheme['admission_process'] ?? '';
$important_dates   = $scheme['important_dates'] ?? '';
$application_fee   = $scheme['application_fee'] ?? '';
$eligibility       = $scheme['eligibility'] ?? '';
$required_docs     = $scheme['required_documents'] ?? '';
$total_posts       = htmlspecialchars($scheme['total_posts'] ?? '', ENT_QUOTES, 'UTF-8');
$total_seats       = htmlspecialchars($scheme['total_seats'] ?? '', ENT_QUOTES, 'UTF-8');
$banner_image      = htmlspecialchars($scheme['banner_image'] ?? '', ENT_QUOTES, 'UTF-8');

// लिंक्स की मैपिंग
$apply_link        = $scheme['apply_link'] ?? '#';
$result_link       = $scheme['result_link'] ?? '#';
$notification_link = $scheme['notification_link'] ?? '#';
$official_website  = $scheme['official_website'] ?? '#';

// 4. URL शेयरिंग
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$share_text  = urlencode("🎯 " . $title . "\n\nइस योजना की पूरी जानकारी और आवेदन लिंक यहाँ देखें: " . $current_url);

// 5. अन्य संबंधित योजनाएं (Related Schemes)
$rel_stmt = $conn->prepare("SELECT id, title, post_date FROM vacancies WHERE (category = 'scheme' OR category = 'schemes') AND id != ? AND status = 'live' ORDER BY post_date DESC LIMIT 5");
$rel_stmt->bind_param("i", $id);
$rel_stmt->execute();
$related_schemes = $rel_stmt->get_result();
?>

<!-- 🎨 PhonePe App UI Custom CSS -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Hind:wght@500;600;700&display=swap');

    :root {
        --phonepe-purple: #5f259f;
        --phonepe-dark-purple: #391968;
        --phonepe-accent: #673ab7;
        --phonepe-green: #00baf2;
        --phonepe-success: #10b981;
        --phonepe-bg: #f4f5f8;
        --phonepe-card: #ffffff;
        --phonepe-border: #e2e8f0;
        --phonepe-text: #0f172a;
        --phonepe-subtext: #64748b;
    }

    * { box-sizing: border-box; }

    body {
        background-color: var(--phonepe-bg) !important;
        font-family: 'Plus Jakarta Sans', 'Hind', sans-serif;
        color: var(--phonepe-text);
        margin: 0;
        padding-bottom: 85px !important;
        overflow-x: hidden;
    }

    .phonepe-wrapper {
        max-width: 1100px;
        margin: 0 auto;
        padding: 0 12px;
    }

    .phonepe-app-header {
        background: linear-gradient(135deg, var(--phonepe-purple) 0%, var(--phonepe-dark-purple) 100%);
        margin: -15px -15px 20px -15px;
        padding: 20px 18px 30px;
        border-bottom-left-radius: 28px;
        border-bottom-right-radius: 28px;
        color: #ffffff;
        box-shadow: 0 8px 25px rgba(95, 37, 159, 0.25);
        position: relative;
    }

    .phonepe-nav-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
    }

    .phonepe-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #ffffff !important;
        text-decoration: none !important;
        font-weight: 700;
        font-size: 0.9rem;
        background: rgba(255, 255, 255, 0.15);
        padding: 6px 14px;
        border-radius: 50px;
        backdrop-filter: blur(5px);
    }

    .phonepe-header-actions {
        display: flex;
        gap: 10px;
    }

    .phonepe-icon-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.18);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        text-decoration: none !important;
        font-size: 0.9rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .phonepe-title-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.2);
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.3px;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .phonepe-main-title {
        font-size: 1.35rem;
        font-weight: 800;
        line-height: 1.4;
        margin: 0 0 12px 0;
        color: #ffffff;
    }

    .phonepe-meta-strip {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 0.8rem;
        opacity: 0.92;
        flex-wrap: wrap;
    }

    .phonepe-services-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin-top: -35px;
        margin-bottom: 20px;
        position: relative;
        z-index: 10;
        padding: 0 4px;
    }

    .service-tile {
        background: #ffffff;
        border: 1px solid #edf2f7;
        border-radius: 18px;
        padding: 12px 6px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .service-tile-icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        margin-bottom: 6px;
    }

    .service-tile-label {
        font-size: 0.7rem;
        color: var(--phonepe-subtext);
        font-weight: 600;
        margin-bottom: 2px;
    }

    .service-tile-val {
        font-size: 0.78rem;
        font-weight: 800;
        color: var(--phonepe-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    .phonepe-grid-layout {
        display: grid;
        grid-template-columns: 1fr 310px;
        gap: 20px;
    }

    .phonepe-card {
        background: var(--phonepe-card);
        border-radius: 20px;
        padding: 20px;
        border: 1px solid var(--phonepe-border);
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
        margin-bottom: 18px;
    }

    .phonepe-card-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
        padding-bottom: 10px;
        border-bottom: 1.5px solid #f1f5f9;
    }

    .phonepe-card-header h3, .phonepe-card-header h4 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--phonepe-text);
    }

    .phonepe-card-header i {
        color: var(--phonepe-purple);
        font-size: 1.1rem;
    }

    .phonepe-highlight-box {
        background: #f0fdf4;
        border-left: 4px solid var(--phonepe-success);
        padding: 14px 16px;
        border-radius: 14px;
        font-size: 0.92rem;
        color: #14532d;
        line-height: 1.55;
        margin-bottom: 20px;
    }

    .scheme-section {
        margin-bottom: 22px;
    }

    .scheme-section-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--phonepe-purple);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .app-content-body {
        font-size: 0.98rem;
        color: #334155;
        line-height: 1.75;
    }

    .app-content-body table {
        width: 100% !important;
        display: block;
        overflow-x: auto;
        border-collapse: collapse;
        margin: 15px 0;
    }

    .app-content-body table td, .app-content-body table th {
        padding: 10px;
        border: 1px solid var(--phonepe-border);
        font-size: 0.85rem;
    }

    .app-content-body img {
        max-width: 100% !important;
        height: auto !important;
        border-radius: 12px;
        margin: 10px 0;
    }

    .phonepe-action-tile {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 14px 16px;
        margin-bottom: 12px;
        text-decoration: none !important;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }

    .phonepe-action-tile:hover {
        border-color: var(--phonepe-purple);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(95, 37, 159, 0.08);
    }

    .tile-left-content {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .tile-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .tile-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--phonepe-text);
        display: block;
        line-height: 1.3;
    }

    .tile-sub {
        font-size: 0.75rem;
        color: var(--phonepe-subtext);
        display: block;
        margin-top: 2px;
    }

    .tile-arrow {
        color: #94a3b8;
        font-size: 0.9rem;
    }

    .ad-phonepe-slot {
        background: #ffffff;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        padding: 8px;
        text-align: center;
        margin: 15px 0;
    }
    .ad-phonepe-slot span {
        display: block;
        font-size: 0.62rem;
        color: #94a3b8;
        font-weight: 800;
        text-transform: uppercase;
    }

    .phonepe-share-strip {
        background: #f8fafc;
        border: 1px solid var(--phonepe-border);
        border-radius: 16px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .phonepe-share-pill {
        padding: 8px 14px;
        border-radius: 50px;
        color: #fff !important;
        font-weight: 700;
        font-size: 0.8rem;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .phonepe-sticky-bottom {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #ffffff;
        border-top: 1px solid var(--phonepe-border);
        padding: 10px 16px;
        box-shadow: 0 -8px 25px rgba(0,0,0,0.1);
        z-index: 9999;
    }

    .bottom-bar-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 600px;
        margin: 0 auto;
    }

    .phonepe-pay-btn {
        flex: 1;
        background: linear-gradient(135deg, var(--phonepe-purple) 0%, #4c1d95 100%);
        color: #ffffff !important;
        text-align: center;
        padding: 13px;
        border-radius: 14px;
        font-weight: 800;
        font-size: 0.95rem;
        text-decoration: none !important;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 15px rgba(95, 37, 159, 0.3);
    }

    .phonepe-wa-btn {
        width: 48px;
        height: 48px;
        background: #25D366;
        color: #ffffff !important;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        text-decoration: none !important;
        flex-shrink: 0;
    }

    @media (max-width: 850px) {
        .phonepe-grid-layout { grid-template-columns: 1fr; }
        .phonepe-sticky-bottom { display: block; }
        .phonepe-app-header { margin-top: -10px; }
    }

    @media (max-width: 480px) {
        .phonepe-services-grid { grid-template-columns: repeat(4, 1fr); gap: 6px; }
        .service-tile { padding: 10px 4px; }
        .service-tile-icon { width: 34px; height: 34px; font-size: 0.95rem; }
        .service-tile-label { font-size: 0.65rem; }
        .service-tile-val { font-size: 0.72rem; }
        .phonepe-main-title { font-size: 1.15rem; }
    }
</style>

<div class="phonepe-wrapper">

    <!-- 📱 PhonePe Style Header Bar -->
    <div class="phonepe-app-header">
        <div class="phonepe-nav-row">
            <a href="schemes.php" class="phonepe-back-btn">
                <i class="fas fa-arrow-left"></i> वापस जाएं
            </a>
            <div class="phonepe-header-actions">
                <a href="https://api.whatsapp.com/send?text=<?php echo $share_text; ?>" target="_blank" class="phonepe-icon-circle">
                    <i class="fab fa-whatsapp" style="color:#25D366;"></i>
                </a>
                <button onclick="copySchemeUrl()" class="phonepe-icon-circle" style="border:none; cursor:pointer;">
                    <i class="fas fa-share-alt"></i>
                </button>
            </div>
        </div>

        <span class="phonepe-title-badge">
            <i class="fas fa-check-circle" style="color:#6ee7b7;"></i> वेरीफाइड योजना
        </span>

        <h1 class="phonepe-main-title"><?php echo $title; ?></h1>

        <div class="phonepe-meta-strip">
            <span><i class="far fa-calendar-alt"></i> पोस्ट तारीख: <?php echo $post_date; ?></span>
            <?php if($last_date !== 'N/A'): ?>
                <span><i class="fas fa-hourglass-end"></i> अंतिम तारीख: <?php echo $last_date; ?></span>
            <?php endif; ?>
            <span><i class="fas fa-map-marker-alt"></i> <?php echo $state; ?></span>
        </div>
    </div>

    <!-- ⚡ PhonePe Quick Action Grid (4 App Tiles) -->
    <div class="phonepe-services-grid">
        <div class="service-tile">
            <div class="service-tile-icon" style="background:#e0e7ff; color:#3730a3;">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="service-tile-label">राज्य</div>
            <div class="service-tile-val"><?php echo (mb_strlen($state) > 8) ? mb_substr($state, 0, 7).'..' : $state; ?></div>
        </div>

        <div class="service-tile">
            <div class="service-tile-icon" style="background:#fef3c7; color:#d97706;">
                <i class="fas fa-users"></i>
            </div>
            <div class="service-tile-label">पद / सीट</div>
            <div class="service-tile-val"><?php echo !empty($total_posts) ? $total_posts : (!empty($total_seats) ? $total_seats : 'सभी पात्र'); ?></div>
        </div>

        <div class="service-tile">
            <div class="service-tile-icon" style="background:#dcfce7; color:#15803d;">
                <i class="fas fa-file-signature"></i>
            </div>
            <div class="service-tile-label">प्रक्रिया</div>
            <div class="service-tile-val">ऑनलाइन</div>
        </div>

        <div class="service-tile">
            <div class="service-tile-icon" style="background:#f1f5f9; color:#475569;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="service-tile-label">स्थिति</div>
            <div class="service-tile-val" style="color:var(--phonepe-success);">सक्रिय</div>
        </div>
    </div>

    <!-- Top Ad -->
    <div class="ad-phonepe-slot">
        <span>ADVERTISEMENT</span>
        <div><!-- ADSENSE TOP BANNER CODE HERE --></div>
    </div>

    <div class="phonepe-grid-layout">
        
        <!-- 📝 Left Column: Main Details -->
        <main>
            <!-- Details Card -->
            <div class="phonepe-card">
                
                <?php if(!empty($short_desc)): ?>
                    <div class="phonepe-highlight-box">
                        <strong><i class="fas fa-bolt"></i> मुख्य विवरण:</strong> <?php echo $short_desc; ?>
                    </div>
                <?php endif; ?>

                <div class="phonepe-card-header">
                    <i class="fas fa-align-left"></i>
                    <h3>योजना का संपूर्ण विवरण</h3>
                </div>

                <div class="app-content-body">
                    
                    <!-- 1. मुख्य विवरण (Description) -->
                    <?php if(!empty($description)): ?>
                        <div class="scheme-section">
                            <?php echo $description; ?>
                        </div>
                    <?php endif; ?>

                    <!-- 2. विस्तृत जानकारी (Long Details) -->
                    <?php if(!empty($long_details)): ?>
                        <div class="scheme-section">
                            <div class="scheme-section-title">
                                <i class="fas fa-info-circle"></i> विस्तृत विवरण (Long Details)
                            </div>
                            <div><?php echo $long_details; ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- 3. पात्रता एवं मानदंड (Eligibility) -->
                    <?php if(!empty($eligibility)): ?>
                        <div class="scheme-section">
                            <div class="scheme-section-title">
                                <i class="fas fa-user-check"></i> पात्रता एवं मानदंड (Eligibility)
                            </div>
                            <div><?php echo $eligibility; ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- 4. आवेदन प्रक्रिया (Admission/Application Process) -->
                    <?php if(!empty($admission_process)): ?>
                        <div class="scheme-section">
                            <div class="scheme-section-title">
                                <i class="fas fa-tasks"></i> आवेदन/प्रवेश प्रक्रिया (Process)
                            </div>
                            <div><?php echo $admission_process; ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- 5. महत्वपूर्ण तिथियां (Important Dates) -->
                    <?php if(!empty($important_dates)): ?>
                        <div class="scheme-section">
                            <div class="scheme-section-title">
                                <i class="fas fa-calendar-alt"></i> महत्वपूर्ण तिथियां (Important Dates)
                            </div>
                            <div><?php echo $important_dates; ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- 6. आवेदन शुल्क (Application Fee) -->
                    <?php if(!empty($application_fee)): ?>
                        <div class="scheme-section">
                            <div class="scheme-section-title">
                                <i class="fas fa-rupee-sign"></i> आवेदन शुल्क (Application Fee)
                            </div>
                            <div><?php echo $application_fee; ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- 7. आवश्यक दस्तावेज (Required Documents) -->
                    <?php if(!empty($required_docs)): ?>
                        <div class="scheme-section">
                            <div class="scheme-section-title">
                                <i class="fas fa-file-alt"></i> आवश्यक दस्तावेज (Required Documents)
                            </div>
                            <div><?php echo $required_docs; ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- 8. अतिरिक्त विवरण (Full / Extra Details) -->
                    <?php if(!empty($full_details)): ?>
                        <div class="scheme-section">
                            <div class="scheme-section-title">
                                <i class="fas fa-plus-circle"></i> संपूर्ण विवरण (Full Details)
                            </div>
                            <div><?php echo $full_details; ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($extra_details)): ?>
                        <div class="scheme-section">
                            <div class="scheme-section-title">
                                <i class="fas fa-asterisk"></i> अतिरिक्त विवरण (Extra Details)
                            </div>
                            <div><?php echo $extra_details; ?></div>
                        </div>
                    <?php endif; ?>

                </div>

            </div>

            <!-- In-Article Ad Slot -->
            <div class="ad-phonepe-slot">
                <span>ADVERTISEMENT</span>
                <div><!-- ADSENSE IN-ARTICLE AD CODE HERE --></div>
            </div>

            <!-- 💳 Action Links (Apply, Notification, Result, Official Website) -->
            <div class="phonepe-card">
                <div class="phonepe-card-header">
                    <i class="fas fa-link"></i>
                    <h3>महत्वपूर्ण लिंक्स एवं आवेदन</h3>
                </div>

                <?php if(!empty($apply_link) && $apply_link !== '#'): ?>
                    <a href="<?php echo $apply_link; ?>" target="_blank" class="phonepe-action-tile">
                        <div class="tile-left-content">
                            <div class="tile-icon-box" style="background:#dcfce7; color:#16a34a;">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                            <div>
                                <span class="tile-title">ऑनलाइन आवेदन करें (Apply Online)</span>
                                <span class="tile-sub">आधिकारिक पोर्टल पर फॉर्म भरें</span>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right tile-arrow"></i>
                    </a>
                <?php endif; ?>

                <?php if(!empty($result_link) && $result_link !== '#'): ?>
                    <a href="<?php echo $result_link; ?>" target="_blank" class="phonepe-action-tile">
                        <div class="tile-left-content">
                            <div class="tile-icon-box" style="background:#fef3c7; color:#d97706;">
                                <i class="fas fa-poll-h"></i>
                            </div>
                            <div>
                                <span class="tile-title">रिजल्ट / स्टेटस देखें (Check Status)</span>
                                <span class="tile-sub">आवेदन या किस्त का स्टेटस चेक करें</span>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right tile-arrow"></i>
                    </a>
                <?php endif; ?>

                <?php if(!empty($notification_link) && $notification_link !== '#'): ?>
                    <a href="<?php echo $notification_link; ?>" target="_blank" class="phonepe-action-tile">
                        <div class="tile-left-content">
                            <div class="tile-icon-box" style="background:#e0e7ff; color:#2563eb;">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div>
                                <span class="tile-title">ऑफिशियल गाइडलाइन / नोटिफिकेशन (PDF)</span>
                                <span class="tile-sub">सरकारी निर्देश डाउनलोड करें</span>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right tile-arrow"></i>
                    </a>
                <?php endif; ?>

                <?php if(!empty($official_website) && $official_website !== '#'): ?>
                    <a href="<?php echo $official_website; ?>" target="_blank" class="phonepe-action-tile">
                        <div class="tile-left-content">
                            <div class="tile-icon-box" style="background:#f1f5f9; color:#475569;">
                                <i class="fas fa-globe"></i>
                            </div>
                            <div>
                                <span class="tile-title">आधिकारिक वेबसाइट (Official Website)</span>
                                <span class="tile-sub">विभाग की मुख्य वेबसाइट पर जाएं</span>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right tile-arrow"></i>
                    </a>
                <?php endif; ?>

            </div>

            <!-- Share Strip -->
            <div class="phonepe-share-strip">
                <span style="font-size:0.85rem; font-weight:700; color:var(--phonepe-subtext);">
                    <i class="fas fa-share-alt"></i> शेयर करें:
                </span>
                <div style="display:flex; gap:8px;">
                    <a href="https://api.whatsapp.com/send?text=<?php echo $share_text; ?>" target="_blank" class="phonepe-share-pill" style="background:#25D366;">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <button onclick="copySchemeUrl()" class="phonepe-share-pill" style="background:#475569; border:none; cursor:pointer;">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

        </main>

        <!-- 📬 Sidebar -->
        <aside>
            <div class="phonepe-card">
                <div class="phonepe-card-header">
                    <i class="fas fa-fire" style="color:#ef4444;"></i>
                    <h4>अन्य ट्रेंडिंग योजनाएं</h4>
                </div>

                <div style="display:flex; flex-direction:column; gap:12px;">
                    <?php 
                    if ($related_schemes && $related_schemes->num_rows > 0) {
                        while($rel = $related_schemes->fetch_assoc()) {
                            echo '<a href="scheme_details.php?id='.$rel['id'].'" style="text-decoration:none; color:var(--phonepe-text); padding-bottom:10px; border-bottom:1px dashed #e2e8f0; display:block;">';
                            echo '<div style="font-weight:700; font-size:0.88rem; line-height:1.35; margin-bottom:4px;">'.htmlspecialchars($rel['title']).'</div>';
                            echo '<div style="font-size:0.72rem; color:var(--phonepe-subtext);"><i class="far fa-clock"></i> '.date('d M, Y', strtotime($rel['post_date'])).'</div>';
                            echo '</a>';
                        }
                    } else {
                        echo '<div style="color:var(--phonepe-subtext); font-size:0.85rem;">कोई अन्य योजना उपलब्ध नहीं है।</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Sidebar Ad Slot -->
            <div class="phonepe-card" style="padding:10px; text-align:center;">
                <span style="font-size:0.62rem; color:#94a3b8; font-weight:800; text-transform:uppercase;">ADVERTISEMENT</span>
                <div style="min-height:220px; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:12px; display:flex; align-items:center; justify-content:center; margin-top:6px;">
                    <!-- ADSENSE SIDEBAR AD CODE HERE -->
                </div>
            </div>
        </aside>

    </div>

    <!-- Bottom Ad Banner -->
    <div class="ad-phonepe-slot" style="margin-top: 20px;">
        <span>ADVERTISEMENT</span>
        <div><!-- ADSENSE BOTTOM BANNER AD CODE HERE --></div>
    </div>

</div>

<!-- 📱 Native Floating Sticky Bottom Bar (PhonePe Style) -->
<div class="phonepe-sticky-bottom">
    <div class="bottom-bar-wrapper">
        <a href="<?php echo (!empty($apply_link) && $apply_link !== '#') ? $apply_link : '#'; ?>" target="_blank" class="phonepe-pay-btn">
            <i class="fas fa-bolt"></i> तुरंत ऑनलाइन आवेदन करें
        </a>
        <a href="https://api.whatsapp.com/send?text=<?php echo $share_text; ?>" target="_blank" class="phonepe-wa-btn" title="WhatsApp पर शेयर करें">
            <i class="fab fa-whatsapp"></i>
        </a>
    </div>
</div>

<script>
function copySchemeUrl() {
    navigator.clipboard.writeText(window.location.href);
    alert('योजना का लिंक कॉपी हो गया है!');
}
</script>

<?php 
$stmt->close();
$rel_stmt->close();
include 'includes/footer.php'; 
?>