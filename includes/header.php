<?php
// वर्तमान पेज की लोकेशन पहचानें
$current_page = basename($_SERVER['PHP_SELF']);

// 🎯 1. CANONICAL & SEO URL FIX
$request_uri_clean = strtok($_SERVER['REQUEST_URI'], '?');
$canonical_url = "https://vacancyportal.co.in" . $request_uri_clean;

// 🎯 2. Dynamic TITLE & DESCRIPTION
$site_title = isset($page_title) ? $page_title : 'VACANCY PORTAL | सरकारी नौकरी और रिजल्ट पोर्टल';
$site_desc  = isset($page_description) ? $page_description : 'सबसे तेज़ सरकारी नौकरी अपडेट्स, रेलवे, बैंक, पुलिस, एडमिट कार्ड और रिजल्ट यहाँ देखें।';

// 🖼️ 3. WHATSAPP & SOCIAL MEDIA PREVIEW IMAGE LOGIC (FIXED FOR ABSOLUTE URL)
$default_logo = "https://vacancyportal.co.in/assets/vp.png"; 

if (isset($page_image) && !empty($page_image)) {
    // अगर URL में http/https पहले से नहीं है तो डोमेन नेम जोड़ें
    if (strpos($page_image, 'http://') === 0 || strpos($page_image, 'https://') === 0) {
        $share_image = $page_image;
    } else {
        $share_image = "https://vacancyportal.co.in/" . ltrim($page_image, '/');
    }
} else {
    $share_image = $default_logo;
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- 🎯 डायनामिक गूगल SEO टाइटल और मेटा डिस्क्रिप्शन -->
    <title><?php echo htmlspecialchars($site_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($site_desc); ?>">

    <!-- 🚀 AUTOMATED CANONICAL URL -->
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>" />

    <!-- 🟢 GOOGLE ADSENSE CODE AUTOMATION -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-7750018876113294" crossorigin="anonymous"></script>

    <!-- 🌐 WHATSAPP & OPEN GRAPH META TAGS (COMPLETE FIX) -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="hi_IN">
    <meta property="og:site_name" content="VACANCY PORTAL">
    <meta property="og:title" content="<?php echo htmlspecialchars($site_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($site_desc); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    
    <!-- 🔥 WHATSAPP FULL LOGO / IMAGE TAGS -->
    <meta property="og:image" content="<?php echo htmlspecialchars($share_image); ?>">
    <meta property="og:image:secure_url" content="<?php echo htmlspecialchars($share_image); ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="600">
    <meta property="og:image:height" content="600">
    <meta property="og:image:alt" content="VACANCY PORTAL Logo">

    <!-- 🐦 TWITTER / TELEGRAM CARD TAGS -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($site_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($site_desc); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($share_image); ?>">

    <!-- 🎯 FAVICON LINKS (CONSISTENT DOMAIN) -->
    <link rel="icon" type="image/png" href="https://vacancyportal.co.in/assets/vp.png">
    <link rel="shortcut icon" type="image/png" href="https://vacancyportal.co.in/assets/vp.png">
    <link rel="apple-touch-icon" href="https://vacancyportal.co.in/assets/vp.png">
        
    <!-- 🎨 गूगल फ़ॉन्ट्स और आइकन लिंक्स -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --header-bg: #ffffff;
            --top-bg: #1e3a8a;
            --text-main: #334155;
            
            /* 🌈 थीम कलर्स */
            --color-home: #2563eb;
            --color-jobs: #059669;
            --color-poster: #db2777;
            --color-results: #7c3aed;
            --color-cards: #d97706;
            --color-schemes: #e11d48;
            --color-edu: #0891b2;
            --color-admin: #4f46e5;
            --color-forms: #ea580c;
            --color-contact: #2563eb;
        }
        
        body { 
            margin: 0; 
            padding-top: 100px; 
            font-family: 'Hind', 'Poppins', sans-serif; 
            background: #f8fafc; 
        }
        
        /* 📱 लोगो इमेज */
        .logo-img {
            height: 34px;
            width: auto;
            object-fit: contain;
            vertical-align: middle;
        }
        
        /* Main Header */
        .main-header { 
            background: var(--header-bg); 
            box-shadow: 0 4px 20px rgba(0,0,0,0.06); 
            position: fixed; 
            top: 40px; 
            left: 0;
            width: 100%; 
            height: 60px; 
            z-index: 1000; 
            border-bottom: 1px solid #e2e8f0;
            box-sizing: border-box;
        }
        
        .header-container { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            max-width: 1440px; 
            margin: 0 auto; 
            padding: 0 15px; 
            height: 100%; 
        }
        
        /* लोगो */
        .logo { 
            font-size: 18px; 
            font-weight: 700; 
            color: var(--top-bg); 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            white-space: nowrap; 
        }

        /* Navigation Container */
        .main-nav { 
            flex: 1; 
            display: flex; 
            justify-content: flex-end; 
            align-items: center; 
            height: 100%; 
            margin-left: 10px; 
        }
        
        .main-nav ul { 
            display: flex; 
            list-style: none; 
            margin: 0; 
            padding: 0; 
            align-items: center; 
            gap: 6px; 
            height: 100%; 
        }
        
        .main-nav ul li { display: flex; align-items: center; height: 100%; }
        
        /* 💎 3D कैप्सूल बटन स्टाइल */
        .main-nav ul li a { 
            padding: 7px 14px; 
            color: white !important; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 13px; 
            transition: all 0.2s ease-in-out; 
            white-space: nowrap; 
            display: flex;
            align-items: center;
            gap: 6px; 
            border-radius: 50px; 
            position: relative;
            box-shadow: 0 3px 6px rgba(0,0,0,0.12);
            text-shadow: 0 1px 2px rgba(0,0,0,0.25);
        }
        
        .main-nav ul li a i { font-size: 13px; opacity: 0.95; }
        
        /* 🎨 बटन कलर थीम्स */
        .btn-home { background: var(--color-home); }
        .btn-jobs { background: var(--color-jobs); }
        .btn-poster { background: var(--color-poster); }
        .btn-results { background: var(--color-results); }
        .btn-cards { background: var(--color-cards); }
        .btn-schemes { background: var(--color-schemes); }
        .btn-edu { background: var(--color-edu); }
        .btn-admin { background: var(--color-admin); }
        .btn-forms { background: var(--color-forms); }
        .btn-contact { background: var(--color-contact); }

        /* 🚀 होवर और एक्टिव इफेक्ट */
        .main-nav ul li a:hover, 
        .main-nav ul li a.active {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
            filter: brightness(1.1);
            outline: 2px solid white;
        }

        /* 🔴 लाइव बैज */
        .btn-badge {
            position: absolute;
            top: -6px;
            right: 2px;
            color: white;
            font-size: 8px;
            padding: 2px 5px;
            border-radius: 4px;
            font-weight: 700;
            line-height: 1;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            animation: pulseGlow 1.2s infinite;
        }
        .badge-hot { background: #ef4444; border: 1px solid white; }
        .badge-new { background: #f43f5e; border: 1px solid white; }
        .badge-live { background: #10b981; border: 1px solid white; }

        @keyframes pulseGlow {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Mobile Toggle Icon */
        .mobile-toggle { display: none; font-size: 22px; cursor: pointer; color: var(--top-bg); padding: 8px; }

        /* Responsive Breakpoints */
        @media (max-width: 1400px) {
            .main-nav ul li a { font-size: 11px; padding: 6px 9px; gap: 4px; }
            .logo { font-size: 16px; }
            .logo-img { height: 28px; }
        }

        /* 📱 मोबाइल नेविगेशन ग्रिड */
        @media (max-width: 1240px) {
            body { padding-top: 95px; }
            .mobile-toggle { display: block; }
            .main-header { height: 55px; }
            
            .main-nav { 
                display: none; 
                width: 100%; 
                position: absolute; 
                top: 55px; 
                left: 0; 
                background: #f1f5f9; 
                box-shadow: 0 15px 30px rgba(0,0,0,0.15); 
                max-height: 85vh;
                overflow-y: auto;
                border-top: 1px solid #e2e8f0;
                height: auto;
                padding: 15px;
                box-sizing: border-box;
            }
            .main-nav.active { display: block; }
            
            .main-nav ul { 
                display: grid;
                grid-template-columns: repeat(2, 1fr); 
                gap: 10px; 
                padding: 0; 
                margin: 0; 
                height: auto;
                width: 100%;
            }
            .main-nav ul li { width: 100%; height: auto; display: block; }
            
            .main-nav ul li a { 
                display: flex;
                flex-direction: row; 
                justify-content: center;
                align-items: center;
                padding: 12px 10px; 
                font-size: 13px; 
                gap: 8px;
                border-radius: 12px;
                box-sizing: border-box;
                width: 100%;
                box-shadow: 0 3px 6px rgba(0,0,0,0.12);
            }
            
            .main-nav ul li:last-child {
                grid-column: span 2;
            }

            .btn-badge { top: -4px; right: 4px; font-size: 7px; padding: 2px 4px; }
        }

        /* 🛠️ टॉप सूचना पट्टी */
        .top-strip { 
            background: #1e3a8a; 
            color: white; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            z-index: 1001; 
            height: 40px; 
            box-sizing: border-box; 
            overflow: hidden;
        }

        .top-container { 
            display: flex; 
            align-items: center; 
            max-width: 1440px; 
            margin: 0 auto; 
            padding: 0 15px; 
            height: 100%; 
            font-size: 13px; 
        }

        .ticker-icon {
            background: #ef4444; 
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 700;
            white-space: nowrap;
            z-index: 10;
            box-shadow: 3px 0 10px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .ticker-wrap {
            flex: 1;
            overflow: hidden;
            display: flex;
            align-items: center;
            padding-left: 15px;
        }

        .ticker-text {
            white-space: nowrap;
            display: inline-block;
            font-weight: 500;
            font-size: 13.5px;
            letter-spacing: 0.5px;
            padding-right: 50px; 
            animation: marqueeLive 40s linear infinite;
        }

        .ticker-wrap:hover .ticker-text {
            animation-play-state: paused;
        }

        @keyframes marqueeLive {
            0% { transform: translate3d(20%, 0, 0); } 
            100% { transform: translate3d(-100%, 0, 0); }
        }
    </style>
</head>
<body>

<!-- 🔴 मॉडर्न लाइव नोटिफिकेशन पट्टी -->
<div class="top-strip">
    <div class="top-container">
        <div class="ticker-icon">
            <i class="fas fa-bullhorn"></i> ताज़ा अपडेट:
        </div>
        <div class="ticker-wrap">
            <div class="ticker-text">
                🔥 रेलवे (RRB) और पुलिस भर्ती के ऑनलाइन आवेदन शुरू! | 🎓 परीक्षा परिणाम एवं एडमिट कार्ड लिंक जारी! | 📜 नई सरकारी योजनाओं के फॉर्म और नोटिफिकेशन डाउनलोड करें!
            </div>
        </div>
    </div>
</div>

<!-- 🔷 मुख्य नेविगेशन हेडर -->
<header class="main-header">
    <div class="header-container">
        
        <!-- 🎯 1. लोगो और टेक्स्ट की अलग-अलग लिंक -->
        <div class="logo" style="display: flex; align-items: center;">
            <a href="admin/login.php" class="spa-link" style="display: flex; align-items: center;" title="Admin Login">
                <img src="assets/vp.png" alt="Vacancy Portal Logo" class="logo-img">
            </a>
            <a href="index.php" class="spa-link" style="color: inherit; text-decoration: none; margin-left: 8px;" title="Home">
                VACANCY PORTAL
            </a>
        </div>

        <!-- 📱 2. मोबाइल मेनू टॉगल -->
        <div class="mobile-controls">
            <div class="mobile-toggle" onclick="toggleMenu()" aria-label="Toggle Menu">
                <i class="fas fa-bars" id="toggleIcon"></i>
            </div>
        </div>

        <nav class="main-nav" id="mainNav">
            <ul>
                <li>
                    <a href="index.php" class="btn-home spa-link <?php echo ($current_page == 'index.php' || $current_page == '') ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> होम
                    </a>
                </li> 
                
                <li>
                    <a href="job.php" class="btn-jobs spa-link <?php echo ($current_page == 'job.php') ? 'active' : ''; ?>">
                        <i class="fas fa-briefcase"></i> नई जॉब्स
                        <span class="btn-badge badge-hot">HOT</span>
                    </a>
                </li> 
                
                <li>
                    <a href="poster.php" class="btn-poster spa-link <?php echo ($current_page == 'poster.php') ? 'active' : ''; ?>">
                        <i class="fas fa-images"></i> पोस्टर्स
                        <span class="btn-badge badge-new">NEW</span>
                    </a>
                </li>

                <li>
                    <a href="results.php" class="btn-results spa-link <?php echo ($current_page == 'results.php') ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i> रिजल्ट
                        <span class="btn-badge badge-live">LIVE</span>
                    </a>
                </li> 
                
                <li>
                    <a href="admit_card.php" class="btn-cards spa-link <?php echo ($current_page == 'admit_card.php' || $current_page == 'admit_cards.php') ? 'active' : ''; ?>">
                        <i class="fas fa-id-card"></i> एडमिट कार्ड
                    </a>
                </li>

                <li>
                    <a href="schemes.php" class="btn-schemes spa-link <?php echo ($current_page == 'schemes.php') ? 'active' : ''; ?>">
                        <i class="fas fa-hand-holding-heart"></i> योजनाएं
                    </a>
                </li>

                <li>
                    <a href="education.php" class="btn-edu spa-link <?php echo ($current_page == 'education.php') ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i> शिक्षा
                    </a>
                </li>

                <li>
                    <a href="admission.php" class="btn-admin spa-link <?php echo ($current_page == 'admission.php') ? 'active' : ''; ?>">
                        <i class="fas fa-university"></i> एडमिशन
                    </a>
                </li>

                <li>
                    <a href="pdf-forms.php" class="btn-forms spa-link <?php echo ($current_page == 'pdf-forms.php' || $current_page == 'pdf_forms.php') ? 'active' : ''; ?>">
                        <i class="fas fa-file-pdf"></i> PDF फॉर्म्स
                    </a>
                </li>

                <li>
                    <a href="contact.php" class="btn-contact spa-link <?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">
                        <i class="fas fa-envelope"></i> संपर्क
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</header>

<script>
    function toggleMenu() {
        const nav = document.getElementById('mainNav');
        const icon = document.getElementById('toggleIcon');
        
        if (!nav || !icon) return;
        nav.classList.toggle('active');
        
        if (nav.classList.contains('active')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        } else {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    }

    document.addEventListener('click', function(event) {
        const nav = document.getElementById('mainNav');
        const toggle = document.querySelector('.mobile-toggle');
        const icon = document.getElementById('toggleIcon');
        
        if (nav && toggle && !nav.contains(event.target) && !toggle.contains(event.target)) {
            nav.classList.remove('active');
            if (icon) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }
    });
</script>