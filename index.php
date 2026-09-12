<?php
// डेटाबेस और हेडर शामिल करें
include 'includes/db_connect.php'; 

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// --- 1. AJAX LIVE SEARCH HANDLING (बिना रीलोड के) ---
if (isset($_GET['ajax_search'])) {
    header('Content-Type: application/json');
    $q = trim($_GET['ajax_search']);
    
    if (strlen($q) >= 3) {
        $sq = "%" . $q . "%";
        // Title या Short Desc में सर्च करें
        $stmt = $conn->prepare("SELECT id, title, category FROM vacancies WHERE title LIKE ? OR short_desc LIKE ? ORDER BY id DESC LIMIT 10");
        $stmt->bind_param("ss", $sq, $sq);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode($data);
        $stmt->close();
    } else {
        echo json_encode([]);
    }
    exit; // AJAX कॉल के बाद पेज का बाकी हिस्सा लोड न हो
}

$today = date('Y-m-d');
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// --- 2. REGULAR PAGE LOAD DATA FETCHING ---

// Ticker Data Fetching (लेटेस्ट 8 पोस्ट्स)
$ticker_posts = [];
$ticker_res = $conn->query("SELECT id, title, state, last_date FROM vacancies ORDER BY id DESC LIMIT 8");
if ($ticker_res) {
    while ($t_row = $ticker_res->fetch_assoc()) {
        $ticker_posts[] = $t_row;
    }
}

// 8 Columns Data Fetching 
$limit = 25; // 🔥 FIX: हर कॉलम में अब 25 लिंक्स दिखेंगे

$res_jobs      = $conn->query("SELECT id, title FROM vacancies WHERE category='job' ORDER BY id DESC LIMIT $limit");
$res_posters   = $conn->query("SELECT id, title FROM posters ORDER BY id DESC LIMIT $limit"); 
$res_result    = $conn->query("SELECT id, title FROM vacancies WHERE category='result' ORDER BY id DESC LIMIT $limit");
$res_admit     = $conn->query("SELECT id, title FROM vacancies WHERE category='admit_card' ORDER BY id DESC LIMIT $limit");
$res_schemes   = $conn->query("SELECT id, title FROM vacancies WHERE category='scheme' ORDER BY id DESC LIMIT $limit");
$res_education = $conn->query("SELECT id, title FROM vacancies WHERE category='education' ORDER BY id DESC LIMIT $limit");
$res_admission = $conn->query("SELECT id, title FROM vacancies WHERE category='admission' ORDER BY id DESC LIMIT $limit");
$res_pdf       = $conn->query("SELECT id, title FROM vacancies WHERE category='pdf_form' ORDER BY id DESC LIMIT $limit");

include 'includes/header.php'; 
?>

<!-- Google Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Hind:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --brand-dark: #0f172a;
        --bg-surface: #f8fafc;
        --text-main: #1e293b;
        --border-color: #cbd5e1;
        --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --box-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    body {
        background-color: var(--bg-surface);
        color: var(--text-main);
        font-family: 'Hind', 'Plus Jakarta Sans', sans-serif;
        margin: 0;
        line-height: 1.5;
    }

    .container { max-width: 1400px; margin: 0 auto; padding: 0 15px; }

    /* 🚀 HERO SECTION & ADVANCED SEARCH */
    .hero-portal {
        background: var(--brand-dark);
        padding: 50px 0;
        text-align: center;
        color: white;
        background-image: linear-gradient(rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.85)), url('https://images.unsplash.com/photo-1521737711867-e3b97375f902?q=80&w=1500');
        background-size: cover;
        background-position: center;
    }

    .hero-title { font-size: 2.5rem; font-weight: 800; margin-bottom: 8px; letter-spacing: 0.5px; }
    .hero-subtitle { font-size: 1.15rem; color: #94a3b8; margin-bottom: 25px; }

    /* Search Container for Relative Positioning */
    .search-container {
        max-width: 650px;
        margin: 0 auto;
        position: relative;
    }

    .search-form {
        display: flex;
        background: #fff;
        border-radius: 50px;
        overflow: hidden;
        box-shadow: 0 15px 30px rgba(0,0,0,0.3);
        position: relative;
        z-index: 10;
    }

    .search-form input {
        flex: 1; border: none; padding: 18px 25px;
        font-size: 1.1rem; outline: none; font-family: inherit;
        color: #0f172a;
    }

    .search-form button {
        background: #dc2626; color: white; border: none;
        padding: 0 35px; font-weight: 700; font-size: 1.1rem;
        cursor: pointer; transition: background 0.3s ease;
    }

    .search-form button:hover { background: #b91c1c; }

    /* 🔍 LIVE AJAX SEARCH DROPDOWN */
    .live-search-results {
        position: absolute;
        top: 60px;
        left: 0;
        width: 100%;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        list-style: none;
        padding: 0;
        margin: 0;
        z-index: 9;
        display: none;
        max-height: 400px;
        overflow-y: auto;
        text-align: left;
    }

    .live-search-results li {
        border-bottom: 1px solid #f1f5f9;
    }

    .live-search-results li:last-child {
        border-bottom: none;
    }

    .live-search-results a {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 25px;
        color: #1e293b;
        text-decoration: none;
        font-weight: 600;
        font-size: 1rem;
        transition: background 0.2s;
    }

    .live-search-results a:hover { background: #f8fafc; color: #2563eb; }
    
    .cat-badge {
        font-size: 0.75rem; background: #e2e8f0; color: #475569;
        padding: 4px 8px; border-radius: 4px; text-transform: uppercase;
        font-weight: 700; margin-left: 10px; white-space: nowrap;
    }

    .no-result-item { padding: 15px 25px; color: #ef4444; font-weight: 500; }

    /* 📢 TICKER BAR */
    .portal-ticker-bar { background: #ffffff; border-bottom: 1px solid var(--border-color); padding: 10px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .ticker-flex { display: flex; align-items: center; }
    .ticker-badge { background: #dc2626; color: white; padding: 6px 14px; font-weight: 700; font-size: 0.85rem; border-radius: 6px; white-space: nowrap; margin-right: 15px; text-transform: uppercase; letter-spacing: 0.5px; }
    .ticker-flex marquee { font-weight: 600; font-size: 0.95rem; }
    .ticker-link { text-decoration: none; color: #1e293b; margin-right: 40px; transition: color 0.2s; }
    .ticker-link:hover { color: #dc2626; text-decoration: underline; }

    /* 🎛️ 8-COLUMN GRID LAYOUT (4x2 on Desktop) */
    .sarkari-main-table { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin: 40px 0 70px 0; }
    .sarkari-box { background: #fff; border-radius: 10px; overflow: hidden; box-shadow: var(--box-shadow); display: flex; flex-direction: column; transition: transform 0.3s ease, box-shadow 0.3s ease; border: 1px solid #e2e8f0; }
    .sarkari-box:hover { transform: translateY(-3px); box-shadow: var(--box-shadow-hover); }
    .box-head { color: #fff; font-weight: 700; text-align: center; padding: 14px; font-size: 1.25rem; letter-spacing: 0.5px; }

    /* 🎨 Colors */
    .bg-jobs      { background: linear-gradient(135deg, #1e3a8a, #2563eb); } 
    .bg-posters   { background: linear-gradient(135deg, #b45309, #d97706); } 
    .bg-result    { background: linear-gradient(135deg, #9f1239, #e11d48); } 
    .bg-admit     { background: linear-gradient(135deg, #14532d, #16a34a); } 
    .bg-schemes   { background: linear-gradient(135deg, #0c4a6e, #0284c7); } 
    .bg-education { background: linear-gradient(135deg, #4c1d95, #7c3aed); } 
    .bg-admission { background: linear-gradient(135deg, #881337, #be123c); } 
    .bg-pdf       { background: linear-gradient(135deg, #1e293b, #475569); } 

    .sarkari-list { list-style: none; padding: 0; margin: 0; flex-grow: 1; }
    .sarkari-list li { border-bottom: 1px solid #f1f5f9; }
    .sarkari-list li:last-child { border-bottom: none; }
    .sarkari-list a { display: block; padding: 12px 18px; color: #2563eb; text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: all 0.2s ease; position: relative; padding-left: 25px; }
    .sarkari-list a::before { content: '▪'; position: absolute; left: 12px; color: #94a3b8; }
    .sarkari-list a:hover { background: #f8fafc; color: #dc2626; padding-left: 28px; }
    .sarkari-list a:hover::before { color: #dc2626; }
    .no-update { padding: 15px; text-align: center; color: #64748b; font-weight: 500; font-size: 0.95rem; }

    .view-more-btn { display: block; text-align: center; padding: 12px; background: #f1f5f9; color: #0f172a; font-weight: 700; text-decoration: none; border-top: 1px solid #e2e8f0; margin-top: auto; transition: all 0.3s ease; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .view-more-btn:hover { background: #e2e8f0; color: #dc2626; }

    /* 📱 MOBILE RESPONSIVE OPTIMIZATIONS (Shandar Look) */
    @media (max-width: 1200px) { .sarkari-main-table { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 992px) { .sarkari-main-table { grid-template-columns: repeat(2, 1fr); gap: 20px; } }
    @media (max-width: 768px) {
        .hero-portal { padding: 35px 0; }
        .hero-title { font-size: 1.8rem; }
        .hero-subtitle { font-size: 1rem; }
        
        .search-form { flex-direction: column; border-radius: 12px; }
        .search-form input { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; border-radius: 12px 12px 0 0; }
        .search-form button { padding: 16px; width: 100%; border-radius: 0 0 12px 12px; }
        .live-search-results { top: 110px; border-radius: 8px; }

        .sarkari-main-table { grid-template-columns: 1fr; gap: 25px; margin: 30px 0; }
        .sarkari-box { border-width: 1px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .box-head { padding: 14px; font-size: 1.2rem; }
        
        /* Mobile par tap karna aasan banane ke liye height badhayi gayi hai */
        .sarkari-list a { padding: 16px 15px 16px 28px; font-size: 1.05rem; }
        .sarkari-list a::before { top: 18px; font-size: 1.2rem; }
        
        .ticker-badge { display: none; }
    }
</style>

<!-- HERO & SEARCH SECTION -->
<div class="hero-portal">
    <div class="container">
        <h1 class="hero-title">सरकारी नौकरी और रिजल्ट पोर्टल</h1>
        <p class="hero-subtitle">भारत का सबसे तेज़ और भरोसेमंद जॉब अपडेट्स प्लेटफॉर्म</p>
        
        <div class="search-container">
            <form class="search-form" action="index.php" method="GET" id="main-search-form">
                <input type="text" name="search" id="live-search-input" autocomplete="off" placeholder="नौकरी, रिजल्ट या एडमिट कार्ड खोजें..." value="<?php echo e($search_query); ?>" required>
                <button type="submit"><i class="fas fa-search"></i> खोजें</button>
            </form>
            <!-- AJAX Live Results Dropdown -->
            <ul class="live-search-results" id="live-search-results"></ul>
        </div>
    </div>
</div>

<!-- 📢 LIVE NEWS TICKER -->
<?php if (!empty($ticker_posts)): ?>
<div class="portal-ticker-bar">
    <div class="container ticker-flex">
        <div class="ticker-badge"><i class="fas fa-bolt"></i> LATEST UPDATE</div>
        <marquee behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">
            <?php foreach ($ticker_posts as $tk): ?>
                <a href="vacancy_details.php?id=<?php echo $tk['id']; ?>" class="ticker-link">
                    [<?php echo e($tk['state'] ?: 'All India'); ?>] <?php echo e($tk['title']); ?>
                </a>
            <?php endforeach; ?>
        </marquee>
    </div>
</div>
<?php endif; ?>

<!-- 🎛️ MAIN CONTENT AREA -->
<div class="container">
    
    <?php if (!empty($search_query) && !isset($_GET['ajax_search'])): ?>
        <!-- REGULAR SEARCH RESULTS VIEW -->
        <div style="background: #fff; padding: 25px; margin: 40px 0; border-radius: 10px; box-shadow: var(--box-shadow);">
            <h3>"<span style="color:#dc2626;"><?php echo e($search_query); ?></span>" के लिए खोज परिणाम:</h3>
            <ul class="sarkari-list">
                <?php
                $sq = "%" . $search_query . "%";
                $stmt = $conn->prepare("SELECT id, title, category FROM vacancies WHERE title LIKE ? OR short_desc LIKE ? ORDER BY id DESC LIMIT 50");
                $stmt->bind_param("ss", $sq, $sq);
                $stmt->execute();
                $search_res = $stmt->get_result();
                
                if ($search_res->num_rows > 0) {
                    while ($s_row = $search_res->fetch_assoc()) {
                        echo '<li><a href="vacancy_details.php?id='.$s_row['id'].'">'.e($s_row['title']).' <span style="color:#64748b; font-size:0.85rem;">('.strtoupper($s_row['category']).')</span></a></li>';
                    }
                } else {
                    echo "<li class='no-update'>कोई जानकारी नहीं मिली। कृपया कुछ और लिखकर खोजें।</li>";
                }
                $stmt->close();
                ?>
            </ul>
            <a href="index.php" class="view-more-btn" style="margin-top:20px; background:#dc2626; color:#fff; border-radius: 8px;">वापस होमपेज पर जाएँ</a>
        </div>

    <?php else: ?>
        <!-- 8-COLUMN SARKARI LAYOUT -->
        <div class="sarkari-main-table">
            
            <!-- COLUMN 1: LATEST JOBS -->
            <div class="sarkari-box">
                <div class="box-head bg-jobs">नई जॉब्स</div>
                <ul class="sarkari-list">
                    <?php
                    if ($res_jobs && $res_jobs->num_rows > 0) {
                        while($row = $res_jobs->fetch_assoc()) {
                            echo '<li><a href="vacancy_details.php?id='.$row['id'].'">'.e($row['title']).'</a></li>';
                        }
                    } else { echo '<div class="no-update">No Updates Available</div>'; }
                    ?>
                </ul>
                <a href="category.php?cat=job" class="view-more-btn">View All Jobs <i class="fas fa-angle-right"></i></a>
            </div>

           <!-- COLUMN 2: POSTERS -->
            <div class="sarkari-box">
                <div class="box-head bg-posters">पोस्टर्स</div>
                <ul class="sarkari-list">
                    <?php
                    if ($res_posters && $res_posters->num_rows > 0) {
                        while($row = $res_posters->fetch_assoc()) {
                            // 🔥 यहाँ सुधार किया है: vacancy_details की जगह poster_details कर दिया है
                            echo '<li><a href="poster_details.php?id='.$row['id'].'">'.e($row['title']).'</a></li>';
                        }
                    } else { echo '<div class="no-update">No Updates Available</div>'; }
                    ?>
                </ul>
                <a href="category.php?cat=poster" class="view-more-btn">View All Posters <i class="fas fa-angle-right"></i></a>
            </div>

            <!-- COLUMN 3: RESULT -->
            <div class="sarkari-box">
                <div class="box-head bg-result">रिजल्ट</div>
                <ul class="sarkari-list">
                    <?php
                    if ($res_result && $res_result->num_rows > 0) {
                        while($row = $res_result->fetch_assoc()) {
                            echo '<li><a href="result_details.php?id='.$row['id'].'">'.e($row['title']).'</a></li>';
                        }
                    } else { echo '<div class="no-update">No Updates Available</div>'; }
                    ?>
                </ul>
                <a href="category.php?cat=result" class="view-more-btn">View All Results <i class="fas fa-angle-right"></i></a>
            </div>

            <!-- COLUMN 4: ADMIT CARD -->
            <div class="sarkari-box">
                <div class="box-head bg-admit">एडमिट कार्ड</div>
                <ul class="sarkari-list">
                    <?php
                    if ($res_admit && $res_admit->num_rows > 0) {
                        while($row = $res_admit->fetch_assoc()) {
                            echo '<li><a href="admitdetails.php?id='.$row['id'].'">'.e($row['title']).'</a></li>';
                        }
                    } else { echo '<div class="no-update">No Updates Available</div>'; }
                    ?>
                </ul>
                <a href="category.php?cat=admit_card" class="view-more-btn">View All Admit Cards <i class="fas fa-angle-right"></i></a>
            </div>

            <!-- COLUMN 5: SCHEMES -->
            <div class="sarkari-box">
                <div class="box-head bg-schemes">योजनाएं</div>
                <ul class="sarkari-list">
                    <?php
                    if ($res_schemes && $res_schemes->num_rows > 0) {
                        while($row = $res_schemes->fetch_assoc()) {
                            echo '<li><a href="scheme_details.php?id='.$row['id'].'">'.e($row['title']).'</a></li>';
                        }
                    } else { echo '<div class="no-update">No Updates Available</div>'; }
                    ?>
                </ul>
                <a href="category.php?cat=scheme" class="view-more-btn">View All Schemes <i class="fas fa-angle-right"></i></a>
            </div>

            <!-- COLUMN 6: EDUCATION -->
            <div class="sarkari-box">
                <div class="box-head bg-education">शिक्षा</div>
                <ul class="sarkari-list">
                    <?php
                    if ($res_education && $res_education->num_rows > 0) {
                        while($row = $res_education->fetch_assoc()) {
                            echo '<li><a href="vacancy_details.php?id='.$row['id'].'">'.e($row['title']).'</a></li>';
                        }
                    } else { echo '<div class="no-update">No Updates Available</div>'; }
                    ?>
                </ul>
                <a href="category.php?cat=education" class="view-more-btn">View All Education <i class="fas fa-angle-right"></i></a>
            </div>

            <!-- COLUMN 7: ADMISSION -->
            <div class="sarkari-box">
                <div class="box-head bg-admission">एडमिशन</div>
                <ul class="sarkari-list">
                    <?php
                    if ($res_admission && $res_admission->num_rows > 0) {
                        while($row = $res_admission->fetch_assoc()) {
                            echo '<li><a href="vacancy_details.php?id='.$row['id'].'">'.e($row['title']).'</a></li>';
                        }
                    } else { echo '<div class="no-update">No Updates Available</div>'; }
                    ?>
                </ul>
                <a href="category.php?cat=admission" class="view-more-btn">View All Admissions <i class="fas fa-angle-right"></i></a>
            </div>

            <!-- COLUMN 8: PDF FORMS -->
            <div class="sarkari-box">
                <div class="box-head bg-pdf">PDF फॉर्म्स</div>
                <ul class="sarkari-list">
                    <?php
                    if ($res_pdf && $res_pdf->num_rows > 0) {
                        while($row = $res_pdf->fetch_assoc()) {
                            echo '<li><a href="vacancy_details.php?id='.$row['id'].'">'.e($row['title']).'</a></li>';
                        }
                    } else { echo '<div class="no-update">No Updates Available</div>'; }
                    ?>
                </ul>
                <a href="category.php?cat=pdf_form" class="view-more-btn">View All PDFs <i class="fas fa-angle-right"></i></a>
            </div>

        </div>
    <?php endif; ?>

</div>

<!-- ⚡ AJAX SEARCH SCRIPT -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("live-search-input");
    const searchResults = document.getElementById("live-search-results");

    searchInput.addEventListener("keyup", function() {
        let query = this.value.trim();
        
        // 3 या 3 से ज्यादा अक्षर टाइप होने पर ही AJAX कॉल करें
        if (query.length >= 3) {
            fetch("index.php?ajax_search=" + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    searchResults.innerHTML = ""; // पुराना डेटा हटा दें
                    if (data.length > 0) {
                        searchResults.style.display = "block";
                        data.forEach(item => {
                            let li = document.createElement("li");
                            li.innerHTML = `<a href="vacancy_details.php?id=${item.id}">
                                                <span>${item.title}</span> 
                                                <span class="cat-badge">${item.category}</span>
                                            </a>`;
                            searchResults.appendChild(li);
                        });
                    } else {
                        searchResults.style.display = "block";
                        searchResults.innerHTML = "<li class='no-result-item'>कोई जॉब या रिजल्ट नहीं मिला।</li>";
                    }
                })
                .catch(error => console.error("Error fetching data:", error));
        } else {
            // अगर 3 से कम अक्षर हैं, तो ड्रॉपडाउन बंद कर दें
            searchResults.style.display = "none";
        }
    });
    
    // अगर यूज़र ड्रॉपडाउन या सर्च बॉक्स के बाहर क्लिक करे, तो उसे छिपा दें
    document.addEventListener("click", function(e) {
        if(!e.target.closest('.search-container')) {
            searchResults.style.display = "none";
        }
    });
});
</script>

<?php 
// डेटाबेस कनेक्शन बंद करें
if(isset($conn)) { $conn->close(); }

include 'includes/footer.php'; 
?>