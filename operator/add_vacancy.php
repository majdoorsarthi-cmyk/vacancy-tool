<?php
session_start();

// 1. सुरक्षा जाँच (Operator Authentication)
if (!isset($_SESSION['operator_logged_in'])) {
    header("location: login.php");
    exit;
}

include '../includes/db_connect.php';

// 🚀 WhatsApp Channel Auto-Post Function (Direct Meta Cloud API)
function send_whatsapp_channel_notification($title, $vacancy_id, $eligibility, $total_posts) {
    // आपकी सेटिंग्स
    $phone_number_id = "1110702558788418"; 
    $access_token    = "EAAS2fcIKUq0BSNkR6sv2T5KNaQcRoml1rUL7Nsv7N5xtwJPzPHXC80qeQnhOUlWP8PgoOnTBured1eFoRyCXakFsfzlBsriDBRZCzf0LGbyK1Au6DLIGCUcDrOSoZB9BAtWBY3Agy56yEab8e7ZAUvHRUwoG7MPty7Lym2F0FAGJc7VS34yd7EvcWaX9SZBWRj9KGc8lSsE7yCy9EbPkczvxLa8XLjGJfXWeQuYQun2O3HbAb92Rn3GaFeKttQ5vPoPZB7fpS3fgoSqdKSs27uFGF"; 
    $channel_id      = "0029Vb7Cpuy4SpkPg9U35c2x"; 

    $post_url = "https://tc.vacancyportal.co.in/vacancy_details.php?id=" . $vacancy_id;
    
    // HTML और एक्स्ट्रा टैग्स साफ़ करें
    $clean_title       = trim(strip_tags($title));
    $clean_eligibility = trim(strip_tags($eligibility));
    $clean_posts       = trim(strip_tags($total_posts));

    if (empty($clean_eligibility)) {
        $clean_eligibility = "संबंधित Official Notification में निर्धारित योग्यता पूरी करना आवश्यक होगा।";
    }

    if (empty($clean_posts)) {
        $clean_posts = "नोटिफिकेशन देखें";
    }

    // व्हाट्सएप Markdown लेआउट
    $message  = "🔥 *धमाकेदार भर्ती: नया नोटिफिकेशन जारी* 🔥\n";
    $message .= "🚨 *tc.vacancyportal.co.in* 🚨\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $message .= "💥 *भर्ती का नाम (Post Name):*\n";
    $message .= "💎 *" . $clean_title . "* 💎\n\n";
    
    $message .= "ℹ️ *महत्वपूर्ण जानकारी (Details):*\n";
    $message .= "```";
    $message .= "• योग्यता (Eligibility):\n" . $clean_eligibility . "\n\n";
    $message .= "• कुल पद (Total Posts): " . $clean_posts . "\n";
    $message .= "• फॉर्म मोड: ऑनलाइन (Online)\n";
    $message .= "• स्टेटस: लाइव (Official Update)\n";
    $message .= "```\n\n";
    
    $message .= "👇 *ऑफिशियल नोटिफिकेशन एवं लागू लिंक:* 👇\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "🌐 *डायरेक्ट लिंक (Click Here):*\n";
    $message .= "🔗 " . $post_url . "\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $message .= "🌐 *सभी अपडेट्स के लिए गूगल पर सर्च करें:* tc.vacancyportal.co.in\n";
    $message .= "📢 *पल-पल की अपडेट के लिए चैनल को Follow करें!*\n";
    $message .= "🚀 *अपने दोस्तों के साथ ज़रूर शेयर करें!*";

    // Meta API Request
    $url = "https://graph.facebook.com/v18.0/{$phone_number_id}/messages";
    
    $payload = [
        "messaging_product" => "whatsapp",
        "recipient_type"    => "individual",
        "to"                => $channel_id,
        "type"              => "text",
        "text"              => [
            "preview_url" => true,
            "body"        => $message
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$access_token}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

// वर्तमान पेज का नाम जाँचना
$current_uri = basename($_SERVER['PHP_SELF']);

// वॉलेट बैलेंस का सेफ फॉलबैक
$wallet_balance = isset($_SESSION['wallet_balance']) ? $_SESSION['wallet_balance'] : 0;

// ऑपरेटर डेटा (सत्र से)
$operator_name = isset($_SESSION['operator_name']) ? $_SESSION['operator_name'] : 'MINI';
$operator_email = isset($_SESSION['operator_email']) ? $_SESSION['operator_email'] : 'majdoorsarthi@gmail.com';
$operator_id = isset($_SESSION['operator_id']) ? intval($_SESSION['operator_id']) : NULL;

$is_suspended = 0;
$op_rank = 'Junior Operator';
$op_score = 0;

// डेटाबेस से ऑपरेटर की रीयल-टाइम स्थिति निकालें
if ($operator_id) {
    $check_op = $conn->prepare("SELECT is_suspended, designation, performance_score FROM operators WHERE id = ?");
    $check_op->bind_param("i", $operator_id);
    $check_op->execute();
    $check_op->bind_result($is_suspended, $op_rank, $op_score);
    $check_op->fetch();
    $check_op->close();
}

// 🎯 सभी 8 कैटेगरीज की लिस्ट और उनके हिंदी नाम (Fixed)
$defined_categories = [
    'job'        => 'नई सरकारी नौकरी',
    ];

// 📊 ऑपरेटर की कैटेगरी-वाइज़ प्रोग्रेस रिपोर्ट तैयार करना
$category_counts = [];
foreach ($defined_categories as $key => $val) {
    $category_counts[$key] = 0; // डिफ़ॉल्ट 0 सेट करें
}

if ($operator_id) {
    $count_query = "SELECT category, COUNT(*) as total FROM vacancies WHERE operator_id = ? GROUP BY category";
    if ($c_stmt = $conn->prepare($count_query)) {
        $c_stmt->bind_param("i", $operator_id);
        $c_stmt->execute();
        $res = $c_stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            if (array_key_exists($row['category'], $category_counts)) {
                $category_counts[$row['category']] = intval($row['total']);
            }
        }
        $c_stmt->close();
    }
}

// 🏆 पदोन्नति (Promotion) के लिए पात्रता की जाँच करें
$eligible_for_promotion = true;
$motivation_messages = [];
$total_remaining_posts = 0;

foreach ($defined_categories as $cat_key => $cat_name) {
    $current_count = $category_counts[$cat_key];
    if ($current_count < 25) {
        $eligible_for_promotion = false;
        $remaining = 25 - $current_count;
        $total_remaining_posts += $remaining;
        $motivation_messages[] = "📌 **{$cat_name}** में अभी **{$remaining}** पोस्ट और चाहिए।";
    }
}

$message = "";

// फॉर्म सबमिशन हैंडलिंग
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($is_suspended == 1) {
        $message = "<div class='alert error'>❌ आपका अकाउंट ब्लॉक है। आप कोई नई वैकेंसी पोस्ट नहीं कर सकते।</div>";
    } else {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $category = isset($_POST['category']) ? $_POST['category'] : '';
        $state = isset($_POST['state']) ? $_POST['state'] : ''; 
        $short_desc = isset($_POST['short_desc']) ? trim($_POST['short_desc']) : '';
        $result_link = isset($_POST['result_link']) ? trim($_POST['result_link']) : '';
        $long_details = isset($_POST['long_details']) ? $_POST['long_details'] : ''; 
        $description = isset($_POST['description']) ? $_POST['description'] : '';  
        $important_dates = isset($_POST['important_dates']) ? trim($_POST['important_dates']) : '';
        $eligibility = isset($_POST['eligibility']) ? trim($_POST['eligibility']) : '';
        $application_fee = isset($_POST['application_fee']) ? trim($_POST['application_fee']) : '';
        $apply_link = isset($_POST['apply_link']) ? trim($_POST['apply_link']) : '';
        $official_website = isset($_POST['official_website']) ? trim($_POST['official_website']) : '';
        $last_date = !empty($_POST['last_date']) ? $_POST['last_date'] : NULL;
        $total_posts = !empty($_POST['total_posts']) ? trim($_POST['total_posts']) : 'नोटिफिकेशन देखें';
        
        // 🔒 ADMIN VERIFICATION UPDATE: ऑपरेटर द्वारा पोस्ट हमेशा 'pending' ही रहेगी
        $status = 'pending'; 
        
        $full_details = isset($_POST['full_details']) ? $_POST['full_details'] : ''; 
        $post_date = date('Y-m-d H:i:s');

        $sql = "INSERT INTO vacancies (operator_id, title, category, state, short_desc, result_link, long_details, description, important_dates, eligibility, application_fee, apply_link, official_website, post_date, last_date, status, full_details, total_posts)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("isssssssssssssssss", 
                $operator_id, $title, $category, $state, $short_desc, $result_link, $long_details, 
                $description, $important_dates, $eligibility, $application_fee, $apply_link, 
                $official_website, $post_date, $last_date, $status, $full_details, $total_posts
            );

            if ($stmt->execute()) {
                $inserted_id = $conn->insert_id;
                
                // 🚀 GOOGLE AUTO-INDEXING (कोड सुरक्षित है, हालांकि Pending स्टेटस के कारण एडमिन पैनल से भी ट्रिगर किया जा सकता है)
                $indexing_file = __DIR__ . '/../admin/includes/google_indexing.php';
                if (file_exists($indexing_file)) {
                    include_once $indexing_file;
                    $post_url = "https://www.vacancyportal.co.in/pdf_details.php?id=" . $inserted_id;
                    if (function_exists('sendToGoogleIndexing')) {
                        sendToGoogleIndexing($post_url, 'URL_UPDATED');
                    }
                }

                // 🚀 WHATSAPP AUTO-POST (चूंकि स्टेटस हमेशा 'pending' है, यह एडमिन एप्रूवल के बाद ही चलेगा, लेकिन कोड यहीं सुरक्षित है)
                if ($status == 'live') {
                    send_whatsapp_channel_notification($title, $inserted_id, $eligibility, $total_posts);
                }

                // 1. स्कोर हमेशा +10 बढ़ाएं
                $update_score = $conn->prepare("UPDATE operators SET performance_score = performance_score + 10 WHERE id = ?");
                $update_score->bind_param("i", $operator_id);
                $update_score->execute();
                $update_score->close();

                // 2. ताज़ा स्कोर प्राप्त करें
                $refresh_score = $conn->prepare("SELECT performance_score FROM operators WHERE id = ?");
                $refresh_score->bind_param("i", $operator_id);
                $refresh_score->execute();
                $refresh_score->bind_result($new_score);
                $refresh_score->fetch();
                $refresh_score->close();

                // 3. पदोन्नति (Designation) की जाँच
                $category_counts[$category]++;
                $still_eligible = true;
                foreach ($defined_categories as $k => $v) {
                    if ($category_counts[$k] < 25) {
                        $still_eligible = false;
                    }
                }

                $rank_updated_msg = "";
                if ($still_eligible) {
                    if ($new_score > 500 && $new_score <= 1500) {
                        $new_rank = 'Senior Operator';
                    } elseif ($new_score > 1500) {
                        $new_rank = 'Data Moderator';
                    } else {
                        $new_rank = 'Junior Operator';
                    }

                    $update_rank = $conn->prepare("UPDATE operators SET designation = ? WHERE id = ?");
                    $update_rank->bind_param("si", $new_rank, $operator_id);
                    $update_rank->execute();
                    $update_rank->close();
                    $rank_updated_msg = " और आपकी कड़ी मेहनत से आपका पद (Designation) भी अपडेट हो गया है! 🎉";
                }

                header("Location: add_vacancy.php?success=1&rankmsg=" . urlencode($rank_updated_msg));
                exit;
            } else {
                $message = "<div class='alert error'>❌ त्रुटि: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }
}

if (isset($_GET['success'])) {
    $rank_msg = isset($_GET['rankmsg']) ? htmlspecialchars($_GET['rankmsg']) : "";
    $message = "<div class='alert success'>⏳ वैकेंसी समीक्षा (Admin Verification) के लिए भेज दी गई है! (+10 परफॉरमेंस पॉइंट जुड़ गए हैं){$rank_msg}</div>";
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="vp.png">
    <link rel="apple-touch-icon" href="vp.png">
    <title>नई वैकेंसी जोड़ें | ऑपरेटर डैशबोर्ड</title>
    
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>

    <style>
        body { margin: 0; background-color: #f1f5f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .admin-container { background: #fff; padding: 35px; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.04); }
        .form-group { margin-bottom: 25px; }
        label { font-weight: 600; display: block; margin-bottom: 8px; color: #334155; font-size: 14px; }
        input[type="text"], input[type="date"], select, textarea {
            width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box; font-size: 14px; transition: all 0.2s; background-color: #f8fafc;
        }
        input[type="text"]:focus, input[type="date"]:focus, select:focus, textarea:focus { border-color: #4f46e5; outline: none; background-color: #fff; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        .form-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 25px; }
        .form-row > div { flex: 1; min-width: 250px; }
        .btn-submit { 
            background: #4f46e5; color: white; border: none; padding: 16px 30px; cursor: pointer; border-radius: 8px; font-size: 16px; font-weight: bold; width: 100%; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2); transition: all 0.2s;
        }
        .btn-submit:hover { background: #4338ca; }
        .btn-submit:disabled { background: #94a3b8; cursor: not-allowed; box-shadow: none; }
        .alert { padding: 18px; margin-bottom: 25px; border-radius: 8px; font-size: 14px; font-weight: 500; }
        .success { background: #dfe7ff; color: #1e40af; border-left: 5px solid #3b82f6; }
        .error { background: #fee2e2; color: #991b1b; border-left: 5px solid #ef4444; }
    </style>
</head>
<body>

<div class="flex min-h-screen">
    
    <!-- Sidebar -->
    <div class="hidden md:flex flex-col w-64 bg-[#0f1423] text-slate-400 min-h-screen border-r border-slate-800/40 shrink-0 justify-between p-4">
        <div>
            <div class="flex items-center gap-3 px-2 py-4 mb-4 border-b border-slate-800">
                <div class="h-10 w-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg">
                    <i class="fas fa-user-tie text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-white flex items-center gap-1.5">कार्यक्षेत्र (Pro)</h2>
                    <span class="text-[10px] <?php echo ($is_suspended) ? 'text-rose-400' : 'text-emerald-400'; ?> flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full <?php echo ($is_suspended) ? 'bg-rose-500' : 'bg-emerald-500 animate-pulse'; ?>"></span> 
                        <?php echo ($is_suspended) ? 'अकाउंट ब्लॉक है' : 'सुरक्षित सत्र सक्रिय'; ?>
                    </span>
                </div>
            </div>

            <div class="px-3 py-2.5 mb-6 bg-slate-900/40 rounded-xl border border-slate-800/50">
                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-500 block mb-0.5">लॉग-इन ऑपरेटर</span>
                <span class="text-xs font-bold text-amber-400 block"><?php echo htmlspecialchars($operator_name); ?></span>
                <span class="text-[11px] text-slate-400 block truncate mb-1"><?php echo htmlspecialchars($operator_email); ?></span>
                
                <div class="mt-2 pt-2 border-t border-slate-800 flex justify-between items-center text-[11px]">
                    <span class="text-slate-400"><i class="fas fa-award text-yellow-500 me-1"></i> <?php echo htmlspecialchars($op_rank); ?></span>
                    <span class="text-emerald-400 font-bold"><?php echo $op_score; ?> Pts</span>
                </div>
            </div>

            <nav class="space-y-1.5">
                <a href="dashboard.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-[13px] font-medium transition-all duration-300 <?php echo ($current_uri == 'dashboard.php') ? 'bg-indigo-600/15 text-indigo-400 border-l-4 border-indigo-500 font-semibold' : 'hover:bg-slate-900 hover:text-slate-200'; ?>">
                    <i class="fas fa-chart-pie text-sm w-4 text-center"></i> <span>डैशबोर्ड मुख्य</span>
                </a>
                <a href="add_vacancy.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-[13px] font-medium transition-all duration-300 <?php echo ($current_uri == 'add_vacancy.php') ? 'bg-indigo-600/15 text-indigo-400 border-l-4 border-indigo-500 font-semibold' : 'hover:bg-slate-900 hover:text-slate-200'; ?>">
                    <i class="fas fa-plus-circle text-sm w-4 text-center"></i> <span>नई वैकेंसी जोड़ें</span>
                </a>
                <a href="manage_vacancies.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-[13px] font-medium transition-all duration-300 <?php echo ($current_uri == 'manage_vacancies.php') ? 'bg-indigo-600/15 text-indigo-400 border-l-4 border-indigo-500 font-semibold' : 'hover:bg-slate-900 hover:text-slate-200'; ?>">
                    <i class="fas fa-list-check text-sm w-4 text-center"></i> <span>वैकेंसी प्रबंधित करें</span>
                </a>
                <a href="wallet.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-[13px] font-medium transition-all duration-300 <?php echo ($current_uri == 'wallet.php') ? 'bg-indigo-600/15 text-indigo-400 border-l-4 border-indigo-500 font-semibold' : 'hover:bg-slate-900 hover:text-slate-200'; ?>">
                    <i class="fas fa-wallet text-sm w-4 text-center"></i> <span>वॉलेट और आय</span>
                </a>
            </nav>
        </div>
        <div class="pt-4 border-t border-slate-800">
            <a href="logout.php" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl border border-rose-500/30 text-xs font-bold text-rose-400 hover:bg-rose-500 hover:text-white transition-all duration-300">
                <i class="fas fa-power-off"></i> <span>लॉगआउट करें</span>
            </a>
        </div>
    </div>

    <!-- Main Content Area -->
    <main class="flex-grow p-8 box-sizing-border overflow-x-hidden">
        
        <!-- Rank Promotion Target -->
        <div class="mb-6 p-5 bg-gradient-to-r from-slate-900 to-indigo-950 rounded-2xl text-white shadow-xl border border-indigo-500/20">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h3 class="text-lg font-bold flex items-center gap-2 text-amber-400">
                        <i class="fas fa-trophy animate-bounce"></i> पदोन्नति मिशन (Rank Promotion Target)
                    </h3>
                    <p class="text-xs text-slate-300 mt-1">
                        वर्तमान पद: <span class="bg-indigo-500/30 px-2 py-0.5 rounded text-white border border-indigo-500/50 font-bold"><?php echo htmlspecialchars($op_rank); ?></span>
                    </p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 block">कुल परफॉरमेंस स्कोर</span>
                    <span class="text-2xl font-black text-emerald-400"><?php echo $op_score; ?> Pts</span>
                </div>
            </div>
            
            <hr class="my-4 border-slate-800">

            <?php if ($eligible_for_promotion): ?>
                <div class="bg-emerald-500/10 border border-emerald-500/30 p-3 rounded-xl text-emerald-400 text-sm flex items-center gap-2">
                    <i class="fas fa-check-circle"></i> 
                    <strong>बधाई हो!</strong> आपने सभी 8 कैटेगरीज में 25-25 पोस्ट का कोटा पूरा कर लिया है। अब आपका पद आपके कुल स्कोर के हिसाब से ऑटो-अपडेट होगा!
                </div>
            <?php else: ?>
                <div class="text-sm text-slate-200">
                    <p class="font-semibold mb-2 text-indigo-300">
                        🚀 <span class="text-white">अगले उच्च पद (Designation Update) पर पहुँचने के लिए</span> आपको सभी 8 कैटेगरीज में कम से कम 25-25 पोस्ट पूरे करने होंगे। (अभी कुल <strong><?php echo $total_remaining_posts; ?> post</strong> बाकी हैं)
                    </p>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mt-3 text-xs">
                        <?php foreach ($defined_categories as $key => $name): 
                            $count = $category_counts[$key];
                            $percent = min(100, ($count / 25) * 100);
                            $is_done = ($count >= 25);
                        ?>
                            <div class="bg-slate-800/60 p-2.5 rounded-xl border <?php echo $is_done ? 'border-emerald-500/30 bg-emerald-950/20' : 'border-slate-700/50'; ?>">
                                <div class="flex justify-between font-medium mb-1 truncate">
                                    <span class="<?php echo $is_done ? 'text-emerald-400 font-bold' : 'text-slate-300'; ?>"><?php echo $name; ?></span>
                                    <span class="<?php echo $is_done ? 'text-emerald-400' : 'text-amber-400'; ?>"><?php echo $count; ?>/25</span>
                                </div>
                                <div class="w-full bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                    <div class="h-full <?php echo $is_done ? 'bg-emerald-500' : 'bg-indigo-500'; ?>" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex justify-between items-center mb-6 pb-4 border-b border-slate-200 gap-2 wrap">
            <h2 class="text-2xl font-bold text-slate-800">➕ नई वैकेंसी जोड़ें (ऑपरेटर डैशबोर्ड)</h2>
            
            <div class="flex gap-2">
                <button type="button" onclick="openGuideModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-xl shadow transition flex items-center gap-2 text-sm">
                    <i class="fas fa-question-circle"></i> फॉर्म कैसे भरें?
                </button>

                <button type="button" onclick="openPosterModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-xl shadow transition flex items-center gap-2 text-sm">
                    <i class="fas fa-file-image"></i> गाइड पोस्टर देखें (Full Guide)
                </button>
            </div>
        </div>

        <?php 
        echo $message; 
        if ($is_suspended == 1 && empty($message)) {
            echo "<div class='alert error'>❌ आपका अकाउंट सस्पेंडेड है। फॉर्म सबमिशन लॉक कर दिया गया है।</div>";
        }
        ?>

        <form method="POST">
            <div class="form-group">
                <label>Post Title (मुख्य शीर्षक):</label>
                <input type="text" name="title" required placeholder="जैसे: UP Police Constable Recruitment 2026" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
            </div>

            <div class="form-row">
                <div>
                    <label>Category (श्रेणी):</label>
                    <select name="category" required <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                        <option value="">--चुनें--</option>
                        <?php foreach ($defined_categories as $k => $v): ?>
                            <option value="<?php echo $k; ?>"><?php echo $v; ?> (किया है: <?php echo $category_counts[$k]; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>State (राज्य चुनें):</label>
                    <select name="state" required <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                        <option value="All India">All India (Central Job)</option>
                        <option value="Bihar">Bihar (बिहार)</option>
                        <option value="Delhi">Delhi (दिल्ली)</option>
                        <option value="Haryana">Haryana (हरियाणा)</option>
                        <option value="Madhya Pradesh">Madhya Pradesh (मध्य प्रदेश)</option>
                        <option value="Rajasthan">Rajasthan (राजस्थान)</option>
                        <option value="Uttar Pradesh">Uttar Pradesh (उत्तर प्रदेश)</option>
                        <option value="Uttarakhand">Uttarakhand (उत्तराखंड)</option>
                        <option value="Other">Other (अन्य राज्य)</option>
                    </select>
                </div>
                
                <!-- यहाँ ऑपरेटर का स्टेटस सिलेक्शन हटाकर 'Pending' फिक्स कर दिया गया है -->
                <div>
                    <label>Status (स्थिति):</label>
                    <select name="status" disabled class="bg-gray-200 cursor-not-allowed">
                        <option value="pending" selected>Pending (समीक्षा हेतु भेजें)</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label>Last Date (अंतिम तिथि):</label>
                    <input type="date" name="last_date" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                </div>
                <div>
                    <label>Total Posts (कुल पद):</label>
                    <input type="text" name="total_posts" placeholder="जैसे: 542 पद या नोटिफिकेशन देखें" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-group">
                <label>आवेदन शुल्क (Application Fee):</label>
                <textarea name="application_fee" rows="3" placeholder="जैसे: Gen/OBC: ₹500, SC/ST: ₹0"></textarea>
            </div>

            <div class="form-group">
                <label>Short Description (छोटा विवरण):</label>
                <input type="text" name="short_desc" placeholder="एक line में भर्ती का विवरण लिखें..." <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
            </div>

            <div class="form-group">
                <label>भर्ती की पूरी जानकारी (Long Details):</label>
                <textarea name="long_details" id="editor1" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
            </div>

            <div class="form-group">
                <label>आवेदन निर्देश (Instructions):</label>
                <textarea name="description" id="editor2" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
            </div>

            <div class="form-group">
                <label>अन्तरिक्त जानकारी (Extra Details):</label>
                <textarea name="full_details" id="editor3" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
            </div>

            <div class="form-row">
                <div>
                    <label>पात्रता (Eligibility):</label>
                    <textarea name="eligibility" rows="4" placeholder="पात्रता सम्बन्धी जानकारी दर्ज करें..." <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
                </div>
                <div>
                    <label>महत्वपूर्ण तिथियां (Dates Text):</label>
                    <textarea name="important_dates" rows="4" placeholder="महत्वपूर्ण तिथियां यहाँ लिखें..." <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label>Apply Link:</label>
                    <input type="text" name="apply_link" placeholder="https://..." <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                </div>
                <div>
                    <label>Official Website:</label>
                    <input type="text" name="official_website" placeholder="https://..." <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                </div>
                <div>
                    <label>Result/Other Link:</label>
                    <input type="text" name="result_link" placeholder="https://..." <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                </div>
            </div>

            <!-- बटन का नाम बदल दिया गया है -->
            <button type="submit" class="btn-submit" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>📤 समीक्षा के लिए भेजें (Send for Admin Verification)</button>
        </form>
    </main>
</div>

<script>
    window.addEventListener('DOMContentLoaded', (event) => {
        if (typeof CKEDITOR !== 'undefined') {
            CKEDITOR.replace('editor1', { height: 350 });
            CKEDITOR.replace('editor2', { height: 200 });
            CKEDITOR.replace('editor3', { height: 200 });
        }
    });

    function openGuideModal() {
        const modal = document.getElementById('guideModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeGuideModal() {
        const modal = document.getElementById('guideModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }

    function openPosterModal() {
        const modal = document.getElementById('posterModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closePosterModal() {
        const modal = document.getElementById('posterModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }

    window.onclick = function(event) {
        const guideModal = document.getElementById('guideModal');
        const posterModal = document.getElementById('posterModal');
        if (event.target === guideModal) { closeGuideModal(); }
        if (event.target === posterModal) { closePosterModal(); }
    }
</script>

<!-- ऑपरेटर गाइड पॉप-अप (Modal) -->
<div id="guideModal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4 backdrop-blur-sm overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden border border-slate-200 animate-fade-in my-auto">
        <div class="bg-indigo-900 text-white px-6 py-4 flex justify-between items-center border-b border-indigo-800 shrink-0">
            <h3 class="text-lg font-bold flex items-center gap-2">
                <i class="fas fa-book-reader text-amber-400"></i> Vacancy Portal – फॉर्म भरने की गाइड
            </h3>
            <button onclick="closeGuideModal()" class="text-slate-300 hover:text-white text-2xl font-bold transition">&times;</button>
        </div>
        <div class="p-6 overflow-y-auto space-y-6 text-slate-700 text-sm leading-relaxed">
            <div class="bg-amber-50 border-l-4 border-amber-500 p-3.5 rounded text-amber-900 font-medium">
                📌 <strong>नोट:</strong> सभी फील्ड्स को सही और स्पष्ट हिंदी/अंग्रेजी में भरें।
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <strong class="text-indigo-700 block mb-1">1. Post Title (मुख्य शीर्षक)</strong>
                    भर्ती का पूरा नाम लिखें (उदा. MP Police Constable 2026)।
                </div>
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <strong class="text-indigo-700 block mb-1">2. Category & State</strong>
                    सही श्रेणी और संबंधित राज्य चुनें।
                </div>
            </div>
        </div>
        <div class="bg-slate-100 px-6 py-3 flex justify-end shrink-0 border-t border-slate-200">
            <button onclick="closeGuideModal()" class="bg-slate-700 hover:bg-slate-800 text-white font-semibold py-2 px-6 rounded-lg transition">
                समझ गया (Close)
            </button>
        </div>
    </div>
</div>

<!-- पोस्टर पॉप-अप (Image Modal) -->
<div id="posterModal" class="fixed inset-0 bg-black/80 z-50 hidden items-center justify-center p-2 sm:p-4 backdrop-blur-sm overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-5xl w-full max-h-[95vh] flex flex-col shadow-2xl overflow-hidden border border-slate-200 my-auto">
        <div class="bg-slate-900 text-white px-6 py-3 flex justify-between items-center border-b border-slate-700 shrink-0">
            <h3 class="text-base sm:text-lg font-bold flex items-center gap-2">
                <i class="fas fa-image text-amber-400"></i> ऑपरेटर गाइड पोस्टर
            </h3>
            <button onclick="closePosterModal()" class="text-slate-300 hover:text-white text-3xl font-bold transition leading-none">&times;</button>
        </div>
        <div class="p-2 sm:p-4 overflow-y-auto flex justify-center items-start bg-slate-100">
            <img src="gaid.png" alt="Guide Poster" class="max-w-full h-auto rounded-lg shadow-md border border-slate-300">
        </div>
        <div class="bg-white px-6 py-3 flex justify-end shrink-0 border-t border-slate-200">
            <button onclick="closePosterModal()" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2 px-6 rounded-xl transition text-sm">
                बंद करें (Close)
            </button>
        </div>
    </div>
</div>

</body>
</html>