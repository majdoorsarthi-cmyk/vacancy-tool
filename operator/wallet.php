<?php
session_start();
// लॉगिन जांच
if (!isset($_SESSION['operator_logged_in']) || !isset($_SESSION['operator_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db_connect.php'; 

$operator_id = intval($_SESSION['operator_id']);

// --- 🔄 1. एडमिन द्वारा सेट किया गया डायरेक्ट बैलेंस (operators टेबल से) ---
$op_query = "SELECT wallet_balance FROM operators WHERE id = ?";
$op_stmt = mysqli_prepare($conn, $op_query);
mysqli_stmt_bind_param($op_stmt, "i", $operator_id);
mysqli_stmt_execute($op_stmt);
$op_res = mysqli_stmt_get_result($op_stmt);
$op_data = mysqli_fetch_assoc($op_res);
$admin_set_balance = isset($op_data['wallet_balance']) ? floatval($op_data['wallet_balance']) : null;
mysqli_stmt_close($op_stmt);

// --- 📊 2. कुल पोस्ट / एडमिन क्रेडिट कमाई (Total Credited Lifetime) ---
// LOWER() और TRIM() से केस-सेंसिटिविटी या एक्स्ट्रा स्पेस की समस्या खत्म होगी
$credit_query = "SELECT SUM(amount) as total_credited 
                 FROM operator_wallet 
                 WHERE operator_id = ? 
                 AND LOWER(TRIM(status)) = 'credited'";
$credit_stmt = mysqli_prepare($conn, $credit_query);
mysqli_stmt_bind_param($credit_stmt, "i", $operator_id);
mysqli_stmt_execute($credit_stmt);
$credit_res = mysqli_stmt_get_result($credit_stmt);
$credit_data = mysqli_fetch_assoc($credit_res);
$total_earnings = floatval($credit_data['total_credited'] ?? 0.00);
mysqli_stmt_close($credit_stmt);

// --- 🔻 3. कुल डेबिट/निकासी (Total Debited) ---
$debit_query = "SELECT SUM(amount) as total_debited 
                FROM operator_wallet 
                WHERE operator_id = ? 
                AND LOWER(TRIM(status)) = 'debited'";
$debit_stmt = mysqli_prepare($conn, $debit_query);
mysqli_stmt_bind_param($debit_stmt, "i", $operator_id);
mysqli_stmt_execute($debit_stmt);
$debit_res = mysqli_stmt_get_result($debit_stmt);
$debit_data = mysqli_fetch_assoc($debit_res);
$total_debited = floatval($debit_data['total_debited'] ?? 0.00);
mysqli_stmt_close($debit_stmt);

// --- 🎯 4. रियल-टाइम फाइनल बैलेंस लॉजिक (कोई डबल-काउंटिंग नहीं) ---
// यदि एडमिन ने operators टेबल में मैन्युअल बैलेंस डाला है तो वही दिखेगा, अन्यथा लेज़र (Credit - Debit) से दिखेगा
$ledger_balance = $total_earnings - $total_debited;
if ($admin_set_balance !== null && $admin_set_balance > 0) {
    $wallet_balance = $admin_set_balance;
} else {
    $wallet_balance = ($ledger_balance > 0) ? $ledger_balance : 0.00;
}

// --- 📜 5. ट्रांजैक्शन हिस्ट्री (ऑपरेटर स्पेसिफिक) ---
$history_query = "SELECT ow.*, v.title as job_title 
                  FROM operator_wallet ow 
                  LEFT JOIN vacancies v ON ow.vacancy_id = v.id 
                  WHERE ow.operator_id = ? 
                  ORDER BY ow.created_at DESC LIMIT 50";
$hist_stmt = mysqli_prepare($conn, $history_query);
mysqli_stmt_bind_param($hist_stmt, "i", $operator_id);
mysqli_stmt_execute($hist_stmt);
$history_result = mysqli_stmt_get_result($hist_stmt);
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="vp.png">
    <link rel="apple-touch-icon" href="vp.png">
    <title>ऑपरेटर वॉलेट | टीसी अकादमी</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 selection:bg-indigo-600 selection:text-white min-h-screen">

<div class="flex flex-col md:flex-row min-h-screen">
    <!-- Sidebar Included -->
    <?php 
        if (file_exists('../includes/sidebar.php')) {
            include '../includes/sidebar.php'; 
        } elseif (file_exists('includes/sidebar.php')) {
            include 'includes/sidebar.php';
        } elseif (file_exists('sidebar.php')) {
            include 'sidebar.php';
        }
    ?>

    <div class="flex-1 flex flex-col overflow-y-auto">
        <main class="flex-1 p-4 md:p-8">
            
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 pb-4 border-b border-slate-800">
                <div>
                    <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <i class="fas fa-wallet text-indigo-400"></i> ऑपरेटर वॉलेट लेज़र
                    </h1>
                    <p class="text-slate-400 text-xs sm:text-sm font-medium mt-1">लाइव डेटाबेस सिंक, पोस्ट कमीशन और एडमिन एडजस्टमेंट ट्रैकिंग।</p>
                </div>
                <button onclick="window.print()" class="self-start sm:self-auto bg-slate-800 border border-slate-700 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-200 hover:bg-slate-700 shadow-sm transition flex items-center gap-2">
                    <i class="fas fa-file-export text-indigo-400"></i> लेज़र प्रिंट करें
                </button>
            </div>

            <!-- Stats Cards Bar -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- 1. Live Wallet Balance -->
                <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-700 to-slate-900 p-6 rounded-2xl shadow-xl shadow-indigo-600/20 text-white flex flex-col justify-between border border-indigo-500/30">
                    <div class="absolute -right-6 -top-6 h-28 w-28 rounded-full bg-indigo-400/20 blur-2xl"></div>
                    <div>
                        <div class="flex justify-between items-center">
                            <p class="text-indigo-200 text-xs uppercase font-black tracking-wider">उपलब्ध बैलेंस (Live Balance)</p>
                            <span class="inline-flex items-center gap-1.5 bg-emerald-500/20 text-emerald-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-emerald-500/30">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Verified Real
                            </span>
                        </div>
                        <h2 class="text-3xl sm:text-4xl font-black text-white mt-3 tracking-tight">
                            ₹<?php echo number_format($wallet_balance, 2); ?>
                        </h2>
                    </div>
                    <div class="mt-6 pt-3 border-t border-indigo-500/30 text-[11px] text-indigo-200 flex items-center justify-between font-semibold">
                        <span>कुल लाइफटाइम जमा (Total Credited):</span>
                        
                    </div>
                </div>

                <!-- 2. Total Debited -->
                <div class="bg-slate-800/80 p-6 rounded-2xl border border-slate-700/80 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-center">
                            <p class="text-slate-400 text-xs uppercase font-extrabold tracking-wider">कुल निकाली गई राशि (Debited)</p>
                            <div class="h-9 w-9 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center text-xs font-bold">
                                <i class="fas fa-arrow-up-right-from-square"></i>
                            </div>
                        </div>
                        <h2 class="text-3xl font-black text-white mt-3 tracking-tight">
                            ₹<?php echo number_format($total_debited, 2); ?>
                        </h2>
                    </div>
                    <p class="text-[11px] text-slate-400 font-medium mt-4">आपके बैंक/खाते में हस्तांतरित की गई राशि</p>
                </div>

                <!-- 3. Status Overview -->
                <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-center">
                            <p class="text-amber-400 text-xs uppercase font-extrabold tracking-wider">पेआउट पात्रता</p>
                            <i class="fas fa-shield-halved text-amber-400 text-base"></i>
                        </div>
                        <div class="mt-4">
                            <?php if ($wallet_balance >= 10000): ?>
                                <span class="inline-flex items-center gap-1.5 bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 text-xs font-extrabold px-3 py-1.5 rounded-xl">
                                    <i class="fas fa-circle-check text-emerald-400"></i> निकासी हेतु पात्र
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 bg-amber-500/20 border border-amber-500/40 text-amber-300 text-xs font-extrabold px-3 py-1.5 rounded-xl">
                                    <i class="fas fa-clock text-amber-400"></i> न्यूनतम ₹10,000 आवश्यक
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400 font-medium mt-4">ऑपरेटर नोड सुरक्षित और सक्रिय है।</p>
                </div>
            </div>

            <!-- 10,000 Withdrawal Banner -->
            <?php if ($wallet_balance >= 10000): ?>
                <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-800 text-white p-6 rounded-2xl mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 shadow-xl shadow-emerald-950/40 border border-emerald-500/30">
                    <div class="space-y-1">
                        <h4 class="font-black text-white text-base flex items-center gap-2">
                            🎉 बधाई हो! आप पेआउट (Payout) लेने के पात्र हैं!
                        </h4>
                        <p class="text-emerald-100 text-xs font-medium">आपका बैलेंस न्यूनतम थ्रेशोल्ड ₹10,000 के पार हो चुका है।</p>
                    </div>
                    <a href="withdraw_request.php" class="bg-white text-emerald-950 font-black text-xs px-6 py-3 rounded-xl hover:bg-emerald-100 shadow-md transition whitespace-nowrap flex items-center gap-2">
                        <i class="fas fa-wallet text-emerald-600"></i> तुरंत निकासी (Withdraw) करें
                    </a>
                </div>
            <?php endif; ?>

            <!-- Transaction Table -->
            <div class="bg-slate-800/80 rounded-2xl border border-slate-700/80 shadow-md overflow-hidden">
                <div class="p-5 border-b border-slate-700 bg-slate-900/60 flex justify-between items-center">
                    <h3 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-list-check text-indigo-400"></i> लेन-देन इतिहास (Ledger Transactions)
                    </h3>
                    <span class="text-[11px] text-slate-400 font-semibold">अंतिम 50 रिकॉर्ड्स</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-900/80 border-b border-slate-700">
                            <tr>
                                <th class="px-6 py-4 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">लेन-देन विवरण</th>
                                <th class="px-6 py-4 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">दिनांक एवं समय</th>
                                <th class="px-6 py-4 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">स्टेटस</th>
                                <th class="px-6 py-4 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider text-right">राशि (Amount)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/60 text-xs font-semibold text-slate-200">
                            <?php if (mysqli_num_rows($history_result) > 0): 
                                while($row = mysqli_fetch_assoc($history_result)): 
                                    $st = strtolower(trim($row['status']));
                                    
                                    // विवरण (Title) का सही निर्धारण ताकि कोई भ्रम न रहे
                                    if (!empty($row['job_title'])) {
                                        $title_display = $row['job_title'];
                                    } elseif (!empty($row['description'])) {
                                        $title_display = $row['description'];
                                    } else {
                                        $title_display = ($st === 'debited') ? 'वॉलेट निकासी / पेआउट' : 'एडमिन द्वारा वॉलेट क्रेडिट / बोनस';
                                    }

                                    if ($st === 'credited') {
                                        $status_color = 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30';
                                        $sign = '+';
                                        $amt_color = 'text-emerald-400';
                                    } elseif ($st === 'debited') {
                                        $status_color = 'bg-rose-500/15 text-rose-400 border-rose-500/30';
                                        $sign = '-';
                                        $amt_color = 'text-rose-400';
                                    } elseif ($st === 'pending') {
                                        $status_color = 'bg-amber-500/15 text-amber-400 border-amber-500/30';
                                        $sign = '';
                                        $amt_color = 'text-amber-400';
                                    } else {
                                        $status_color = 'bg-slate-700 text-slate-300 border-slate-600';
                                        $sign = '';
                                        $amt_color = 'text-slate-300';
                                    }
                            ?>
                            <tr class="hover:bg-slate-700/40 transition duration-150">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-white text-sm">
                                        <?php echo htmlspecialchars($title_display); ?>
                                    </p>
                                    <p class="text-[10px] text-slate-400 font-mono tracking-wider mt-1">TXN ID: #<?php echo $row['id']; ?></p>
                                </td>
                                <td class="px-6 py-4 text-slate-400 font-medium">
                                    <?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase border <?php echo $status_color; ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-black text-sm <?php echo $amt_color; ?>">
                                    <?php echo $sign; ?> ₹<?php echo number_format(floatval($row['amount']), 2); ?>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="4" class="p-12 text-center text-slate-400">
                                    <i class="fas fa-receipt text-4xl mb-3 text-slate-600 block"></i>
                                    <p class="font-bold text-slate-300 text-sm">कोई लेन-देन इतिहास नहीं मिला।</p>
                                    <p class="text-xs text-slate-500 mt-1">जब पोस्ट भरेंगे या एडमिन पैसे जोड़ेंगे/निकालेंगे, तो सूची यहाँ दिखेगी।</p>
                                </td>
                            </tr>
                            <?php endif; 
                            mysqli_stmt_close($hist_stmt);
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>