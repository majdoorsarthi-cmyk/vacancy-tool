<?php
/**
 * Project: Vacancy Portal - Public Vacancy Details Page
 * Updated Version: Clean Canonical, Proper 404 Handling, Expired Vacancy Support & Soft 404 Prevented
 */

// 1. डेटाबेस कनेक्शन
include 'includes/db_connect.php'; 

// ID सैनिटाइजेशन
$vacancy_id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;
$vacancy = null;

// 2. 'vacancies' और 'expired_vacancies' दोनों टेबल्स से भर्ती सुरक्षित तरीके से खोजें
if ($vacancy_id && isset($conn) && $conn) {
    // 2.1 सबसे पहले मुख्य 'vacancies' टेबल में खोजें
    $stmt = $conn->prepare("SELECT * FROM vacancies WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $vacancy_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $vacancy = $result->fetch_assoc();
        }
        $stmt->close();
    }
    
    // 2.2 अगर मुख्य टेबल में न मिले, तो 'expired_vacancies' में खोजें
    if (!$vacancy) {
        $stmt_exp = $conn->prepare("SELECT * FROM expired_vacancies WHERE id = ? LIMIT 1");
        if ($stmt_exp) {
            $stmt_exp->bind_param("i", $vacancy_id);
            $stmt_exp->execute();
            $result_exp = $stmt_exp->get_result();
            if ($result_exp && $result_exp->num_rows > 0) {
                $vacancy = $result_exp->fetch_assoc();
                $vacancy['is_expired'] = true; // एक्सपायर्ड फ़्लैग सेट करें
            }
            $stmt_exp->close();
        }
    }
}

// 🎯 3. SOFT 404 / REDIRECT ERROR FIX
// यदि दोनों टेबल्स में से कहीं भी भर्ती न मिले, तभी 404 पेज दिखाएं
if (!$vacancy) {
    http_response_code(404);
    $page_title = "404 - भर्ती नहीं मिली | Vacancy Portal";
    include 'includes/header.php';
    ?>
    <div style="max-width: 800px; margin: 60px auto; padding: 40px 20px; text-align: center; background: #ffffff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); font-family: 'Hind', sans-serif;">
        <div style="font-size: 70px; color: #ef4444; margin-bottom: 10px;"><i class="fas fa-exclamation-triangle"></i></div>
        <h1 style="font-size: 2rem; color: #0f172a; margin-bottom: 15px;">404 - यह भर्ती उपलब्ध नहीं है या हटा दी गई है</h1>
        <p style="color: #64748b; font-size: 1.1rem; margin-bottom: 25px;">आप जिस जॉब नोटिफिकेशन को खोज रहे हैं, वह गलत URL है या स्थायी रूप से हटा दी गई है।</p>
        <a href="index.php" style="display: inline-block; padding: 12px 28px; background: #2563eb; color: #ffffff; text-decoration: none; font-weight: 700; border-radius: 50px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);">
            <i class="fas fa-home"></i> मुख्य पृष्ठ (Home Page) पर जाएं
        </a>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

// 4. CLEAN CANONICAL URL SETUP
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$base_url = $scheme . "://" . $_SERVER['HTTP_HOST'];
$current_url = $base_url . "/vacancy_details.php?id=" . $vacancy_id;

// 5. फ़ील्ड्स सैनिटाइजेशन एवं डेट सेटअप
$title               = !empty($vacancy['title']) ? mb_strtoupper(htmlspecialchars($vacancy['title'], ENT_QUOTES, 'UTF-8')) : 'JOB VACANCY';
$category            = !empty($vacancy['category']) ? htmlspecialchars($vacancy['category'], ENT_QUOTES, 'UTF-8') : '';
$state               = !empty($vacancy['state']) ? htmlspecialchars($vacancy['state'], ENT_QUOTES, 'UTF-8') : 'All India';
$location            = !empty($vacancy['location']) ? htmlspecialchars($vacancy['location'], ENT_QUOTES, 'UTF-8') : 'India';
$short_desc          = !empty($vacancy['short_desc']) ? htmlspecialchars($vacancy['short_desc'], ENT_QUOTES, 'UTF-8') : '';
$posts               = !empty($vacancy['total_posts']) ? htmlspecialchars($vacancy['total_posts'], ENT_QUOTES, 'UTF-8') : 'विभिन्न पद';

// अतिरिक्त डेटाबेस फ़ील्ड्स
$salary              = !empty($vacancy['salary']) ? htmlspecialchars($vacancy['salary'], ENT_QUOTES, 'UTF-8') : 'नियमानुसार (As per Rules)';
$age_limit           = !empty($vacancy['age_limit']) ? $vacancy['age_limit'] : '';
$selection_process   = !empty($vacancy['selection_process']) ? $vacancy['selection_process'] : '';
$admit_card_date     = !empty($vacancy['admit_card_date']) ? htmlspecialchars($vacancy['admit_card_date'], ENT_QUOTES, 'UTF-8') : 'शीघ्र उपलब्ध';
$exam_date           = !empty($vacancy['exam_date']) ? htmlspecialchars($vacancy['exam_date'], ENT_QUOTES, 'UTF-8') : 'नोटिफिकेशन देखें';

// ISO डेट्स (Google Schema Compliance)
$raw_post_date       = !empty($vacancy['post_date']) ? $vacancy['post_date'] : date('Y-m-d');
$post_date           = date('d M, Y', strtotime($raw_post_date));
$iso_post_date       = date('c', strtotime($raw_post_date));

$last_date_raw       = !empty($vacancy['last_date']) ? $vacancy['last_date'] : '';
$last_date_formatted = !empty($last_date_raw) ? date('d M, Y', strtotime($last_date_raw)) : 'शीघ्र उपलब्ध';
$iso_last_date       = !empty($last_date_raw) ? date('c', strtotime($last_date_raw)) : date('c', strtotime('+60 days'));

$eligibility_raw     = !empty($vacancy['eligibility']) ? $vacancy['eligibility'] : '';
$eligibility_short   = !empty($eligibility_raw) ? mb_strimwidth(strip_tags($eligibility_raw), 0, 120, "...") : 'नियमानुसार';
$application_fee     = !empty($vacancy['application_fee']) ? $vacancy['application_fee'] : '';
$important_dates     = !empty($vacancy['important_dates']) ? $vacancy['important_dates'] : '';
$description         = !empty($vacancy['description']) ? $vacancy['description'] : '';

// विस्तृत जानकारी
$long_details        = !empty($vacancy['long_details']) ? $vacancy['long_details'] : '';
$extra_details       = !empty($vacancy['full_details']) ? $vacancy['full_details'] : (!empty($vacancy['extra_details']) ? $vacancy['extra_details'] : '');

// लिंक्स
$apply_link          = !empty($vacancy['apply_link']) ? htmlspecialchars($vacancy['apply_link'], ENT_QUOTES, 'UTF-8') : '';
$notification_link   = !empty($vacancy['notification_link']) ? htmlspecialchars($vacancy['notification_link'], ENT_QUOTES, 'UTF-8') : '';
$official_website    = !empty($vacancy['official_website']) ? htmlspecialchars($vacancy['official_website'], ENT_QUOTES, 'UTF-8') : '';
$result_link         = !empty($vacancy['result_link']) ? htmlspecialchars($vacancy['result_link'], ENT_QUOTES, 'UTF-8') : '';
$image_url           = !empty($vacancy['image_url']) ? htmlspecialchars($vacancy['image_url'], ENT_QUOTES, 'UTF-8') : $base_url . '/assets/vp.png';

// 500 SERVER ERROR PREVENT: Safe Function Redeclaration Check
if (!function_exists('render_portal_content')) {
    function render_portal_content($content) {
        if (empty($content)) return '';
        if ($content !== strip_tags($content)) {
            return $content;
        }
        return nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'));
    }
}

// WhatsApp Share Msg
$wa_share_msg  = "🔔 *" . strip_tags($title) . "*\n\n";
if (!empty($category)) { $wa_share_msg .= "📂 *श्रेणी:* {$category}\n"; }
if (!empty($state) || !empty($location)) { $wa_share_msg .= "📍 *स्थान/राज्य:* {$state} ({$location})\n"; }
if (!empty($posts)) { $wa_share_msg .= "🔢 *कुल पद:* {$posts}\n"; }
if (!empty($last_date_formatted)) { $wa_share_msg .= "📅 *अंतिम तिथि:* {$last_date_formatted}\n"; }
if (!empty($eligibility_short)) { $wa_share_msg .= "🎓 *योग्यता:* {$eligibility_short}\n\n"; }
$wa_share_msg .= "🔗 *पूरी जानकारी और फॉर्म भरने का लिंक:* \n{$current_url}";

// 15 FAQs Array Generation
$faqs_array = [
    ["q" => "{$title} के लिए ऑनलाइन आवेदन करने की अंतिम तिथि क्या है?", "a" => "इस भर्ती के अंतर्गत आवेदन करने की अंतिम तिथि <strong>{$last_date_formatted}</strong> है। अंतिम समय में सर्वर की समस्या से बचने हेतु जल्द से जल्द फॉर्म भरें।"],
    ["q" => "इस भर्ती प्रक्रिया में कुल कितने पदों के लिए अधिसूचना जारी की गई है?", "a" => "आधिकारिक नोटिफिकेशन के अनुसार कुल <strong>{$posts}</strong> पदों पर नियुक्ति की जाएगी।"],
    ["q" => "{$title} के लिए क्या शैक्षणिक योग्यता (Eligibility) तय की गई है?", "a" => !empty($eligibility_raw) ? strip_tags($eligibility_raw) : "शैक्षणिक योग्यता और पात्रता की विस्तृत जानकारी ऊपर दी गई तालिका में दी गई है।"],
    ["q" => "आवेदन करने के लिए आयु सीमा (Age Limit) क्या रखी गई है?", "a" => !empty($age_limit) ? strip_tags($age_limit) : "आरक्षित श्रेणियों (SC/ST/OBC/Ex-Servicemen) को सरकारी नियमों के अनुसार अधिकतम आयु सीमा में छूट दी जाएगी।"],
    ["q" => "आवेदन शुल्क (Application Fee) कितना देना होगा?", "a" => !empty($application_fee) ? strip_tags($application_fee) : "आवेदन शुल्क की जानकारी ऊपर दी गई तालिका में वर्ग अनुसार (General/OBC/SC/ST) उपलब्ध है।"],
    ["q" => "चयन प्रक्रिया (Selection Process) का आधार क्या होगा?", "a" => !empty($selection_process) ? strip_tags($selection_process) : "चयन प्रक्रिया में आमतौर पर लिखित परीक्षा, कौशल परीक्षण (Skill Test), दस्तावेज सत्यापन और चिकित्सा परीक्षण शामिल होते हैं।"],
    ["q" => "वेतनमान (Salary / Pay Scale) कितना प्राप्त होगा?", "a" => "चयनित उम्मीदवारों को नियमानुसार प्रतिमाह <strong>{$salary}</strong> का वेतनमान दिया जाएगा।"],
    ["q" => "एडमिट कार्ड (Admit Card) कब और कहाँ जारी किया जाएगा?", "a" => "एडमिट कार्ड परीक्षा तिथि से 7 से 10 दिन पूर्व आधिकारिक पोर्टल पर जारी कर दिए जाएंगे। हमारी वेबसाइट पर डायरेक्ट लिंक सक्रिय हो जाएगा।"],
    ["q" => "इस भर्ती के लिए ऑनलाइन फॉर्म (Online Apply) कैसे भरें?", "a" => "आवेदन के लिए ऊपर दिए गए 'महत्वपूर्ण लिंक्स' अनुभाग में 'Click Here (Apply Online)' बटन पर क्लिक करके आधिकारिक पोर्टल पर जाएं।"],
    ["q" => "क्या इस भर्ती में महिलाओं एवं आरक्षित वर्गों को छूट मिलेगी?", "a" => "हाँ, राज्य एवं केंद्र सरकार के नियमानुसार आरक्षित वर्गों (SC/ST/OBC/EWS/PWD) एवं महिलाओं को आयु और शुल्क में छूट का प्रावधान है।"],
    ["q" => "आवेदन पत्र में संशोधन (Correction) का मौका मिलेगा या नहीं?", "a" => "अधिकांश परीक्षाओं में आवेदन की अंतिम तिथि के बाद 2-3 दिनों के लिए संशोधन विंडो (Correction Window) खोली जाती है।"],
    ["q" => "परीक्षा का माध्यम (Exam Medium) हिंदी होगा या अंग्रेजी?", "a" => "सामान्यतः परीक्षा प्रश्न पत्र हिंदी और अंग्रेजी दोनों भाषाओं (Bilingual) में उपलब्ध कराया जाता है।"],
    ["q" => "ऑफिशियल नोटिफिकेशन (Official PDF) कहाँ से डाउनलोड करें?", "a" => "आप ऊपर लिंक्स सेक्शन में दिए गए 'Download Notification' बटन पर क्लिक करके आधिकारिक पीडीएफ डाउनलोड कर सकते हैं।"],
    ["q" => "क्या अन्य राज्यों के उम्मीदवार भी इस पद के लिए पात्र हैं?", "a" => "यदि भर्ती अखिल भारतीय (All India) स्तर की है या राज्य सरकार अन्य राज्यों के अभ्यर्थियों को सामान्य श्रेणी के अंतर्गत अनुमति देती है तो आप आवेदन कर सकते हैं।"],
    ["q" => "भर्ती से जुड़ी नई अपडेट्स सबसे पहले कहाँ प्राप्त करें?", "a" => "तत्काल नोटिफिकेशन और डेली जॉब अलर्ट्स के लिए ऊपर दिए गए 'Join WhatsApp Group' लिंक से हमारे ग्रुप में जुड़ें।"]
];

// Header के लिए टाइटल पास करें
$page_title = $title . " - ऑनलाइन फॉर्म, पात्रता एवं पूरी जानकारी";
$page_description = !empty($short_desc) ? $short_desc : $title . ' - ऑनलाइन फॉर्म, अंतिम तिथि, कुल पद ' . $posts . ' और शैक्षणिक योग्यता की पूरी जानकारी देखें।';
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <link rel="canonical" href="<?php echo $current_url; ?>" />

    <!-- Open Graph Social Meta Tags -->
    <meta property="og:title" content="<?php echo $title; ?>" />
    <meta property="og:description" content="राज्य: <?php echo $state; ?> | कुल पद: <?php echo $posts; ?> | अंतिम तिथि: <?php echo $last_date_formatted; ?>" />
    <meta property="og:url" content="<?php echo $current_url; ?>" />
    <meta property="og:type" content="article" />
    <meta property="og:image" content="<?php echo $image_url; ?>" />

    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <!-- INLINE COLORFUL VIBRANT STYLING -->
    <style>
        :root {
            --primary-bg: #f8fafc;
            --card-bg: #ffffff;
            --header-grad: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            --accent-blue: #2563eb;
            --accent-green: #16a34a;
            --accent-red: #dc2626;
            --accent-amber: #d97706;
            --text-dark: #0f172a;
            --text-muted: #475569;
        }

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
            box-shadow: 0 10px 25px rgba(30, 27, 75, 0.25);
            border-left: 8px solid #38bdf8;
        }

        .job-badge {
            display: inline-block;
            background: rgba(56, 189, 248, 0.2);
            color: #38bdf8;
            border: 1px solid #38bdf8;
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        .job-title-main {
            font-size: 1.8rem;
            font-weight: 800;
            margin: 0 0 10px 0;
            color: #ffffff;
            line-height: 1.3;
        }

        .job-subtitle {
            font-size: 0.98rem;
            color: #cbd5e1;
            margin: 0;
        }

        /* Tables Styling */
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
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
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

        /* Specific Table Color Variants */
        .table-theme-blue th { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important; }
        .table-theme-green th { background: linear-gradient(135deg, #16a34a 0%, #15803d 100%) !important; }
        .table-theme-purple th { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important; }
        .table-theme-teal th { background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%) !important; }
        .table-theme-dark th { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%) !important; }

        .long-text-area {
            font-size: 0.98rem;
            line-height: 1.8;
            color: #1e293b;
        }

        /* Buttons */
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
        .link-row-btn.green { background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); }
        .link-row-btn.red { background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); }
        .link-row-btn.whatsapp { background: linear-gradient(135deg, #25d366 0%, #128c7e 100%); }

        /* FAQs Accordion */
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
        .faq-item:hover { border-color: #0284c7; }
        .faq-question {
            padding: 14px 18px;
            font-weight: 700;
            font-size: 0.98rem;
            color: #0369a1;
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

        /* Share Box */
        .share-box {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 1.5px solid #bfdbfe;
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
        }
    </style>

    <!-- GOOGLE SCHEMA.ORG (JSON-LD) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org/",
      "@type": "JobPosting",
      "title": "<?php echo addslashes(strip_tags($title)); ?>",
      "description": "<?php echo addslashes(strip_tags(!empty($short_desc) ? $short_desc : $title)); ?>",
      "datePosted": "<?php echo $iso_post_date; ?>",
      "validThrough": "<?php echo $iso_last_date; ?>",
      "directApply": true,
      "employmentType": "FULL_TIME",
      "hiringOrganization": {
        "@type": "Organization",
        "name": "Vacancy Portal",
        "sameAs": "<?php echo $base_url; ?>"
      },
      "jobLocation": {
        "@type": "Place",
        "address": {
          "@type": "PostalAddress",
          "addressLocality": "<?php echo !empty($location) ? addslashes($location) : 'India'; ?>",
          "addressRegion": "<?php echo !empty($state) ? addslashes($state) : 'India'; ?>",
          "addressCountry": "IN"
        }
      }
    }
    </script>

    <!-- 15 FAQs SCHEMA FOR GOOGLE SEARCH -->
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
                "name": "' . addslashes($faq['q']) . '",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "' . addslashes(strip_tags($faq['a'])) . '"
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
            
            <!-- EXPIRED VACANCY BANNER (अगर भर्ती एक्सपायर टेबल से आ रही हो) -->
            <?php if (!empty($vacancy['is_expired'])): ?>
            <div style="background: #fef2f2; border: 2px solid #ef4444; color: #991b1b; padding: 14px 20px; border-radius: 12px; margin-bottom: 20px; text-align: center; font-weight: 700; font-size: 1rem;">
                ⚠️ यह भर्ती/अधिसूचना समाप्त (Expired) हो चुकी है। यह जानकारी केवल आपके संदर्भ और रिकॉर्ड के लिए उपलब्ध है।
            </div>
            <?php endif; ?>

            <!-- 1. VIBRANT HERO HEADER -->
            <div class="job-header">
                <?php if(!empty($category) || !empty($state)): ?>
                <div class="job-badge">
                    <i class="fas fa-fire"></i> <?php echo !empty($category) ? strtoupper($category) : ''; ?><?php echo (!empty($category) && !empty($state)) ? ' • ' : ''; ?><?php echo $state; ?>
                </div>
                <?php endif; ?>

                <h1 class="job-title-main"><?php echo $title; ?></h1>

                <?php if(!empty($short_desc)): ?>
                <p class="job-subtitle"><i class="fas fa-info-circle text-sky-400"></i> <?php echo $short_desc; ?></p>
                <?php endif; ?>
            </div>

            <!-- 2. VACANCY HIGHLIGHTS TABLE -->
            <div class="table-responsive">
                <table class="job-table table-theme-blue">
                    <thead>
                        <tr>
                            <th colspan="2"><i class="fas fa-list-check"></i> भर्ती संक्षिप्त विवरण (Vacancy Highlights)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="label-col">भर्ती का नाम (Post Name)</td>
                            <td><strong style="color:#0284c7;"><?php echo $title; ?></strong></td>
                        </tr>
                        
                        <?php if(!empty($category)): ?>
                        <tr>
                            <td class="label-col">श्रेणी / वर्ग (Category)</td>
                            <td><span style="background: #e2e8f0; color: #1e293b; padding: 3px 8px; border-radius: 4px; font-weight: 600;"><?php echo $category; ?></span></td>
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
                            <td><span style="background: #e0f2fe; color: #0369a1; padding: 3px 10px; border-radius: 6px; font-weight: 800;"><?php echo $posts; ?></span></td>
                        </tr>
                        <tr>
                            <td class="label-col">वेतनमान / सैलरी (Salary)</td>
                            <td><strong style="color: #16a34a;"><?php echo $salary; ?></strong></td>
                        </tr>
                        <tr>
                            <td class="label-col">जारी होने की तिथि (Post Date)</td>
                            <td><?php echo $post_date; ?></td>
                        </tr>
                        <tr>
                            <td class="label-col">अंतिम तिथि (Last Date)</td>
                            <td><strong style="color: #dc2626; background: #ffe4e6; padding: 2px 8px; border-radius: 4px;"><?php echo $last_date_formatted; ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 3. IMPORTANT DATES & APPLICATION FEE TABLE -->
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
                                    • आवेदन शुरू तिथि: <strong><?php echo $post_date; ?></strong><br>
                                    • अंतिम तिथि: <strong style="color:#dc2626;"><?php echo $last_date_formatted; ?></strong><br>
                                    • परीक्षा तिथि: <strong><?php echo $exam_date; ?></strong><br>
                                    • एडमिट कार्ड तिथि: <strong><?php echo $admit_card_date; ?></strong>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(!empty($application_fee)): ?>
                                    <?php echo render_portal_content($application_fee); ?>
                                <?php else: ?>
                                    • नोटिफिकेशन के अनुसार / वर्ग अनुसार
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 4. AGE LIMIT & SELECTION PROCESS -->
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

            <!-- 5. ELIGIBILITY DETAILS -->
            <?php if(!empty($eligibility_raw)): ?>
            <div class="table-responsive">
                <table class="job-table table-theme-teal">
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

            <!-- 6. INSTRUCTIONS / APPLICATION PROCESS -->
            <?php if(!empty($description)): ?>
            <div class="table-responsive">
                <table class="job-table table-theme-blue">
                    <thead>
                        <tr>
                            <th><i class="fas fa-tasks"></i> आवेदन कैसे करें एवं निर्देश (Instructions)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="long-text-area">
                                    <?php echo render_portal_content($description); ?>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- 7. DETAILED NOTIFICATION / LONG DETAILS -->
            <?php if(!empty($long_details)): ?>
            <div class="table-responsive">
                <table class="job-table table-theme-dark">
                    <thead>
                        <tr>
                            <th><i class="fas fa-file-invoice"></i> विस्तृत अधिसूचना विवरण (Detailed Notification & Syllabus)</th>
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

            <!-- 8. EXTRA DETAILS SECTION -->
            <?php if(!empty($extra_details)): ?>
            <div class="table-responsive">
                <table class="job-table table-theme-purple">
                    <thead>
                        <tr>
                            <th><i class="fas fa-info-circle"></i> अतिरिक्त महत्वपूर्ण जानकारी (Extra Details)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="long-text-area">
                                    <?php echo render_portal_content($extra_details); ?>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- 9. IMPORTANT DIRECT LINKS TABLE -->
            <div class="table-responsive">
                <table class="job-table table-theme-dark">
                    <thead>
                        <tr>
                            <th colspan="2" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);"><i class="fas fa-link"></i> महत्वपूर्ण लिंक्स (Important Direct Links)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($apply_link)): ?>
                        <tr>
                            <td class="label-col">ऑनलाइन आवेदन करें (Apply Online)</td>
                            <td>
                                <a href="<?php echo $apply_link; ?>" target="_blank" rel="nofollow noopener" class="link-row-btn green">
                                    Click Here <i class="fas fa-paper-plane"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php if(!empty($notification_link)): ?>
                        <tr>
                            <td class="label-col">ऑफिशियल नोटिफिकेशन डाउनलोड करें (Download Notification)</td>
                            <td>
                                <a href="<?php echo $notification_link; ?>" target="_blank" rel="nofollow noopener" class="link-row-btn red">
                                    Download PDF <i class="fas fa-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php if(!empty($result_link)): ?>
                        <tr>
                            <td class="label-col">रिजल्ट / एडमिट कार्ड (Result / Admit Card)</td>
                            <td>
                                <a href="<?php echo $result_link; ?>" target="_blank" rel="nofollow noopener" class="link-row-btn red">
                                    Click Here <i class="fas fa-poll"></i>
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
                            <td class="label-col">व्हाट्सएप जॉब अलर्ट (Join Job Group)</td>
                            <td>
                                <a href="https://chat.whatsapp.com/GF1BDKhdBVZ5AH59aVmcRJ" target="_blank" rel="noopener" class="link-row-btn whatsapp">
                                    Join WhatsApp <i class="fab fa-whatsapp"></i>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 10. FULL 15 FAQS ACCORDION SECTION -->
            <div class="faq-container">
                <div class="faq-title"><i class="fas fa-question-circle text-sky-600"></i> अक्सर पूछे जाने वाले 15 महत्वपूर्ण प्रश्न (FAQ)</div>

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

            <!-- 11. SHARE FOOTER STRIP -->
            <div class="share-box">
                <div>📢 <strong>दोस्तों के साथ शेयर करें:</strong></div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button id="btnPoster" class="btn-share" style="background:#475569;">
                        <i class="fas fa-image"></i> Poster Download
                    </button>
                    <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($wa_share_msg); ?>" target="_blank" class="btn-share" style="background:#25d366;">
                        <i class="fab fa-whatsapp"></i> WhatsApp Share
                    </a>
                    <button id="btnCopy" class="btn-share" style="background:#2563eb;">
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
            <div style="font-size:1.4rem; font-weight:700; color:#38bdf8; margin-bottom:10px;"><?php echo $title; ?></div>
            <div style="margin-bottom:8px;">🔢 <strong>कुल पद:</strong> <?php echo $posts; ?></div>
            <div style="margin-bottom:8px;">📅 <strong>अंतिम तिथि:</strong> <?php echo $last_date_formatted; ?></div>
            <div style="margin-bottom:12px;">🎓 <strong>योग्यता:</strong> <?php echo $eligibility_short; ?></div>
            <div style="font-size:0.85rem; color:#94a3b8; border-top:1px solid #334155; padding-top:10px;">पूरी जानकारी के लिए विजिट करें: <?php echo $_SERVER['HTTP_HOST']; ?></div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- External JS File -->
    <script src="<?php echo $base_url; ?>/assets/js/vacancy_details.js?v=<?php echo time(); ?>"></script>
</body>
</html>