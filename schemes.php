<?php 
/**
 * File: schemes.php
 * उद्देश्य: सरकारी योजनाओं को Mobile App UI और Desktop Table View के रूप में डायनेमिक प्रदर्शित करना
 */
include 'includes/db_connect.php'; 
include 'includes/header.php'; 

// 1. Pagination & Search/Filter Logic
$limit = 12; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$s_query = isset($_GET['s']) ? mysqli_real_escape_string($conn, trim($_GET['s'])) : '';
$cat_filter = isset($_GET['cat']) ? mysqli_real_escape_string($conn, trim($_GET['cat'])) : '';

// --- Dynamic SQL Query Construction ---
$where_clause = "(category = 'scheme' OR category = 'schemes') AND status = 'live'";

if (!empty($s_query)) {
    $where_clause .= " AND (title LIKE '%$s_query%' OR short_desc LIKE '%$s_query%' OR state LIKE '%$s_query%')";
}

if (!empty($cat_filter)) {
    $where_clause .= " AND (title LIKE '%$cat_filter%' OR short_desc LIKE '%$cat_filter%')";
}

// मुख्य डेटा फेच करना
$query = "SELECT * FROM vacancies WHERE $where_clause ORDER BY post_date DESC LIMIT $offset, $limit";
$result = $conn->query($query);

// ब्रेकिंग न्यूज़ / टिकर के लिए
$marquee_query = "SELECT id, title FROM vacancies WHERE (category = 'scheme' OR category = 'schemes') AND status = 'live' ORDER BY post_date DESC LIMIT 8";
$marquee_result = $conn->query($marquee_query);

// पेजिनेशन के लिए टोटल काउंट
$total_result = $conn->query("SELECT COUNT(id) AS total FROM vacancies WHERE $where_clause");
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
?>

<!-- 🎨 Mobile App + Desktop Modern UI Stylesheet -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap');

    :root { 
        --app-primary: #1e3a8a; 
        --app-secondary: #2563eb;
        --app-accent: #ef4444; 
        --app-green: #059669;
        --app-bg: #f8fafc; 
        --app-card: #ffffff;
        --app-border: #e2e8f0;
        --app-text: #0f172a;
        --app-subtext: #64748b;
        --app-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    }

    body { 
        background: var(--app-bg); 
        font-family: 'Hind', sans-serif; 
        color: var(--app-text);
        margin: 0;
        padding: 0;
    }

    .app-page-wrapper { padding-bottom: 60px; }

    /* 🏛️ Hero App Header */
    .app-hero-header { 
        background: linear-gradient(135deg, var(--app-primary) 0%, #1d4ed8 100%); 
        padding: 40px 15px 50px; 
        color: white; 
        text-align: center; 
        position: relative;
    }
    .app-hero-header h2 { 
        font-family: 'Outfit', sans-serif; 
        font-size: 1.8rem; 
        font-weight: 800; 
        margin-bottom: 6px; 
    }
    .app-hero-header p { 
        opacity: 0.9; 
        font-size: 0.95rem; 
        margin: 0;
    }

    /* 🔍 Search App Bar */
    .app-search-wrapper { 
        max-width: 680px; 
        margin: -25px auto 20px; 
        padding: 0 15px; 
        position: relative; 
        z-index: 10; 
    }
    .app-search-box { 
        display: flex; 
        background: var(--app-card); 
        border-radius: 50px; 
        overflow: hidden; 
        box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
        padding: 4px; 
        border: 1px solid var(--app-border); 
    }
    .app-search-box input { 
        flex: 1; 
        border: none; 
        padding: 10px 20px; 
        outline: none; 
        font-size: 15px; 
        border-radius: 50px; 
    }
    .app-search-box button { 
        background: var(--app-accent); 
        color: white; 
        border: none; 
        padding: 0 25px; 
        border-radius: 50px; 
        cursor: pointer; 
        transition: 0.3s; 
        font-weight: bold; 
    }

    /* 🏷️ Horizontal Mobile Filter Chips */
    .app-chips-container {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding: 10px 15px;
        margin-bottom: 20px;
        scrollbar-width: none; /* Hide scrollbar Firefox */
    }
    .app-chips-container::-webkit-scrollbar { display: none; } /* Hide scrollbar Chrome */
    
    .chip-item {
        background: var(--app-card);
        border: 1px solid var(--app-border);
        color: var(--app-subtext);
        padding: 6px 16px;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        white-space: nowrap;
        text-decoration: none !important;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .chip-item.active, .chip-item:hover {
        background: var(--app-primary);
        color: #ffffff !important;
        border-color: var(--app-primary);
        box-shadow: 0 4px 10px rgba(30, 58, 138, 0.2);
    }

    /* ⚡ News Ticker */
    .app-ticker-bar { 
        background: var(--app-card); 
        border: 1px solid var(--app-border); 
        border-radius: 12px; 
        display: flex; 
        align-items: center; 
        margin: 0 15px 25px; 
        overflow: hidden; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.02); 
    }
    .ticker-lbl { 
        background: var(--app-accent); 
        color: white; 
        padding: 10px 14px; 
        font-weight: 800; 
        font-size: 12px; 
        text-transform: uppercase; 
        white-space: nowrap; 
    }
    .ticker-content { flex: 1; padding: 0 12px; overflow: hidden; }
    .ticker-content a { color: #334155; text-decoration: none; font-weight: 600; font-size: 0.88rem; margin-right: 25px; }

    /* 🖥️ Desktop Modern Table View */
    .desktop-table-container { 
        background: var(--app-card); 
        border-radius: 16px; 
        border: 1px solid var(--app-border); 
        box-shadow: var(--app-shadow); 
        overflow: hidden; 
        margin-bottom: 30px; 
    }
    .desktop-table { width: 100%; border-collapse: collapse; text-align: left; }
    .desktop-table thead { background: #f1f5f9; border-bottom: 2px solid var(--app-border); }
    .desktop-table th { padding: 16px 20px; font-size: 0.88rem; font-weight: 700; color: #1e293b; text-transform: uppercase; }
    .desktop-table td { padding: 18px 20px; border-bottom: 1px solid var(--app-border); vertical-align: middle; }
    .desktop-table tbody tr:hover { background: #f8fafc; }

    .scheme-title h3 { font-size: 1.05rem; font-weight: 700; color: var(--app-text); margin: 0 0 4px; line-height: 1.4; }
    .scheme-title p { font-size: 0.85rem; color: var(--app-subtext); margin: 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    .badge-state { background: #e0e7ff; color: #3730a3; padding: 4px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 700; white-space: nowrap; }
    .badge-date { color: var(--app-subtext); font-size: 0.82rem; white-space: nowrap; }

    .btn-action-view { 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        gap: 6px; 
        background: var(--app-primary); 
        color: white !important; 
        padding: 8px 16px; 
        border-radius: 8px; 
        text-decoration: none !important; 
        font-weight: 700; 
        font-size: 0.85rem; 
        transition: 0.2s; 
    }
    .btn-action-view:hover { background: var(--app-secondary); }

    /* 📱 Native Mobile App Card List (App Look) */
    .mobile-app-list { display: none; }

    .mobile-app-card {
        background: var(--app-card);
        border: 1px solid var(--app-border);
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 14px;
        box-shadow: var(--app-shadow);
        position: relative;
    }

    .mobile-card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .mobile-card-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--app-text);
        margin-bottom: 8px;
        line-height: 1.4;
    }

    .mobile-card-desc {
        font-size: 0.85rem;
        color: var(--app-subtext);
        margin-bottom: 14px;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .mobile-card-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px dashed var(--app-border);
        padding-top: 10px;
    }

    /* 📄 Pagination */
    .pagination-wrap { margin-top: 30px; display: flex; justify-content: center; gap: 6px; flex-wrap: wrap; }
    .pagination-wrap a { padding: 8px 16px; background: white; border: 1px solid var(--app-border); border-radius: 8px; color: var(--app-primary); text-decoration: none; font-weight: bold; font-size: 0.88rem; }
    .pagination-wrap a.active { background: var(--app-primary); color: white; border-color: var(--app-primary); }

    /* 📢 Ad Boxes */
    .ad-slot-responsive { max-width: 1140px; margin: 15px auto; text-align: center; padding: 0 15px; }
    .ad-card-box { background: #fff; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 12px; text-align: center; color: #94a3b8; font-size: 10px; text-transform: uppercase; }

    /* 📱 Responsive Toggle (Mobile App vs Desktop Table) */
    @media (max-width: 768px) {
        .desktop-table-container { display: none; }
        .mobile-app-list { display: block; }
        .app-hero-header h2 { font-size: 1.4rem; }
    }
</style>

<div class="app-page-wrapper">
    
    <!-- 🏛️ Hero App Header -->
    <div class="app-hero-header">
        <div class="container">
            <h2><i class="fas fa-hand-holding-heart"></i> सरकारी योजना पोर्टल (2026)</h2>
            <p>सभी केंद्र व राज्य सरकार की योजनाएं एक ही स्थान पर</p>
        </div>
    </div>

    <!-- 🔍 Search Bar -->
    <div class="app-search-wrapper">
        <form action="schemes.php" method="GET" class="app-search-box">
            <input type="text" name="s" placeholder="योजना का नाम या राज्य खोजें..." value="<?php echo htmlspecialchars($s_query); ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <div class="container" style="max-width: 1140px; margin: 0 auto;">

        <!-- 🏷️ Horizontal Mobile App Filter Chips -->
        <div class="app-chips-container">
            <a href="schemes.php" class="chip-item <?php echo (empty($cat_filter) && empty($s_query)) ? 'active' : ''; ?>">
                <i class="fas fa-border-all"></i> सभी योजनाएं
            </a>
            <a href="schemes.php?cat=किसान" class="chip-item <?php echo ($cat_filter === 'किसान') ? 'active' : ''; ?>">
                🌾 किसान योजना
            </a>
            <a href="schemes.php?cat=महिला" class="chip-item <?php echo ($cat_filter === 'महिला') ? 'active' : ''; ?>">
                👩 महिला कल्याण
            </a>
            <a href="schemes.php?cat=आवास" class="chip-item <?php echo ($cat_filter === 'आवास') ? 'active' : ''; ?>">
                🏠 आवास योजना
            </a>
            <a href="schemes.php?cat=छात्र" class="chip-item <?php echo ($cat_filter === 'छात्र') ? 'active' : ''; ?>">
                🎓 छात्रवृत्ति
            </a>
            <a href="schemes.php?cat=राशन" class="chip-item <?php echo ($cat_filter === 'राशन') ? 'active' : ''; ?>">
                🍚 राशन कार्ड
            </a>
        </div>

        <!-- Top Ad Banner -->
        <div class="ad-slot-responsive">
            <div class="ad-card-box">
                <span>ADVERTISEMENT</span>
                <div><!-- ADSENSE TOP BANNER AD CODE --></div>
            </div>
        </div>

        <!-- ⚡ News Ticker -->
        <div class="app-ticker-bar">
            <div class="ticker-lbl"><i class="fas fa-bolt"></i> अपडेट</div>
            <div class="ticker-content">
                <marquee onmouseover="this.stop();" onmouseout="this.start();">
                    <?php while($m = $marquee_result->fetch_assoc()): ?>
                        <a href="scheme_details.php?id=<?php echo $m['id']; ?>">🎯 <?php echo htmlspecialchars($m['title']); ?></a>
                    <?php endwhile; ?>
                </marquee>
            </div>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>

            <!-- 🖥️ DESKTOP VIEW: Modern Table Container -->
            <div class="desktop-table-container">
                <table class="desktop-table">
                    <thead>
                        <tr>
                            <th style="width: 52%;">योजना का विवरण</th>
                            <th style="width: 16%;">राज्य / क्षेत्र</th>
                            <th style="width: 14%;">जारी तिथि</th>
                            <th style="width: 18%; text-align: right;">कार्रवाई</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $dt_count = 0;
                        // Data loop reset for both views
                        $rows = $result->fetch_all(MYSQLI_ASSOC);
                        foreach($rows as $row): 
                            $dt_count++;
                        ?>
                            <tr>
                                <td class="scheme-title">
                                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                                    <p><?php echo htmlspecialchars($row['short_desc'] ?? 'पात्रता, आवश्यक दस्तावेज एवं आवेदन प्रक्रिया देखने के लिए बटन पर क्लिक करें।'); ?></p>
                                </td>
                                <td>
                                    <span class="badge-state"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($row['state'] ?? 'All India'); ?></span>
                                </td>
                                <td>
                                    <span class="badge-date"><i class="far fa-clock"></i> <?php echo date('d M, Y', strtotime($row['post_date'])); ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="scheme_details.php?id=<?php echo $row['id']; ?>" class="btn-action-view">
                                        विवरण देखें <i class="fas fa-chevron-right"></i>
                                    </a>
                                </td>
                            </tr>

                            <?php if($dt_count % 6 == 0): ?>
                                <tr>
                                    <td colspan="4" style="background:#fafafa; text-align:center; padding:12px;">
                                        <div style="font-size:9px; color:#94a3b8; font-weight:700;">ADVERTISEMENT</div>
                                        <div><!-- ADSENSE IN-FEED TABLE AD CODE --></div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- 📱 MOBILE VIEW: Native App Item Cards -->
            <div class="mobile-app-list">
                <?php 
                $mb_count = 0;
                foreach($rows as $row): 
                    $mb_count++;
                ?>
                    <div class="mobile-app-card">
                        <div class="mobile-card-top">
                            <span class="badge-state"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($row['state'] ?? 'All India'); ?></span>
                            <span class="badge-date"><i class="far fa-clock"></i> <?php echo date('d M, Y', strtotime($row['post_date'])); ?></span>
                        </div>
                        
                        <div class="mobile-card-title"><?php echo htmlspecialchars($row['title']); ?></div>
                        
                        <div class="mobile-card-desc">
                            <?php echo htmlspecialchars($row['short_desc'] ?? 'पात्रता, आवश्यक दस्तावेज एवं आवेदन प्रक्रिया की पूरी जानकारी यहाँ देखें।'); ?>
                        </div>

                        <div class="mobile-card-bottom">
                            <span style="font-size: 0.78rem; color: var(--app-green); font-weight: 700;">
                                <i class="fas fa-check-circle"></i> आवेदन सक्रिय
                            </span>
                            <a href="scheme_details.php?id=<?php echo $row['id']; ?>" class="btn-action-view" style="padding: 7px 14px;">
                                विवरण देखें <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <?php if($mb_count % 4 == 0): ?>
                        <div class="ad-card-box" style="margin-bottom:14px;">
                            <span>ADVERTISEMENT</span>
                            <div><!-- ADSENSE MOBILE IN-FEED AD CODE --></div>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>
            </div>

            <!-- Middle Ad -->
            <div class="ad-slot-responsive">
                <div class="ad-card-box">
                    <span>ADVERTISEMENT</span>
                    <div><!-- ADSENSE MIDDLE AD CODE --></div>
                </div>
            </div>

            <!-- 📄 Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-wrap">
                    <?php if($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&s=<?php echo urlencode($s_query); ?>&cat=<?php echo urlencode($cat_filter); ?>">&laquo; पिछला</a>
                    <?php endif; ?>

                    <?php for($i=1; $i<=$total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&s=<?php echo urlencode($s_query); ?>&cat=<?php echo urlencode($cat_filter); ?>" class="<?php echo ($page==$i)?'active':''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&s=<?php echo urlencode($s_query); ?>&cat=<?php echo urlencode($cat_filter); ?>">अगला &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Empty Search State -->
            <div style="text-align:center; padding:60px 20px; background:#fff; border-radius:16px; border:1px solid var(--app-border); margin: 20px 0;">
                <i class="fas fa-folder-open" style="font-size: 50px; color: #cbd5e1; margin-bottom: 12px;"></i>
                <h3 style="color:#1e293b; font-weight:700; margin-bottom: 8px;">कोई योजना नहीं मिली!</h3>
                <p style="color:#64748b; margin-bottom: 18px; font-size:0.9rem;">कृपया किसी अन्य कीवर्ड से खोजें या सभी योजनाएं देखें।</p>
                <a href="schemes.php" style="background: var(--app-primary); color: white; padding: 10px 22px; border-radius: 50px; text-decoration: none; font-weight: bold; font-size: 0.88rem; display: inline-block;">सभी योजनाएं देखें</a>
            </div>
        <?php endif; ?>

        <!-- Bottom Ad Banner -->
        <div class="ad-slot-responsive" style="margin-top: 30px;">
            <div class="ad-card-box">
                <span>ADVERTISEMENT</span>
                <div><!-- ADSENSE BOTTOM BANNER AD CODE --></div>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>