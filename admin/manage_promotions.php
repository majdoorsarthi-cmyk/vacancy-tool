<?php
session_start();

// ─── 1. एडमिन ऑथेंटिकेशन ───
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include '../includes/db_connect.php';

$message = '';
$msg_type = '';

// ─── 2. ऑपरेटर स्कोर, रैंक, कमीशन रेट व पदनाम अपडेट हैंडलर ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_operator_score'])) {
    $op_id = intval($_POST['operator_id']);
    $action_type = $_POST['action_type']; // 'override', 'add', 'subtract'
    $input_score = isset($_POST['performance_score']) && $_POST['performance_score'] !== '' ? intval($_POST['performance_score']) : null;
    $custom_comm_rate = isset($_POST['commission_rate']) && $_POST['commission_rate'] !== '' ? floatval($_POST['commission_rate']) : null;
    $new_designation = trim($_POST['designation'] ?? '');

    // वर्तमान ऑपरेटर का डेटा लें
    $stmt_get = $conn->prepare("SELECT performance_score, commission_rate, username, designation FROM operators WHERE id = ?");
    $stmt_get->bind_param("i", $op_id);
    $stmt_get->execute();
    $current_op = $stmt_get->get_result()->fetch_assoc();
    $stmt_get->close();

    if ($current_op) {
        $final_score = intval($current_op['performance_score']);
        $final_comm_rate = floatval($current_op['commission_rate']);

        // लॉजिक A: यदि एडमिन ने "कमीशन रेट" में नया मान दर्ज किया है
        if ($custom_comm_rate !== null) {
            $final_comm_rate = $custom_comm_rate;

            if ($custom_comm_rate >= 2.00) {
                $final_score = 501; // Diamond Elite
            } elseif ($custom_comm_rate >= 1.50) {
                $final_score = 201; // Gold Master
            } elseif ($custom_comm_rate >= 1.00) {
                $final_score = 51;  // Silver Executive
            } else {
                $final_score = 10;  // Bronze Starter
            }
        } 
        // लॉजिक B: यदि कमीशन रेट खाली है और सीधे स्कोर बदल रहे हैं
        elseif ($input_score !== null) {
            if ($action_type === 'override') {
                $final_score = $input_score;
            } elseif ($action_type === 'add') {
                $final_score += $input_score;
            } elseif ($action_type === 'subtract') {
                $final_score = max(0, $final_score - $input_score);
            }

            if ($final_score >= 501) {
                $final_comm_rate = 2.00;
            } elseif ($final_score >= 201) {
                $final_comm_rate = 1.50;
            } elseif ($final_score >= 51) {
                $final_comm_rate = 1.00;
            } else {
                $final_comm_rate = 0.50;
            }
        }

        // 🌟 पदनाम अपडेट लॉजिक (Fix): 
        // 1. अगर एडमिन ने ड्रॉपडाउन से कोई विशिष्ट पद चुना है तो वह सेव होगा।
        // 2. अगर "स्वचालित (Auto-Select Rank)" चुना है, तो स्कोर के आधार पर रैंक DB में सेव होगी।
        if (!empty($new_designation)) {
            $desig_to_update = $new_designation;
        } else {
            if ($final_score >= 501) {
                $desig_to_update = 'Diamond Elite';
            } elseif ($final_score >= 201) {
                $desig_to_update = 'Gold Master';
            } elseif ($final_score >= 51) {
                $desig_to_update = 'Silver Exec';
            } else {
                $desig_to_update = 'Bronze Starter';
            }
        }

        // डेटाबेस में वास्तविक बदलाव (Update DB)
        $stmt_up = $conn->prepare("UPDATE operators SET performance_score = ?, commission_rate = ?, designation = ? WHERE id = ?");
        $stmt_up->bind_param("idsi", $final_score, $final_comm_rate, $desig_to_update, $op_id);
        
        if ($stmt_up->execute()) {
            $message = "ऑपरेटर <strong>" . htmlspecialchars($current_op['username']) . "</strong> का डेटा अपडेट हो गया! (पदनाम: <strong>$desig_to_update</strong>, रेट: ₹" . number_format($final_comm_rate, 2) . ", स्कोर: $final_score pts)";
            $msg_type = "success";
        } else {
            $message = "डेटाबेस अपडेट करने में त्रुटि हुई!";
            $msg_type = "error";
        }
        $stmt_up->close();
    }
}

// ─── 3. ऑपरेटर डेटा फ़ेच करें ───
$query = "SELECT id, username, email, whatsapp_no, performance_score, commission_rate, wallet_balance, designation, is_suspended 
          FROM operators 
          ORDER BY performance_score DESC";
$result = $conn->query($query);

// स्कोर के आधार पर डायनामिक रैंक व स्टाइल देने वाला फ़ंक्शन
function getRankDetails($score) {
    if ($score >= 501) {
        return ['name' => 'Diamond Elite', 'badge' => '💎', 'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200 ring-1 ring-cyan-400/30'];
    } elseif ($score >= 201) {
        return ['name' => 'Gold Master', 'badge' => '🥇', 'class' => 'bg-amber-50 text-amber-700 border-amber-200 ring-1 ring-amber-400/30'];
    } elseif ($score >= 51) {
        return ['name' => 'Silver Exec', 'badge' => '🥈', 'class' => 'bg-slate-100 text-slate-700 border-slate-300 ring-1 ring-slate-400/30'];
    } else {
        return ['name' => 'Bronze Starter', 'badge' => '🥉', 'class' => 'bg-orange-50 text-orange-700 border-orange-200 ring-1 ring-orange-400/30'];
    }
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ऑपरेटर रैंक एवं प्रमोशन कंट्रोल - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans min-h-screen">

    <?php 
        if (file_exists('includes/admin_header.php')) {
            include 'includes/admin_header.php';
        } elseif (file_exists('../includes/admin_header.php')) {
            include '../includes/admin_header.php';
        }
    ?>

    <main class="p-4 md:p-8 max-w-7xl mx-auto space-y-6">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 flex items-center gap-2.5">
                    <span class="p-2 bg-amber-50 rounded-xl text-amber-500 border border-amber-200/60 shadow-xs">
                        <i class="fa-solid fa-trophy text-xl"></i>
                    </span> 
                    ऑपरेटर रैंक व प्रमोशन कंट्रोल
                </h1>
                <p class="text-xs md:text-sm text-slate-500 mt-1">
                    कमीशन रेट, पॉइंट्स या पदनाम अपडेट करें — डेटाबेस में तुरंत सुरक्षित होगा।
                </p>
            </div>
            
            <div class="w-full md:w-80 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400 text-sm"></i>
                <input type="text" id="searchInput" onkeyup="filterOperators()" placeholder="नाम, ईमेल या मोबाइल से खोजें..." 
                       class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition-all shadow-xs">
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="p-4 rounded-xl border <?php echo $msg_type === 'success' ? 'bg-emerald-50 border-emerald-300 text-emerald-800' : 'bg-rose-50 border-rose-300 text-rose-800'; ?> flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2 text-sm font-medium">
                    <i class="<?php echo $msg_type === 'success' ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-triangle-exclamation text-rose-600'; ?>"></i>
                    <span><?php echo $message; ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-lg font-bold hover:opacity-75">&times;</button>
            </div>
        <?php endif; ?>

        <!-- समरी स्लॉट्स -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200/80 p-4 rounded-2xl shadow-xs flex items-center gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-orange-50 border border-orange-100 flex items-center justify-center text-2xl shadow-xs">🥉</div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm">Bronze Starter</h4>
                    <span class="text-xs text-slate-500 block mt-0.5">0 - 50 Points <strong class="text-orange-600 font-semibold">(₹0.50/post)</strong></span>
                </div>
            </div>
            <div class="bg-white border border-slate-200/80 p-4 rounded-2xl shadow-xs flex items-center gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-2xl shadow-xs">🥈</div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm">Silver Executive</h4>
                    <span class="text-xs text-slate-500 block mt-0.5">51 - 200 Points <strong class="text-slate-700 font-semibold">(₹1.00/post)</strong></span>
                </div>
            </div>
            <div class="bg-white border border-slate-200/80 p-4 rounded-2xl shadow-xs flex items-center gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-2xl shadow-xs">🥇</div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm">Gold Master Pro</h4>
                    <span class="text-xs text-slate-500 block mt-0.5">201 - 500 Points <strong class="text-amber-600 font-semibold">(₹1.50/post)</strong></span>
                </div>
            </div>
            <div class="bg-white border border-slate-200/80 p-4 rounded-2xl shadow-xs flex items-center gap-4 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-cyan-50 border border-cyan-100 flex items-center justify-center text-2xl shadow-xs">💎</div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm">Diamond Elite</h4>
                    <span class="text-xs text-slate-500 block mt-0.5">501+ Points <strong class="text-cyan-600 font-semibold">(₹2.00/post)</strong></span>
                </div>
            </div>
        </div>

        <!-- डेटा तालिका -->
        <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                    <i class="fa-solid fa-users-gear text-indigo-600"></i> ऑपरेटर सूची व वर्तमान स्थिति
                </h3>
                <span class="text-xs font-semibold px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full border border-indigo-100">
                    कुल ऑपरेटर: <?php echo $result ? $result->num_rows : 0; ?>
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600" id="operatorsTable">
                    <thead class="bg-slate-100/70 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200/80">
                        <tr>
                            <th class="p-4">ऑपरेटर विवरण</th>
                            <th class="p-4">सम्पर्क जानकारी</th>
                            <th class="p-4">वर्तमान पद/रैंक</th>
                            <th class="p-4">स्कोर (Points)</th>
                            <th class="p-4">कमीशन रेट</th>
                            <th class="p-4">वॉलेट बैलेंस</th>
                            <th class="p-4">स्टेटस</th>
                            <th class="p-4 text-center">एक्शन</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($op = $result->fetch_assoc()): ?>
                                <?php 
                                    $score_val = intval($op['performance_score'] ?? 0);
                                    $db_comm_rate = floatval($op['commission_rate'] ?? 0.00);
                                    $rank = getRankDetails($score_val); 
                                ?>
                                <tr class="hover:bg-slate-50/80 transition operator-row">
                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 text-base op-name"><?php echo htmlspecialchars($op['username']); ?></div>
                                        <div class="text-xs text-indigo-600 font-semibold mt-0.5 inline-block bg-indigo-50/80 px-2 py-0.5 rounded border border-indigo-100">
                                            <?php echo htmlspecialchars(!empty($op['designation']) ? $op['designation'] : $rank['name']); ?>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <div class="text-slate-800 font-semibold text-xs op-whatsapp flex items-center gap-1.5">
                                            <i class="fa-brands fa-whatsapp text-emerald-500"></i> <?php echo htmlspecialchars($op['whatsapp_no'] ?? 'N/A'); ?>
                                        </div>
                                        <div class="text-xs text-slate-400 font-normal op-email mt-0.5"><?php echo htmlspecialchars($op['email'] ?? ''); ?></div>
                                    </td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border text-xs font-bold shadow-2xs <?php echo $rank['class']; ?>">
                                            <span><?php echo $rank['badge']; ?></span>
                                            <span><?php echo !empty($op['designation']) ? htmlspecialchars($op['designation']) : $rank['name']; ?></span>
                                        </span>
                                    </td>
                                    <td class="p-4 font-black text-amber-500 text-base">
                                        <?php echo $score_val; ?> <span class="text-xs text-slate-400 font-normal">pts</span>
                                    </td>
                                    <td class="p-4 font-bold text-emerald-600 text-sm">
                                        ₹<?php echo number_format($db_comm_rate, 2); ?>
                                    </td>
                                    <td class="p-4 font-bold text-slate-800">
                                        ₹<?php echo number_format(floatval($op['wallet_balance'] ?? 0), 2); ?>
                                    </td>
                                    <td class="p-4">
                                        <?php if (!empty($op['is_suspended'])): ?>
                                            <span class="px-2.5 py-1 text-[11px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200 rounded-md">Suspended</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 text-[11px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-md">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-center">
                                        <button onclick="openModal(<?php echo $op['id']; ?>, '<?php echo htmlspecialchars($op['username'], ENT_QUOTES); ?>', <?php echo $score_val; ?>, '<?php echo htmlspecialchars($op['designation'] ?? '', ENT_QUOTES); ?>', <?php echo $db_comm_rate; ?>)" 
                                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs px-3.5 py-2 rounded-xl transition shadow-xs inline-flex items-center gap-1.5">
                                            <i class="fa-solid fa-sliders"></i> मैनेज करें
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="p-8 text-center text-slate-400 font-medium">कोई ऑपरेटर डेटा उपलब्ध नहीं है।</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- ─── 5. पॉपअप मॉडल (Modal Window) ─── -->
    <div id="scoreModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4 z-50 transition-all duration-200">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-5 shadow-2xl border border-slate-100 transform transition-all">
            
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2" id="modalOpName">
                    <i class="fa-solid fa-user-pen text-indigo-600"></i> ऑपरेटर अपडेट
                </h3>
                <button onclick="closeModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 font-bold flex items-center justify-center transition">&times;</button>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="update_operator_score" value="1">
                <input type="hidden" name="operator_id" id="modalOpId">

                <div class="bg-indigo-50/60 p-3 rounded-xl border border-indigo-100 text-xs text-indigo-800">
                    💡 <strong>नोट:</strong> पदनाम या कमीशन रेट बदलें — डेटाबेस में सीधे सेव होगा।
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">कमीशन रेट (₹/Post):</label>
                        <input type="number" step="0.01" name="commission_rate" id="modalOpComm" placeholder="उदा. 1.50" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm font-bold text-emerald-600 focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">पॉइंट्स (Performance Score):</label>
                        <input type="number" name="performance_score" id="modalOpScore" placeholder="उदा. 250" min="0" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm font-bold text-amber-600 focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">एक्शन (अगर केवल पॉइंट्स बदल रहे हों):</label>
                    <select name="action_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-800 font-medium focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none">
                        <option value="override">सीधे नया स्कोर सेट करें (Override)</option>
                        <option value="add">बोनस पॉइंट्स जोड़ें (+ Add Bonus)</option>
                        <option value="subtract">पॉइंट्स घटाएं (- Penalty)</option>
                    </select>
                </div>

                <!-- ─── पदनाम (Designation Dropdown List) ─── -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">पदनाम चुनें (Select Designation):</label>
                    <select name="designation" id="modalOpDesig" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm text-slate-800 font-medium focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none">
                        <option value="">-- स्वचालित (Auto-Select Rank) --</option>
                        <option value="Bronze Starter">🥉 Bronze Starter</option>
                        <option value="Silver Executive">🥈 Silver Executive</option>
                        <option value="Gold Master Pro">🥇 Gold Master Pro</option>
                        <option value="Diamond Elite">💎 Diamond Elite</option>
                        <option value="Senior Operator">⭐ Senior Operator</option>
                        <option value="Team Leader">🔰 Team Leader</option>
                        <option value="Admin Assistant">👑 Admin Assistant</option>
                    </select>
                </div>

                <div class="flex gap-3 pt-3">
                    <button type="button" onclick="closeModal()" class="w-1/2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3 rounded-xl text-xs transition">रद्द करें</button>
                    <button type="submit" class="w-1/2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl text-xs transition shadow-md">अपडेट करें 🚀</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── JavaScript फ़ंक्शन ─── -->
    <script>
        function openModal(id, username, score, desig, commRate) {
            document.getElementById('modalOpId').value = id;
            document.getElementById('modalOpName').innerHTML = '<i class="fa-solid fa-user-pen text-indigo-600"></i> ' + username + ' - प्रमोट/अपडेट';
            document.getElementById('modalOpScore').value = score;
            document.getElementById('modalOpComm').value = commRate;
            
            // पदनाम ड्रॉपडाउन में वर्तमान पद चुनना
            let desigSelect = document.getElementById('modalOpDesig');
            desigSelect.value = desig;

            // यदि DB में पद ऐसा है जो Dropdown सूची में नहीं है, तो उसे ऑटो-ऐड कर दें
            if (desig && desigSelect.value !== desig) {
                let exists = Array.from(desigSelect.options).some(option => option.value === desig);
                if (!exists) {
                    let newOption = new Option(desig, desig, true, true);
                    desigSelect.add(newOption);
                }
            }

            document.getElementById('scoreModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('scoreModal').classList.add('hidden');
        }

        function filterOperators() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let rows = document.querySelectorAll('#operatorsTable .operator-row');

            rows.forEach(row => {
                let name = row.querySelector('.op-name')?.textContent.toLowerCase() || '';
                let email = row.querySelector('.op-email')?.textContent.toLowerCase() || '';
                let whatsapp = row.querySelector('.op-whatsapp')?.textContent.toLowerCase() || '';

                if (name.includes(input) || email.includes(input) || whatsapp.includes(input)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>