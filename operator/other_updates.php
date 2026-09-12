<?php
session_start();

// 1. सुरक्षा जाँच (Operator Authentication)
if (!isset($_SESSION['operator_logged_in'])) {
    header("location: login.php");
    exit;
}

include '../includes/db_connect.php';

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

// 🎯 सभी कैटेगरीज की लिस्ट
$defined_categories = [
    'job'        => 'नई सरकारी नौकरी',
    'admit_card' => 'एडमिट कार्ड',
    'scheme'     => 'योजनाएं (Scheme)',
    'education'  => 'शिक्षा (Education)',
    'admission'  => 'प्रवेश (Admission)',
    'pdf_form'   => 'Pdf फॉर्म्स',
    'result'     => 'परीक्षा परिणाम',
    'other'      => 'अन्य अपडेट'
];

// 📊 ऑपरेटर की कैटेगरी-वाइज़ प्रोग्रेस रिपोर्ट तैयार करना (Real-Time Sync)
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

// 🏆 पदोन्नति (Promotion) की स्थिति
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

// 📥 फॉर्म सबमिशन हैंडलिंग (Other Updates Special - Sent to Admin for Review)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($is_suspended == 1) {
        $message = "<div class='alert error'>❌ आपका अकाउंट ब्लॉक है। आप नया अपडेट पोस्ट नहीं कर सकते।</div>";
    } else {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $category = 'other'; // 🔔 'Other Updates' कैटेगरी फिक्स है
        $state = isset($_POST['state']) ? $_POST['state'] : ''; 
        $short_desc = isset($_POST['short_desc']) ? trim($_POST['short_desc']) : '';
        $result_link = isset($_POST['result_link']) ? trim($_POST['result_link']) : ''; // Notice Link
        $long_details = isset($_POST['long_details']) ? $_POST['long_details'] : ''; // Full Explanation / Details
        $description = isset($_POST['description']) ? $_POST['description'] : ''; // Important Rules / Information
        $important_dates = isset($_POST['important_dates']) ? trim($_POST['important_dates']) : '';
        $eligibility = isset($_POST['eligibility']) ? trim($_POST['eligibility']) : ''; // Applicable To / Eligibility
        $application_fee = isset($_POST['application_fee']) ? trim($_POST['application_fee']) : '';
        $apply_link = isset($_POST['apply_link']) ? trim($_POST['apply_link']) : ''; // Direct Action / Portal Link
        $official_website = isset($_POST['official_website']) ? trim($_POST['official_website']) : '';
        $last_date = !empty($_POST['last_date']) ? $_POST['last_date'] : NULL; // Effective Last Date
        $total_posts = !empty($_POST['total_posts']) ? trim($_POST['total_posts']) : 'N/A';
        
        // 🔒 सुरक्षा: हमेशा एडमिन वेरिफिकेशन हेतु 'pending' रहेगा
        $status = 'pending'; 
        
        $full_details = isset($_POST['full_details']) ? $_POST['full_details'] : ''; // Helpline & Contact Details
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
                // 1. स्कोर बढ़ाएं (+10 Points)
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

                // 3. पदोन्नति (Rank) की जाँच
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
                    $rank_updated_msg = " और आपका पद (Designation) भी अपडेट हो गया है! 🎉";
                }

                header("Location: other_updates.php?success=1&rankmsg=" . urlencode($rank_updated_msg));
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
    $message = "<div class='alert success'>⏳ नया अपडेट सफलतापूर्वक एडमिन वेरिफिकेशन (Approval) के लिए भेज दिया गया है! एडमिन की मंजूरी के बाद यह लाइव होगा। (+10 पॉइंट्स प्राप्त हुए){$rank_msg}</div>";
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="vp.png">
    <title>अन्य अपडेट जोड़ें | ऑपरेटर डैशबोर्ड</title>
    
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
        input[type="text"]:focus, input[type="date"]:focus, select:focus, textarea:focus { border-color: #0284c7; outline: none; background-color: #fff; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1); }
        .form-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 25px; }
        .form-row > div { flex: 1; min-width: 250px; }
        .btn-submit { 
            background: #0284c7; color: white; border: none; padding: 16px 30px; cursor: pointer; border-radius: 8px; font-size: 16px; font-weight: bold; width: 100%; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.2); transition: all 0.2s;
        }
        .btn-submit:hover { background: #0369a1; }
        .btn-submit:disabled { background: #94a3b8; cursor: not-allowed; box-shadow: none; }
        .alert { padding: 18px; margin-bottom: 25px; border-radius: 8px; font-size: 14px; font-weight: 500; }
        .success { background: #d1fae5; color: #065f46; border-left: 5px solid #10b981; }
        .error { background: #fee2e2; color: #991b1b; border-left: 5px solid #ef4444; }
    </style>
</head>
<body>

<div class="flex min-h-screen">
    
    <!-- Sidebar -->
    <div class="hidden md:flex flex-col w-64 bg-[#0f1423] text-slate-400 min-h-screen border-r border-slate-800/40 shrink-0 justify-between p-4">
        <div>
            <div class="flex items-center gap-3 px-2 py-4 mb-4 border-b border-slate-800">
                <div class="h-10 w-10 rounded-xl bg-purple-600 flex items-center justify-center text-white shadow-lg">
                    <i class="fas fa-bell text-lg"></i>
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
                <a href="dashboard.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-[13px] font-medium hover:bg-slate-900 hover:text-slate-200 transition-all">
                    <i class="fas fa-chart-pie text-sm w-4 text-center"></i> <span>डैशबोर्ड मुख्य</span>
                </a>
                <a href="add_vacancy.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-[13px] font-medium hover:bg-slate-900 hover:text-slate-200 transition-all">
                    <i class="fas fa-plus-circle text-sm w-4 text-center"></i> <span>नई वैकेंसी जोड़ें</span>
                </a>
                <a href="add_admit_card.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-[13px] font-medium hover:bg-slate-900 hover:text-slate-200 transition-all">
                    <i class="fas fa-id-card text-sm w-4 text-center"></i> <span>एडमिट कार्ड जोड़ें</span>
                </a>
                <a href="pdf.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-[13px] font-medium hover:bg-slate-900 hover:text-slate-200 transition-all">
                    <i class="fas fa-file-pdf text-sm w-4 text-center"></i> <span>Pdf फॉर्म्स जोड़ें</span>
                </a>
                <a href="other_updates.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-[13px] font-semibold bg-purple-600/15 text-purple-400 border-l-4 border-purple-500 transition-all">
                    <i class="fas fa-bell text-sm w-4 text-center"></i> <span>अन्य अपडेट जोड़ें</span>
                </a>
                <a href="manage_vacancies.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-[13px] font-medium hover:bg-slate-900 hover:text-slate-200 transition-all">
                    <i class="fas fa-list-check text-sm w-4 text-center"></i> <span>वैकेंसी/अपडेट्स प्रबंधित करें</span>
                </a>
            </nav>
        </div>
        <div class="pt-4 border-t border-slate-800">
            <a href="logout.php" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl border border-rose-500/30 text-xs font-bold text-rose-400 hover:bg-rose-500 hover:text-white transition-all">
                <i class="fas fa-power-off"></i> <span>लॉगआउट करें</span>
            </a>
        </div>
    </div>

    <!-- Main Content Area -->
    <main class="flex-grow p-8 box-sizing-border overflow-x-hidden">
        
        <!-- 🏆 पदोन्नति मिशन (Rank Promotion Target) कार्ड -->
        <div class="bg-[#0c1222] border border-slate-800 rounded-2xl p-6 mb-6 text-slate-200 shadow-2xl">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 pb-3 border-b border-slate-800/80">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 text-lg">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-bold text-lg text-amber-400">पदोन्नति मिशन (Rank Promotion Target)</h3>
                            <span class="bg-indigo-950 text-indigo-300 border border-indigo-700/50 px-2.5 py-0.5 rounded-md text-xs font-medium">
                                वर्तमान पद: <span class="text-white font-bold"><?php echo htmlspecialchars($op_rank); ?></span>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="text-left md:text-right">
                    <span class="block text-[11px] text-slate-400 uppercase font-bold tracking-wider">कुल परफॉर्मेंस स्कोर</span>
                    <span class="text-2xl font-black text-emerald-400"><?php echo $op_score; ?> Pts</span>
                </div>
            </div>

            <!-- Subtitle info -->
            <p class="text-xs text-slate-300 mt-3 flex items-center gap-1.5 flex-wrap">
                <span>🚀 <strong>अगले उच्च पद (Designation Update)</strong> पर पहुँचने के लिए आपको सभी 8 कैटेगरीज में कम से कम 25-25 पोस्ट पूरे करने होंगे।</span>
                <span class="text-indigo-300 font-semibold">(अभी कुल <?php echo $total_remaining_posts; ?> post बाकी हैं)</span>
            </p>

            <!-- 8 Categories Grid with Progress Bars -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
                <?php foreach ($defined_categories as $cat_key => $cat_name): 
                    $count = isset($category_counts[$cat_key]) ? $category_counts[$cat_key] : 0;
                    $pct = min(100, round(($count / 25) * 100));
                    $is_complete = $count >= 25;
                ?>
                    <div class="bg-[#131b2e] border border-slate-800/80 p-3 rounded-xl flex flex-col justify-between">
                        <div class="flex justify-between items-center text-xs mb-2">
                            <span class="text-slate-300 font-medium truncate" title="<?php echo htmlspecialchars($cat_name); ?>"><?php echo htmlspecialchars($cat_name); ?></span>
                            <span class="font-bold <?php echo $is_complete ? 'text-emerald-400' : 'text-slate-400'; ?> text-[11px]">
                                <?php echo $count; ?>/25
                            </span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                            <div class="<?php echo $is_complete ? 'bg-emerald-500' : 'bg-indigo-500'; ?> h-1.5 rounded-full transition-all duration-300" style="width: <?php echo $pct; ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Header Top / Action Buttons -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-bell text-purple-600"></i> अन्य अपडेट जोड़ें (ऑपरेटर डैशबोर्ड)
            </h2>
            
            <div class="flex gap-2 flex-wrap">
                <button type="button" onclick="openGuideModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded-xl shadow transition flex items-center gap-2 text-sm">
                    <i class="fas fa-question-circle"></i> गाइड फॉर्म
                </button>
                <button type="button" onclick="openPosterModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl shadow transition flex items-center gap-2 text-sm">
                    <i class="fas fa-file-lines"></i> नियम एवं निर्देश
                </button>
            </div>
        </div>

        <?php 
        echo $message; 
        if ($is_suspended == 1 && empty($message)) {
            echo "<div class='alert error'>❌ आपका अकाउंट सस्पेंडेड है। सबमिशन लॉक कर दिया गया है।</div>";
        }
        ?>

        <div class="admin-container">
            <form method="POST">
                
                <!-- 1. मुख्य जानकारी -->
                <div class="form-group">
                    <label>Update Title (अन्य अपडेट / सूचना का नाम): *</label>
                    <input type="text" name="title" required placeholder="जैसे: MP Voter ID Correction Status 2026 या e-KYC Portal Mandatory Guidelines" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                </div>

                <div class="form-row">
                    <div>
                        <label>Category (कैटेगरी):</label>
                        <select name="category" disabled class="bg-slate-200 font-bold text-purple-800">
                            <option value="other" selected>🔔 अन्य अपडेट (Other Updates)</option>
                        </select>
                    </div>
                    <div>
                        <label>State / Department (राज्य या विभाग चुनें): *</label>
                        <select name="state" required <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                            <option value="All India">All India (केन्द्रीय स्तर)</option>
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
                        <label>Status (स्थिति):</label>
                        <input type="text" value="⏳ पेंडिंग (एडमिन वेरिफिकेशन हेतु)" disabled class="bg-amber-50 text-amber-700 font-bold border-amber-300">
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label>Last Date / Deadline (अंतिम तिथि - यदि लागू हो):</label>
                        <input type="date" name="last_date" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label>Department / Portal Name (विभाग या पोर्टल):</label>
                        <input type="text" name="total_posts" placeholder="जैसे: UIDAI / NVSP / CSC Services" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label>Service Fee / Charges (पोर्टल या सेवा शुल्क):</label>
                        <input type="text" name="application_fee" placeholder="जैसे: नि:शुल्क / ₹50 पोर्टल चार्ज" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <div class="form-group">
                    <label>Short Summary (संक्षिप्त सारांश):</label>
                    <input type="text" name="short_desc" placeholder="जैसे: वोटर आईडी कार्ड में ऑनलाइन सुधार और ई-केवाईसी की प्रक्रिया शुरू, यहाँ से करें..." <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                </div>

                <!-- 2. विस्तृत विवरण (CKEditors) -->
                <div class="form-group">
                    <label>अपडेट/सेवा का विस्तृत विवरण और प्रक्रिया (Step by Step Guide):</label>
                    <textarea name="long_details" id="editor1" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
                </div>

                <div class="form-group">
                    <label>महत्वपूर्ण नियम एवं शर्तें (Important Instructions & Guidelines):</label>
                    <textarea name="description" id="editor2" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
                </div>

                <div class="form-group">
                    <label>हेल्पलाइन एवं संपर्क विवरण (Helpline / Support Details):</label>
                    <textarea name="full_details" id="editor3" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
                </div>

                <!-- 3. पात्रता और महत्वपूर्ण तिथियां -->
                <div class="form-row">
                    <div>
                        <label>पात्रता एवं आवश्यकताएँ (Applicable Criteria):</label>
                        <textarea name="eligibility" rows="4" placeholder="जैसे: 
1. आधार कार्ड से मोबाइल नंबर लिंक होना अनिवार्य है।
2. केवल भारतीय नागरिक ही पात्र हैं।" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
                    </div>
                    <div>
                        <label>महत्वपूर्ण तिथियां (Important Timeline):</label>
                        <textarea name="important_dates" rows="4" placeholder="जैसे: 
• प्रक्रिया प्रारंभ तिथि: 01/01/2026
• अंतिम तिथि: 31/12/2026" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
                    </div>
                </div>

                <!-- 4. डायरेक्ट लिंक्स -->
                <div class="form-row">
                    <div>
                        <label>Direct Action / Portal Link: *</label>
                        <input type="text" name="apply_link" required placeholder="https://..." <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label>Official Press Notification PDF Link:</label>
                        <input type="text" name="result_link" placeholder="https://..." <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label>Official Portal / Department Link:</label>
                        <input type="text" name="official_website" placeholder="https://..." <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <button type="submit" class="btn-submit" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                    📤 अपडेट एडमिन समीक्षा (Verification) के लिए भेजें
                </button>
            </form>
        </div>
    </main>
</div>

<!-- Modal 1: Form Fill Guide -->
<div id="guideModal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200">
        <h3 class="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2">
            <i class="fas fa-info-circle text-purple-600"></i> अन्य अपडेट पोस्ट करने के निर्देश
        </h3>
        <ul class="list-disc list-inside space-y-2 text-sm text-slate-600">
            <li><strong>Title:</strong> अपडेट या सूचना का नाम स्पष्ट दर्ज करें।</li>
            <li><strong>Direct Link:</strong> 'Direct Action Link' में सीधे संबंधित आधिकारिक पोर्टल या एक्शन पेज का URL दें।</li>
            <li><strong>Guidelines:</strong> उपयोगकर्ताओं को समझने के लिए आसान भाषा में स्टेप्स लिखें।</li>
        </ul>
        <div class="mt-6 text-right">
            <button onclick="closeGuideModal()" class="bg-slate-800 text-white font-semibold py-2 px-5 rounded-xl text-sm">समझ गया</button>
        </div>
    </div>
</div>

<!-- Modal 2: Full Guide Poster -->
<div id="posterModal" class="fixed inset-0 bg-black/70 z-50 hidden items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-2xl max-w-3xl w-full p-6 shadow-2xl border border-slate-200 text-slate-800">
        <div class="flex justify-between items-center mb-4 pb-2 border-b">
            <h3 class="text-lg font-bold flex items-center gap-2 text-emerald-700">
                <i class="fas fa-shield-alt"></i> एडमिन समीक्षा एवं परफॉर्मेंस क्रेडिट
            </h3>
            <button onclick="closePosterModal()" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>
        <div class="space-y-3 text-sm text-slate-600 max-h-[70vh] overflow-y-auto pr-2">
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <strong class="text-indigo-600 block mb-1">1. पेंडिंग वेरिफिकेशन:</strong>
                <p>आपके द्वारा सबमिट किया गया यह अपडेट सीधा एडमिन वेरिफिकेशन में जाएगा। एडमिन की मंजूरी के बाद यह वेबसाइट पर प्रदर्शित होगा।</p>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <strong class="text-indigo-600 block mb-1">2. तुरंत अंक (+10 Points):</strong>
                <p>अपडेट फॉर्म सबमिट करते ही ऑपरेटर खाते में तुरंत +10 पॉइंट्स क्रेडिट हो जाएंगे!</p>
            </div>
        </div>
        <div class="mt-6 text-right">
            <button onclick="closePosterModal()" class="bg-emerald-600 text-white font-semibold py-2 px-5 rounded-xl text-sm">बंद करें</button>
        </div>
    </div>
</div>

<script>
    window.addEventListener('DOMContentLoaded', () => {
        if (typeof CKEDITOR !== 'undefined') {
            CKEDITOR.replace('editor1', { height: 250 });
            CKEDITOR.replace('editor2', { height: 200 });
            CKEDITOR.replace('editor3', { height: 200 });
        }
    });

    function openGuideModal() {
        document.getElementById('guideModal').classList.remove('hidden');
        document.getElementById('guideModal').classList.add('flex');
    }

    function closeGuideModal() {
        document.getElementById('guideModal').classList.remove('flex');
        document.getElementById('guideModal').classList.add('hidden');
    }

    function openPosterModal() {
        document.getElementById('posterModal').classList.remove('hidden');
        document.getElementById('posterModal').classList.add('flex');
    }

    function closePosterModal() {
        document.getElementById('posterModal').classList.remove('flex');
        document.getElementById('posterModal').classList.add('hidden');
    }
</script>
</body>
</html>