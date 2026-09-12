<?php
// poster_details.php
include 'includes/db_connect.php'; 
include 'includes/header.php'; 

// सुरक्षित तरीके से ID गेट करना
$poster_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// डेटाबेस से उस विशिष्ट पोस्टर की जानकारी निकालना
$stmt = $conn->prepare("SELECT * FROM posters WHERE id = ?");
$stmt->bind_param("i", $poster_id);
$stmt->execute();
$result = $stmt->get_result();
$poster = $result->fetch_assoc();

if (!$poster) {
    echo '<div class="container" style="margin: 50px auto; text-align: center;">';
    echo '<h2>पोस्टर नहीं मिला!</h2>';
    echo '<p>शायद यह डिलीट कर दिया गया है या लिंक गलत है।</p>';
    echo '<br><a href="poster.php" class="btn-action btn-back"><i class="fas fa-arrow-left"></i> सभी पोस्टर्स देखें</a>';
    echo '</div>';
    include 'includes/footer.php';
    exit;
}

// मुख्य फिक्स: टेक्स्ट में छुपे हुए \r\n को असली लाइन ब्रेक में बदलना
$clean_description = str_replace(array('\r\n', '\r', '\n', '\\r\\n', '\\n', '\\r'), "\n", $poster['description']);

// वर्तमान पेज का URL शेयरिंग के लिए
$current_url = urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
$share_title = urlencode($poster['title']);
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <title><?php echo htmlspecialchars($poster['title']); ?> - विवरण</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600;700&display=swap">
    <!-- FontAwesome CSS (अगर हेडर में न हो तो) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { font-family: 'Hind', sans-serif; background-color: #f1f5f9; }
        
        /* 🔥 1. स्टाइलिश लाइव मारकी बार */
        .marquee-wrapper { background: linear-gradient(90deg, #ef4444, #b91c1c); color: white; padding: 10px 20px; font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 20px; border-radius: 4px; }
        .marquee-label { background: #fff; color: #b91c1c; padding: 2px 10px; border-radius: 4px; font-size: 0.8rem; text-transform: uppercase; font-weight: 700; margin-right: 15px; white-space: nowrap; box-shadow: 0 2px 4px rgba(0,0,0,0.15); animation: blink 1.5s infinite; }
        @keyframes blink { 50% { opacity: 0.5; } }

        /* मुख्य लेआउट */
        .details-container { display: flex; flex-wrap: wrap; gap: 30px; margin: 20px auto 40px auto; max-width: 1200px; background: white; padding: 30px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        
        /* लेफ्ट साइड */
        .poster-side { flex: 1; min-width: 340px; text-align: center; }
        .poster-image-box { position: relative; overflow: hidden; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; margin-bottom: 20px; background: #fff; }
        .poster-side img { max-width: 100%; height: auto; display: block; transition: transform 0.3s ease; }
        .poster-image-box:hover img { transform: scale(1.03); }
        
        /* इमेज टूल्स (Zoom/Print) */
        .image-tools { display: flex; justify-content: center; gap: 15px; margin-bottom: 20px; }
        .tool-btn { background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-weight: 500; }
        .tool-btn:hover { background: #e2e8f0; color: #0f172a; }

        /* राइट साइड */
        .info-side { flex: 1.4; min-width: 340px; display: flex; flex-direction: column; justify-content: space-between; }
        .details-header { border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 25px; }
        .details-header h1 { margin: 0 0 12px 0; font-size: 1.9rem; color: #0f172a; font-weight: 700; line-height: 1.4; }
        
        .poster-meta-info { font-size: 0.9rem; color: #64748b; display: flex; flex-wrap: wrap; gap: 20px; align-items: center; }
        .live-badge { background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 9999px; font-weight: 600; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px; }

        /* प्रीमियम विवरण बॉक्स */
        .info-content { font-size: 1.15rem; line-height: 1.8; color: #1e293b; white-space: pre-wrap; background: #f8fafc; padding: 30px; border-radius: 12px; border-left: 5px solid #2563eb; margin-bottom: 25px; box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.02); }
        
        /* 🔗 क्विक लिंक्स विजेट */
        .quick-links-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 20px; margin-bottom: 25px; }
        .quick-links-box h4 { margin: 0 0 12px 0; color: #1e40af; font-size: 1rem; display: flex; align-items: center; gap: 8px; }
        .quick-links-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; }
        .widget-link { background: white; border: 1px solid #dbeafe; padding: 8px; text-align: center; border-radius: 6px; text-decoration: none; color: #1e40af; font-size: 0.9rem; font-weight: 500; display: block; transition: all 0.2s; }
        .widget-link:hover { background: #1e40af; color: white; transform: translateY(-1px); }

        /* 📢 सोशल शेयरिंग बटन्स */
        .share-section { margin-bottom: 25px; padding: 15px 0; border-top: 1px dashed #e2e8f0; }
        .share-title { font-size: 0.95rem; font-weight: 600; color: #475569; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
        .share-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
        .share-btn { padding: 8px 16px; border-radius: 6px; color: white; font-size: 0.9rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: transform 0.2s; }
        .share-btn:hover { transform: translateY(-1px); filter: brightness(0.95); }
        .share-whatsapp { background-color: #25d366; }
        .share-telegram { background-color: #0088cc; }

        /* एक्शन बटन्स */
        .action-buttons { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 15px; }
        .btn-action { display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: all 0.2s; cursor: pointer; border: none; font-size: 1rem; }
        .btn-download { background: #10b981; color: white; width: 100%; justify-content: center; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2); font-size: 1.1rem; }
        .btn-download:hover { background: #059669; transform: translateY(-2px); }
        .btn-back { background: #475569; color: white; box-shadow: 0 4px 6px -1px rgba(71, 85, 105, 0.2); }
        .btn-back:hover { background: #334155; transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="container main-layout" style="max-width: 1200px; margin: 0 auto; padding: 0 15px;">
    
    <!-- 🌟 फ़ीचर 1: लाइव मारकी न्यूज़ स्ट्रिप -->
    <div class="marquee-wrapper">
        <span class="marquee-label"><i class="fas fa-bell"></i> ताज़ा अपडेट</span>
        <marquee behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">
            👉 <?php echo htmlspecialchars($poster['title']); ?> का पूरा विवरण नीचे दिया गया है। अंतिम तिथि से पहले फॉर्म जरूर भरें।
        </marquee>
    </div>

    <div class="details-container">
        
        <!-- लेफ्ट साइड: पोस्टर विजुअल, डाउनलोड और इमेज टूल्स -->
        <div class="poster-side">
            <div class="poster-image-box" id="posterBox">
                <img src="banners/<?php echo htmlspecialchars($poster['image_name']); ?>" alt="<?php echo htmlspecialchars($poster['title']); ?>" id="posterImg">
            </div>
            
            <!-- फ़ीचर 5: इमेज यूटिलिटी टूल्स -->
            <div class="image-tools">
                <button class="tool-btn" onclick="toggleZoom()"><i class="fas fa-search-plus"></i> ज़ूम करें</button>
                <button class="tool-btn" onclick="window.print()"><i class="fas fa-print"></i> विवरण प्रिंट करें</button>
            </div>
            
            <a href="banners/<?php echo htmlspecialchars($poster['image_name']); ?>" download="<?php echo htmlspecialchars($poster['title']); ?>" class="btn-action btn-download">
                <i class="fas fa-download"></i> पोस्टर डाउनलोड करें (Save Image)
            </a>
        </div>
        
        <!-- राइट साइड: प्रीमियम कंटेंट, विजेट्स और शेयरिंग -->
        <div class="info-side">
            <div>
                <div class="details-header">
                    <div style="margin-bottom: 8px;">
                        <span class="live-badge"><i class="fas fa-eye"></i> 485+ छात्र देख चुके हैं</span>
                    </div>
                    <h1><?php echo htmlspecialchars($poster['title']); ?></h1>
                    <div class="poster-meta-info">
                        <span><i class="far fa-calendar-alt"></i> अपलोड तिथि: <?php echo date('d M Y', strtotime($poster['uploaded_at'])); ?></span>
                        <span><i class="fas fa-check-circle" style="color: #2563eb;"></i> प्रमाणित जानकारी</span>
                    </div>
                </div>
                
                <!-- डायनामिक सुधरा हुआ वीआईपी हिंदी टेक्स्ट -->
                <div class="info-content"><?php echo htmlspecialchars($clean_description); ?></div>
                
                <!-- 🌟 फ़ीचर 3: क्विक लिंक्स नेविगेशन विजेट -->
                <div class="quick-links-box">
                    <h4><i class="fas fa-link"></i> त्वरित लिंक्स (Quick Navigation)</h4>
                    <div class="quick-links-grid">
                        <a href="index.php" class="widget-link">होम पेज</a>
                        <a href="latest-jobs.php" class="widget-link">नई सरकारी भर्तियां</a>
                        <a href="admit-card.php" class="widget-link">प्रवेश पत्र</a>
                        <a href="results.php" class="widget-link">परीक्षा परिणाम</a>
                    </div>
                </div>

                <!-- 🌟 फ़ीचर 2: सोशल मीडिया शेयरिंग सिस्टम -->
                <div class="share-section">
                    <div class="share-title"><i class="fas fa-share-alt"></i> दोस्तों के साथ शेयर करें (Share Info):</div>
                    <div class="share-buttons">
                        <a href="https://api.whatsapp.com/send?text=*<?php echo $share_title; ?>*%0Aविवरण देखें: <?php echo $current_url; ?>" target="_blank" class="share-btn share-whatsapp">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                        <a href="https://telegram.me/share/url?url=<?php echo $current_url; ?>&text=<?php echo $share_title; ?>" target="_blank" class="share-btn share-telegram">
                            <i class="fab fa-telegram-plane"></i> Telegram
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="poster.php" class="btn-action btn-back">
                    <i class="fas fa-arrow-left"></i> वापस सभी पोस्टर्स पर जाएँ
                </a>
            </div>
        </div>
        
    </div>
</div>

<script>
// ज़ूम इन/आउट करने का सिंपल और शानदार लॉजिक
let zoomed = false;
function toggleZoom() {
    const img = document.getElementById('posterImg');
    if(!zoomed) {
        img.style.transform = "scale(1.5)";
        img.style.zIndex = "99";
        img.style.position = "relative";
        zoomed = true;
    } else {
        img.style.transform = "scale(1)";
        img.style.position = "static";
        zoomed = false;
    }
}
</script>

<?php 
$stmt->close();
$conn->close();
include 'includes/footer.php'; 
?>
</body>
</html>