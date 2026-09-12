<?php
// 1. डेटाबेस कनेक्शन शामिल करें
include 'includes/db_connect.php'; 

$vacancy_id = null;
$vacancy = null;

// 2. URL से रिक्ति ID प्राप्त करें
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $vacancy_id = intval($_GET['id']);
}

// 3. रिक्ति डेटा लोड करें
if ($vacancy_id) {
    $stmt = $conn->prepare("SELECT * FROM vacancies WHERE id = ? AND status = 'live'");
    $stmt->bind_param("i", $vacancy_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $vacancy = $result->fetch_assoc();
    }
    $stmt->close();
}

// यदि रिक्ति नहीं मिली तो होम पेज पर भेजें
if (!$vacancy) {
    header("location: index.php"); 
    exit;
}

$category_label = [
    'job' => '🔥 नई सरकारी नौकरी',
    'result' => '✅ परीक्षा परिणाम',
    'admit_card' => '🎟️ एडमिट कार्ड',
    'other' => '📁 अन्य अपडेट'
];

// Poster Themes Configuration
$theme_class = "theme-" . ($vacancy['category'] ?? 'other');
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($vacancy['title']); ?> - YOJNA PORTAL</title>
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        :root {
            --primary: #2563eb;
            --success: #16a34a;
            --danger: #dc2626;
            --bg: #f1f5f9;
            --text: #1e293b;
        }

        body {
            font-family: 'Hind', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 0;
        }

        .container { max-width: 900px; margin: 20px auto; padding: 0 15px; }
        .article-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .page-title { font-size: 1.8rem; color: #111827; margin-bottom: 10px; }
        .category-badge { background: var(--primary); color: white; padding: 4px 12px; border-radius: 4px; font-weight: 600; }
        
        /* Table Styles */
        .table-wrapper { overflow-x: auto; margin: 20px 0; border-radius: 8px; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .label-cell { background: #f8fafc; font-weight: 700; width: 35%; }

        /* Button Styles */
        .btn-group { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 20px; }
        .btn { flex: 1; text-align: center; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: none; }
        .btn-apply { background: var(--success); color: white; }
        .btn-poster { background: #8b5cf6; color: white; }

        /* --- DYNAMIC POSTER GENERATOR DESIGN --- */
        #poster-canvas-area {
            width: 700px;
            height: 700px;
            position: fixed;
            left: -9999px;
            top: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            text-align: center;
            overflow: hidden;
            background: #fff;
            border: 15px solid #fff;
        }

        /* Poster Themes */
        .theme-job { background: linear-gradient(180deg, #004d00 0%, #008000 100%); } /* Green Theme like PM Kisan */
        .theme-result { background: linear-gradient(180deg, #1e3a8a 0%, #3b82f6 100%); } /* Blue Theme */
        .theme-admit_card { background: linear-gradient(180deg, #991b1b 0%, #ef4444 100%); } /* Red Theme */
        .theme-other { background: linear-gradient(180deg, #4b5563 0%, #1f2937 100%); }

        .poster-top-header {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            font-size: 32px;
            font-weight: 800;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .poster-main-content {
            flex-grow: 1;
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .poster-logo { width: 180px; margin-bottom: 10px; }

        .poster-title-box {
            background: #fff;
            color: #000;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 38px;
            font-weight: 900;
            margin: 20px 0;
            width: 90%;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .poster-highlight-text {
            font-size: 45px;
            color: #fff;
            font-weight: 800;
            margin: 10px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .poster-info-grid {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            width: 100%;
            justify-content: center;
        }

        .info-pill {
            background: #fff;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .theme-job .info-pill { color: #008000; border: 2px solid #008000; }
        .theme-result .info-pill { color: #1e3a8a; border: 2px solid #1e3a8a; }

        .poster-details-list {
            background: rgba(255,255,255,0.9);
            width: 90%;
            border-radius: 15px;
            padding: 20px;
            margin-top: 25px;
            text-align: left;
        }

        .poster-details-list h3 { margin: 0 0 10px 0; color: #333; font-size: 24px; border-bottom: 2px solid #ddd; padding-bottom: 5px; }
        .poster-details-list ul { list-style: none; padding: 0; margin: 0; font-size: 22px; font-weight: 600; color: #000; }
        .poster-details-list li { margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
        .poster-details-list li::before { content: '●'; color: #008000; }

        .poster-bottom-bar {
            background: #fff;
            padding: 15px;
            font-size: 24px;
            font-weight: 700;
            color: #000;
        }

        .poster-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 100px;
            opacity: 0.1;
            white-space: nowrap;
            font-weight: 900;
            pointer-events: none;
        }

        @media (max-width: 768px) {
            .mobile-action-bar { position: fixed; bottom: 0; left: 0; right: 0; background: white; padding: 10px; display: flex; gap: 10px; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); z-index: 1000; }
            .btn-group { display: none; }
            body { padding-bottom: 70px; }
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="container">
        <article class="article-card">
            <h1 class="page-title"><?php echo htmlspecialchars($vacancy['title']); ?></h1>
            <div class="post-meta">
                <span class="category-badge"><?php echo $category_label[$vacancy['category']] ?? 'अपडेट'; ?></span>
                &nbsp; | &nbsp; <i class="far fa-calendar-alt"></i> <?php echo date('d M, Y', strtotime($vacancy['post_date'])); ?>
            </div>

            <div class="table-wrapper">
                <table>
                    <tr>
                        <td class="label-cell">संक्षिप्त विवरण</td>
                        <td><?php echo nl2br(htmlspecialchars($vacancy['short_desc'])); ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell">योग्यता</td>
                        <td><?php echo nl2br(htmlspecialchars($vacancy['eligibility'])); ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell">अंतिम तिथि</td>
                        <td style="color:var(--danger); font-weight:bold;"><?php echo date('d M, Y', strtotime($vacancy['last_date'])); ?></td>
                    </tr>
                </table>
            </div>

            <div class="btn-group">
                <a href="<?php echo $vacancy['apply_link']; ?>" target="_blank" class="btn btn-apply">अभी आवेदन करें</a>
                <button onclick="downloadJobPoster()" class="btn btn-poster">पोस्टर डाउनलोड करें</button>
            </div>
            
            <div style="margin-top:20px;">
                <?php echo $vacancy['long_details']; ?>
            </div>
        </article>
    </div>

    <div id="poster-canvas-area" class="<?php echo $theme_class; ?>">
        <div class="poster-watermark">YOJNA PORTAL</div>
        
        <div class="poster-top-header">
            <?php echo htmlspecialchars($vacancy['title']); ?>
        </div>

        <div class="poster-main-content">
            <div class="poster-highlight-text">YOJNA PORTAL</div>
            
            <div class="poster-title-box">
                <?php echo ($vacancy['category'] == 'job') ? 'ऑनलाइन आवेदन' : 'रिजल्ट / अपडेट'; ?>
            </div>

            <div class="poster-highlight-text">
                <?php echo ($vacancy['category'] == 'job') ? 'जल्द करें आवेदन' : 'अभी चेक करें'; ?>
            </div>

            <div class="poster-info-grid">
                <div class="info-pill">स्टेटस चेक</div>
                <div class="info-pill">बैंक लिंक</div>
                <div class="info-pill">ई-केवाईसी</div>
            </div>

            <div class="poster-details-list">
                <h3>आवश्यक जानकारी</h3>
                <ul>
                    <li>योग्यता: <?php echo mb_strimwidth(strip_tags($vacancy['eligibility']), 0, 40, "..."); ?></li>
                    <li>अंतिम तिथि: <?php echo date('d M, Y', strtotime($vacancy['last_date'])); ?></li>
                    <li>आवेदन माध्यम: ऑनलाइन</li>
                    <li>आधिकारिक वेबसाइट: yojnaportal.com</li>
                </ul>
            </div>
        </div>

        <div class="poster-bottom-bar">
            भारत सरकार की सभी योजनाओं की जानकारी सबसे पहले - www.yojnaportal.com
        </div>
    </div>

    <div class="mobile-action-bar">
        <button onclick="downloadJobPoster()" class="btn btn-poster" style="flex:1"><i class="fas fa-image"></i></button>
        <a href="<?php echo $vacancy['apply_link']; ?>" class="btn btn-apply" style="flex:3">अभी अप्लाई करें</a>
    </div>

    <script>
    function downloadJobPoster() {
        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        const poster = document.getElementById('poster-canvas-area');
        
        html2canvas(poster, {
            scale: 2,
            useCORS: true,
            allowTaint: true
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'yojna-update-<?php echo $vacancy_id; ?>.png';
            link.href = canvas.toDataURL("image/png");
            link.click();
            btn.innerHTML = originalHtml;
        });
    }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>