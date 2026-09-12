<?php
// poster.php
include 'includes/db_connect.php'; 
include 'includes/header.php'; 

// 📄 PAGINATION SYSTEM (1000 पोस्टर्स के लिए जरूरी ताकि पेज फास्ट रहे)
$limit = 16; // एक पेज पर कितने पोस्टर्स दिखाने हैं
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// कुल पोस्टर्स की संख्या गिनने के लिए
$total_result = $conn->query("SELECT COUNT(*) AS total FROM posters");
$total_row = $total_result->fetch_assoc();
$total_posters = $total_row['total'];
$total_pages = ceil($total_posters / $limit);
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>सरकारी भर्ती पोस्टर्स और डिजिटल बैनर्स | TC Digital Center</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600;700&display=swap">
    
    <style>
        :root { 
            --primary-gradient: linear-gradient(135deg, #ff007f, #7928ca);
            --bg-mesh: radial-gradient(at 0% 0%, rgba(243, 232, 255, 0.7) 0px, transparent 50%),
                       radial-gradient(at 100% 0%, rgba(238, 242, 255, 0.7) 0px, transparent 50%),
                       radial-gradient(at 100% 100%, rgba(252, 231, 243, 0.7) 0px, transparent 50%),
                       radial-gradient(at 0% 100%, rgba(224, 242, 254, 0.7) 0px, transparent 50%);
            --text-main: #0f172a;
            --text-muted: #64748b;
        }
        
        body { 
            margin: 0; 
            padding: 0; 
            overflow-x: hidden; 
            font-family: 'Hind', sans-serif; 
            background-color: #fcfbfe;
            background-image: var(--bg-mesh);
            background-attachment: fixed;
        }
        
        /* 🎬 वाइब्रेंट और डायनामिक हीरो सेक्शन */
        .hero { 
            position: relative; 
            width: 100%; 
            height: 55vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            overflow: hidden;
            background: linear-gradient(135deg, #6B11D6, #0f172a); 
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        #bg-video { 
            position: absolute; 
            top: 50%;
            left: 50%;
            min-width: 100%; 
            min-height: 100%; 
            width: auto;
            height: auto;
            transform: translate(-50%, -50%);
            object-fit: cover; 
            z-index: 1; 
        }
        
        .overlay { 
            background: linear-gradient(to bottom, rgba(15, 23, 42, 0.5), rgba(15, 23, 42, 0.85));
            position: absolute; 
            width: 100%; 
            height: 100%; 
            top: 0; 
            left: 0; 
            z-index: 2; 
        }
        
        .hero-text { 
            position: relative; 
            z-index: 3; 
            color: white; 
            width: 100%; 
            max-width: 750px; 
            padding: 0 20px; 
        }
        
        .hero-text h2 { 
            font-weight: 700; 
            font-size: 2.6rem; 
            text-shadow: 0 4px 12px rgba(0,0,0,0.5);
            letter-spacing: 0.5px;
        }

        /* 🔍 ग्लास-मॉर्फिज्म सर्च बार */
        .search-wrapper { position: relative; max-width: 580px; margin: 30px auto 0 auto; }
        .search-input { 
            width: 100%; 
            padding: 16px 24px 16px 55px; 
            border-radius: 50px; 
            border: 2px solid rgba(255, 255, 255, 0.25); 
            font-size: 1.1rem; 
            background: rgba(255, 255, 255, 0.15); 
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.25); 
            outline: none; 
            color: #ffffff; 
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        .search-input::placeholder { color: rgba(255, 255, 255, 0.75); font-weight: 400; }
        .search-input:focus { 
            border-color: #ff007f; 
            box-shadow: 0 15px 40px rgba(255, 0, 127, 0.4); 
            background: #ffffff; 
            color: #0f172a;
        }
        .search-icon { 
            position: absolute; 
            left: 22px; 
            top: 18px; 
            color: #ff007f; 
            font-size: 1.4rem; 
            z-index: 10; 
            transition: all 0.3s;
        }
        .search-input:focus + .search-icon { color: #7928ca; }

        /* 🎴 प्रीमियम चंकी कार्ड्स UI */
        .poster-section { position: relative; z-index: 4; margin-top: -50px; padding-bottom: 80px; }
        .poster-card-link { text-decoration: none; color: inherit; display: block; height: 100%; }
        
        .poster-card { 
            background: rgba(255, 255, 255, 0.9); 
            border-radius: 24px; 
            overflow: hidden; 
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04); 
            border: 2px solid #ffffff; 
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1); 
            display: flex; 
            flex-direction: column; 
            height: 100%; 
        }
        .poster-card:hover { 
            transform: translateY(-10px) scale(1.01); 
            box-shadow: 0 25px 45px rgba(121, 40, 202, 0.15); 
            border-color: #7928ca; 
            background: #ffffff;
        }
        
        .poster-img-container { 
            width: 100%; 
            padding-top: 100%; 
            position: relative; 
            background: linear-gradient(135deg, #f5f3ff, #fae8ff); 
            overflow: hidden; 
        }
        .poster-img-container img { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            object-fit: contain; 
            padding: 12px; 
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        .poster-card:hover .poster-img-container img { transform: scale(1.06); }

        .poster-body { padding: 22px; display: flex; flex-direction: column; flex-grow: 1; }
        .poster-title { 
            font-size: 1.25rem; 
            color: var(--text-main); 
            font-weight: 700; 
            line-height: 1.4; 
            margin-bottom: 10px; 
            display: -webkit-box; 
            -webkit-line-clamp: 2; 
            -webkit-box-orient: vertical; 
            overflow: hidden; 
            height: 3.5rem; 
            transition: color 0.2s;
        }
        .poster-card:hover .poster-title { color: #ff007f; }
        
        .poster-desc-preview { 
            font-size: 0.92rem; 
            color: var(--text-muted); 
            line-height: 1.6; 
            margin-bottom: 20px; 
            display: -webkit-box; 
            -webkit-line-clamp: 2; 
            -webkit-box-orient: vertical; 
            overflow: hidden; 
            height: 3rem; 
        }
        
        /* 🏷️ कार्ड फूटर और एक्शन */
        .poster-footer { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            border-top: 2px solid #f8fafc; 
            padding-top: 15px; 
            margin-top: auto; 
            font-size: 0.88rem; 
            color: var(--text-muted); 
            font-weight: 500;
        }
        .view-details-txt { 
            color: #7928ca; 
            font-weight: 700; 
            display: flex; 
            align-items: center; 
            gap: 4px; 
            background: #f3e8ff;
            padding: 5px 14px;
            border-radius: 30px;
            transition: all 0.2s;
        }
        .poster-card:hover .view-details-txt {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 12px rgba(255, 0, 127, 0.2);
        }

        /* ❌ नो रिजल्ट्स डिज़ाइन */
        .no-results-box { 
            background: white; 
            border-radius: 24px; 
            padding: 60px 40px; 
            text-align: center; 
            border: 3px dashed #cbd5e1; 
            color: var(--text-muted); 
            display: none; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.02);
        }

        /* 📄 कलरफुल पेजिनेशन */
        .pagination .page-link { 
            color: #7928ca; 
            border: none;
            background: rgba(255, 255, 255, 0.8);
            margin: 0 5px; 
            font-weight: 700;
            padding: 10px 18px;
            border-radius: 12px !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            transition: all 0.2s;
        }
        .pagination .page-link:hover { background: #f3e8ff; color: #ff007f; }
        .pagination .page-item.active .page-link { 
            background: var(--primary-gradient); 
            color: white; 
            box-shadow: 0 6px 15px rgba(121, 40, 202, 0.3);
        }
        .pagination .page-item.disabled .page-link { background: rgba(226, 232, 240, 0.5); color: #94a3b8; }

        /* एनीमेशन */
        .poster-card-item {
            transition: opacity 0.4s ease, transform 0.4s ease;
        }

        @media (max-width: 768px) {
            .hero { height: 45vh; }
            .hero-text h2 { font-size: 1.8rem; line-height: 1.4; }
            .search-input { padding: 14px 20px 14px 50px; font-size: 1rem; }
            .search-icon { top: 16px; left: 18px; }
            .poster-section { margin-top: -30px; }
            .poster-card { border-radius: 20px; }
            .poster-body { padding: 18px; }
        }
    </style>
</head>
<body>

<section class="hero text-center">
    <!-- 🎬 वीडियो बैकग्राउंड -->
    <video autoplay muted loop playsinline id="bg-video">
        <source src="assets/bg-video.mp4" type="video/mp4" />
    </video>
    <div class="overlay"></div>
    
    <div class="hero-text">
        <h2><i class="bi bi-fire text-warning animate-pulse"></i> सरकारी भर्ती पोस्टर्स और डिजिटल बैनर्स</h2>
        
        <div class="search-wrapper">
            <input type="text" id="liveSearchInput" class="search-input" placeholder="यहाँ लाइव सर्च करें (उदा. नवोदय, DCA, पुलिस भर्ती)..." onkeyup="executeLiveSearch()">
            <i class="bi bi-search search-icon"></i>
        </div>
    </div>
</section>

<section class="poster-section">
    <div class="container">
        
        <!-- ग्रिड कंटेनर -->
        <div class="row g-4" id="postersGridContainer">
            <?php
            // LIMIT और OFFSET का उपयोग किया ताकि वेबसाइट सुपर फ़ास्ट लोड हो
            $result = $conn->query("SELECT * FROM posters ORDER BY uploaded_at DESC LIMIT $limit OFFSET $offset");
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $clean_preview = str_replace(array('\r\n', '\r', '\n', '\\r\\n', '\\n', '\\r'), " ", $row['description']);
                    ?>
                    
                    <div class="col-12 col-md-4 col-lg-3 poster-card-item">
                        <a href="poster_details.php?id=<?= $row['id'] ?>" class="poster-card-link">
                            <div class="poster-card">
                                <div class="poster-img-container">
                                    <img src="banners/<?= htmlspecialchars($row['image_name']) ?>" alt="<?= htmlspecialchars($row['title']) ?>" loading="lazy">
                                </div>
                                <div class="poster-body">
                                    <h3 class="poster-title"><?= htmlspecialchars($row['title']) ?></h3>
                                    <div class="poster-desc-preview"><?= htmlspecialchars($clean_preview) ?></div>
                                    <div class="poster-footer">
                                        <span><i class="bi bi-calendar3 text-primary me-1"></i> <?= date('d M Y', strtotime($row['uploaded_at'])) ?></span>
                                        <span class="view-details-txt">देखें <i class="bi bi-arrow-right-short"></i></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <?php
                }
            } else {
                echo '<div class="col-12 text-center py-5"><div class="card p-5 shadow-sm border-0 rounded-4"><i class="bi bi-folder-x display-1 text-muted mb-3"></i><h4 class="text-secondary">वर्तमान में कोई पोस्टर उपलब्ध नहीं है।</h4></div></div>';
            }
            ?>
        </div>

        <!-- खोज परिणाम न मिलने का बॉक्स -->
        <div id="noResultsFound" class="no-results-box mt-5">
            <i class="bi bi-search-heart text-purple display-3 d-block mb-3" style="color: #7928ca;"></i>
            <h4 class="text-dark fw-bold">क्षमा करें! आपकी खोज के अनुसार कोई पोस्टर नहीं मिला।</h4>
            <p class="text-muted">कृपया कोई दूसरा कीवर्ड या सरल शब्द टाइप करके देखें।</p>
        </div>

        <!-- 📄 स्टाइलिश पेजिनेशन -->
        <?php if ($total_pages > 1): ?>
        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i> पिछला</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>">अगला <i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

    </div>
</section>

<script>
// लाइव क्लाइंट-साइड फ़िल्टरिंग एनीमेशन के साथ
function executeLiveSearch() {
    let input = document.getElementById('liveSearchInput').value.toLowerCase().trim();
    let cards = document.getElementsByClassName('poster-card-item');
    let hasResults = false;
    
    for (let i = 0; i < cards.length; i++) {
        let title = cards[i].querySelector('.poster-title').innerText.toLowerCase();
        let preview = cards[i].querySelector('.poster-desc-preview').innerText.toLowerCase();
        
        if (title.includes(input) || preview.includes(input)) {
            cards[i].style.display = "";
            setTimeout(() => { cards[i].style.opacity = "1"; }, 10);
            hasResults = true;
        } else {
            cards[i].style.opacity = "0";
            cards[i].style.display = "none";
        }
    }
    
    let noResultsBox = document.getElementById('noResultsFound');
    if(noResultsBox) {
        noResultsBox.style.display = hasResults ? "none" : "block";
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>
</body>
</html>