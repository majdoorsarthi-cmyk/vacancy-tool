<?php
/**
 * Project: Public PDF Details Page
 * Updated Version: Vibrant Modern Theme, Production-Ready, Live PDF Viewer, 15 Dynamic FAQs & SEO Schema
 */

// 1. डेटाबेस कनेक्शन एवं एरर हैंडलिंग
include 'includes/db_connect.php'; 

$pdf_id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;
$pdf_data = null;

// 2. केवल 'live' स्टेटस वाला डेटा लोड करें
if ($pdf_id) {
    $stmt = $conn->prepare("SELECT * FROM vacancies WHERE id = ? AND status = 'live' LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $pdf_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $pdf_data = $result->fetch_assoc();
        $stmt->close();
    }
}

// 3. यदि डेटा न मिले तो मुख्य पृष्ठ पर रीडायरेक्ट करें
if (!$pdf_data) {
    header("Location: pdf-forms.php"); 
    exit;
}

// 4. सुरक्षित URL सेटअप
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$base_url = $scheme . "://" . $host;
$current_url = $base_url . $request_uri;

// 5. फ़ील्ड्स सैनिटाइजेशन एवं वेरिएबल सेटअप
$raw_title           = !empty($pdf_data['title']) ? $pdf_data['title'] : 'PDF FORM DOWNLOAD';
$title               = mb_strtoupper(htmlspecialchars($raw_title, ENT_QUOTES, 'UTF-8'));
$category            = !empty($pdf_data['category']) ? htmlspecialchars($pdf_data['category'], ENT_QUOTES, 'UTF-8') : 'Offline Form';
$state               = !empty($pdf_data['state']) ? htmlspecialchars($pdf_data['state'], ENT_QUOTES, 'UTF-8') : 'All India';
$location            = !empty($pdf_data['location']) ? htmlspecialchars($pdf_data['location'], ENT_QUOTES, 'UTF-8') : 'India';
$short_desc          = !empty($pdf_data['short_desc']) ? htmlspecialchars($pdf_data['short_desc'], ENT_QUOTES, 'UTF-8') : '';
$posts               = !empty($pdf_data['total_posts']) ? htmlspecialchars($pdf_data['total_posts'], ENT_QUOTES, 'UTF-8') : 'विभिन्न पद';

// PDF एवं अन्य फ़ील्ड्स
$pdf_file_url        = !empty($pdf_data['pdf_file']) ? htmlspecialchars($pdf_data['pdf_file'], ENT_QUOTES, 'UTF-8') : (!empty($pdf_data['notification_link']) ? htmlspecialchars($pdf_data['notification_link'], ENT_QUOTES, 'UTF-8') : '');
$pdf_size            = !empty($pdf_data['file_size']) ? htmlspecialchars($pdf_data['file_size'], ENT_QUOTES, 'UTF-8') : 'मानक आकार (Standard)';
$form_type           = !empty($pdf_data['form_type']) ? htmlspecialchars($pdf_data['form_type'], ENT_QUOTES, 'UTF-8') : 'ऑफलाइन / PDF फॉर्म';
$postal_address      = !empty($pdf_data['postal_address']) ? $pdf_data['postal_address'] : '';

$application_fee     = !empty($pdf_data['application_fee']) ? $pdf_data['application_fee'] : '';
$eligibility_raw     = !empty($pdf_data['eligibility']) ? $pdf_data['eligibility'] : '';
$eligibility_short   = !empty($eligibility_raw) ? mb_strimwidth(strip_tags($eligibility_raw), 0, 120, "...") : 'नियमानुसार शैक्षणिक योग्यता';
$age_limit           = !empty($pdf_data['age_limit']) ? $pdf_data['age_limit'] : '';
$selection_process   = !empty($pdf_data['selection_process']) ? $pdf_data['selection_process'] : '';
$important_dates     = !empty($pdf_data['important_dates']) ? $pdf_data['important_dates'] : '';
$description         = !empty($pdf_data['description']) ? $pdf_data['description'] : (!empty($pdf_data['content']) ? $pdf_data['content'] : '');
$long_details        = !empty($pdf_data['long_details']) ? $pdf_data['long_details'] : '';

// तिथियां
$raw_post_date       = !empty($pdf_data['post_date']) ? $pdf_data['post_date'] : date('Y-m-d');
$post_date           = date('d M, Y', strtotime($raw_post_date));
$iso_post_date       = date('c', strtotime($raw_post_date));

$last_date_raw       = !empty($pdf_data['last_date']) ? $pdf_data['last_date'] : '';
$last_date_formatted = (!empty($last_date_raw) && $last_date_raw !== '0000-00-00') ? date('d M, Y', strtotime($last_date_raw)) : 'शीघ्र उपलब्ध';

// लिंक्स एवं इमेजेस
$official_website    = !empty($pdf_data['official_website']) ? htmlspecialchars($pdf_data['official_website'], ENT_QUOTES, 'UTF-8') : '';
$image_url           = !empty($pdf_data['image_url']) ? htmlspecialchars($pdf_data['image_url'], ENT_QUOTES, 'UTF-8') : $base_url . '/tc.png';

// Safe Content Renderer Function
function render_portal_content($content) {
    if (empty($content)) return '';
    if ($content !== strip_tags($content)) {
        return $content; // HTML Render
    }
    return nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'));
}

// WhatsApp Message Construct
$wa_share_msg  = "📄 *" . strip_tags($title) . " (PDF Form)*\n\n";
if (!empty($state) || !empty($location)) { $wa_share_msg .= "📍 *स्थान/राज्य:* {$state} ({$location})\n"; }
if (!empty($posts)) { $wa_share_msg .= "🔢 *कुल पद:* {$posts}\n"; }
if (!empty($last_date_formatted)) { $wa_share_msg .= "📅 *अंतिम तिथि:* {$last_date_formatted}\n"; }
if (!empty($eligibility_short)) { $wa_share_msg .= "🎓 *योग्यता:* {$eligibility_short}\n\n"; }
$wa_share_msg .= "📥 *पीडीएफ फॉर्म डाउनलोड लिंक:* \n{$current_url}";

// 15 Dynamic FAQs Array Generation
$faqs_array = [
    ["q" => "{$title} का पीडीएफ फॉर्म (PDF Form) कैसे डाउनलोड करें?", "a" => "आप इस पेज पर दिए गए रेड कलर के <strong>'Download PDF Form'</strong> बटन पर क्लिक करके सीधे आधिकारिक आवेदन फॉर्म की कॉपी डाउनलोड कर सकते हैं।"],
    ["q" => "क्या यह फॉर्म ऑफलाइन जमा (Send Offline) करना होगा?", "a" => "जी हाँ, यह एक ऑफलाइन भर्ती/आवेदन पत्र है। आपको फॉर्म का प्रिंट आउट निकालकर इसे हाथ से भरकर सही पते पर स्पीड पोस्ट या रजिस्टर्ड डाक से भेजना होगा।"],
    ["q" => "ऑफलाइन फॉर्म भेजने की अंतिम तिथि (Last Date) क्या है?", "a" => "आवेदन पत्र संबंधित विभाग को प्राप्त होने की अंतिम तिथि <strong>{$last_date_formatted}</strong> रखी गई है।"],
    ["q" => "फॉर्म भरने के लिए आवश्यक शैक्षणिक योग्यता (Eligibility) क्या है?", "a" => !empty($eligibility_raw) ? strip_tags($eligibility_raw) : "विस्तृत योग्यता की जानकारी ऊपर दी गई विवरण तालिका में उपलब्ध है।"],
    ["q" => "इस फॉर्म के लिए आयु सीमा (Age Limit) क्या रखी गई है?", "a" => !empty($age_limit) ? strip_tags($age_limit) : "नियमानुसार आरक्षित श्रेणियों (SC/ST/OBC/PWBD) को अधिकतम आयु सीमा में छूट प्रदान की जाएगी।"],
    ["q" => "क्या इस PDF फॉर्म को प्रिंट (Print Out) कराना अनिवार्य है?", "a" => "हाँ, डाउनलोड किए गए PDF का A4 साइज़ सफ़ेद पेपर पर साफ़-सुथरा प्रिंट आउट निकालकर ही नीले या काले बॉल पेन से भरें।"],
    ["q" => "आवेदन शुल्क (Application Fee / Demand Draft) कितना लगेगा?", "a" => !empty($application_fee) ? strip_tags($application_fee) : "शुल्क या पोस्टल ऑर्डर की विस्तृत जानकारी ऊपर दिए गए फीस विवरण में देखें।"],
    ["q" => "फॉर्म के साथ कौन-कौन से दस्तावेज (Documents) संलग्न करने होंगे?", "a" => "सामान्यतः मार्कशीट, जाति प्रमाण पत्र, मूल निवास प्रमाण पत्र, पहचान पत्र और पासपोर्ट आकार की फोटो की स्व-सत्यापित (Self-Attested) फोटोकॉपी लगानी होती है।"],
    ["q" => "आवेदन पत्र किस पते (Postal Address) पर भेजना है?", "a" => !empty($postal_address) ? strip_tags($postal_address) : "आवेदन पत्र भेजने का सटीक पता नोटिफिकेशन एवं डाउनलोड किए गए PDF फॉर्म के ऊपर अंकित है।"],
    ["q" => "लिफाफे (Envelope) के ऊपर क्या लिखना आवश्यक है?", "a" => "लिफाफे के सबसे ऊपर स्पष्ट शब्दों में <strong>'Application for the post of ...'</strong> और अपनी श्रेणी (Category) अवश्य लिखें।"],
    ["q" => "क्या इस ऑफ़लाइन फॉर्म में पदों (Total Posts) की संख्या बताई गई है?", "a" => "हाँ, उपलब्ध सूचना के आधार पर इस भर्ती के तहत कुल <strong>{$posts}</strong> पदों पर नियुक्ति की जाएगी।"],
    ["q" => "क्या साधारण डाक (Ordinary Post) से फॉर्म भेज सकते हैं?", "a" => "सलाह दी जाती है कि आप फॉर्म को केवल रजिस्टर्ड पोस्ट (Registered Post) या स्पीड पोस्ट (Speed Post) से ही भेजें ताकि फॉर्म समय पर सुरक्षित पहुँच सके।"],
    ["q" => "क्या फॉर्म में हुई गलती को सुधारने (Correction) का मौका मिलेगा?", "a" => "ऑफलाइन फॉर्म में करेक्शन का मौका नहीं मिलता, इसलिए फॉर्म भरते समय कोई कटिंग या ओवरराइटिंग न करें।"],
    ["q" => "क्या इस PDF फॉर्म को मोबाइल से ऑनलाइन सबमिट किया जा सकता है?", "a" => "नहीं, इसे डाउनलोड करके A4 शीट पर प्रिंट निकालना होगा और पूरी प्रक्रिया ऑफलाइन ही पूरी की जाएगी।"],
    ["q" => "नवीनतम ऑफलाइन पीडीएफ फॉर्म अपडेट्स कैसे प्राप्त करें?", "a" => "दैनिक जॉब अलर्ट और पीडीएफ फॉर्म्स की सीधी जानकारी पाने के लिए हमारे व्हाट्सएप ग्रुप से जुड़े रहें।"]
];
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - PDF Form Download & Offline Details</title>
    <meta name="description" content="<?php echo !empty($short_desc) ? $short_desc : $title . ' - ऑफ़लाइन फॉर्म पीडीएफ डाउनलोड करें, अंतिम तिथि ' . $last_date_formatted . ' और पूरी जानकारी देखें।'; ?>">
    <link rel="canonical" href="<?php echo $current_url; ?>" />

    <!-- Open Graph Social Meta Tags -->
    <meta property="og:title" content="<?php echo $title; ?> (PDF Form Download)" />
    <meta property="og:description" content="राज्य: <?php echo $state; ?> | कुल पद: <?php echo $posts; ?> | अंतिम तिथि: <?php echo $last_date_formatted; ?>" />
    <meta property="og:url" content="<?php echo $current_url; ?>" />
    <meta property="og:type" content="article" />
    <meta property="og:image" content="<?php echo $image_url; ?>" />

    <!-- Google Fonts & Font Awesome Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <!-- INLINE MODERN & VIBRANT STYLING -->
    <style>
        :root {
            --primary-bg: #f8fafc;
            --card-bg: #ffffff;
            --header-grad: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            --accent-red: #ef4444;
            --accent-blue: #0284c7;
            --accent-green: #16a34a;
            --text-dark: #0f172a;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Hind', 'Poppins', sans-serif;
            background-color: #f1f5f9;
            color: var(--text-dark);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .portal-wrapper {
            max-width: 1060px;
            margin: 20px auto;
            padding: 0 12px;
        }

        .job-card-box {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.06);
            overflow: hidden;
            padding: 24px;
            border: 1px solid #e2e8f0;
        }

        /* Hero Header Banner */
        .job-header {
            background: var(--header-grad);
            color: #ffffff;
            padding: 28px 24px;
            border-radius: 16px;
            margin-bottom: 25px;
            position: relative;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.25);
            border-left: 8px solid #ef4444;
        }

        .job-badge {
            display: inline-block;
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid #ef4444;
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        .job-title-main {
            font-size: 1.75rem;
            font-weight: 800;
            margin: 0 0 10px 0;
            color: #ffffff;
            line-height: 1.35;
        }

        .job-subtitle {
            font-size: 0.98rem;
            color: #cbd5e1;
            margin: 0;
        }

        /* Direct PDF Download Card */
        .download-hero-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 2px solid #334155;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            color: #ffffff;
            margin-bottom: 25px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .download-hero-card h2 {
            margin: 0 0 8px 0;
            font-size: 1.3rem;
            color: #f8fafc;
        }
        .download-hero-card p {
            margin: 0 0 18px 0;
            color: #94a3b8;
            font-size: 0.92rem;
        }

        .btn-pdf-download-main {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            color: #ffffff !important;
            font-size: 1.1rem;
            font-weight: 800;
            padding: 14px 32px;
            border-radius: 10px;
            text-decoration: none !important;
            transition: all 0.25s ease;
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }
        .btn-pdf-download-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.6);
        }

        /* Table Styling */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 24px;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }

        .job-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            overflow: hidden;
        }

        .job-table th {
            color: #ffffff;
            padding: 14px 18px;
            font-size: 1.05rem;
            text-align: left;
            font-weight: 700;
        }

        .job-table td {
            padding: 14px 18px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.98rem;
            color: #334155;
        }

        .job-table tr:last-child td { border-bottom: none; }
        .job-table tr:nth-child(even) { background-color: #f8fafc; }

        .label-col {
            font-weight: 700;
            color: #0f172a;
            width: 35%;
            background: #f1f5f9;
        }

        /* Table Theme Variations */
        .table-theme-red th { background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%) !important; }
        .table-theme-blue th { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important; }
        .table-theme-green th { background: linear-gradient(135deg, #16a34a 0%, #15803d 100%) !important; }
        .table-theme-purple th { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important; }
        .table-theme-dark th { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%) !important; }

        .long-text-area {
            font-size: 0.98rem;
            line-height: 1.8;
            color: #1e293b;
        }

        /* Row Buttons */
        .link-row-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.92rem;
            text-decoration: none !important;
            color: #ffffff !important;
            transition: transform 0.2s, box-shadow 0.2s;
            background: #0284c7;
            box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
        }
        .link-row-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.2); }
        .link-row-btn.red { background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); }
        .link-row-btn.whatsapp { background: linear-gradient(135deg, #25d366 0%, #128c7e 100%); }

        /* PDF Viewer Container */
        .pdf-viewer-container {
            border: 2px solid #cbd5e1;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 25px;
            background: #f8fafc;
        }
        .pdf-viewer-header {
            background: #1e293b;
            color: #ffffff;
            padding: 12px 18px;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
        }

        /* FAQ Accordion */
        .faq-container {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            margin-top: 30px;
        }
        .faq-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 12px;
        }
        .faq-item {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            margin-bottom: 12px;
            overflow: hidden;
            transition: border-color 0.2s;
        }
        .faq-item:hover { border-color: #ef4444; }
        .faq-question {
            padding: 14px 18px;
            font-weight: 700;
            font-size: 0.98rem;
            color: #b91c1c;
            cursor: pointer;
            outline: none;
            list-style: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .faq-question::-webkit-details-marker { display: none; }
        .faq-answer {
            padding: 0 18px 16px 18px;
            font-size: 0.94rem;
            color: #475569;
            line-height: 1.7;
            border-top: 1px solid #f1f5f9;
            margin-top: 6px;
            padding-top: 10px;
        }

        /* Share Container */
        .share-box {
            background: linear-gradient(135deg, #fef2f2 0%, #ffe4e6 100%);
            border: 1.5px solid #fecdd3;
            border-radius: 14px;
            padding: 18px 22px;
            margin-top: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .btn-share {
            padding: 9px 18px;
            border-radius: 8px;
            color: #ffffff;
            border: none;
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none !important;
        }

        @media (max-width: 640px) {
            .job-card-box { padding: 14px; }
            .job-header { padding: 18px 16px; }
            .job-title-main { font-size: 1.35rem; }
            .label-col { width: 45%; }
            .share-box { flex-direction: column; align-items: flex-start; }
            .btn-pdf-download-main { width: 100%; box-sizing: border-box; }
        }
    </style>

    <!-- GOOGLE SCHEMA.ORG (JSON-LD) - DIGITAL DOCUMENT -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org/",
      "@type": "DigitalDocument",
      "name": <?php echo json_encode(strip_tags($title), JSON_UNESCAPED_UNICODE); ?>,
      "description": <?php echo json_encode(strip_tags(!empty($short_desc) ? $short_desc : $title), JSON_UNESCAPED_UNICODE); ?>,
      "datePublished": "<?php echo $iso_post_date; ?>",
      "fileFormat": "application/pdf",
      "url": "<?php echo $current_url; ?>"
    }
    </script>

    <!-- 15 FAQs SCHEMA FOR GOOGLE SEARCH COMPLIANCE -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        <?php 
        $schema_faqs = [];
        foreach($faqs_array as $faq) {
            $schema_faqs[] = '{
                "@type": "Question",
                "name": ' . json_encode($faq['q'], JSON_UNESCAPED_UNICODE) . ',
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": ' . json_encode(strip_tags($faq['a']), JSON_UNESCAPED_UNICODE) . '
                }
            }';
        }
        echo implode(',', $schema_faqs);
        ?>
      ]
    }
    </script>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="portal-wrapper">
        <div class="job-card-box">
            
            <!-- 1. VIBRANT HERO HEADER -->
            <div class="job-header">
                <div class="job-badge">
                    <i class="fas fa-file-pdf"></i> <?php echo strtoupper($category); ?><?php echo (!empty($category) && !empty($state)) ? ' • ' : ''; ?><?php echo $state; ?>
                </div>

                <h1 class="job-title-main"><?php echo $title; ?></h1>

                <?php if(!empty($short_desc)): ?>
                <p class="job-subtitle"><i class="fas fa-info-circle text-red-400"></i> <?php echo $short_desc; ?></p>
                <?php endif; ?>
            </div>

            <!-- 2. DIRECT PDF DOWNLOAD ACTION BOX -->
            <div class="download-hero-card">
                <h2><i class="fas fa-file-arrow-down text-red-500"></i> ऑफलाइन फॉर्म डाउनलोड केंद्र</h2>
                <p>निचे दिए गए बटन पर क्लिक करके आधिकारिक एप्लीकेशन पीडीएफ फॉर्म डाउनलोड करें और प्रिंट निकालें।</p>
                
                <?php if(!empty($pdf_file_url)): ?>
                    <a href="<?php echo $pdf_file_url; ?>" download target="_blank" rel="nofollow noopener" class="btn-pdf-download-main">
                        <i class="fas fa-download fa-lg"></i> PDF Form Download (डाउनलोड करें)
                    </a>
                <?php else: ?>
                    <a href="#" onclick="alert('PDF फ़ाइल जल्द ही उपलब्ध कराई जाएगी।'); return false;" class="btn-pdf-download-main" style="background: #64748b; box-shadow: none;">
                        <i class="fas fa-exclamation-triangle"></i> फॉर्म शीघ्र उपलब्ध होगा
                    </a>
                <?php endif; ?>
            </div>

            <!-- 3. VACANCY / FORM HIGHLIGHTS TABLE -->
            <div class="table-responsive">
                <table class="job-table table-theme-red">
                    <thead>
                        <tr>
                            <th colspan="2"><i class="fas fa-list-check"></i> फॉर्म एवं भर्ती संक्षिप्त विवरण (PDF Summary)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="label-col">फॉर्म / भर्ती का नाम</td>
                            <td><strong style="color:#dc2626;"><?php echo $title; ?></strong></td>
                        </tr>
                        <tr>
                            <td class="label-col">फॉर्म का प्रकार (Form Type)</td>
                            <td><span style="background:#fef2f2; color:#dc2626; padding:3px 10px; border-radius:6px; font-weight:700;"><?php echo $form_type; ?></span></td>
                        </tr>
                        <?php if(!empty($category)): ?>
                        <tr>
                            <td class="label-col">श्रेणी / वर्ग (Category)</td>
                            <td><?php echo $category; ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if(!empty($state) || !empty($location)): ?>
                        <tr>
                            <td class="label-col">राज्य / स्थान (Location)</td>
                            <td><?php echo $state; ?> <?php echo !empty($location) ? "({$location})" : ''; ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="label-col">कुल पद (Total Vacancies)</td>
                            <td><strong style="color:#0284c7;"><?php echo $posts; ?></strong></td>
                        </tr>
                        <tr>
                            <td class="label-col">फॉर्म अपडेट तिथि (Post Date)</td>
                            <td><?php echo $post_date; ?></td>
                        </tr>
                        <tr>
                            <td class="label-col">अंतिम तिथि (Last Date)</td>
                            <td><strong style="color: #dc2626; background: #ffe4e6; padding: 2px 8px; border-radius: 4px;"><?php echo $last_date_formatted; ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 4. IMPORTANT DATES & APPLICATION FEE TABLE -->
            <div class="table-responsive">
                <table class="job-table table-theme-green">
                    <thead>
                        <tr>
                            <th style="width: 50%;"><i class="fas fa-calendar-alt"></i> महत्वपूर्ण तिथियां (Important Dates)</th>
                            <th style="width: 50%;"><i class="fas fa-wallet"></i> आवेदन शुल्क (Application Fee)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <?php if(!empty($important_dates)): ?>
                                    <?php echo render_portal_content($important_dates); ?>
                                <?php else: ?>
                                    • फॉर्म जारी तिथि: <strong><?php echo $post_date; ?></strong><br>
                                    • जमा करने की अंतिम तिथि: <strong style="color:#dc2626;"><?php echo $last_date_formatted; ?></strong>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(!empty($application_fee)): ?>
                                    <?php echo render_portal_content($application_fee); ?>
                                <?php else: ?>
                                    • नि:शुल्क / नोटिफिकेशन अनुसार
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 5. AGE LIMIT & SELECTION PROCESS -->
            <?php if(!empty($age_limit) || !empty($selection_process)): ?>
            <div class="table-responsive">
                <table class="job-table table-theme-purple">
                    <thead>
                        <tr>
                            <?php if(!empty($age_limit)): ?><th style="width: 50%;"><i class="fas fa-user-clock"></i> आयु सीमा (Age Limit)</th><?php endif; ?>
                            <?php if(!empty($selection_process)): ?><th style="width: 50%;"><i class="fas fa-user-check"></i> चयन प्रक्रिया (Selection Process)</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php if(!empty($age_limit)): ?>
                            <td><?php echo render_portal_content($age_limit); ?></td>
                            <?php endif; ?>

                            <?php if(!empty($selection_process)): ?>
                            <td><?php echo render_portal_content($selection_process); ?></td>
                            <?php endif; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- 6. ELIGIBILITY DETAILS -->
            <?php if(!empty($eligibility_raw)): ?>
            <div class="table-responsive">
                <table class="job-table table-theme-blue">
                    <thead>
                        <tr>
                            <th><i class="fas fa-graduation-cap"></i> पात्रता एवं शैक्षणिक योग्यता (Eligibility Details)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="long-text-area">
                                    <?php echo render_portal_content($eligibility_raw); ?>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- 7. POSTAL ADDRESS / SUBMISSION INSTRUCTIONS -->
            <?php if(!empty($postal_address) || !empty($description)): ?>
            <div class="table-responsive">
                <table class="job-table table-theme-red">
                    <thead>
                        <tr>
                            <th><i class="fas fa-envelope-open-text"></i> आवेदन भेजने का पता एवं निर्देश (Postal Address & How to Send)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($postal_address)): ?>
                        <tr>
                            <td style="background: #fff5f5;">
                                <strong>📮 स्पीड पोस्ट / डाक द्वारा भेजने का पता:</strong><br>
                                <div style="font-size: 1.05rem; font-weight:700; color: #991b1b; margin-top:5px;">
                                    <?php echo render_portal_content($postal_address); ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($description)): ?>
                        <tr>
                            <td>
                                <div class="long-text-area">
                                    <?php echo render_portal_content($description); ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- 8. LIVE EMBEDDED PDF PREVIEW (IF AVAILABLE) -->
            <?php if(!empty($pdf_file_url)): ?>
            <div class="pdf-viewer-container">
                <div class="pdf-viewer-header">
                    <span><i class="fas fa-eye text-red-400 mr-2"></i> लाइव PDF फॉर्म पूर्वावलोकन (Preview)</span>
                    <a href="<?php echo $pdf_file_url; ?>" target="_blank" style="color:#38bdf8; text-decoration:none;">फुल स्क्रीन देखें <i class="fas fa-external-link-alt text-xs"></i></a>
                </div>
                <iframe src="<?php echo $pdf_file_url; ?>" width="100%" height="580px" style="border:none;"></iframe>
            </div>
            <?php endif; ?>

            <!-- 9. EXTRA DETAILS / NOTIFICATION DETAILS -->
            <?php if(!empty($long_details)): ?>
            <div class="table-responsive">
                <table class="job-table table-theme-dark">
                    <thead>
                        <tr>
                            <th><i class="fas fa-file-invoice"></i> विस्तृत जानकारी (Full Details)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="long-text-area">
                                    <?php echo render_portal_content($long_details); ?>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- 10. IMPORTANT DIRECT LINKS TABLE -->
            <div class="table-responsive">
                <table class="job-table table-theme-dark">
                    <thead>
                        <tr>
                            <th colspan="2" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);"><i class="fas fa-link"></i> महत्वपूर्ण डाउनलोड लिंक्स (Important Direct Links)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($pdf_file_url)): ?>
                        <tr>
                            <td class="label-col">ऑफलाइन PDF फॉर्म डाउनलोड करें</td>
                            <td>
                                <a href="<?php echo $pdf_file_url; ?>" download target="_blank" rel="nofollow noopener" class="link-row-btn red">
                                    Download Form PDF <i class="fas fa-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php if(!empty($official_website)): ?>
                        <tr>
                            <td class="label-col">आधिकारिक वेबसाइट (Official Website)</td>
                            <td>
                                <a href="<?php echo $official_website; ?>" target="_blank" rel="nofollow noopener" class="link-row-btn">
                                    Visit Website <i class="fas fa-globe"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <tr>
                            <td class="label-col">व्हाट्सएप पीडीएफ अलर्ट (Join Job Group)</td>
                            <td>
                                <a href="https://chat.whatsapp.com/GF1BDKhdBVZ5AH59aVmcRJ" target="_blank" rel="noopener" class="link-row-btn whatsapp">
                                    Join WhatsApp <i class="fab fa-whatsapp"></i>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 11. FULL 15 FAQS ACCORDION SECTION -->
            <div class="faq-container">
                <div class="faq-title"><i class="fas fa-question-circle text-red-600"></i> अक्सर पूछे जाने वाले 15 महत्वपूर्ण प्रश्न (FAQ)</div>

                <?php foreach($faqs_array as $index => $faq): ?>
                <details class="faq-item" <?php echo $index === 0 ? 'open' : ''; ?>>
                    <summary class="faq-question">
                        <span><strong>Q<?php echo ($index+1); ?>.</strong> <?php echo $faq['q']; ?></span>
                        <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                    </summary>
                    <div class="faq-answer">
                        <?php echo $faq['a']; ?>
                    </div>
                </details>
                <?php endforeach; ?>
            </div>

            <!-- 12. SHARE FOOTER STRIP -->
            <div class="share-box">
                <div>📢 <strong>पीडीएफ फॉर्म दोस्तों के साथ शेयर करें:</strong></div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button id="btnPoster" class="btn-share" style="background:#475569;">
                        <i class="fas fa-image"></i> Poster Download
                    </button>
                    <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($wa_share_msg); ?>" target="_blank" class="btn-share" style="background:#25d366;">
                        <i class="fab fa-whatsapp"></i> WhatsApp Share
                    </a>
                    <button id="btnCopy" class="btn-share" style="background:#0284c7;">
                        <i class="fas fa-copy"></i> Copy Text
                    </button>
                </div>
                <textarea id="hiddenCopyText" style="position: absolute; left: -9999px;"><?php echo $wa_share_msg; ?></textarea>
            </div>

        </div>
    </div>

    <!-- POSTER TEMPLATE FOR HTML2CANVAS -->
    <div id="poster-template-wrapper" style="position: absolute; left: -9999px;">
        <div id="statusPoster" style="width:500px; padding:25px; background:#0f172a; color:#fff; font-family:'Hind', sans-serif;">
            <div style="font-size:1.4rem; font-weight:700; color:#ef4444; margin-bottom:10px;"><?php echo $title; ?> (PDF Form)</div>
            <div style="margin-bottom:8px;">🔢 <strong>कुल पद:</strong> <?php echo $posts; ?></div>
            <div style="margin-bottom:8px;">📅 <strong>अंतिम तिथि:</strong> <?php echo $last_date_formatted; ?></div>
            <div style="margin-bottom:12px;">🎓 <strong>योग्यता:</strong> <?php echo $eligibility_short; ?></div>
            <div style="font-size:0.85rem; color:#94a3b8; border-top:1px solid #334155; padding-top:10px;">PDF फॉर्म डाउनलोड करने के लिए विजिट करें: <?php echo $host; ?></div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- External JS File with Cache Buster -->
    <script src="<?php echo $base_url; ?>/assets/js/vacancy_details.js?v=<?php echo time(); ?>"></script>
</body>
</html>