<?php
include 'includes/db_connect.php';
include 'includes/header.php'; 

// 🆔 URL से सुरक्षित तरीके से ID प्राप्त करें
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    echo "<script>window.location.href='result.php';</script>";
    exit();
}

// 📊 1. डेटाबेस से परिणाम का पूरा डायनामिक डेटा निकालें
$stmt = $conn->prepare("SELECT title, category, state, total_posts, result_link, official_website, important_dates, short_desc, long_details, description, post_date FROM vacancies WHERE id = ? AND category = 'result' AND status = 'live'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div style='text-align:center; padding:100px 20px; font-family:sans-serif;'>
            <h2>⚠️ यह परिणाम उपलब्ध नहीं है या समीक्षा के अधीन है।</h2>
            <br><a href='result.php' style='padding:10px 20px; background:#0f172a; color:#fff; text-decoration:none; border-radius:8px;'>मुख्य पेज पर जाएं</a>
          </div>";
    include 'includes/footer.php';
    exit();
}

$row = $result->fetch_assoc();

// फ़ील्ड्स को सुरक्षित हिंदी वेरिएबल्स में सेट करें
$title = htmlspecialchars($row["title"] ?? '');
$state = !empty($row["state"]) ? htmlspecialchars($row["state"]) : "अखिल भारतीय (All India)";
$total_posts = !empty($row["total_posts"]) ? htmlspecialchars($row["total_posts"]) : "विभिन्न पद";
$result_link = !empty($row["result_link"]) ? trim($row["result_link"]) : "";
$official_website = !empty($row["official_website"]) ? trim($row["official_website"]) : "";
$important_dates = !empty($row["important_dates"]) ? htmlspecialchars($row["important_dates"]) : "घोषित किया जा चुका है";

// संक्षिप्त विवरण (Overview) Fallback
$default_overview = "विभाग द्वारा " . $title . " का परीक्षा परिणाम आधिकारिक रूप से घोषित कर दिया गया है। जिन उम्मीदवारों ने इस परीक्षा में भाग लिया था, वे नीचे दिए गए डायरेक्ट लिंक के माध्यम से अपना स्कोरकार्ड, कट-ऑफ मार्क्स और मेरिट लिस्ट चेक कर सकते हैं।";
$short_desc = !empty($row["short_desc"]) ? htmlspecialchars($row["short_desc"]) : $default_overview;

// विस्तृत विवरण और स्टेप्स (CKEditor HTML)
$long_details = !empty($row["long_details"]) ? $row["long_details"] : "";
$description_steps = !empty($row["description"]) ? $row["description"] : "";

$post_date = date('d F, Y', strtotime($row["post_date"]));

// 🔗 URL व SEO मटीरियल
$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$meta_desc = htmlspecialchars(mb_strimwidth(strip_tags($short_desc), 0, 160, "..."));

// ❓ डायनामिक FAQ डेटा
$faq_q1 = $title . " का रिजल्ट कब जारी हुआ?";
$faq_a1 = "यह परिणाम आधिकारिक रूप से " . $post_date . " को जारी किया गया है।";

$faq_q2 = "मैं अपना परीक्षा परिणाम कैसे चेक कर सकता हूँ?";
$faq_a2 = "आप ऊपर दिए गए 'परिणाम डाउनलोड करें' बटन पर क्लिक करके अपना रोल नंबर और जन्म तिथि दर्ज करके रिजल्ट देख सकते हैं।";

$faq_q3 = "क्या रिजल्ट के साथ कट-ऑफ और मेरिट लिस्ट भी जारी हुई है?";
$faq_a3 = "हाँ, विभाग द्वारा परिणाम के साथ वर्गवार कट-ऑफ और सफल अभ्यर्थियों की मेरिट सूची भी जारी की जाती है।";
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    
    <!-- 🎯 SEO & Google AdSense Meta Tags -->
    <title><?php echo $title; ?> - परीक्षा परिणाम और स्कोरकार्ड</title>
    <meta name="description" content="<?php echo $meta_desc; ?>">
    <link rel="canonical" href="<?php echo $actual_link; ?>">

    <!-- Open Graph (Social Sharing) -->
    <meta property="og:title" content="<?php echo $title; ?>">
    <meta property="og:description" content="<?php echo $meta_desc; ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo $actual_link; ?>">

    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- 🤖 Google Structured Data (NewsArticle & FAQ Schema) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "NewsArticle",
          "headline": "<?php echo addslashes($title); ?>",
          "datePublished": "<?php echo date('c', strtotime($row["post_date"])); ?>",
          "description": "<?php echo addslashes($meta_desc); ?>",
          "mainEntityOfPage": "<?php echo $actual_link; ?>"
        },
        {
          "@type": "FAQPage",
          "mainEntity": [
            {
              "@type": "Question",
              "name": "<?php echo addslashes($faq_q1); ?>",
              "acceptedAnswer": { "@type": "Answer", "text": "<?php echo addslashes($faq_a1); ?>" }
            },
            {
              "@type": "Question",
              "name": "<?php echo addslashes($faq_q2); ?>",
              "acceptedAnswer": { "@type": "Answer", "text": "<?php echo addslashes($faq_a2); ?>" }
            },
            {
              "@type": "Question",
              "name": "<?php echo addslashes($faq_q3); ?>",
              "acceptedAnswer": { "@type": "Answer", "text": "<?php echo addslashes($faq_a3); ?>" }
            }
          ]
        }
      ]
    }
    </script>

    <style>
        :root {
            --neon-amber: #f59e0b;
            --neon-orange: #ea580c;
            --text-dark: #0f172a;
            --phonepe-purple: #5f259f;
            --phonepe-dark: #391061;
            --phonepe-light: #fdfaff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding-bottom: 70px; /* मोबाइल बॉटम बार के लिए स्पेस */
            color: var(--text-dark);
            overflow-x: hidden;
        }

        .details-wrapper {
            max-width: 1250px;
            margin: 25px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        /* 👑 हेडर कार्ड - (ओरिजनल नो-चेंज) */
        .details-header-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 24px;
            padding: 30px;
            color: white;
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            border-bottom: 5px solid var(--neon-amber);
            grid-column: 1 / -1;
            word-wrap: break-word;
        }
        .details-header-card h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2.1rem;
            line-height: 1.4;
            margin: 0 0 18px 0;
            background: linear-gradient(135deg, #ffe066 0%, var(--neon-amber) 60%, #ff5500 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }

        /* इन्फो कैप्सूल */
        .info-capsule-bar {
            display: flex;
            gap: 12px;
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
        .capsule i { color: var(--neon-amber); }

        /* 📱⚡ टॉप ऐप-स्टाइल क्विक एक्शन कार्ड (Top Quick Action Hero Card) */
        .top-app-hero-card {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, #ffffff 0%, #fdfaff 100%);
            border: 2px solid #e9d5ff;
            border-radius: 20px;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            box-shadow: 0 8px 25px rgba(95, 37, 159, 0.08);
            position: relative;
            overflow: hidden;
        }
        .top-app-hero-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 6px;
            background: linear-gradient(180deg, var(--phonepe-purple), var(--neon-amber));
        }
        .hero-action-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .hero-badge-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--phonepe-purple), var(--phonepe-dark));
            color: #fff;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 6px 15px rgba(95, 37, 159, 0.3);
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

        /* 💜 PhonePe स्टाइल स्टाइलिश बटन */
        .phonepe-pulse-btn {
            background: linear-gradient(135deg, #5f259f 0%, #391061 100%);
            color: #ffffff !important;
            border: none;
            padding: 14px 28px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 0.98rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 22px rgba(95, 37, 159, 0.38);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            letter-spacing: 0.3px;
            white-space: nowrap;
            position: relative;
        }
        .phonepe-pulse-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 28px rgba(95, 37, 159, 0.55);
            background: linear-gradient(135deg, #6e2cb8 0%, #48157a 100%);
        }
        .phonepe-pulse-btn:active {
            transform: scale(0.97);
        }
        .phonepe-btn-blue {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            box-shadow: 0 6px 18px rgba(2, 132, 199, 0.35);
        }

        /* मुख्य कंटेंट पैनल */
        .main-content-panel {
            background: #ffffff;
            border-radius: 24px;
            padding: 30px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.02);
            min-width: 0;
        }
        
        .section-heading {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-dark);
            margin: 25px 0 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px dashed #e2e8f0;
            padding-bottom: 8px;
        }
        .section-heading i { color: var(--neon-orange); }

        .desc-text {
            font-size: 0.98rem;
            line-height: 1.8;
            color: #334155;
            word-wrap: break-word;
        }

        /* 📊 एडवांस डायनामिक टेबल */
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
            word-break: break-word;
        }
        .styled-data-table th {
            background-color: #f8fafc;
            color: var(--text-dark);
            font-weight: 700;
            width: 38%;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.95rem;
        }
        .styled-data-table td {
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            font-weight: 600;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .styled-data-table tr:last-child th,
        .styled-data-table tr:last-child td {
            border-bottom: none;
        }

        /* 💜 लिंक्स हब */
        .download-links-hub {
            background: var(--phonepe-light);
            border: 2px solid #e9d5ff;
            border-radius: 20px;
            padding: 20px;
            margin: 25px 0;
            box-shadow: 0 4px 20px rgba(95, 37, 159, 0.05);
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
        
        .link-label {
            font-weight: 700;
            font-size: 0.98rem;
            color: #1e293b;
        }

        /* FAQ Box Style */
        .faq-box {
            background: #f8fafc;
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }
        .faq-question {
            font-weight: 700;
            font-size: 1.02rem;
            color: #0f172a;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .faq-question i { color: var(--neon-amber); }
        .faq-answer {
            font-size: 0.95rem;
            color: #475569;
            line-height: 1.6;
            padding-left: 28px;
        }

        /* CKEditor Format Support */
        .ck-content {
            font-size: 1rem;
            line-height: 1.7;
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

        .ad-slot {
            background: #f1f5f9;
            border: 2px dashed #cbd5e1;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
            color: #94a3b8;
            font-size: 0.8rem;
            font-weight: 700;
            border-radius: 16px;
        }

        /* साइडबार */
        .sidebar-panel {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        .sidebar-widget {
            background: #ffffff;
            border-radius: 20px;
            padding: 22px;
            border: 1px solid #e2e8f0;
        }
        .widget-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0 0 15px 0;
            color: var(--text-dark);
            border-left: 4px solid var(--neon-orange);
            padding-left: 10px;
        }

        .share-grid { display: grid; grid-template-columns: 1fr; gap: 10px; }
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
        }
        .wa { background: #25D366; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.2); }
        .tg { background: #0088cc; box-shadow: 0 4px 12px rgba(0, 136, 204, 0.2); }

        .quick-links-list { list-style: none; padding: 0; margin: 0; }
        .quick-links-list li { border-bottom: 1px solid #f1f5f9; padding: 10px 0; }
        .quick-links-list li:last-child { border-bottom: none; }
        .quick-links-list a { color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; }

        /* 📱 मोबाइल ऐप बॉटम स्टिकी एक्शन बार */
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

        /* 📱📱 मोबाइल व्यू रेस्पॉन्सिव CSS */
        @media (max-width: 900px) {
            body {
                padding-bottom: 75px;
            }
            .details-wrapper { 
                grid-template-columns: 1fr; 
                padding: 0 12px;
                margin: 12px auto;
                gap: 15px;
            }
            .details-header-card h1 {
                font-size: 1.4rem;
            }
            
            /* टॉप हीरो कार्ड मोबाइल ऐप लुक */
            .top-app-hero-card {
                flex-direction: column;
                text-align: center;
                padding: 18px 15px;
                gap: 15px;
            }
            .hero-action-info {
                flex-direction: column;
                gap: 8px;
            }
            .phonepe-pulse-btn {
                width: 100%;
                font-size: 1rem;
                padding: 12px 20px;
            }

            .main-content-panel {
                padding: 18px 14px;
                border-radius: 18px;
            }

            /* मोबाइल में तालिका को कार्ड्स में बदलना */
            .styled-data-table, 
            .styled-data-table tbody, 
            .styled-data-table tr, 
            .styled-data-table th, 
            .styled-data-table td {
                display: block;
                width: 100%;
            }
            .styled-data-table {
                border: none;
                background: transparent;
            }
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
                color: var(--phonepe-purple);
                font-size: 0.88rem;
            }
            .styled-data-table td {
                border-bottom: none;
                padding: 6px 0 0 0;
                color: #1e293b;
                font-size: 0.95rem;
            }

            .link-row { 
                flex-direction: column; 
                gap: 10px; 
                align-items: stretch; 
            }
            .faq-answer {
                padding-left: 0;
            }

            /* मोबाइल में बॉटम स्टिकी बार सक्षम करें */
            .mobile-app-bottom-bar {
                display: block;
            }
        }
    </style>
</head>
<body>

<div class="details-wrapper">

    <!-- 👑 मुख्य हेडर (ओरिजनल नो-चेंज) -->
    <div class="details-header-card">
        <h1><?php echo $title; ?></h1>
        <div class="info-capsule-bar">
            <div class="capsule"><i class="fa-solid fa-folder-open"></i> श्रेणी: परीक्षा परिणाम</div>
            <div class="capsule"><i class="fa-solid fa-map-marker-alt"></i> राज्य: <?php echo $state; ?></div>
            <div class="capsule"><i class="fa-solid fa-calendar-day"></i> जारी तिथि: <?php echo $post_date; ?></div>
            <div class="capsule"><i class="fa-solid fa-circle-check" style="color:#22c55e;"></i> स्थिति: परिणाम घोषित</div>
        </div>
    </div>

    <!-- 📱⚡ एडवांस ऐप-स्टाइल टॉप क्विक एक्शन कार्ड (Top Result Action Bar) -->
    <div class="top-app-hero-card">
        <div class="hero-action-info">
            <div class="hero-badge-icon">
                <i class="fa-solid fa-trophy"></i>
            </div>
            <div class="hero-action-text">
                <h3>परीक्षा परिणाम घोषित हो चुका है!</h3>
                <p>रोल नंबर या लॉगिन क्रेडेंशियल दर्ज करके तुरंत स्कोरकार्ड देखें।</p>
            </div>
        </div>
        <div>
            <?php if (!empty($result_link)): ?>
                <a href="<?php echo $result_link; ?>" target="_blank" rel="nofollow noopener" class="phonepe-pulse-btn">
                    <i class="fa-solid fa-bolt" style="color:var(--neon-amber);"></i> डायरेक्ट रिजल्ट चेक करें
                </a>
            <?php else: ?>
                <a href="#downloadSection" class="phonepe-pulse-btn" style="background:#64748b; box-shadow:none;">
                    <i class="fa-solid fa-clock"></i> लिंक शीघ्र सक्रिय होगा
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 📝 बायाँ मुख्य कंटेंट एरिया -->
    <main class="main-content-panel">
        
        <!-- विज्ञापन स्थान (Ad Slot Top) -->
        <div class="ad-slot"><i class="fa-solid fa-rectangle-ad"></i> विज्ञापन स्थान (Google AdSense)</div>

        <!-- 1. संक्षिप्त विवरण (Overview) -->
        <div class="section-heading">
            <i class="fa-solid fa-circle-info"></i> संक्षिप्त विवरण (Overview)
        </div>
        <p class="desc-text"><?php echo nl2br($short_desc); ?></p>

        <!-- 2. महत्वपूर्ण तालिका (Dynamic & Mobile Stacked) -->
        <div class="section-heading">
            <i class="fa-solid fa-table-list"></i> महत्वपूर्ण जानकारी तालिका
        </div>
        
        <table class="styled-data-table">
            <tr>
                <th><i class="fa-solid fa-graduation-cap" style="color:var(--phonepe-purple); margin-right:6px;"></i> परीक्षा / भर्ती का नाम</th>
                <td><strong><?php echo $title; ?></strong></td>
            </tr>
            <tr>
                <th><i class="fa-solid fa-map-location-dot" style="color:var(--phonepe-purple); margin-right:6px;"></i> संबंधित राज्य / क्षेत्र</th>
                <td><?php echo $state; ?></td>
            </tr>
            <tr>
                <th><i class="fa-solid fa-users" style="color:var(--phonepe-purple); margin-right:6px;"></i> कुल पद (Total Vacancies)</th>
                <td><span style="background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-weight:700; color:#0f172a;"><?php echo $total_posts; ?></span></td>
            </tr>
            <tr>
                <th><i class="fa-solid fa-calendar-check" style="color:var(--phonepe-purple); margin-right:6px;"></i> परिणाम जारी होने की तिथि</th>
                <td><?php echo $post_date; ?></td>
            </tr>
            <tr>
                <th><i class="fa-solid fa-clock" style="color:var(--phonepe-purple); margin-right:6px;"></i> परीक्षा / महत्वपूर्ण तिथियां</th>
                <td><?php echo nl2br($important_dates); ?></td>
            </tr>
        </table>

        <!-- 3. महत्वपूर्ण डाउनलोड लिंक्स (PhonePe Dynamic Hub) -->
        <div class="section-heading" id="downloadSection">
            <i class="fa-solid fa-cloud-arrow-down"></i> महत्वपूर्ण डाउनलोड लिंक्स
        </div>
        
        <div class="download-links-hub">
            <?php if (!empty($result_link)): ?>
                <div class="link-row">
                    <span class="link-label"><i class="fa-solid fa-trophy" style="color:var(--neon-amber);"></i> परीक्षा परिणाम डाउनलोड करें (Direct Link)</span>
                    <a href="<?php echo $result_link; ?>" target="_blank" rel="nofollow noopener" class="phonepe-pulse-btn">
                        <i class="fa-solid fa-bolt"></i> चेक रिजल्ट (Check Result)
                    </a>
                </div>
            <?php else: ?>
                <div class="link-row">
                    <span class="link-label" style="color:#64748b;"><i class="fa-solid fa-clock"></i> परिणाम लिंक शीघ्र ही सक्रिय किया जाएगा।</span>
                </div>
            <?php endif; ?>

            <?php if (!empty($official_website)): ?>
                <div class="link-row">
                    <span class="link-label"><i class="fa-solid fa-globe" style="color:#0284c7;"></i> आधिकारिक वेबसाइट (Official Portal)</span>
                    <a href="<?php echo $official_website; ?>" target="_blank" rel="nofollow noopener" class="phonepe-pulse-btn phonepe-btn-blue">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> वेबसाइट पर जाएं
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- 4. विस्तृत विवरण -->
        <?php if (!empty($long_details)): ?>
            <div class="section-heading">
                <i class="fa-solid fa-file-lines"></i> परिणाम एवं कट-ऑफ की विस्तृत जानकारी
            </div>
            <div class="ck-content">
                <?php echo $long_details; ?>
            </div>
        <?php endif; ?>

        <!-- 5. परिणाम कैसे देखें? -->
        <div class="section-heading">
            <i class="fa-solid fa-circle-question"></i> परिणाम कैसे डाउनलोड करें? (स्टेप-बाय-स्टेप)
        </div>
        
        <?php if (!empty($description_steps)): ?>
            <div class="ck-content">
                <?php echo $description_steps; ?>
            </div>
        <?php else: ?>
            <div class="desc-text">
                <ol style="padding-left:20px; line-height: 2;">
                    <li>सबसे पहले ऊपर टेबल में दिए गए <strong>"परिणाम डाउनलोड करें"</strong> लिंक पर क्लिक करें।</li>
                    <li>आपके सामने विभाग का आधिकारिक लॉगिन/रिजल्ट पोर्टल खुल जाएगा।</li>
                    <li>यहाँ अपना <strong>रोल नंबर / रजिस्ट्रेशन नंबर</strong> तथा <strong>जन्म तिथि (Password)</strong> दर्ज करें।</li>
                    <li>कैप्चा कोड भरकर <strong>Submit / Search</strong> बटन पर क्लिक करें।</li>
                    <li>आपका रिजल्ट और स्कोरकार्ड स्क्रीन पर आ जाएगा, इसे पीडीएफ फॉर्मेट में सेव कर लें।</li>
                </ol>
            </div>
        <?php endif; ?>

        <!-- 6. FAQ Section -->
        <div class="section-heading" style="margin-top: 35px;">
            <i class="fa-solid fa-comments"></i> अक्सर पूछे जाने वाले प्रश्न (FAQ)
        </div>

        <div class="faq-box">
            <div class="faq-question">
                <i class="fa-solid fa-circle-question"></i> <?php echo $faq_q1; ?>
            </div>
            <div class="faq-answer">
                <?php echo $faq_a1; ?>
            </div>
        </div>

        <div class="faq-box">
            <div class="faq-question">
                <i class="fa-solid fa-circle-question"></i> <?php echo $faq_q2; ?>
            </div>
            <div class="faq-answer">
                <?php echo $faq_a2; ?>
            </div>
        </div>

        <div class="faq-box">
            <div class="faq-question">
                <i class="fa-solid fa-circle-question"></i> <?php echo $faq_q3; ?>
            </div>
            <div class="faq-answer">
                <?php echo $faq_a3; ?>
            </div>
        </div>

        <!-- विज्ञापन स्थान (Ad Slot Bottom) -->
        <div class="ad-slot" style="margin-top: 30px;"><i class="fa-solid fa-rectangle-ad"></i> विज्ञापन स्थान (Google AdSense)</div>
    </main>

    <!-- 🗂️ दायाँ साइडबार पैनल -->
    <aside class="sidebar-panel">
        
        <!-- सोशल शेयरिंग -->
        <div class="sidebar-widget">
            <h4 class="widget-title">दोस्तों के साथ शेयर करें</h4>
            <div class="share-grid">
                <a href="https://api.whatsapp.com/send?text=<?php echo urlencode('🔥 ' . $title . ' का रिजल्ट जारी हो गया है! यहाँ से डाउनलोड करें: ' . $actual_link); ?>" target="_blank" class="sidebar-share-btn wa">
                    <i class="fab fa-whatsapp fa-lg"></i> WhatsApp पर शेयर करें
                </a>
                <a href="https://t.me/share/url?url=<?php echo urlencode($actual_link); ?>&text=<?php echo urlencode($title); ?>" target="_blank" class="sidebar-share-btn tg">
                    <i class="fa-brands fa-telegram fa-lg"></i> Telegram पर भेजें
                </a>
            </div>
        </div>

        <!-- त्वरित नेविगेशन -->
        <div class="sidebar-widget">
            <h4 class="widget-title">त्वरित नेविगेशन</h4>
            <ul class="quick-links-list">
                <li><a href="index.php"><i class="fa-solid fa-house"></i> मुख्य पृष्ठ (Home)</a></li>
                <li><a href="result.php"><i class="fa-solid fa-trophy"></i> सभी परीक्षा परिणाम</a></li>
                <li><a href="admit_card.php"><i class="fa-solid fa-ticket"></i> प्रवेश पत्र (Admit Card)</a></li>
                <li><a href="latest_jobs.php"><i class="fa-solid fa-briefcase"></i> नई सरकारी भर्तियां</a></li>
            </ul>
        </div>

        <!-- साइडबार विज्ञापन -->
        <div class="sidebar-widget" style="background: #f1f5f9; text-align: center; padding: 30px 10px; color:#94a3b8; font-weight:700; border: 2px dashed #cbd5e1;">
            <i class="fa-solid fa-square-ad"></i><br>SIDEBAR AD
        </div>
    </aside>

</div>

<!-- 📱 मोबाइल बॉटम ऐप-स्टाइल स्टिकी बार (Mobile App Floating Bottom Bar) -->
<div class="mobile-app-bottom-bar">
    <?php if (!empty($result_link)): ?>
        <a href="<?php echo $result_link; ?>" target="_blank" rel="nofollow noopener" class="phonepe-pulse-btn" style="width:100%; font-size:0.95rem;">
            <i class="fa-solid fa-bolt" style="color:var(--neon-amber);"></i> चेक रिजल्ट (Direct Link)
        </a>
    <?php else: ?>
        <a href="#downloadSection" class="phonepe-pulse-btn" style="width:100%; background:#64748b; font-size:0.95rem;">
            <i class="fa-solid fa-clock"></i> लिंक जल्द सक्रिय होगा
        </a>
    <?php endif; ?>
</div>

<?php 
if (isset($conn)) {
    $conn->close();
}
include 'includes/footer.php'; 
?>
</body>
</html>