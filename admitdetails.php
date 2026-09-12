<?php
include 'includes/db_connect.php';
include 'includes/header.php';

// 1. Valid ID Check
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admitcards.php");
    exit;
}

$id = (int)$_GET['id'];

// 2. Fetch Admit Card Record securely
$stmt = $conn->prepare("SELECT * FROM vacancies WHERE id = ? AND category = 'admit_card' AND status = 'live'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div style='text-align:center; padding: 80px 20px; font-family:sans-serif;'>
            <h2 style='color:#e11d48; margin-bottom:15px;'>⚠️ क्षमा करें, यह एडमिट कार्ड उपलब्ध नहीं है या हटा दिया गया है।</h2>
            <a href='admitcards.php' style='display:inline-block; margin-top:10px; padding:12px 24px; background:#0f172a; color:#fff; border-radius:10px; text-decoration:none; font-weight:700;'>वापस एडमिट कार्ड सूची में जाएं</a>
          </div>";
    include 'includes/footer.php';
    exit;
}

$data = $result->fetch_assoc();
$title = htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8');
$post_date = date('d F, Y', strtotime($data['post_date']));
$state = !empty($data['state']) ? htmlspecialchars($data['state'], ENT_QUOTES, 'UTF-8') : 'अखिल भारतीय (All India)';
$total_posts = !empty($data['total_posts']) ? htmlspecialchars($data['total_posts'], ENT_QUOTES, 'UTF-8') : 'नोटिफिकेशन देखें';
$application_fee = !empty($data['application_fee']) ? htmlspecialchars($data['application_fee'], ENT_QUOTES, 'UTF-8') : 'लागू नहीं / वर्ग अनुसार';

$apply_link = !empty($data['apply_link']) ? trim($data['apply_link']) : '';
$result_link = !empty($data['result_link']) ? trim($data['result_link']) : '';
$official_website = !empty($data['official_website']) ? trim($data['official_website']) : '';

// Canonical / Share URLs
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$current_url = $scheme . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$share_text = urlencode("🔥 " . $title . " - हॉल टिकट जारी! डायरेक्ट डाउनलोड करें: " . $current_url);
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    
    <!-- Dynamic SEO Meta Tags -->
    <title><?php echo $title; ?> Admit Card - हॉल टिकट डाउनलोड करें</title>
    <meta name="description" content="<?php echo !empty($data['short_desc']) ? htmlspecialchars(strip_tags($data['short_desc']), ENT_QUOTES, 'UTF-8') : $title . ' एडमिट कार्ड जारी। डाउनलोड प्रक्रिया, परीक्षा तिथि और डायरेक्ट लिंक यहाँ देखें।'; ?>">
    <link rel="canonical" href="<?php echo $current_url; ?>" />

    <!-- Open Graph (Social Sharing) -->
    <meta property="og:title" content="<?php echo $title; ?> Admit Card">
    <meta property="og:description" content="<?php echo !empty($data['short_desc']) ? htmlspecialchars(strip_tags($data['short_desc']), ENT_QUOTES, 'UTF-8') : $title; ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo $current_url; ?>">

    <!-- Google Fonts & FontAwesome Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Schema Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "NewsArticle",
      "headline": "<?php echo addslashes($title); ?> Admit Card",
      "datePublished": "<?php echo date('c', strtotime($data['post_date'])); ?>",
      "description": "<?php echo addslashes(strip_tags($data['short_desc'] ?? $title)); ?>",
      "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "<?php echo $current_url; ?>"
      }
    }
    </script>

    <style>
        :root {
            --sky-blue: #0284c7;
            --sky-dark: #0369a1;
            --phonepe-purple: #5f259f;
            --phonepe-dark: #391061;
            --neon-amber: #f59e0b;
            --text-dark: #0f172a;
            --bg-light: #f8fafc;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding-bottom: 75px; /* Mobile Sticky Bar Space */
            color: var(--text-dark);
            overflow-x: hidden;
        }

        .details-wrapper {
            max-width: 1250px;
            margin: 25px auto;
            padding: 0 18px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        /* SEO Breadcrumbs */
        .breadcrumb-nav {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
            color: #64748b;
            font-weight: 600;
        }
        .breadcrumb-nav a { color: var(--sky-blue); text-decoration: none; }
        .breadcrumb-nav a:hover { text-decoration: underline; }

        /* Top Banner Hero Header */
        .details-header-card {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 24px;
            padding: 28px 32px;
            color: #ffffff;
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            border-bottom: 5px solid var(--sky-blue);
            position: relative;
        }
        .header-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
            border: 1px solid rgba(74, 222, 128, 0.3);
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 14px;
        }
        .pulse-dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            animation: pulse-ring 1.6s infinite;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        .details-header-card h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2rem;
            line-height: 1.35;
            margin: 0 0 18px 0;
            background: linear-gradient(135deg, #ffffff 0%, #bae6fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }

        .info-capsule-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .capsule {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .capsule i { color: var(--sky-blue); }

        /* Top Mobile & Desktop Hero Action Card */
        .top-app-hero-card {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
            border: 2px solid #bae6fd;
            border-radius: 20px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.08);
            position: relative;
        }
        .hero-action-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .hero-badge-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, var(--sky-blue), var(--sky-dark));
            color: #fff;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 6px 15px rgba(2, 132, 199, 0.3);
            flex-shrink: 0;
        }
        .hero-action-text h3 {
            margin: 0 0 4px 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
        }
        .hero-action-text p {
            margin: 0;
            font-size: 0.88rem;
            color: #64748b;
            font-weight: 600;
        }

        /* 3D App Buttons */
        .app-btn-primary {
            background: linear-gradient(135deg, var(--sky-blue) 0%, var(--sky-dark) 100%);
            color: #ffffff !important;
            border: none;
            padding: 13px 26px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(2, 132, 199, 0.35);
            transition: all 0.25s ease;
            text-decoration: none;
            white-space: nowrap;
        }
        .app-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(2, 132, 199, 0.5);
        }
        .app-btn-sub {
            background: #334155;
            color: #ffffff !important;
            border: none;
            padding: 11px 22px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .app-btn-sub:hover { background: #0f172a; }

        /* Main Content Layout */
        .main-content-panel {
            background: #ffffff;
            border-radius: 24px;
            padding: 28px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.02);
            min-width: 0;
        }

        .section-heading {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-dark);
            margin: 24px 0 16px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px dashed #e2e8f0;
            padding-bottom: 8px;
        }
        .section-heading i { color: var(--sky-blue); }

        .overview-box {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            border-left: 5px solid var(--sky-blue);
            padding: 18px;
            border-radius: 14px;
            font-size: 0.98rem;
            color: #0c4a6e;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        /* Advance Dynamic Table / Card Grid */
        .styled-data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 18px 0;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            background: #ffffff;
        }
        .styled-data-table th, .styled-data-table td {
            padding: 14px 18px;
            text-align: left;
        }
        .styled-data-table th {
            background-color: #f8fafc;
            color: var(--text-dark);
            font-weight: 700;
            width: 38%;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.92rem;
        }
        .styled-data-table td {
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .styled-data-table tr:last-child th,
        .styled-data-table tr:last-child td { border-bottom: none; }

        /* Links Download Hub */
        .download-links-hub {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            padding: 20px;
            margin: 22px 0;
        }
        .link-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px dashed #cbd5e1;
            gap: 15px;
        }
        .link-row:last-child { border-bottom: none; }
        .link-label { font-weight: 700; font-size: 0.98rem; color: #1e293b; }

        /* CKEditor Sections */
        .ck-content {
            font-size: 1rem;
            line-height: 1.8;
            color: #334155;
            overflow-x: auto;
        }
        .ck-content table {
            width: 100% !important;
            border-collapse: collapse;
            margin: 15px 0;
            display: block;
            overflow-x: auto;
        }
        .ck-content table td, .ck-content table th {
            border: 1px solid #cbd5e1;
            padding: 10px;
        }

        /* FAQ Section */
        .faq-box {
            background: #f8fafc;
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 14px;
            border: 1px solid #e2e8f0;
        }
        .faq-question {
            font-weight: 700;
            font-size: 1rem;
            color: #0f172a;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .faq-question i { color: var(--sky-blue); }
        .faq-answer {
            font-size: 0.94rem;
            color: #475569;
            line-height: 1.6;
            padding-left: 24px;
        }

        /* Ads Placeholders */
        .ad-slot {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            padding: 14px;
            text-align: center;
            margin: 20px 0;
            color: #94a3b8;
            font-size: 0.78rem;
            font-weight: 700;
            border-radius: 14px;
            text-transform: uppercase;
        }

        /* Sidebar Styling */
        .sidebar-panel {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }
        .sidebar-widget {
            background: #ffffff;
            border-radius: 20px;
            padding: 20px;
            border: 1px solid #e2e8f0;
        }
        .widget-title {
            font-size: 1.05rem;
            font-weight: 700;
            margin: 0 0 14px 0;
            color: var(--text-dark);
            border-left: 4px solid var(--sky-blue);
            padding-left: 10px;
        }
        .share-grid { display: flex; flex-direction: column; gap: 10px; }
        .sidebar-share-btn {
            padding: 12px;
            border-radius: 50px;
            color: white !important;
            text-decoration: none;
            text-align: center;
            font-weight: 700;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }
        .wa { background: #25D366; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.2); }
        .tg { background: #0088cc; box-shadow: 0 4px 12px rgba(0, 136, 204, 0.2); }
        .copy-btn { background: #64748b; box-shadow: 0 4px 12px rgba(100, 116, 139, 0.2); }

        .quick-links-list { list-style: none; padding: 0; margin: 0; }
        .quick-links-list li { border-bottom: 1px solid #f1f5f9; padding: 10px 0; }
        .quick-links-list li:last-child { border-bottom: none; }
        .quick-links-list a { color: #475569; text-decoration: none; font-weight: 600; font-size: 0.88rem; }
        .quick-links-list a:hover { color: var(--sky-blue); }

        /* Mobile App Bottom Floating Action Bar */
        .mobile-app-bottom-bar {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #ffffff;
            padding: 10px 16px;
            box-shadow: 0 -8px 25px rgba(0,0,0,0.12);
            z-index: 9999;
            border-top: 1px solid #e2e8f0;
        }

        /* 📱 Mobile Responsive View */
        @media (max-width: 900px) {
            body { padding-bottom: 75px; }
            .details-wrapper { grid-template-columns: 1fr; padding: 0 12px; margin: 15px auto; gap: 15px; }
            .details-header-card h1 { font-size: 1.45rem; }
            
            .top-app-hero-card {
                flex-direction: column;
                text-align: center;
                padding: 18px 14px;
            }
            .hero-action-info { flex-direction: column; gap: 8px; }
            .app-btn-primary { width: 100%; }

            .main-content-panel { padding: 18px 14px; border-radius: 18px; }

            /* Table Transformation into Mobile App Stacked Cards */
            .styled-data-table, .styled-data-table tbody, .styled-data-table tr, .styled-data-table th, .styled-data-table td {
                display: block; width: 100%;
            }
            .styled-data-table { border: none; background: transparent; }
            .styled-data-table tr {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                margin-bottom: 10px;
                padding: 12px 14px;
                box-shadow: 0 3px 10px rgba(15, 23, 42, 0.03);
            }
            .styled-data-table th {
                border-right: none;
                border-bottom: 1px dashed #e2e8f0;
                padding: 0 0 6px 0;
                width: 100%;
                background: transparent;
                color: var(--sky-blue);
                font-size: 0.85rem;
            }
            .styled-data-table td { border-bottom: none; padding: 6px 0 0 0; font-size: 0.92rem; }

            .link-row { flex-direction: column; gap: 10px; align-items: stretch; }
            .mobile-app-bottom-bar { display: block; }
        }
    </style>
</head>
<body>

<div class="details-wrapper">

    <!-- SEO Breadcrumbs -->
    <div class="breadcrumb-nav">
        <a href="index.php"><i class="fa-solid fa-house"></i> होम</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <a href="admit_card.php">एडमिट कार्ड</a>
        <i class="fa-solid fa-chevron-right text-xs"></i>
        <span>विवरण</span>
    </div>

    <!-- 👑 Top Dynamic Header Box -->
    <div class="details-header-card">
        <div class="header-status-badge">
            <span class="pulse-dot"></span> एडमिट कार्ड डाउनलोड हेतु उपलब्ध
        </div>
        <h1><?php echo $title; ?></h1>
        <div class="info-capsule-bar">
            <div class="capsule"><i class="fa-solid fa-folder-open"></i> श्रेणी: प्रवेश पत्र (Hall Ticket)</div>
            <div class="capsule"><i class="fa-solid fa-map-marker-alt"></i> राज्य: <?php echo $state; ?></div>
            <div class="capsule"><i class="fa-solid fa-calendar-day"></i> अपडेट तिथि: <?php echo $post_date; ?></div>
        </div>
    </div>

    <!-- 📱⚡ Mobile App-Style Top Hero Action Banner -->
    <div class="top-app-hero-card">
        <div class="hero-action-info">
            <div class="hero-badge-icon">
                <i class="fa-solid fa-id-card"></i>
            </div>
            <div class="hero-action-text">
                <h3>हॉल टिकट / एडमिट कार्ड जारी!</h3>
                <p>रोल नंबर या रजिस्ट्रेशन नंबर द्वारा तुरंत डाउनलोड करें।</p>
            </div>
        </div>
        <div>
            <?php if (!empty($apply_link)): ?>
                <a href="<?php echo $apply_link; ?>" target="_blank" rel="nofollow noopener" class="app-btn-primary">
                    <i class="fa-solid fa-bolt" style="color:var(--neon-amber);"></i> डायरेक्ट एडमिट कार्ड डाउनलोड करें
                </a>
            <?php else: ?>
                <a href="#downloadSection" class="app-btn-primary" style="background:#64748b; box-shadow:none;">
                    <i class="fa-solid fa-clock"></i> लिंक जल्द सक्रिय होगा
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 📝 Left Main Content Panel -->
    <main class="main-content-panel">
        
        <!-- Ad Slot Top -->
        <div class="ad-slot"><i class="fa-solid fa-rectangle-ad"></i> विज्ञापन स्थान (Google AdSense)</div>

        <!-- Short Description -->
        <?php if (!empty($data['short_desc'])): ?>
            <div class="section-heading">
                <i class="fa-solid fa-bullhorn"></i> संक्षेप में मुख्य विवरण
            </div>
            <div class="overview-box">
                <?php echo htmlspecialchars($data['short_desc'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <!-- Dynamic Information Table -->
        <div class="section-heading">
            <i class="fa-solid fa-list-check"></i> महत्वपूर्ण भर्ती एवं परीक्षा विवरण
        </div>
        
        <table class="styled-data-table">
            <tr>
                <th><i class="fa-solid fa-graduation-cap" style="color:var(--sky-blue); margin-right:6px;"></i> परीक्षा / भर्ती का नाम</th>
                <td><strong><?php echo $title; ?></strong></td>
            </tr>
            <tr>
                <th><i class="fa-solid fa-location-dot" style="color:var(--sky-blue); margin-right:6px;"></i> संबंधित राज्य / क्षेत्र</th>
                <td><?php echo $state; ?></td>
            </tr>
            <tr>
                <th><i class="fa-solid fa-users" style="color:var(--sky-blue); margin-right:6px;"></i> कुल पद (Total Vacancies)</th>
                <td><span style="background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-weight:700;"><?php echo $total_posts; ?></span></td>
            </tr>
            <tr>
                <th><i class="fa-solid fa-wallet" style="color:var(--sky-blue); margin-right:6px;"></i> आवेदन / प्रवेश शुल्क</th>
                <td><?php echo $application_fee; ?></td>
            </tr>
            <tr>
                <th><i class="fa-solid fa-calendar-check" style="color:var(--sky-blue); margin-right:6px;"></i> एडमिट कार्ड जारी तिथि</th>
                <td><?php echo $post_date; ?></td>
            </tr>
            <?php if (!empty($data['important_dates'])): ?>
            <tr>
                <th><i class="fa-solid fa-clock" style="color:var(--sky-blue); margin-right:6px;"></i> परीक्षा तिथि / तिथियां</th>
                <td><?php echo nl2br(htmlspecialchars($data['important_dates'], ENT_QUOTES, 'UTF-8')); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($data['eligibility'])): ?>
            <tr>
                <th><i class="fa-solid fa-key" style="color:var(--sky-blue); margin-right:6px;"></i> लॉगिन क्रेडेंशियल</th>
                <td><?php echo nl2br(htmlspecialchars($data['eligibility'], ENT_QUOTES, 'UTF-8')); ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <!-- Direct Link Hub (Download Center) -->
        <div class="section-heading" id="downloadSection">
            <i class="fa-solid fa-cloud-arrow-down"></i> डायरेक्ट डाउनलोड लिंक्स हब
        </div>
        
        <div class="download-links-hub">
            <!-- Main Admit Card Link -->
            <div class="link-row">
                <span class="link-label"><i class="fa-solid fa-id-card-clip" style="color:var(--sky-blue);"></i> डाउनलोड एडमिट कार्ड (Direct Link)</span>
                <?php if (!empty($apply_link)): ?>
                    <a href="<?php echo $apply_link; ?>" target="_blank" rel="nofollow noopener" class="app-btn-primary">
                        <i class="fa-solid fa-arrow-down-long"></i> Download Now
                    </a>
                <?php else: ?>
                    <span style="color:#64748b; font-weight:700; font-size:0.88rem;"><i class="fa-solid fa-clock"></i> शीघ्र उपलब्ध होगा</span>
                <?php endif; ?>
            </div>

            <!-- Exam Notice / City Intimation -->
            <?php if (!empty($result_link)): ?>
                <div class="link-row">
                    <span class="link-label"><i class="fa-solid fa-scroll" style="color:var(--neon-amber);"></i> परीक्षा नोटिस / एग्जाम सिटी स्लिप</span>
                    <a href="<?php echo $result_link; ?>" target="_blank" rel="nofollow noopener" class="app-btn-sub">
                        <i class="fa-solid fa-file-pdf"></i> नोटिस खोलें
                    </a>
                </div>
            <?php endif; ?>

            <!-- Official Website -->
            <?php if (!empty($official_website)): ?>
                <div class="link-row">
                    <span class="link-label"><i class="fa-solid fa-globe" style="color:#10b981;"></i> आधिकारिक पोर्टल (Official Portal)</span>
                    <a href="<?php echo $official_website; ?>" target="_blank" rel="nofollow noopener" class="app-btn-sub">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> वेबसाइट देखें
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- CKEditor Dynamic Details 1: Download Step-by-Step Guide -->
        <?php if (!empty($data['long_details'])): ?>
            <div class="section-heading">
                <i class="fa-solid fa-circle-info"></i> डाउनलोड करने का तरीका (Step-by-Step Guide)
            </div>
            <div class="ck-content">
                <?php echo $data['long_details']; ?>
            </div>
        <?php endif; ?>

        <!-- CKEditor Dynamic Details 2: Exam Instructions -->
        <?php if (!empty($data['description'])): ?>
            <div class="section-heading">
                <i class="fa-solid fa-triangle-exclamation" style="color:#eab308;"></i> परीक्षा केंद्र हेतु आवश्यक निर्देश
            </div>
            <div class="ck-content">
                <?php echo $data['description']; ?>
            </div>
        <?php endif; ?>

        <!-- CKEditor Dynamic Details 3: Shift / Exam Center Details -->
        <?php if (!empty($data['full_details'])): ?>
            <div class="section-heading">
                <i class="fa-solid fa-map-location-dot" style="color:#10b981;"></i> शिफ्ट समय व परीक्षा केंद्र जानकारी
            </div>
            <div class="ck-content">
                <?php echo $data['full_details']; ?>
            </div>
        <?php endif; ?>

        <!-- FAQs Section -->
        <div class="section-heading" style="margin-top: 30px;">
            <i class="fa-solid fa-comments"></i> अक्सर पूछे जाने वाले सवाल (FAQs)
        </div>

        <div class="faq-box">
            <div class="faq-question">
                <i class="fa-solid fa-circle-question"></i> Q1. <?php echo $title; ?> का एडमिट कार्ड कैसे डाउनलोड करें?
            </div>
            <div class="faq-answer">
                उत्तर: आप ऊपर तालिका में दिए गए <strong>"डायरेक्ट एडमिट कार्ड डाउनलोड करें"</strong> बटन पर क्लिक करें। फिर अपना रजिस्ट्रेशन नंबर/रोल नंबर और जन्मतिथि दर्ज करके हॉल टिकट सेव करें।
            </div>
        </div>

        <div class="faq-box">
            <div class="faq-question">
                <i class="fa-solid fa-circle-question"></i> Q2. परीक्षा केंद्र पर कौन-कौन से दस्तावेज ले जाने अनिवार्य हैं?
            </div>
            <div class="faq-answer">
                उत्तर: प्रवेश पत्र (Admit Card) का प्रिंटआउट, मूल फोटो पहचान पत्र (जैसे आधार कार्ड, वोटर ID, DL) तथा हाल ही में खिंचवाई गई 2 पासपोर्ट साइज फोटो।
            </div>
        </div>

        <!-- Ad Slot Bottom -->
        <div class="ad-slot" style="margin-top: 30px;"><i class="fa-solid fa-rectangle-ad"></i> विज्ञापन स्थान (Google AdSense)</div>
    </main>

    <!-- 🗂️ Right Sidebar Panel -->
    <aside class="sidebar-panel">
        
        <!-- Social Sharing Widget -->
        <div class="sidebar-widget">
            <h4 class="widget-title">सोशल मीडिया पर शेयर करें</h4>
            <div class="share-grid">
                <a href="https://api.whatsapp.com/send?text=<?php echo $share_text; ?>" target="_blank" class="sidebar-share-btn wa">
                    <i class="fab fa-whatsapp fa-lg"></i> WhatsApp पर भेजें
                </a>
                <a href="https://t.me/share/url?url=<?php echo urlencode($current_url); ?>&text=<?php echo urlencode($title); ?>" target="_blank" class="sidebar-share-btn tg">
                    <i class="fa-brands fa-telegram fa-lg"></i> Telegram ग्रुप में डालें
                </a>
                <button onclick="copyCurrentUrl()" class="sidebar-share-btn copy-btn">
                    <i class="fa-solid fa-link"></i> पेज लिंक कॉपी करें
                </button>
            </div>
        </div>

        <!-- Quick Navigation -->
        <div class="sidebar-widget">
            <h4 class="widget-title">त्वरित नेविगेशन</h4>
            <ul class="quick-links-list">
                <li><a href="index.php"><i class="fa-solid fa-house"></i> मुख्य पृष्ठ (Home)</a></li>
                <li><a href="admitcards.php"><i class="fa-solid fa-ticket"></i> सभी प्रवेश पत्र (Admit Cards)</a></li>
                <li><a href="result.php"><i class="fa-solid fa-trophy"></i> परीक्षा परिणाम (Results)</a></li>
                <li><a href="latest_jobs.php"><i class="fa-solid fa-briefcase"></i> नई सरकारी भर्तियां</a></li>
            </ul>
        </div>

        <!-- Sidebar Ad Slot -->
        <div class="sidebar-widget" style="background: #f8fafc; text-align: center; padding: 25px 10px; color:#94a3b8; font-weight:700; border: 2px dashed #cbd5e1;">
            <i class="fa-solid fa-square-ad"></i><br>SIDEBAR AD
        </div>
    </aside>

</div>

<!-- 📱 Mobile Floating Bottom Action Bar -->
<div class="mobile-app-bottom-bar">
    <?php if (!empty($apply_link)): ?>
        <a href="<?php echo $apply_link; ?>" target="_blank" rel="nofollow noopener" class="app-btn-primary" style="width:100%; font-size:0.95rem;">
            <i class="fa-solid fa-bolt" style="color:var(--neon-amber);"></i> एडमिट कार्ड डाउनलोड करें
        </a>
    <?php else: ?>
        <a href="#downloadSection" class="app-btn-primary" style="width:100%; background:#64748b; font-size:0.95rem;">
            <i class="fa-solid fa-clock"></i> लिंक जल्द सक्रिय होगा
        </a>
    <?php endif; ?>
</div>

<script>
function copyCurrentUrl() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        alert("पेज का लिंक सफलतापूर्वक कॉपी हो गया है!");
    }).catch(err => {
        console.error('Link copy failed', err);
    });
}
</script>

<?php 
$stmt->close();
if (isset($conn)) {
    $conn->close();
}
include 'includes/footer.php'; 
?>
</body>
</html>