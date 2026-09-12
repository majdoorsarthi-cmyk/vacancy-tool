<?php
session_start();

// एडमिन लॉगिन चेक
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db_connect.php';

// --- APPROVE OR REJECT LOGIC WITH TRANSACTION SYNC ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $request_id = intval($_GET['id']);
    $action = $_GET['action'];

    // रिक्वेस्ट का डेटा सिलेक्ट करें
    $req_query = "SELECT * FROM withdrawal_requests WHERE id = $request_id";
    $req_result = mysqli_query($conn, $req_query);
    $request_data = mysqli_fetch_assoc($req_result);

    if ($request_data && $request_data['status'] == 'pending') {
        $op_id = intval($request_data['operator_id']);
        $amount = floatval($request_data['amount']);

        if ($action == 'approve') {
            // रीयल-टाइम सिंक के लिए डेटाबेस ट्रांजैक्शन शुरू करें
            mysqli_begin_transaction($conn);

            try {
                // 1. withdrawal_requests टेबल की स्थिति बदलें
                mysqli_query($conn, "UPDATE withdrawal_requests SET status = 'approved' WHERE id = $request_id");
                
                // 2. operator_wallet टेबल में DEBIT एंट्री डालें ताकि ऑपरेटर का उपलब्ध बैलेंस तुरंत माइनस हो जाए
                mysqli_query($conn, "INSERT INTO operator_wallet (operator_id, vacancy_id, amount, status, created_at) 
                                     VALUES ($op_id, NULL, $amount, 'debited', NOW())");
                
                // दोनों क्वेरी सफल होने पर कमिट करें
                mysqli_commit($conn);
                $_SESSION['msg'] = "💸 अनुरोध स्वीकृत कर दिया गया है और ऑपरेटर का बैलेंस रीयल-टाइम सिंक हो गया है!";
            } catch (Exception $e) {
                // गड़बड़ी होने पर रोलबैक करें
                mysqli_rollback($conn);
                $_SESSION['msg'] = "❌ कुछ तकनीकी समस्या आई। बैलेंस अपडेट नहीं हुआ।";
            }

        } elseif ($action == 'reject') {
            // सिम्पल रिजेक्ट (वॉलेट से कोई छेड़छाड़ नहीं)
            mysqli_query($conn, "UPDATE withdrawal_requests SET status = 'rejected' WHERE id = $request_id");
            $_SESSION['msg'] = "❌ अनुरोध को सफलतापूर्वक अस्वीकृत (Rejected) कर दिया गया है।";
        }
    }
    header("Location: manage_withdrawals.php");
    exit();
}

// सभी विड्रॉल रिक्वेस्ट्स को ऑपरेटर के नाम के साथ फ़ेच करें
$query = "SELECT wr.*, o.username as operator_name 
          FROM withdrawal_requests wr 
          JOIN operators o ON wr.operator_id = o.id 
          ORDER BY wr.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>निकासी प्रबंधन (Admin) | वैकेंसी पोर्टल</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans antialiased">
<div class="flex h-screen overflow-hidden">
    
    <!-- Sidebar (Dynamic Safe Path Check) -->
    <?php 
    if (file_exists('sidebar.php')) {
        include 'sidebar.php';
    } elseif (file_exists('includes/sidebar.php')) {
        include 'includes/sidebar.php';
    } elseif (file_exists('../includes/sidebar.php')) {
        include '../includes/sidebar.php';
    } else {
        echo "<div class='bg-red-500 text-white p-4'>Sidebar component not found.</div>";
    }
    ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <main class="flex-1 overflow-y-auto p-6 lg:p-8">
            
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">निकासी प्रबंधन (Withdrawals)</h1>
                    <p class="text-slate-500 text-sm mt-0.5">ऑपरेटरों द्वारा भेजे गए सभी पेआउट्स और कमीशन निकासी का रीयल-टाइम डेटा।</p>
                </div>
            </div>

            <!-- Alert Notification Messages -->
            <?php if (isset($_SESSION['msg'])): ?>
                <div class="bg-indigo-50 border border-indigo-100 text-indigo-800 p-4 rounded-xl mb-6 shadow-sm flex items-center gap-3 animate-fade-in">
                    <i class="fas fa-info-circle text-indigo-500 text-lg"></i>
                    <p class="text-sm font-medium"><?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?></p>
                </div>
            <?php endif; ?>

            <!-- Main Data Table Container -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">ऑपरेटर</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">निकासी राशि</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">भुगतान विवरण</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">अनुरोध तिथि</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">स्थिति</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">कार्रवाई (Action)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <?php if (mysqli_num_rows($result) > 0): 
                                while($row = mysqli_fetch_assoc($result)): 
                                    // स्टेटस पिल्स स्टाइलिंग
                                    if ($row['status'] == 'approved') {
                                        $status_badge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    } elseif ($row['status'] == 'pending') {
                                        $status_badge = 'bg-amber-50 text-amber-700 border-amber-200';
                                    } else {
                                        $status_badge = 'bg-rose-50 text-rose-700 border-rose-200';
                                    }
                            ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <!-- Operator Name -->
                                <td class="px-6 py-4 font-semibold text-slate-800">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 bg-slate-100 rounded-full flex items-center justify-center text-xs text-slate-600 font-bold uppercase">
                                            <?php echo substr($row['operator_name'], 0, 2); ?>
                                        </div>
                                        <span><?php echo htmlspecialchars($row['operator_name']); ?></span>
                                    </div>
                                </td>
                                
                                <!-- Amount -->
                                <td class="px-6 py-4 font-bold text-slate-900 text-base">
                                    ₹<?php echo number_format((float)$row['amount'], 2); ?>
                                </td>
                                
                                <!-- Payment Details -->
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 mb-1 border border-slate-200">
                                        <?php echo htmlspecialchars(strtoupper($row['payment_method'])); ?>
                                    </span>
                                    <p class="text-slate-600 font-mono text-xs max-w-xs truncate" title="<?php echo htmlspecialchars($row['payment_details']); ?>">
                                        <?php echo htmlspecialchars($row['payment_details']); ?>
                                    </p>
                                </td>
                                
                                <!-- Date & Time -->
                                <td class="px-6 py-4 text-slate-500 text-xs whitespace-nowrap">
                                    <i class="far fa-clock mr-1 text-slate-400"></i>
                                    <?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?>
                                </td>
                                
                                <!-- Status Badge -->
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase border <?php echo $status_badge; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                
                                <!-- Action Buttons -->
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <a href="manage_withdrawals.php?action=approve&id=<?php echo $row['id']; ?>" 
                                           onclick="return confirm('क्या आप इस पेआउट को एप्रूव करना चाहते हैं? ऐसा करने से ऑपरेटर के वॉलेट से ₹<?php echo number_format($row['amount'], 2); ?> रीयल-टाइम में कट जाएंगे।')" 
                                           class="inline-flex items-center bg-emerald-600 text-white text-xs px-3 py-1.5 rounded-lg mr-2 hover:bg-emerald-700 font-semibold shadow-sm transition">
                                            <i class="fas fa-check mr-1.5"></i> एप्रूव
                                        </a>
                                        <a href="manage_withdrawals.php?action=reject&id=<?php echo $row['id']; ?>" 
                                           onclick="return confirm('क्या आप निश्चित रूप से इस अनुरोध को रिजेक्ट करना चाहते हैं?')" 
                                           class="inline-flex items-center bg-rose-50 text-rose-600 border border-rose-200 text-xs px-3 py-1.5 rounded-lg hover:bg-rose-100 font-semibold transition">
                                            <i class="fas fa-times mr-1.5"></i> रिजेक्ट
                                        </a>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs italic bg-slate-50 px-2.5 py-1 rounded-md border border-slate-100">
                                            <i class="fas fa-lock mr-1 text-slate-300"></i> आर्काइव्ड
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <!-- Empty State Design -->
                            <tr>
                                <td colspan="6" class="p-16 text-center">
                                    <div class="w-16 h-16 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-inner text-slate-400">
                                        <i class="fas fa-wallet text-2xl"></i>
                                    </div>
                                    <h3 class="text-slate-700 font-bold text-base mb-1">कोई अनुरोध मौजूद नहीं है</h3>
                                    <p class="text-slate-400 text-xs max-w-xs mx-auto">वर्तमान में किसी भी ऑपरेटर ने बैलेंस निकासी का अनुरोध नहीं भेजा है।</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </main>
    </div>
</div>
</body>
</html>