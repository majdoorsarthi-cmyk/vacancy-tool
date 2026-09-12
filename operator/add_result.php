<?php
session_start();

// 1. सुरक्षा जाँच (Operator Authentication)
if (!isset($_SESSION['operator_logged_in'])) {
    header("location: login.php");
    exit;
}

include '../includes/db_connect.php';

// ऑपरेटर डेटा
$operator_name = isset($_SESSION['operator_name']) ? $_SESSION['operator_name'] : 'Operator';
$operator_id = isset($_SESSION['operator_id']) ? intval($_SESSION['operator_id']) : NULL;

$is_suspended = 0;
$op_rank = 'Junior Operator';
$op_score = 0;

if ($operator_id) {
    $check_op = $conn->prepare("SELECT is_suspended, designation, performance_score FROM operators WHERE id = ?");
    $check_op->bind_param("i", $operator_id);
    $check_op->execute();
    $check_op->bind_result($is_suspended, $op_rank, $op_score);
    $check_op->fetch();
    $check_op->close();
}

// ==========================================
// 🎯 RANK PROMOTION TARGET LOGIC
// ==========================================
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
// ==========================================

$message = "";

// 2. फॉर्म सबमिशन हैंडलिंग (Admin Verification Syncing)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($is_suspended == 1) {
        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm'>❌ आपका अकाउंट ब्लॉक है। आप परिणाम पोस्ट नहीं कर सकते।</div>";
    } else {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $category = 'result'; // 🎯 कैटेगरी फिक्स
        $state = isset($_POST['state']) ? $_POST['state'] : 'All India'; 
        
        // 🔒 पोस्ट का स्टेटस हमेशा 'pending' ही रहेगा (डायरेक्ट पब्लिश नहीं होगा)
        $status = 'pending'; 
        
        $short_desc = isset($_POST['short_desc']) ? trim($_POST['short_desc']) : '';
        $total_posts = isset($_POST['total_posts']) ? trim($_POST['total_posts']) : '';
        $result_link = isset($_POST['result_link']) ? trim($_POST['result_link']) : '';
        $official_website = isset($_POST['official_website']) ? trim($_POST['official_website']) : '';
        
        $important_dates = isset($_POST['important_dates']) ? trim($_POST['important_dates']) : '';
        $long_details = isset($_POST['long_details']) ? $_POST['long_details'] : ''; // CKEditor 1
        $description = isset($_POST['description']) ? $_POST['description'] : '';    // CKEditor 2

        // SQL Query
        $sql = "INSERT INTO vacancies (
            operator_id, title, category, state, status, 
            short_desc, total_posts, result_link, official_website, 
            important_dates, long_details, description
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("isssssssssss", 
                $operator_id, $title, $category, $state, $status, 
                $short_desc, $total_posts, $result_link, $official_website, 
                $important_dates, $long_details, $description
            );

            if ($stmt->execute()) {
                // स्कोर अपडेट (+10)
                $update_score = $conn->prepare("UPDATE operators SET performance_score = performance_score + 10 WHERE id = ?");
                $update_score->bind_param("i", $operator_id);
                $update_score->execute();
                $update_score->close();

                header("Location: add_result.php?success=1");
                exit;
            } else {
                $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm'>❌ त्रुटि: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }
}

if (isset($_GET['success'])) {
    $message = "
<div class='bg-amber-100 border-l-4 border-amber-500 text-amber-800 p-4 mb-6 rounded shadow-sm font-medium'>
📌 <strong>परिणाम पेंडिंग (Under Verification) में भेज दिया गया है!</strong><br>
यह पोस्ट सीधे वेबसाइट पर लाइव नहीं होगी। एडमिन द्वारा चेक और Approved होने के बाद ही प्रदर्शित होगी।
</div>";
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>परीक्षा परिणाम जोड़ें | ऑपरेटर डैशबोर्ड</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
</head>
<body class="bg-slate-50 font-sans">

<div class="flex min-h-screen">
    
    <!-- 🟢 साइडबार (Sidebar) -->
    <aside class="w-64 bg-slate-900 text-slate-300 flex-shrink-0 hidden md:flex flex-col border-r border-slate-800">
        <div class="h-16 flex items-center justify-center border-b border-slate-800 bg-slate-950">
            <h1 class="text-xl font-bold text-white tracking-wider">
                <i class="fas fa-briefcase text-emerald-500 mr-2"></i> पोर्टल पैनल
            </h1>
        </div>
        
        <div class="p-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center border-2 border-emerald-500">
                    <i class="fas fa-user text-emerald-500"></i>
                </div>
                <div>
                    <h3 class="text-white font-medium text-sm"><?php echo htmlspecialchars($operator_name); ?></h3>
                    <p class="text-xs text-emerald-500"><?php echo htmlspecialchars($op_rank); ?></p>
                </div>
            </div>
        </div>

        <nav class="flex-1 px-4 space-y-2 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center gap-3 text-slate-400 hover:text-white hover:bg-slate-800 px-4 py-3 rounded-xl font-medium text-sm transition">
                <i class="fas fa-home w-5 text-center"></i> डैशबोर्ड
            </a>

            <a href="add_vacancy.php" class="flex items-center gap-3 text-slate-400 hover:text-white hover:bg-slate-800 px-4 py-3 rounded-xl font-medium text-sm transition <?php echo ($is_suspended == 1) ? 'pointer-events-none opacity-30' : ''; ?>">
                <i class="fas fa-plus-circle w-5 text-center"></i> नई वेकेंसी जोड़ें
            </a>

            <a href="add_result.php" class="flex items-center gap-3 text-white bg-indigo-600 shadow-lg shadow-indigo-600/20 px-4 py-3 rounded-xl font-medium text-sm transition <?php echo ($is_suspended == 1) ? 'pointer-events-none opacity-30' : ''; ?>">
                <i class="fas fa-trophy w-5 text-center"></i> परीक्षा परिणाम जोड़ें
            </a>
            
            <a href="manage_vacancies.php" class="flex items-center gap-3 text-slate-400 hover:text-white hover:bg-slate-800 px-4 py-3 rounded-xl font-medium text-sm transition">
                <i class="fas fa-list w-5 text-center"></i> मेरी पोस्ट्स देखें
            </a>

            <a href="wallet.php" class="flex items-center gap-3 text-slate-400 hover:text-white hover:bg-slate-800 px-4 py-3 rounded-xl font-medium text-sm transition">
                <i class="fas fa-wallet w-5 text-center"></i> वॉलेट और आय
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800">
            <a href="logout.php" class="flex items-center gap-3 text-red-400 hover:text-red-300 hover:bg-slate-800 px-4 py-3 rounded-xl font-medium text-sm transition">
                <i class="fas fa-sign-out-alt w-5 text-center"></i> लॉग आउट
            </a>
        </div>
    </aside>

    <!-- मुख्य कंटेंट एरिया -->
    <main class="flex-grow p-8 h-screen overflow-y-auto">
        
        <!-- ===================================== -->
        <!-- 🎯 RANK PROMOTION TARGET UI -->
        <!-- ===================================== -->
        <div class="mb-6 p-5 bg-gradient-to-r from-slate-900 to-indigo-950 rounded-2xl text-white shadow-xl border border-indigo-500/20 max-w-5xl mx-auto">
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

        <div class="flex justify-between items-center mb-6 border-b border-slate-200 pb-4 max-w-5xl mx-auto">
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-trophy text-amber-500"></i> परीक्षा परिणाम (Result) जोड़ें
            </h2>
        </div>

        <!-- 🛡️ Admin Verification Alert Banner -->
        <div class="max-w-5xl mx-auto mb-4 bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-3 rounded-lg text-sm flex items-center gap-3">
            <i class="fas fa-user-shield text-xl text-blue-600"></i>
            <div>
                <strong>एडमिन वेरिफिकेशन आवश्यक:</strong> आपके द्वारा पोस्ट किया गया रिजल्ट सीधे लाइव नहीं होगा। सबमिट करने के बाद यह एडमिन पैनल में सत्यापन (Verification) के लिए जाएगा।
            </div>
        </div>

        <div class="max-w-5xl mx-auto">
            <?php echo $message; ?>
        </div>

        <!-- Form Container -->
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200 max-w-5xl mx-auto">
            <form method="POST" class="space-y-6">
                
                <!-- 1. मुख्य जानकारी (Main Info) -->
                <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100">
                    <h3 class="text-indigo-800 font-bold mb-4 border-b border-indigo-200 pb-2"><i class="fas fa-info-circle"></i> 1. बुनियादी जानकारी</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block font-semibold text-slate-700 mb-1 text-sm">Post Title (परिणाम का मुख्य शीर्षक) *</label>
                            <input type="text" name="title" required placeholder="जैसे: SSC CGL Tier 1 Final Result 2026" class="w-full p-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1 text-sm">State (राज्य)</label>
                            <select name="state" required class="w-full p-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 transition" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                                <option value="All India">All India (Central Job)</option>
                                <option value="Uttar Pradesh">Uttar Pradesh (उत्तर प्रदेश)</option>
                                <option value="Bihar">Bihar (बिहार)</option>
                                <option value="Madhya Pradesh">Madhya Pradesh (मध्य प्रदेश)</option>
                                <option value="Rajasthan">Rajasthan (राजस्थान)</option>
                                <option value="Haryana">Haryana (हरियाणा)</option>
                                <option value="Delhi">Delhi (दिल्ली)</option>
                                <option value="Uttarakhand">Uttarakhand (उत्तराखंड)</option>
                                <option value="Other">Other (अन्य)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1 text-sm">Total Posts (कुल पद) - वैकल्पिक</label>
                            <input type="text" name="total_posts" placeholder="जैसे: 5425 पद" class="w-full p-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 transition" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                        </div>
                    </div>
                </div>

                <!-- 2. रिजल्ट लिंक्स -->
                <div class="bg-amber-50/50 p-4 rounded-xl border border-amber-200">
                    <h3 class="text-amber-800 font-bold mb-4 border-b border-amber-200 pb-2"><i class="fas fa-link"></i> 2. महत्वपूर्ण लिंक्स (Result Links)</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-amber-900 mb-1 text-sm">Result Check Link (डायरेक्ट लिंक) *</label>
                            <input type="text" name="result_link" required placeholder="https://..." class="w-full p-2.5 border border-amber-300 bg-white rounded-lg focus:ring-2 focus:ring-amber-500 transition" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                            <span class="text-xs text-amber-700 mt-1 block">यहाँ रिजल्ट डाउनलोड या लॉगिन का लिंक डालें।</span>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1 text-sm">Official Website (विभाग की वेबसाइट)</label>
                            <input type="text" name="official_website" placeholder="https://..." class="w-full p-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 transition" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                        </div>
                    </div>
                </div>

                <!-- 3. विवरण (Details) -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
                    <h3 class="text-slate-800 font-bold border-b border-slate-200 pb-2"><i class="fas fa-align-left"></i> 3. संक्षिप्त विवरण एवं तिथियां</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1 text-sm">Short Description (संक्षिप्त विवरण)</label>
                            <textarea name="short_desc" rows="3" placeholder="लिखित परीक्षा का परिणाम घोषित कर दिया गया है। अपना रोल नंबर डालकर चेक करें..." class="w-full p-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 transition" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1 text-sm">Important Dates (महत्वपूर्ण तिथियां)</label>
                            <textarea name="important_dates" rows="3" placeholder="Exam Date: 12 July 2026&#10;Result Declared: 25 July 2026" class="w-full p-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 transition" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
                        </div>
                    </div>
                </div>

                <!-- 4. Long Content (CKEditor) -->
                <div class="space-y-6 pt-4 border-t border-slate-200">
                    <div>
                        <label class="block font-bold text-slate-800 mb-2">रिजल्ट की पूरी जानकारी (Cut-off, Merit List Details)</label>
                        <textarea name="long_details" id="editor1" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-800 mb-2">रिजल्ट कैसे चेक करें? (How to Check Result / Instructions)</label>
                        <textarea name="description" id="editor2" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>></textarea>
                    </div>
                </div>

                <!-- 🎯 Corrected Submit Button (Clear Sync Intent) -->
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg transition duration-200 flex justify-center items-center gap-2 text-lg" <?php echo ($is_suspended == 1) ? 'disabled' : ''; ?>>
                    <i class="fas fa-paper-plane"></i> एडमिन वेरिफिकेशन के लिए भेजें (Submit for Admin Approval)
                </button>

            </form>
        </div>
    </main>
</div>

<script>
    // Initialize CKEditor
    window.addEventListener('DOMContentLoaded', (event) => {
        if (typeof CKEDITOR !== 'undefined') {
            CKEDITOR.replace('editor1', { height: 250 });
            CKEDITOR.replace('editor2', { height: 200 });
        }
    });
</script>

</body>
</html>