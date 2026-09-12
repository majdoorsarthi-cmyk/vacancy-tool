<?php
// डेटाबेस कनेक्शन शामिल करें
include 'includes/db_connect.php'; 

// URL से ID प्राप्त करें
$vacancy_id = $_GET['id'] ?? null;
$post = null;
$error_message = '';

if (!$vacancy_id || !is_numeric($vacancy_id)) {
    $error_message = "❌ अमान्य पोस्ट ID। कृपया सही लिंक का उपयोग करें।";
} else {
    // डेटाबेस से जानकारी प्राप्त करें
    $sql = "SELECT * FROM vacancies WHERE id = ? AND category = 'result' AND status = 'live'";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        $error_message = "❌ तकनीकी समस्या: कृपया बाद में प्रयास करें।";
    } else {
        $stmt->bind_param("i", $vacancy_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $post = $result->fetch_assoc();
        $stmt->close();

        if (!$post) {
            $error_message = "⚠️ क्षमा करें! यह परिणाम उपलब्ध नहीं है या हटा दिया गया है।";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $post ? htmlspecialchars($post['title']) . " Result" : "त्रुटि"; ?> | सरकारी अपडेट</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #1e3a8a;
            --secondary: #2563eb;
            --success: #059669;
            --bg: #f8fafc;
            --text: #334155;
            --white: #ffffff;
        }

        body {
            font-family: 'Hind', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .container {
            max-width: 1100px;
            margin: 20px auto;
            padding: 0 15px;
        }

        /* हेडर सेक्शन */
        .header-section {
            background: var(--white);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 4px solid var(--secondary);
        }

        .result-main-title {
            font-size: 1.8rem;
            color: var(--primary);
            margin: 0 0 10px 0;
        }

        .post-meta {
            font-size: 0.95rem;
            color: #64748b;
        }

        /* लेआउट ग्रिड */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
        }

        /* मुख्य कंटेंट */
        .main-card {
            background: var(--white);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .status-box {
            background: #ecfdf5;
            border: 1px solid #10b981;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .status-icon {
            font-size: 2rem;
            color: var(--success);
        }

        /* लिंक बटन */
        .links-container {
            margin: 30px 0;
        }

        .link-item {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 15px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            transition: 0.3s ease;
        }

        .btn-download {
            background: var(--success);
            color: white;
            box-shadow: 0 4px 0 #047857;
        }

        .btn-download:hover { background: #047857; transform: translateY(-2px); }

        .btn-official {
            background: var(--secondary);
            color: white;
            box-shadow: 0 4px 0 #1d4ed8;
        }

        .btn-official:hover { background: #1d4ed8; transform: translateY(-2px); }

        .btn-disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* साइडबार */
        .sidebar-widget {
            background: var(--white);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .widget-title {
            font-size: 1.2rem;
            margin-top: 0;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 10px;
            color: var(--primary);
        }

        .info-list {
            list-style: none;
            padding: 0;
            margin: 15px 0 0 0;
        }

        .info-list li {
            padding: 8px 0;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 0.95rem;
        }

        /* विज्ञापन स्लॉट */
        .ad-container {
            margin: 20px 0;
            text-align: center;
            background: #f1f5f9;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .detail-grid { grid-template-columns: 1fr; }
            .result-main-title { font-size: 1.4rem; }
            .btn-action { width: 100%; box-sizing: border-box; }
        }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="container">

    <?php if ($error_message): ?>
        <div class="main-card" style="text-align: center; border-top: 5px solid #ef4444;">
            <p style="font-size: 1.2rem; color: #ef4444;"><?php echo $error_message; ?></p>
            <a href="index.php" class="btn-action btn-official">⬅️ होमपेज पर वापस जाएँ</a>
        </div>
    <?php else: ?>
        
        <!-- हेडर एडसेंस -->
        <div class="ad-container">
            <ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-XXX" data-ad-slot="XXX" data-ad-format="horizontal"></ins>
            Google AdSense - Header Ad
        </div>

        <div class="header-section">
            <h1 class="result-main-title"><?php echo htmlspecialchars($post['title']); ?> Result Details</h1>
            <div class="post-meta">
                <span><i class="far fa-calendar-alt"></i> जारी तिथि: <strong><?php echo date('d M, Y', strtotime($post['post_date'])); ?></strong></span>
                &nbsp; | &nbsp; 
                <span><i class="fas fa-hashtag"></i> पोस्ट ID: <?php echo $post['id']; ?></span>
            </div>
        </div>
        
        <div class="detail-grid">
            <div class="main-content">
                
                <div class="main-card">
                    <div class="status-box">
                        <div class="status-icon"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <strong style="font-size: 1.2rem; display: block;">स्थिति: परिणाम घोषित (Result Out)</strong>
                            <span>परिणाम डाउनलोड करने के लिए नीचे दिए गए लिंक का उपयोग करें।</span>
                        </div>
                    </div>

                    <div class="post-content">
                        <h3 style="color: var(--primary); border-left: 4px solid var(--secondary); padding-left: 10px;">📜 मुख्य जानकारी</h3>
                        <div class="rich-content" style="margin-top: 15px; font-size: 1.05rem;">
                            <?php echo $post['description'] ?? '<p>इस परिणाम के संबंध में विस्तृत जानकारी जल्द ही अपडेट की जाएगी।</p>'; ?>
                        </div>
                    </div>

                    <!-- मध्य एडसेंस -->
                    <div class="ad-container">
                        Google AdSense - In-Article Ad
                    </div>

                    <div class="links-container">
                        <h3 style="color: var(--primary); border-left: 4px solid var(--secondary); padding-left: 10px;">⬇️ डाउनलोड लिंक</h3>
                        
                        <div class="link-item">
                            <?php if (!empty(trim($post['result_link']))): ?>
                                <a href="<?php echo htmlspecialchars($post['result_link']); ?>" class="btn-action btn-download" target="_blank" rel="noopener noreferrer">
                                    <i class="fas fa-file-download"></i> परिणाम डाउनलोड करें (PDF/Link)
                                </a>
                                <p style="font-size: 0.85rem; color: #64748b; margin: 0;">* यह लिंक आपको सीधे मेरिट सूची या रिजल्ट लॉगिन पेज पर ले जाएगा।</p>
                            <?php else: ?>
                                <a href="#" class="btn-action btn-disabled">
                                    <i class="fas fa-hourglass-half"></i> लिंक जल्द सक्रिय होगा
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="link-item" style="margin-top: 20px;">
                            <?php if (!empty(trim($post['official_website']))): ?>
                                <a href="<?php echo htmlspecialchars($post['official_website']); ?>" class="btn-action btn-official" target="_blank" rel="noopener noreferrer">
                                    <i class="fas fa-external-link-alt"></i> आधिकारिक वेबसाइट पर जाएँ
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- शेयर सेक्शन -->
                <div class="main-card" style="margin-top: 20px; text-align: center;">
                    <p style="font-weight: 600;">दोस्तों के साथ यह जानकारी शेयर करें:</p>
                    <a href="whatsapp://send?text=<?php echo urlencode($post['title'] . " का परिणाम यहाँ देखें: " . (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" class="btn-action" style="background:#25d366; color:white; padding:10px 20px;">
                        <i class="fab fa-whatsapp"></i> WhatsApp पर भेजें
                    </a>
                </div>

            </div>

            <div class="sidebar-info">
                <div class="sidebar-widget">
                    <h3 class="widget-title"><i class="fas fa-list-ul"></i> संक्षिप्त विवरण</h3>
                    <ul class="info-list">
                        <li><strong>विभाग:</strong> सरकारी अपडेट</li>
                        <li><strong>श्रेणी:</strong> परीक्षा परिणाम</li>
                        <li><strong>अपडेट:</strong> लाइव (Live)</li>
                        <li><strong>ID:</strong> #<?php echo $post['id']; ?></li>
                    </ul>
                </div>

                <!-- साइडबार एडसेंस -->
                <div class="sidebar-widget ad-container" style="min-height: 250px;">
                    <ins class="adsbygoogle" style="display:inline-block;width:300px;height:250px" data-ad-client="ca-pub-XXX" data-ad-slot="XXX"></ins>
                    300x250 Sidebar Ad
                </div>

                <div class="sidebar-widget">
                    <h3 class="widget-title"><i class="fas fa-bell"></i> अन्य परिणाम</h3>
                    <p style="font-size: 0.9rem; color: #64748b;">लेटेस्ट रिजल्ट्स देखने के लिए हमारे मुख्य पृष्ठ पर जाएँ।</p>
                    <a href="index.php" style="color: var(--secondary); text-decoration: none; font-weight: 600;">सभी परिणाम देखें →</a>
                </div>
            </div>
        </div>
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