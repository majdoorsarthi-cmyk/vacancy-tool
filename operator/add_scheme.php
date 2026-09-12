<?php
session_start();

// 1. सुरक्षा जाँच (Operator Authentication)
if (!isset($_SESSION['operator_logged_in'])) {
    header("location: login.php");
    exit;
}

include '../includes/db_connect.php';

// डेटाबेस UTF-8 सपोर्ट सेट करें (हिंदी कैरेक्टर्स सही सेव करने के लिए)
if (isset($conn)) {
    $conn->set_charset("utf8mb4");
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

// डेटाबेस से ऑपरेटर की स्थिति निकालें
if ($operator_id) {
    $check_op = $conn->prepare("SELECT is_suspended, designation, performance_score FROM operators WHERE id = ?");
    $check_op->bind_param("i", $operator_id);
    $check_op->execute();
    $check_op->bind_result($is_suspended, $op_rank, $op_score);
    $check_op->fetch();
    $check_op->close();
}

// 🎯 सभी 8 कैटेगरीज की लिस्ट और उनके नाम
$defined_categories = [
    'job'        => 'नई सरकारी नौकरी',
    'result'     => 'परीक्षा परिणाम',
    'admit_card' => 'एडमिट कार्ड',
    'scheme'     => 'योजनाएं (Scheme)',
    'education'  => 'शिक्षा (Education)',
    'admission'  => 'प्रवेश (Admission)',
    'pdf_form'   => 'Pdf फॉर्म्स',
    'other'      => 'अन्य अपडेट'
];

// 📊 ऑपरेटर की कैटेगरी-वाइज़ प्रोग्रेस रिपोर्ट तैयार करना
$category_counts = [];
foreach ($defined_categories as $key => $val) {
    $category_counts[$key] = 0;
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

// 🏆 पदोन्नति (Promotion) के लिए पात्रता की जाँच
$eligible_for_promotion = true;
$total_remaining_posts = 0;

foreach ($defined_categories as $cat_key => $cat_name) {
    $current_count = $category_counts[$cat_key];
    if ($current_count < 25) {
        $eligible_for_promotion = false;
        $remaining = 25 - $current_count;
        $total_remaining_posts += $remaining;
    }
}

$message = "";

// 2. फॉर्म सबमिशन (Scheme Insertion)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($is_suspended == 1) {
        $message = "<div class='alert error'>❌ आपका अकाउंट ब्लॉक है। आप कोई नई योजना जोड़ नहीं सकते।</div>";
    } else {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $category = 'scheme'; // डिफ़ॉल्ट रूप से योजना सेट किया गया है
        $state = isset($_POST['state']) ? $_POST['state'] : 'All India'; 
        $short_desc = isset($_POST['short_desc']) ? trim($_POST['short_desc']) : '';
        $result_link = isset($_POST['result_link']) ? trim($_POST['result_link']) : ''; 
        $long_details = isset($_POST['long_details']) ? $_POST['long_details'] : ''; 
        $description = isset($_POST['description']) ? $_POST['description'] : '';  
        $important_dates = isset($_POST['important_dates']) ? trim($_POST['important_dates']) : '';
        $eligibility = isset($_POST['eligibility']) ? trim($_POST['eligibility']) : '';
        $application_fee = isset($_POST['application_fee']) ? trim($_POST['application_fee']) : 'निःशुल्क (Free)';
        $apply_link = isset($_POST['apply_link']) ? trim($_POST['apply_link']) : '';
        $official_website = isset($_POST['official_website']) ? trim($_POST['official_website']) : '';
        $last_date = !empty($_POST['last_date']) ? $_POST['last_date'] : NULL;
        $total_posts = !empty($_POST['total_posts']) ? trim($_POST['total_posts']) : 'सभी पात्र नागरिक';
        
        // 🔒 बदलाव 1: पोस्ट सीधे LIVE नहीं होगी, अब 'pending' (वेरिफिकेशन हेतु) रहेगी
        $status = 'pending'; 
        
        $full_details = isset($_POST['full_details']) ? $_POST['full_details'] : ''; 
        $post_date = date('Y-m-d H:i:s');

        // 🔍 बदलाव 2: पहले चेक करें कि इस Title से पोस्ट पहले से तो नहीं बनी है (Duplicate Check)
        $check_title = $conn->prepare("SELECT id FROM vacancies WHERE title = ?");
        $check_title->bind_param("s", $title);
        $check_title->execute();
        $check_title->store_result();

        if ($check_title->num_rows > 0) {
            $message = "<div class='alert error'>❌ इस नाम/शीर्षक से योजना पहले से मौजूद है! कृपया शीर्षक में कुछ बदलाव करें।</div>";
            $check_title->close();
        } else {
            $check_title->close();

            $sql = "INSERT INTO vacancies (operator_id, title, category, state, short_desc, result_link, long_details, description, important_dates, eligibility, application_fee, apply_link, official_website, post_date, last_date, status, full_details, total_posts)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("isssssssssssssssss", 
                    $operator_id, $title, $category, $state, $short_desc, $result_link, $long_details, 
                    $description, $important_dates, $eligibility, $application_fee, $apply_link, 
                    $official_website, $post_date, $last_date, $status, $full_details, $total_posts
                );

                if ($stmt->execute()) {
                    header("Location: add_scheme.php?success=1");
                    exit;
                } else {
                    $message = "<div class='alert error'>❌ त्रुटि: " . $stmt->error . "</div>";
                }
                $stmt->close();
            }
        }
    }
}

if (isset($_GET['success'])) {
    $message = "<div class='alert success'>⏳ योजना सफलतापूर्वक सबमिट हो गई है! यह एडमिन वेरिफिकेशन (Admin Approval) के बाद लाइव होगी।</div>";
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="vp.png">
    <link rel="apple-touch-icon" href="vp.png">
    <title>नई योजना जोड़ें | ऑपरेटर डैशबोर्ड</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>

    <style>
        body { margin: 0; background-color: #f1f5f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .form-group { margin-bottom: 20px; }
        label { font-weight: 600; display: block; margin-bottom: 8px; color: #334155; font-size: 14px; }
        input[type="text"], input[type="date"], select, textarea {
            width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background-color: #f8fafc; transition: all 0.2s;
        }
        input:focus, select:focus, textarea:focus { border-color: #10b981; outline: none; background-color: #fff; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
        .form-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; }
        .form-row > div { flex: 1; min-width: 240px; }
        .btn-submit { 
            background: #10b981; color: white; border: none; padding: 16px; cursor: pointer; border-radius: 10px; font-size: 16px; font-weight: bold; width: 100%; transition: all 0.2s; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        .btn-submit:hover { background: #059669; }
        .btn-submit:disabled { background: #94a3b8; cursor: not-allowed; box-shadow: none; }
        .alert { padding: 16px; margin-bottom: 20px; border-radius: 8px; font-size: 14px; font-weight: 500; }
        .success { background: #fef3c7; color: #92400e; border-left: 5px solid #f59e0b; }
        .error { background: #fee2e2; color: #991b1b; border-left: 5px solid #ef4444; }
    </style>
</head>
<body>

<div class="flex min-h-screen">
    
    <!-- Sidebar -->
    <div class="hidden md:flex flex-col w-64 bg-[#0f1423] text-slate-400 min-h-screen border-r border-slate-800/40 shrink-0 justify-between p-4">
        <div>
            <div class="flex items-center gap-3 px-2 py-4 mb-4 border-b border-slate-800">
                <div class="h-10 w-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-lg">
                    <i class="fas fa-hand-holding-heart text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-white">योजना पोर्टल</h2>
                    <span class="text-[10px] <?php echo ($is_suspended) ? 'text-rose-400' : 'text-emerald-400'; ?> flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full <?php echo ($is_suspended) ? 'bg-rose-500' : 'bg-emerald-500 animate-pulse'; ?>"></span> 
                        <?php echo ($is_suspended) ? 'अकाउंट ब्लॉक है' : 'ऑपरेटर मोड सक्रिय'; ?>
                    </span>
                </div>
            </div>

            <!-- ऑपरेटर स्टेटस कार्ड -->
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
                <a href="dashboard.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-[13px] font-medium transition-all duration-300 <?php echo ($current_uri == 'dashboard.php') ? 'bg-emerald-600/15 text-emerald-400 border-l-4 border-emerald-500 font-semibold' : 'hover:bg-slate-900 hover:text-slate-200'; ?>">
                    <i class="fas fa-chart-pie text-sm w-4 text-center"></i> <span>डैशबोर्ड मुख्य</span>
                </a>
                <a href="add_scheme.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-[13px] font-medium transition-all duration-300 <?php echo ($current_uri == 'add_scheme.php') ? 'bg-emerald-600/15 text-emerald-400 border-l-4 border-emerald-500 font-semibold' : 'hover:bg-slate-900 hover:text-slate-200'; ?>">
                    <i class="fas fa-hand-holding-heart text-sm w-4 text-center"></i> <span>नई योजना जोड़ें</span>
                </a>
                <a href="manage_vacancies.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-[13px] font-medium transition-all duration-300 <?php echo ($current_uri == 'manage_vacancies.php') ? 'bg-emerald-600/15 text-emerald-400 border-l-4 border-emerald-500 font-semibold' : 'hover:bg-slate-900 hover:text-slate-200'; ?>">
                    <i class="fas fa-list-check text-sm w-4 text-center"></i> <span>सभी योजनाएं / पोस्ट</span>
                </a>
            </nav>
        </div>
        <div class="pt-4 border-t border-slate-800">
            <a href="logout.php" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl border border-rose-500/30 text-xs font-bold text-rose-400 hover:bg-rose-500 hover:text-white transition-all duration-300">
                <i class="fas fa-power-off"></i> <span>लॉगआउट</span>
            </a>
        </div>
    </div>

    <!-- Main Content Area -->
    <main class="flex-grow p-6 md:p-8 overflow-x-hidden">
        
        <!-- 🏆 ऊपर की रैंक और पदोन्नति पट्टी (Rank Promotion Target) -->
        <div class="mb-6 p-5 bg-gradient-to-r from-slate-900 to-emerald-950 rounded-2xl text-white shadow-xl border border-emerald-500/20">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h3 class="text-lg font-bold flex items-center gap-2 text-amber-400">
                        <i class="fas fa-trophy animate-bounce"></i> पदोन्नति मिशन (Rank Promotion Target)
                    </h3>
                    <p class="text-xs text-slate-300 mt-1">
                        वर्तमान पद: <span class="bg-emerald-500/30 px-2 py-0.5 rounded text-white border border-emerald-500/50 font-bold"><?php echo htmlspecialchars($op_rank); ?></span>
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
                    <strong>बधाई हो!</strong> आपने सभी 8 कैटेगरीज में 25-25 पोस्ट का कोटा पूरा कर लिया है।
                </div>
            <?php else: ?>
                <div class="text-sm text-slate-200">
                    <p class="font-semibold mb-2 text-emerald-300">
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

        <!-- 📌 शीर्षक एवं निर्देश/गाइड बटन -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 pb-4 border-b border-slate-200">
            <div>
                <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-hand-holding-heart text-emerald-600"></i> नई सरकारी योजना (Scheme) जोड़ें
                </h2>
                <span class="bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full mt-1 inline-block">श्रेणी: योजनाएं (Scheme)</span>
            </div>
            
            <div class="flex gap-2">
                <button type="button" onclick="openGuideModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-xl shadow transition flex items-center gap-2 text-sm">
                    <i class="fas fa-question-circle"></i> फॉर्म कैसे भरें?
                </button>

                <button type="button" onclick="openPosterModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-xl shadow transition flex items-center gap-2 text-sm">
                    <i class="fas fa-file-image"></i> गाइड पोस्टर देखें (Full Guide)
                </button>
            </div>
        </div>

        <?php echo $message; ?>

        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-slate-200">
            <form method="POST">
                
                <div class="form-group">
                    <label>योजना का नाम (Scheme Title):</label>
                    <input type="text" name="title" required placeholder="जैसे: PM Kisan Samman Nidhi Yojana 2026 या MP Ladli Behna Yojana" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                </div>

                <div class="form-row">
                    <div>
                        <label>राज्य (State):</label>
                        <select name="state" required <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                            <option value="All India">All India (केन्द्र सरकार की योजना)</option>
                            <option value="Madhya Pradesh">Madhya Pradesh (मध्य प्रदेश)</option>
                            <option value="Uttar Pradesh">Uttar Pradesh (उत्तर प्रदेश)</option>
                            <option value="Bihar">Bihar (बिहार)</option>
                            <option value="Rajasthan">Rajasthan (राजस्थान)</option>
                            <option value="Delhi">Delhi (दिल्ली)</option>
                            <option value="Haryana">Haryana (हरियाणा)</option>
                            <option value="Other">Other (अन्य राज्य)</option>
                        </select>
                    </div>
                    <div>
                        <label>अंतिम तिथि (Last Date if applicable):</label>
                        <input type="date" name="last_date" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label>पात्र लाभार्थी (Target Beneficiary):</label>
                        <input type="text" name="total_posts" placeholder="जैसे: सभी महिलाएँ / किसान / विद्यार्थी" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label>आवेदन शुल्क (Application Fee):</label>
                        <input type="text" name="application_fee" value="निःशुल्क (Free)" placeholder="जैसे: निःशुल्क या ₹50 पोर्टल चार्ज" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label>संक्षिप्त विवरण (Short Description):</label>
                        <input type="text" name="short_desc" placeholder="1 लाइन में योजना का मुख्य उद्देश्य लिखें..." <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <div class="form-group">
                    <label>योजना के लाभ और मुख्य विवरण (Benefits & Details):</label>
                    <textarea name="long_details" id="editor1" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
                </div>

                <div class="form-group">
                    <label>आवेदन प्रक्रिया व आवश्यक दस्तावेज़ (Application Process & Documents Required):</label>
                    <textarea name="description" id="editor2" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
                </div>

                <div class="form-row">
                    <div>
                        <label>पात्रता व शर्तें (Eligibility):</label>
                        <textarea name="eligibility" rows="3" placeholder="आय सीमा, उम्र सीमा या अन्य आवश्यक शर्तें..." <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
                    </div>
                    <div>
                        <label>महत्वपूर्ण तिथियां (Important Dates Text):</label>
                        <textarea name="important_dates" rows="3" placeholder="आवेदन प्रारंभ तिथि, किस्त जारी होने की तिथि आदि..." <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label>ऑनलाइन / ऑफलाइन आवेदन लिंक (Apply Link):</label>
                        <input type="text" name="apply_link" placeholder="https://..." <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label>आधिकारिक वेबसाइट (Official Website):</label>
                        <input type="text" name="official_website" placeholder="https://..." <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label>योजना नियम / PDF लिंक (Official Notification PDF):</label>
                        <input type="text" name="result_link" placeholder="https://..." <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <!-- 🔒 बदलाव 3: बटन का टेक्स्ट अपडेट किया गया -->
                <button type="submit" class="btn-submit" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                    📤 योजना सबमिट करें (एडमिन वेरिफिकेशन हेतु)
                </button>
            </form>
        </div>
    </main>
</div>

<!-- 🚨 1. ऑपरेटर गाइड पॉप-अप (Modal) -->
<div id="guideModal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4 backdrop-blur-sm overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden border border-slate-200 my-auto">
        <div class="bg-indigo-900 text-white px-6 py-4 flex justify-between items-center border-b border-indigo-800 shrink-0">
            <h3 class="text-lg font-bold flex items-center gap-2">
                <i class="fas fa-book-reader text-amber-400"></i> Vacancy Portal – फॉर्म भरने की गाइड
            </h3>
            <button onclick="closeGuideModal()" class="text-slate-300 hover:text-white text-2xl font-bold transition">&times;</button>
        </div>

        <div class="p-6 overflow-y-auto space-y-6 text-slate-700 text-sm leading-relaxed">
            <div class="bg-amber-50 border-l-4 border-amber-500 p-3.5 rounded text-amber-900 font-medium">
                📌 <strong>नोट:</strong> सभी फील्ड्स को सही और स्पष्ट हिंदी/अंग्रेजी में भरें ताकि आवेदकों को सही जानकारी मिल सके।
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <strong class="text-indigo-700 block mb-1">1. Scheme Title (योजना का नाम)</strong>
                    योजना का पूरा और स्पष्ट नाम लिखें। <br>
                    <em>उदाहरण: MP Ladli Behna Yojana 2026</em>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <strong class="text-indigo-700 block mb-1">2. State (राज्य चुनें)</strong>
                    योजना किस राज्य की है, ड्रॉपडाउन से चुनें। केंद्र सरकार की योजना के लिए All India चुनें।
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <strong class="text-indigo-700 block mb-1">3. Last Date & Beneficiaries</strong>
                    • <strong>Last Date:</strong> अंतिम तिथि दर्ज करें।<br>
                    • <strong>Beneficiaries:</strong> जैसे: सभी महिलाएँ / किसान।
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <strong class="text-indigo-700 block mb-1">4. Application Fee & Short Desc</strong>
                    • <strong>Fee:</strong> निःशुल्क या निर्धारित शुल्क लिखें।<br>
                    • <strong>Short Desc:</strong> 1 लाइन में योजना का उद्देश्य लिखें।
                </div>
            </div>

            <div class="space-y-4">
                <div class="p-4 bg-indigo-50/50 border border-indigo-100 rounded-xl">
                    <strong class="text-indigo-900 text-base block mb-1">5. योजना के लाभ और विवरण (Benefits & Details)</strong>
                    योजना के अंतर्गत मिलने वाली वित्तीय या अन्य सहायता का विस्तार से उल्लेख करें।
                </div>

                <div class="p-4 bg-indigo-50/50 border border-indigo-100 rounded-xl">
                    <strong class="text-indigo-900 text-base block mb-1">6. आवेदन प्रक्रिया व दस्तावेज़ (Process & Documents)</strong>
                    आवेदन करने की चरण-दर-चरण प्रक्रिया और लगने वाले जरूरी दस्तावेज़ (आधार, राशन कार्ड, समग्र आईडी आदि) लिखें।
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

<!-- 🚨 2. पोस्टर/इमेज गाइड पॉप-अप (Image Modal) -->
<div id="posterModal" class="fixed inset-0 bg-black/80 z-50 hidden items-center justify-center p-2 sm:p-4 backdrop-blur-sm overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-5xl w-full max-h-[95vh] flex flex-col shadow-2xl overflow-hidden border border-slate-200 my-auto">
        <div class="bg-slate-900 text-white px-6 py-3 flex justify-between items-center border-b border-slate-700 shrink-0">
            <h3 class="text-base sm:text-lg font-bold flex items-center gap-2">
                <i class="fas fa-image text-amber-400"></i> ऑपरेटर गाइड पोस्टर (Sample Form & Rules)
            </h3>
            <button onclick="closePosterModal()" class="text-slate-300 hover:text-white text-3xl font-bold transition leading-none">&times;</button>
        </div>

        <div class="p-2 sm:p-4 overflow-y-auto flex justify-center items-start bg-slate-100">
            <img src="gaid.png" alt="Vacancy Portal Guide Poster" class="max-w-full h-auto rounded-lg shadow-md border border-slate-300">
        </div>

        <div class="bg-white px-6 py-3 flex justify-between items-center shrink-0 border-t border-slate-200">
            <span class="text-xs text-slate-500 hidden sm:inline">💡 आप इमेज पर ज़ूम करके पूरा पोस्टर स्पष्ट देख सकते हैं।</span>
            <button onclick="closePosterModal()" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2 px-6 rounded-xl transition text-sm">
                बंद करें (Close)
            </button>
        </div>
    </div>
</div>

<script>
    window.addEventListener('DOMContentLoaded', () => {
        if (typeof CKEDITOR !== 'undefined') {
            CKEDITOR.replace('editor1', { height: 280 });
            CKEDITOR.replace('editor2', { height: 200 });
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
        if (event.target === guideModal) {
            closeGuideModal();
        }
        if (event.target === posterModal) {
            closePosterModal();
        }
    }
</script>

</body>
</html>