<?php
// डेटाबेस और हेडर शामिल करें
include 'includes/db_connect.php'; 
include 'includes/header.php'; 

// 🔥 FIX 1: 'type' की जगह 'cat' किया गया है ताकि index.php से सही डेटा आ सके
$cat_slug = isset($_GET['cat']) ? mysqli_real_escape_string($conn, $_GET['cat']) : 'job';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15; // एक पेज पर कितनी पोस्ट दिखानी हैं
$offset = ($page - 1) * $limit;

// कैटेगरी का नाम सुंदर दिखाने के लिए मैपिंग
$category_names = [
    'job' => '🔥 लेटेस्ट सरकारी नौकरियां (Latest Jobs)',
    'poster' => '🖼️ महत्वपूर्ण पोस्टर्स (Posters)',
    'result' => '✅ परीक्षा परिणाम (Results)',
    'admit_card' => '🎟️ एडमिट कार्ड (Admit Cards)',
    'scheme' => '🏛️ सरकारी योजनाएं (Schemes)',
    'education' => '📚 शिक्षा जगत (Education)',
    'admission' => '🏫 एडमिशन (Admissions)',
    'pdf_form' => '📄 पीडीएफ फॉर्म (PDF Forms)'
];

$display_name = isset($category_names[$cat_slug]) ? $category_names[$cat_slug] : 'वैकेंसी अपडेट';

// 🔥 FIX 2: Posters के लिए अलग डेटाबेस टेबल लॉजिक 
if ($cat_slug === 'poster') {
    $count_sql = "SELECT COUNT(id) FROM posters";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute();
    $total_rows = $count_stmt->get_result()->fetch_row()[0];
    
    // Posters टेबल में post_date या short_desc नहीं है, इसलिए NULL पास किया है
    $sql = "SELECT id, title, NULL as short_desc, NULL as post_date FROM posters ORDER BY id DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
} else {
    // अन्य सभी कैटेगरीज के लिए Vacancies टेबल
    $count_sql = "SELECT COUNT(id) FROM vacancies WHERE category = ? AND status = 'live'";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("s", $cat_slug);
    $count_stmt->execute();
    $total_rows = $count_stmt->get_result()->fetch_row()[0];

    $sql = "SELECT id, title, short_desc, post_date FROM vacancies WHERE category = ? AND status = 'live' ORDER BY id DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $cat_slug, $limit, $offset);
}

$total_pages = ceil($total_rows / $limit);
$stmt->execute();
$result = $stmt->get_result();
?>

<!-- Google Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Hind:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --brand-dark: #0f172a;
        --brand-blue: #2563eb;
        --brand-red: #dc2626;
        --text-main: #1e293b;
        --bg-color: #f8fafc;
        --border-color: #e2e8f0;
    }

    body { 
        background: var(--bg-color); 
        color: var(--text-main); 
        font-family: 'Hind', 'Plus Jakarta Sans', sans-serif; 
        margin: 0;
    }

    .container { max-width: 1400px; margin: 0 auto; padding: 0 15px; }

    /* 🚀 Category Hero Banner */
    .cat-hero {
        background: var(--brand-dark);
        background-image: linear-gradient(rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.9)), url('https://images.unsplash.com/photo-1450101499163-c8848c66ca85?q=80&w=2070&auto=format&fit=crop');
        background-size: cover;
        background-position: center;
        padding: 40px 0;
        color: white;
        text-align: left;
        border-bottom: 4px solid var(--brand-red);
    }
    
    .cat-hero h1 { font-size: 2.2rem; margin: 10px 0; font-weight: 800; letter-spacing: 0.5px; }
    .cat-hero p { color: #94a3b8; font-size: 1.1rem; margin: 0; }
    
    .breadcrumb { 
        background: transparent; padding: 0; margin-bottom: 15px; 
        list-style: none; display: flex; font-size: 0.95rem; font-weight: 500;
    }
    .breadcrumb-item a { color: #cbd5e1; text-decoration: none; transition: 0.2s; }
    .breadcrumb-item a:hover { color: white; }
    .breadcrumb-item.active { color: var(--brand-red); font-weight: 700; }
    .breadcrumb-item + .breadcrumb-item::before { content: "»"; color: #cbd5e1; padding: 0 10px; }

    /* 🎛️ Layout Grid (Content + Sidebar) */
    .content-sidebar-grid {
        display: grid;
        grid-template-columns: 1fr 350px; /* Sidebar is 350px wide */
        gap: 30px;
        margin: 40px 0;
        align-items: start;
    }

    /* 📋 List Design */
    .list-wrapper { 
        background: white; 
        border-radius: 12px; 
        border: 1px solid var(--border-color); 
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        overflow: hidden; 
    }
    
    .cat-item { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 20px 25px; 
        border-bottom: 1px solid var(--border-color); 
        transition: all 0.3s ease;
    }
    .cat-item:last-child { border-bottom: none; }
    
    .cat-item:hover { 
        background: #f1f5f9; 
        transform: translateX(5px);
        border-left: 4px solid var(--brand-red);
    }
    
    .cat-item h3 { font-size: 1.15rem; margin: 0 0 8px 0; }
    .cat-item h3 a { text-decoration: none; color: var(--brand-blue); font-weight: 700; }
    .cat-item h3 a:hover { color: var(--brand-red); text-decoration: underline; }
    
    .item-meta { font-size: 0.9rem; color: #64748b; display: flex; gap: 15px; }
    .item-meta span { display: flex; align-items: center; gap: 5px; }

    .apply-btn { 
        background: var(--brand-red); 
        color: white; 
        padding: 10px 25px; 
        border-radius: 50px; 
        text-decoration: none; 
        font-size: 0.95rem; 
        font-weight: 700;
        box-shadow: 0 4px 6px rgba(220, 38, 38, 0.2);
        transition: 0.3s ease;
        white-space: nowrap;
    }
    .apply-btn:hover { background: #b91c1c; transform: translateY(-2px); box-shadow: 0 6px 12px rgba(220, 38, 38, 0.3); }

    /* 🔢 Pagination */
    .pagination { display: flex; justify-content: center; gap: 10px; margin: 40px 0; flex-wrap: wrap; }
    .pagination a { 
        padding: 10px 18px; background: white; border: 1px solid var(--border-color); 
        text-decoration: none; color: var(--brand-dark); border-radius: 8px; font-weight: 600;
        transition: 0.3s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .pagination a:hover { border-color: var(--brand-blue); color: var(--brand-blue); }
    .pagination a.active { background: var(--brand-dark); color: white; border-color: var(--brand-dark); }

    /* 📱 Sidebar & Ads */
    .sidebar { display: flex; flex-direction: column; gap: 25px; }
    .side-card {
        background: white; border-radius: 12px; padding: 25px;
        border: 1px solid var(--border-color); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }
    .side-card h3 { margin-top: 0; font-size: 1.2rem; color: var(--brand-dark); border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;}
    
    .ad-container-cat { margin-bottom: 25px; text-align: center; }
    .ad-placeholder { background: #e2e8f0; border: 2px dashed #cbd5e1; padding: 40px; color: #64748b; font-weight: 600; border-radius: 12px; }
    .side-ad-sticky { position: sticky; top: 20px; }
    .ad-placeholder-v { background: #e2e8f0; height: 400px; display: flex; align-items: center; justify-content: center; color: #64748b; font-weight: 600; border-radius: 12px; border: 2px dashed #cbd5e1; }
    
    /* Social Buttons */
    .soc-btn { 
        display: flex; align-items: center; justify-content: center; gap: 10px;
        padding: 14px; border-radius: 8px; text-decoration: none; color: white; 
        font-weight: 700; margin-top: 15px; font-size: 1rem; transition: 0.3s;
    }
    .tg-btn { background: #0088cc; }
    .tg-btn:hover { background: #0077b3; }
    .wa-btn { background: #25d366; }
    .wa-btn:hover { background: #20bd5a; }

    .no-data { text-align: center; padding: 60px 20px; color: #64748b; }
    .no-data img { opacity: 0.5; margin-bottom: 15px; }
    .no-data p { font-size: 1.2rem; font-weight: 600; }

    /* 📱 Mobile Responsive */
    @media (max-width: 992px) {
        .content-sidebar-grid { grid-template-columns: 1fr; }
        .sidebar { order: -1; margin-bottom: 20px; } /* Sidebar goes to top on mobile */
    }
    @media (max-width: 768px) {
        .cat-hero h1 { font-size: 1.8rem; }
        .cat-item { flex-direction: column; align-items: flex-start; gap: 15px; padding: 15px; }
        .apply-btn { width: 100%; text-align: center; border-radius: 8px; }
        .cat-item:hover { transform: none; }
    }
</style>

<div class="cat-hero">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i> होम</a></li>
                <li class="breadcrumb-item active"><?php echo strtoupper(htmlspecialchars($cat_slug)); ?></li>
            </ol>
        </nav>
        <h1><?php echo htmlspecialchars($display_name); ?></h1>
        <p><i class="fas fa-list-ul"></i> कुल अपडेट उपलब्ध: <?php echo $total_rows; ?></p>
    </div>
</div>

<div class="container main-layout">
    <div class="content-sidebar-grid">
        
        <!-- Left Side: Main Content -->
        <main class="main-content">
            <div class="ad-container-cat">
                <div class="ad-placeholder">Google AdSense Horizontal Banner</div>
            </div>

            <div class="list-wrapper">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="cat-item">
                            <div class="item-info">
                                <h3><a href="vacancy_details.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></h3>
                                <div class="item-meta">
                                    <?php if (!empty($row['post_date'])): ?>
                                        <span><i class="far fa-calendar-alt"></i> <?php echo date('d M, Y', strtotime($row['post_date'])); ?></span>
                                    <?php else: ?>
                                        <span><i class="fas fa-bolt"></i> नया अपडेट</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="vacancy_details.php?id=<?php echo $row['id']; ?>" class="apply-btn">पूरी जानकारी देखें</a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">
                        <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="80" alt="No Data Found">
                        <p>अभी इस कैटेगरी में कोई नया अपडेट नहीं है।</p>
                        <a href="index.php" class="apply-btn" style="display:inline-block; margin-top:15px;">होमपेज पर वापस जाएं</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?cat=<?php echo htmlspecialchars($cat_slug); ?>&page=<?php echo $page-1; ?>"><i class="fas fa-angle-double-left"></i> पिछला</a>
                    <?php endif; ?>

                    <?php 
                    // एडवांस पेजनेशन (सिर्फ 5 पेज नंबर दिखाएगा ताकि मोबाइल पर अच्छा दिखे)
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for($i = $start_page; $i <= $end_page; $i++): 
                    ?>
                        <a href="?cat=<?php echo htmlspecialchars($cat_slug); ?>&page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if($page < $total_pages): ?>
                        <a href="?cat=<?php echo htmlspecialchars($cat_slug); ?>&page=<?php echo $page+1; ?>">अगला <i class="fas fa-angle-double-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>

        <!-- Right Side: Sidebar -->
        <aside class="sidebar">
            <div class="side-card">
                <h3>📢 सोशल मीडिया जॉइन करें</h3>
                <p style="color:#64748b; font-size:0.95rem; margin-top:0;">सबसे पहले लाइव अपडेट्स और पीडीएफ पाने के लिए हमारे ग्रुप्स से जुड़ें:</p>
                <a href="#" class="soc-btn tg-btn"><i class="fab fa-telegram-plane"></i> Telegram Group</a>
                <a href="#" class="soc-btn wa-btn"><i class="fab fa-whatsapp"></i> WhatsApp Group</a>
            </div>

            <div class="side-ad-sticky">
                <div class="ad-placeholder-v">AdSense Vertical Ad<br>(300x600)</div>
            </div>
        </aside>

    </div>
</div>

<?php 
$stmt->close();
$count_stmt->close();
$conn->close();
include 'includes/footer.php'; 
?>