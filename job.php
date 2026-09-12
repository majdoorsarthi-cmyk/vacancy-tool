<?php
// 1. डेटाबेस कनेक्शन
include 'includes/db_connect.php'; 

if (!$conn) {
    die("कनेक्शन विफल रहा: " . mysqli_connect_error());
}

// XSS सुरक्षा सहायक फ़ंक्शन
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// वर्तमान वर्ष (भविष्य में ऑटो-अपडेट हेतु)
$current_year = date('Y');

// 2. एक्सपायर्ड वैकेंसी ऑटो-क्लिनअप
$today = date('Y-m-d');

/* 
🛠️ एरर फिक्स: यहाँ "SELECT *" की जगह उन सभी कॉलम्स के नाम लिख दिए गए हैं 
जो 'vacancies' टेबल में होते हैं। इससे "Column count" एरर नहीं आएगा। 
*/
$cleanup_sql = "INSERT IGNORE INTO expired_vacancies (
    operator_id, title, category, state, short_desc, result_link, long_details, 
    description, important_dates, eligibility, application_fee, apply_link, 
    official_website, post_date, last_date, status, full_details, total_posts
) 
SELECT 
    operator_id, title, category, state, short_desc, result_link, long_details, 
    description, important_dates, eligibility, application_fee, apply_link, 
    official_website, post_date, last_date, status, full_details, total_posts 
FROM vacancies 
WHERE last_date < '$today' AND last_date IS NOT NULL AND last_date != '0000-00-00'";

$conn->query($cleanup_sql);

// एक्सपायर्ड वैकेंसी को मुख्य टेबल से डिलीट करें
$conn->query("DELETE FROM vacancies WHERE last_date < '$today' AND last_date IS NOT NULL AND last_date != '0000-00-00'");

// 3. सर्च और पेजिनेशन लॉजिक
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// 4. कुल रिकॉर्ड्स गिनें (Prepared Statement)
$count_sql = "SELECT COUNT(*) as total FROM vacancies WHERE status='live' AND category='job'";
if (!empty($search_query)) {
    $count_sql .= " AND (title LIKE ? OR short_desc LIKE ? OR state LIKE ?)";
}

$stmt_count = $conn->prepare($count_sql);
if (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    $stmt_count->bind_param('sss', $search_param, $search_param, $search_param);
}
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = max(1, (int)ceil($total_rows / $limit));

// AdSense Bot सुरक्षा: यदि पेज सीमा से बाहर हो
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// 5. मुख्य डेटा फ़ेच करें
$sql = "SELECT id, title, category, state, short_desc, post_date, last_date FROM vacancies WHERE status='live' AND category='job'";
$params = [];
$types = '';

if (!empty($search_query)) {
    $sql .= " AND (title LIKE ? OR short_desc LIKE ? OR state LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$sql .= " ORDER BY post_date DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$job_vacancies = [];
$state_vacancies = [];
$all_recent = [];

while($row = $result->fetch_assoc()) {
    $row['days_left'] = "";
    if(!empty($row['last_date']) && $row['last_date'] != '0000-00-00') {
        $diff = strtotime($row['last_date']) - strtotime($today);
        $days = (int)floor($diff / (60 * 60 * 24));
        if($days >= 0) {
            $row['days_left'] = " ($days दिन शेष)";
        }
    }

    $all_recent[] = $row;
    $job_vacancies[] = $row;
    $state_name = !empty($row['state']) ? $row['state'] : 'ऑल इंडिया';
    $state_vacancies[$state_name][] = $row;
}

// Meta Variables (Header के लिए - 100% हिंदी और Dynamic Year)
$page_title = "नवीनतम सरकारी नौकरी भर्ती समाचार {$current_year} " . ($page > 1 ? "- पृष्ठ $page" : "") . " | वैकेंसी पोर्टल";
$page_desc = "भारत के सभी राज्यों और केंद्र सरकार की नवीनतम नौकरियों, ऑनलाइन आवेदन पत्रों, प्रवेश पत्रों, परीक्षा तिथियों और राज्यवार भर्ती सूचनाओं के वास्तविक समय अपडेट प्राप्त करें।";

// हेडर शामिल करें
include 'includes/header.php'; 
?>

<!-- Google Structured Data (JSON-LD Schema) -->
<?php if (!empty($job_vacancies)): ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": "नवीनतम सरकारी नौकरियां <?php echo $current_year; ?>",
  "itemListElement": [
    <?php 
    $schema_items = [];
    foreach($job_vacancies as $index => $item) {
        $schema_items[] = json_encode([
            "@type" => "ListItem",
            "position" => $index + 1,
            "name" => $item['title'],
            "url" => "https://vacancyportal.co.in/vacancy_details.php?id=" . $item['id']
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    echo implode(',', $schema_items);
    ?>
  ]
}
</script>
<?php endif; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- आधुनिक समाचार टिकर -->
<?php if (!empty($all_recent)): ?>
<div class="news-ticker">
    <div class="container flex-ticker">
        <div class="ticker-label"><i class="fas fa-bolt"></i> नवीनतम नौकरियां</div>
        <div class="ticker-content-wrapper">
            <div class="ticker-content">
                <?php foreach (array_slice($all_recent, 0, 8) as $item): ?>
                    <a href="vacancy_details.php?id=<?php echo (int)$item['id']; ?>" class="ticker-item">
                        <span class="dot"></span> 
                        <span class="ticker-state">[<?php echo e($item['state'] ?? 'ऑल इंडिया'); ?>]</span> 
                        <?php echo e($item['title']); ?> 
                        <span class="ticker-days"><?php echo e($item['days_left']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container main-layout">
    
    <!-- गूगल एडसेंस टॉप बैनर स्थान -->
    <div class="ad-container top-ad">
        <!-- Google AdSense Responsive Code Here -->
    </div>

    <!-- विस्तृत SEO & High-Value Intro Card (AdSense & SEO Friendly) -->
    <section class="seo-intro-card">
        <h1 class="seo-heading"><i class="fas fa-briefcase"></i> नवीनतम सरकारी नौकरी, भर्ती सूचनाएं एवं अपडेट्स <?php echo $current_year; ?></h1>
        <p class="seo-text">
            <strong>वैकेंसी पोर्टल (Vacancy Portal)</strong> पर आपका स्वागत है। यह प्लेटफ़ॉर्म भारत के सभी राज्यों और केंद्र सरकार द्वारा जारी की जाने वाली **नवीनतम सरकारी नौकरियों (Sarkari Naukri)**, ऑनलाइन आवेदन पत्र, प्रवेश पत्र (Admit Card), परीक्षा तिथियां, उत्तर कुंजी (Answer Key) और परीक्षा परिणामों की 100% सटीक एवं प्रामाणिक जानकारी प्रदान करने के लिए समर्पित है।
        </p>
        <p class="seo-text">
            हम **SSC (CGL, CHSL, GD, MTS), रेलवे (RRB NTPC, Group D), बैंकिंग (IBPS, SBI), रक्षा (सेना, नौसेना, वायुसेना), UPSC, MPPSC, UPPSC, व्यापम (ESB)** एवं विभिन्न राज्यों की पुलिस भर्ती प्रक्रियाओं से जुड़ी हर महत्वपूर्ण अधिसूचना का बारीकी से सत्यापन करते हैं। हमारा उद्देश्य अभ्यर्थियों तक बिना किसी भ्रम के सीधा और प्रामाणिक नोटिफिकेशन पहुँचाना है।
        </p>
        <div class="seo-features">
            <span class="feature-item"><i class="fas fa-check-circle"></i> दैनिक नवीनतम अपडेट्स</span>
            <span class="feature-item"><i class="fas fa-check-circle"></i> आधिकारिक लिंक एवं PDF</span>
            <span class="feature-item"><i class="fas fa-check-circle"></i> राज्यवार एवं योग्यता अनुसार खोज</span>
        </div>
    </section>

    <div class="content-sidebar-grid">
        <main class="main-content">
            
            <div class="section-title-bar">
                <h2><i class="fas fa-list-alt"></i> हाल ही में जारी रिक्तियां (Latest Active Jobs)</h2>
            </div>

            <div class="post-list">
                <?php if (!empty($job_vacancies)): ?>
                    <?php foreach ($job_vacancies as $post): ?>
                        <article class="vacancy-item">
                            <div class="item-meta">
                                <span class="badge badge-job">🔥 नई जॉब</span>
                                <span class="date">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo e($post['state'] ?? 'ऑल इंडिया'); ?> 
                                    <b class="days-alert"><?php echo e($post['days_left']); ?></b>
                                </span>
                            </div>
                            <h3><a href="vacancy_details.php?id=<?php echo (int)$post['id']; ?>"><?php echo e($post['title']); ?></a></h3>
                            <p><?php echo e($post['short_desc']); ?></p>
                            <a href="vacancy_details.php?id=<?php echo (int)$post['id']; ?>" class="read-more" aria-label="<?php echo e($post['title']); ?> का पूरा विवरण देखें">
                                विस्तृत विवरण देखें <i class="fas fa-arrow-right"></i>
                            </a>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-msg">वर्तमान में कोई नई जॉब रिक्ति उपलब्ध नहीं है। कृपया शीघ्र ही पुनः प्रयास करें।</div>
                <?php endif; ?>
            </div>

            <!-- AdSense अनुकूल पेजिनेशन -->
            <?php if ($total_pages > 1): ?>
            <nav class="pagination-wrapper" aria-label="पृष्ठ नेविगेशन">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="page-link prev">
                        <i class="fas fa-chevron-left"></i> पिछला पृष्ठ
                    </a>
                <?php endif; ?>

                <span class="page-info">पृष्ठ <?php echo $page; ?> / <?php echo $total_pages; ?></span>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="page-link next">
                        अगला पृष्ठ <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </main>

        <!-- साइडबार सेक्शन -->
        <aside class="sidebar">
            <div class="sidebar-card">
                <h3><i class="fas fa-tags"></i> राज्य अनुसार खोजें</h3>
                <div class="state-tags-grid">
                    <?php foreach (array_keys($state_vacancies) as $st): ?>
                        <a href="?search=<?php echo urlencode($st); ?>" class="state-tag">
                            <i class="fas fa-map-marker-alt" style="font-size: 0.7rem; opacity: 0.7;"></i> <?php echo e($st); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="sidebar-card">
                <h3><i class="fas fa-link"></i> महत्वपूर्ण आधिकारिक पोर्टल</h3>
                <ul class="sidemenu">
                    <li>
                        <a href="https://upsssc.gov.in/" target="_blank" rel="nofollow noopener noreferrer">
                            <i class="fas fa-chevron-right"></i> उत्तर प्रदेश अधीनस्थ सेवा (UPSSSC)
                        </a>
                    </li>
                    <li>
                        <a href="https://ssc.gov.in/" target="_blank" rel="nofollow noopener noreferrer">
                            <i class="fas fa-chevron-right"></i> कर्मचारी चयन आयोग (SSC)
                        </a>
                    </li>
                    <li>
                        <a href="https://csbc.bihar.gov.in/" target="_blank" rel="nofollow noopener noreferrer">
                            <i class="fas fa-chevron-right"></i> बिहार पुलिस कांस्टेबल भर्ती (CSBC)
                        </a>
                    </li>
                </ul>
            </div>

            <!-- साइडबार विज्ञापन स्थान -->
            <div class="ad-container sidebar-ad">
                <!-- Paste Google AdSense Code Here -->
            </div>

            <div class="sidebar-card">
                <h3><i class="fas fa-share-nodes"></i> सोशल मीडिया से जुड़ें</h3>
                <div class="social-grid">
                    <a href="https://t.me/vacancyportal_official" target="_blank" rel="nofollow noopener noreferrer" class="s-btn tg">
                        <i class="fab fa-telegram-plane"></i> टेलीग्राम
                    </a>
                    <a href="https://chat.whatsapp.com/GF1BDKhdBVZ5AH59aVmcRJ" target="_blank" rel="nofollow noopener noreferrer" class="s-btn wa">
                        <i class="fab fa-whatsapp"></i> व्हाट्सएप
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>

<style>
    :root {
        --primary: #2563eb;
        --primary-hover: #1d4ed8;
        --secondary: #1e293b;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --bg-light: #f8fafc;
        --text-main: #334155;
        --text-dark: #0f172a;
        --border: #e2e8f0;
        --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
        --radius: 12px;
    }

    body { 
        background: var(--bg-light); 
        color: var(--text-main); 
        font-family: 'Hind', sans-serif; 
        margin: 0; 
        line-height: 1.6;
    }

    .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

    /* समाचार टिकर */
    .news-ticker { background: #ffffff; border-bottom: 1px solid var(--border); box-shadow: var(--shadow); }
    .flex-ticker { display: flex; align-items: center; height: 50px; overflow: hidden; }
    .ticker-label { 
        background: var(--primary); 
        color: #ffffff; 
        padding: 5px 14px; 
        font-weight: 700; 
        font-size: 0.85rem; 
        margin-right: 20px; 
        border-radius: 6px; 
        white-space: nowrap;
        z-index: 2;
    }
    .ticker-content-wrapper { overflow: hidden; white-space: nowrap; width: 100%; }
    .ticker-content { display: inline-block; animation: ticker 25s linear infinite; }
    .ticker-content:hover { animation-play-state: paused; }
    @keyframes ticker {
        0% { transform: translate3d(100%, 0, 0); }
        100% { transform: translate3d(-100%, 0, 0); }
    }
    .ticker-item { color: var(--text-dark); text-decoration: none; margin-right: 40px; font-weight: 500; font-size: 0.95rem; }
    .ticker-item:hover { color: var(--primary); }
    .ticker-state { color: var(--primary); font-weight: 600; }
    .ticker-days { color: var(--danger); font-weight: 600; }
    .dot { height: 8px; width: 8px; background: var(--danger); border-radius: 50%; display: inline-block; margin-right: 8px; }

    /* SEO भूमिका कार्ड - Improved Design & Content */
    .seo-intro-card {
        background: #ffffff;
        padding: 24px;
        border-radius: var(--radius);
        border: 1px solid var(--border);
        margin-bottom: 25px;
        box-shadow: var(--shadow);
        border-left: 5px solid var(--primary);
    }
    .seo-heading {
        font-size: 1.35rem;
        color: var(--text-dark);
        margin-top: 0;
        margin-bottom: 12px;
        font-weight: 700;
    }
    .seo-text {
        font-size: 0.96rem;
        color: var(--text-main);
        margin: 0 0 10px 0;
        line-height: 1.7;
    }
    .seo-features {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        margin-top: 15px;
        padding-top: 12px;
        border-top: 1px dashed var(--border);
    }
    .feature-item {
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* मुख्य ग्रिड लेआउट */
    .main-layout { margin-top: 25px; }
    .content-sidebar-grid { display: grid; grid-template-columns: 2.2fr 1fr; gap: 30px; margin-bottom: 50px; }

    .section-title-bar h2 {
        font-size: 1.25rem;
        color: var(--text-dark);
        margin-top: 0;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--primary);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* पोस्ट कार्ड */
    .vacancy-item { 
        background: #ffffff; 
        padding: 24px; 
        border-radius: var(--radius); 
        border: 1px solid var(--border); 
        margin-bottom: 20px; 
        box-shadow: var(--shadow);
        transition: transform 0.2s ease, box-shadow 0.2s ease; 
    }
    .vacancy-item:hover { transform: translateY(-3px); box-shadow: 0 12px 20px -5px rgba(0,0,0,0.08); }
    .item-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    
    .badge { padding: 5px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; color: #ffffff; display: inline-flex; align-items: center; }
    .badge-job { background: var(--primary); }
    
    .date { font-size: 0.9rem; color: #64748b; display: flex; align-items: center; gap: 5px; }
    .days-alert { color: var(--danger); font-weight: 600; }
    
    .vacancy-item h3 { margin: 10px 0; font-size: 1.2rem; }
    .vacancy-item h3 a { color: var(--text-dark); text-decoration: none; }
    .vacancy-item h3 a:hover { color: var(--primary); }
    .vacancy-item p { color: #475569; margin: 0 0 15px 0; font-size: 0.98rem; }
    
    .read-more { color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 6px; }

    /* पेजिनेशन */
    .pagination-wrapper { margin: 30px 0; display: flex; justify-content: center; align-items: center; gap: 15px; }
    .page-info { font-weight: 600; font-size: 0.9rem; color: #64748b; }
    .page-link { padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; }
    .page-link.prev { background: #ffffff; color: var(--secondary); border: 1px solid var(--border); }
    .page-link.next { background: var(--primary); color: #ffffff; }

    /* साइडबार कार्ड्स */
    .sidebar-card { background: #ffffff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--border); margin-bottom: 25px; box-shadow: var(--shadow); }
    .sidebar-card h3 { font-size: 1.15rem; margin-top: 0; margin-bottom: 18px; border-bottom: 2px solid var(--bg-light); padding-bottom: 12px; color: var(--text-dark); display: flex; align-items: center; gap: 10px; }
    
    .state-tags-grid { display: flex; flex-wrap: wrap; gap: 8px; }
    .state-tag { font-size: 0.85rem; background: #f1f5f9; padding: 6px 12px; border-radius: 6px; text-decoration: none; color: #475569; border: 1px solid var(--border); }
    .state-tag:hover { background: var(--primary); color: #ffffff; }

    .sidemenu { list-style: none; padding: 0; margin: 0; }
    .sidemenu li { border-bottom: 1px solid #f1f5f9; }
    .sidemenu li a { text-decoration: none; color: #475569; font-size: 0.95rem; padding: 12px 0; display: block; font-weight: 500; }
    .sidemenu li a:hover { color: var(--primary); }

    .social-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .s-btn { padding: 12px; text-align: center; border-radius: 8px; text-decoration: none; color: #ffffff; font-size: 0.95rem; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; }
    .tg { background: #0088cc; }
    .wa { background: #22c55e; }

    /* एडसेंस सुरक्षा */
    .ad-container { margin: 20px 0; min-height: 90px; text-align: center; }
    .ad-container:empty { display: none; }
    
    .empty-msg { text-align: center; padding: 40px; background: #ffffff; border-radius: var(--radius); border: 1px solid var(--border); color: #64748b; }

    @media (max-width: 992px) {
        .content-sidebar-grid { grid-template-columns: 1fr; }
    }
</style>

<?php include 'includes/footer.php'; ?>