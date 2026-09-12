<?php 
// 1. Database Connection Include
include 'includes/db_connect.php'; 

// --- AJAX Requests Handling (बिना पेज रिफ्रेश किए डेटा टेबल लोड करने के लिए) ---
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    
    $limit = 20; 
    $page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT) : 1;
    if (!$page || $page < 1) { $page = 1; }
    $offset = ($page - 1) * $limit;

    // 1. Total Records Count (Optimized Index Search)
    $count_stmt = $conn->prepare("SELECT COUNT(id) AS total FROM vacancies WHERE category = 'pdf_form' AND status = 'live'");
    $count_stmt->execute();
    $count_res = $count_stmt->get_result();
    $total_rows = $count_res->fetch_assoc()['total'] ?? 0;
    $count_stmt->close();

    $total_pages = max(1, ceil($total_rows / $limit));
    if ($page > $total_pages && $total_rows > 0) { $page = 1; $offset = 0; }

    // 2. Optimized Data Fetching
    $data_stmt = $conn->prepare("SELECT id, title, post_date FROM vacancies WHERE category = 'pdf_form' AND status = 'live' ORDER BY post_date DESC, id DESC LIMIT ? OFFSET ?");
    $data_stmt->bind_param("ii", $limit, $offset);
    $data_stmt->execute();
    $result = $data_stmt->get_result();

    // Generate Table HTML
    if ($result && $result->num_rows > 0): ?>
        <div class="custom-table-card">
            <div class="table-responsive">
                <table class="pdf-table">
                    <thead>
                        <tr>
                            <th width="8%" class="text-center">क्र.सं.</th>
                            <th width="57%">फॉर्म एवं भर्ती का नाम</th>
                            <th width="20%" class="text-center">जारी तिथि</th>
                            <th width="15%" class="text-center">एक्शन</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sr_no = $offset + 1;
                        while($row = $result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td class="text-center font-bold text-slate"><?php echo $sr_no++; ?></td>
                                <td>
                                    <div class="pdf-title-container">
                                        <div class="pdf-type-icon">
                                            <i class="fas fa-file-pdf"></i>
                                        </div>
                                        <a href="pdf_details.php?id=<?php echo (int)$row['id']; ?>" class="pdf-table-link">
                                            <?php echo htmlspecialchars($row['title']); ?>
                                        </a>
                                    </div>
                                </td>
                                <td class="text-center text-meta">
                                    <span class="date-badge">
                                        <i class="far fa-calendar-alt"></i> <?php echo date('d M, Y', strtotime($row['post_date'])); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="pdf_details.php?id=<?php echo (int)$row['id']; ?>" class="download-btn-table">
                                        <i class="fas fa-download"></i> <span>डाउनलोड</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Dynamic AJAX Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    कुल <strong><?php echo number_format($total_rows); ?></strong> में से पेज <strong><?php echo $page; ?></strong> (कुल <?php echo $total_pages; ?> पेज)
                </div>
                
                <div class="pagination">
                    <button onclick="loadPdfPage(1)" class="pg-btn <?php echo ($page <= 1) ? 'disabled' : ''; ?>">&laquo; पहला</button>
                    <button onclick="loadPdfPage(<?php echo max(1, $page - 1); ?>)" class="pg-btn <?php echo ($page <= 1) ? 'disabled' : ''; ?>">&lsaquo; पिछला</button>

                    <?php
                    $start_loop = max(1, $page - 2);
                    $end_loop = min($total_pages, $page + 2);

                    if ($start_loop > 1) { echo '<span class="pg-dots">...</span>'; }

                    for ($i = $start_loop; $i <= $end_loop; $i++): ?>
                        <button onclick="loadPdfPage(<?php echo $i; ?>)" class="pg-btn <?php echo ($page == $i) ? 'active' : ''; ?>"><?php echo $i; ?></button>
                    <?php endfor; 

                    if ($end_loop < $total_pages) { echo '<span class="pg-dots">...</span>'; }
                    ?>

                    <button onclick="loadPdfPage(<?php echo min($total_pages, $page + 1); ?>)" class="pg-btn <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">अगला &rsaquo;</button>
                    <button onclick="loadPdfPage(<?php echo $total_pages; ?>)" class="pg-btn <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">अंतिम &raquo;</button>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="no-data">
            <i class="fas fa-folder-open fa-3x" style="opacity: 0.3; margin-bottom: 15px; color: #ef4444;"></i>
            <h3>कोई फॉर्म उपलब्ध नहीं है</h3>
            <p>वर्तमान में कोई नया ऑफलाइन फॉर्म अपडेट नहीं किया गया है।</p>
            <a href="index.php" class="home-btn"><i class="fas fa-arrow-left"></i> होम पेज पर जाएं</a>
        </div>
    <?php endif; 

    $data_stmt->close();
    $conn->close();
    exit(); // AJAX रिस्पॉन्स भेजने के बाद एग्जिट करें
}

// 2. Header Include (पेज की सामान्य लोडिंग के लिए)
include 'includes/header.php'; 
?>

<!-- 🔍 SEO Meta Schema for Google -->
<head>
    <title>ऑफलाइन फॉर्म एवं PDF डाउनलोड | TC Academy</title>
    <meta name="description" content="सभी सरकारी नौकरियों के ऑफलाइन आवेदन फॉर्म और महत्वपूर्ण PDF सूचनाएं प्राप्त करें।">
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [{
        "@type": "ListItem",
        "position": 1,
        "name": "होम",
        "item": "<?php echo (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]"; ?>"
      },{
        "@type": "ListItem",
        "position": 2,
        "name": "PDF फॉर्म्स"
      }]
    }
    </script>
</head>

<style>
    .pdf-body { background: #f8fafc; min-height: 90vh; padding: 35px 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .pdf-container { max-width: 1140px; margin: 0 auto; padding: 0 16px; }

    /* Page Header */
    .pdf-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #ffffff;
        padding: 32px 20px;
        border-radius: 16px;
        text-align: center;
        margin-bottom: 30px;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12);
        border: 1px solid #334155;
    }
    .pdf-header h1 { margin: 0; font-size: 26px; font-weight: 800; tracking-tight; }
    .pdf-header p { margin: 8px 0 0; color: #94a3b8; font-size: 14px; }

    /* Content Area & Loader */
    #ajax-content-area { position: relative; min-height: 350px; transition: opacity 0.3s ease; }
    .loading-overlay {
        display: none;
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(248, 250, 252, 0.75);
        z-index: 20;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(3px);
        border-radius: 12px;
    }
    .spinner {
        width: 42px;
        height: 42px;
        border: 4px solid #cbd5e1;
        border-top-color: #ef4444;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* 🔥 SHANDAR TABLE STYLING */
    .custom-table-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }
    .table-responsive { width: 100%; overflow-x: auto; }
    .pdf-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    .pdf-table thead tr {
        background: #1e293b;
        color: #ffffff;
        font-size: 14px;
        font-weight: 700;
    }
    .pdf-table th { padding: 16px 18px; text-transform: uppercase; letter-spacing: 0.5px; border: none; }
    .pdf-table td { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }
    .pdf-table tbody tr { transition: background 0.2s ease; }
    .pdf-table tbody tr:hover { background: #fef2f2; }
    .pdf-table tbody tr:last-child td { border-bottom: none; }

    /* Cell Utility Styles */
    .text-center { text-align: center; }
    .font-bold { font-weight: 700; }
    .text-slate { color: #64748b; }
    
    .pdf-title-container { display: flex; align-items: center; gap: 12px; }
    .pdf-type-icon {
        width: 36px;
        height: 36px;
        background: #fef2f2;
        color: #ef4444;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    .pdf-table-link {
        color: #0f172a;
        font-weight: 600;
        text-decoration: none;
        line-height: 1.4;
        transition: color 0.2s;
    }
    .pdf-table-link:hover { color: #ef4444; }

    .date-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f1f5f9;
        color: #475569;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .download-btn-table {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #ef4444;
        color: #ffffff;
        padding: 7px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s ease;
        box-shadow: 0 2px 6px rgba(239, 68, 68, 0.25);
    }
    .download-btn-table:hover {
        background: #dc2626;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.35);
        color: #ffffff;
    }

    /* Empty State */
    .no-data { background: #ffffff; padding: 50px 20px; text-align: center; border-radius: 12px; color: #64748b; border: 1px dashed #cbd5e1; }
    .home-btn { background: #1e293b; color: #fff; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 12px; display: inline-block; margin-top: 15px; }

    /* Dynamic Pagination Styling */
    .pagination-wrapper { display: flex; flex-direction: column; align-items: center; gap: 12px; margin-top: 35px; }
    .pagination-info { font-size: 12px; color: #64748b; font-weight: 600; }
    .pagination { display: flex; flex-wrap: wrap; justify-content: center; gap: 6px; }
    .pg-btn {
        padding: 8px 14px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
        border-radius: 8px;
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .pg-btn.active { background: #ef4444; color: #ffffff; border-color: #ef4444; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.25); }
    .pg-btn:hover:not(.active):not(.disabled) { background: #f1f5f9; border-color: #94a3b8; }
    .pg-btn.disabled { opacity: 0.4; cursor: not-allowed; }
    .pg-dots { padding: 8px 4px; color: #64748b; font-weight: 700; }

    /* Mobile Responsive Logic */
    @media (max-width: 640px) {
        .pdf-header { padding: 22px 14px; }
        .pdf-header h1 { font-size: 20px; }
        .pdf-table th, .pdf-table td { padding: 12px 10px; }
        .download-btn-table span { display: none; } /* मोबाइल पर सिर्फ आइकॉन दिखेगा */
        .download-btn-table { padding: 8px 10px; }
        .date-badge { font-size: 11px; padding: 4px 8px; }
    }
</style>

<div class="pdf-body">
    <div class="pdf-container">
        
        <!-- Main Header -->
        <div class="pdf-header">
            <h1><i class="fas fa-file-pdf text-red-500 mr-2"></i> ऑफलाइन फॉर्म एवं PDF डाउनलोड</h1>
            <p>सभी आधिकारिक एवं सरकारी ऑफलाइन आवेदन पत्र (Offline Forms) यहाँ टेबल में देखें व डाउनलोड करें</p>
        </div>

        <!-- Dynamic Content Area -->
        <div id="ajax-content-area">
            <div class="loading-overlay" id="loading-spinner">
                <div class="spinner"></div>
            </div>
            <div id="dynamic-pdf-list">
                <!-- Table loaded automatically via AJAX -->
            </div>
        </div>

    </div>
</div>

<!-- Real-time Dynamic JavaScript (Without Page Refresh) -->
<script>
function loadPdfPage(pageNumber = 1) {
    const contentBox = document.getElementById('dynamic-pdf-list');
    const spinner = document.getElementById('loading-spinner');

    // लोडर चालू करें
    spinner.style.display = 'flex';
    contentBox.style.opacity = '0.3';

    // AJAX Fetch Query Call
    fetch(`pdf-forms.php?ajax=1&page=${pageNumber}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            // लिस्ट को बिना पेज रिफ्रेश किए टेबल में लोड करें
            contentBox.innerHTML = data;
            
            // यूज़र अनुभव के लिए ऊपर स्क्रॉल करें
            window.scrollTo({
                top: document.getElementById('ajax-content-area').offsetTop - 80,
                behavior: 'smooth'
            });
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            contentBox.innerHTML = '<div class="no-data"><h3>डेटा लोड करने में असमर्थ। कृपया पुनः प्रयास करें।</h3></div>';
        })
        .finally(() => {
            // लोडर बंद करें
            spinner.style.display = 'none';
            contentBox.style.opacity = '1';
        });
}

// जब पहली बार पेज लोड हो, तब डेटा अपने-आप टेबल में फ़ेच करें
document.addEventListener('DOMContentLoaded', function() {
    loadPdfPage(1);
});
</script>

<?php include 'includes/footer.php'; ?>