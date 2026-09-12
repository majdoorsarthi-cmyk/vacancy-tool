<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    exit("Unauthorized");
}

// ब्राउज़र कैश को सख्ती से रोकना
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include '../includes/db_connect.php'; 

// MySQLi को अपवाद (Exceptions) थ्रो करने के लिए सक्षम करें ताकि कैच ब्लॉक काम करे
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$live_notifications = [];

try {
    // -------------------------------------------------------------------------
    // 1. ऑपरेटर्स का वर्तमान सस्पेंशन स्टेटस मैप
    // -------------------------------------------------------------------------
    $op_status_query = "SELECT id, username, is_suspended FROM operators";
    $op_status_res = $conn->query($op_status_query);
    
    $operators_status = [];
    if ($op_status_res && $op_status_res->num_rows > 0) {
        while ($os_row = $op_status_res->fetch_assoc()) {
            $operators_status[(int)$os_row['id']] = [
                'username' => htmlspecialchars($os_row['username']),
                'is_suspended' => (int)$os_row['is_suspended']
            ];
        }
    }

    // -------------------------------------------------------------------------
    // 2. Combined Activity Stream (प्रशासनिक और फॉर्म फिलिंग लॉग्स - Collation Fix के साथ)
    // -------------------------------------------------------------------------
    // CONVERT और COLLATE का उपयोग करके दोनों तालिकाओं के टेक्स्ट फॉर्मेट को एक समान बनाया गया है
    $merged_query = "
        (SELECT 'admin_log' as log_type, 
                CONVERT(al.action USING utf8mb4) COLLATE utf8mb4_general_ci as action, 
                al.created_at, 
                CONVERT(o.username USING utf8mb4) COLLATE utf8mb4_general_ci as username 
         FROM operator_activity_logs al
         LEFT JOIN operators o ON al.operator_id = o.id)
        UNION ALL
        (SELECT 'form_log' as log_type, 
                CONVERT(act.action_message USING utf8mb4) COLLATE utf8mb4_general_ci as action, 
                act.created_at, 
                CONVERT(o.username USING utf8mb4) COLLATE utf8mb4_general_ci as username 
         FROM activity_logs act
         LEFT JOIN operators o ON act.operator_id = o.id)
        ORDER BY created_at DESC LIMIT 15
    ";
    
    $stream_res = $conn->query($merged_query);
    
    if ($stream_res && $stream_res->num_rows > 0) {
        while ($row = $stream_res->fetch_assoc()) {
            $username = !empty($row['username']) ? htmlspecialchars($row['username']) : 'Operator';
            $time = date('h:i A', strtotime($row['created_at']));
            $action_raw = $row['action'];
            $action_lower = strtolower($action_raw);

            // अगर ऑपरेटर ने कोई नया फॉर्म या वैकेंसी पोस्ट भरी है
            if ($row['log_type'] === 'form_log') {
                $live_notifications[] = "<span style='color: #10b981; font-weight: bold;'>📝 <strong>लाइव पोस्ट:</strong> @{$username} ने फॉर्म भरा -> \"".htmlspecialchars($action_raw)."\" ({$time})</span>";
                continue;
            }

            // प्रशासनिक फ़िल्टर्स (operator_activity_logs से)
            if (strpos($action_lower, 'suspend') !== false && strpos($action_lower, 'unsuspend') === false) {
                $live_notifications[] = "<span style='color: #ef4444; font-weight: bold;'>🚫 <strong>अकाउंट सस्पेंड:</strong> @{$username} ब्लॉक। ({$time})</span>";
                continue;
            }
            if (strpos($action_lower, 'unsuspend') !== false || strpos($action_lower, 'activated') !== false) {
                $live_notifications[] = "<span style='color: #06b6d4; font-weight: bold;'>⚡ <strong>अकाउंट बहाल:</strong> @{$username} एक्टिव। ({$time})</span>";
                continue;
            }
            if (strpos($action_lower, 'verify') !== false || strpos($action_lower, 'approved') !== false) {
                $live_notifications[] = "<span style='color: #10b981; font-weight: bold;'>🧑‍💻 <strong>सत्यापन:</strong> @{$username} अप्रूव्ड। ({$time})</span>";
                continue;
            }
            if (strpos($action_lower, 'डैशबोर्ड') !== false || strpos($action_lower, 'login') !== false) {
                $live_notifications[] = "<span style='color: #3b82f6;'>🔑 <strong>लॉगिन:</strong> @{$username} ऑनलाइन। ({$time})</span>";
                continue;
            }
            
            // अन्य कोई सामान्य गतिविधि
            if ($username !== 'Operator') {
                $live_notifications[] = "<span style='color: #64748b;'>⚙️ @{$username}: {$action_raw} ({$time})</span>";
            }
        }
    }

    // -------------------------------------------------------------------------
    // 3. Live Withdrawal/Wallet Tracking
    // -------------------------------------------------------------------------
    $withdraw_query = "SELECT wr.*, o.username 
                       FROM withdrawal_requests wr
                       LEFT JOIN operators o ON wr.operator_id = o.id 
                       ORDER BY wr.id DESC LIMIT 3";
    $withdraw_res = $conn->query($withdraw_query);
    if ($withdraw_res && $withdraw_res->num_rows > 0) {
        while ($w_row = $withdraw_res->fetch_assoc()) {
            $username = htmlspecialchars($w_row['username'] ?? '');
            $amount = number_format($w_row['amount'], 2);
            $w_status = strtolower($w_row['status']);
            
            if (empty($username)) continue;

            if ($w_status == 'rejected') {
                $live_notifications[] = "<span style='color: #f43f5e; font-weight: bold;'>❌ <strong>रिजेक्ट:</strong> @{$username} विड्रॉल ₹{$amount} अस्वीकार।</span>";
            } elseif ($w_status == 'approved' || $w_status == 'success') {
                $live_notifications[] = "<span style='color: #22c55e; font-weight: bold;'>💰 <strong>सफल:</strong> @{$username} विड्रॉल ₹{$amount} ट्रांसफर।</span>";
            }
        }
    }

    if (empty($live_notifications)) {
        $live_notifications[] = "<span style='color: #6b7280;'>📡 लाइव सिंक सक्रिय। कोई नई गतिविधि नहीं है।</span>";
    }

} catch (Exception $e) {
    // डेटाबेस एरर आने पर सटीक टेक्निकल मैसेज दिखाएगा
    $live_notifications[] = "<span style='color: #ef4444;'>⚠️ सिंक त्रुटि: " . htmlspecialchars($e->getMessage()) . "</span>";
}

$final_output = array_values(array_unique($live_notifications));
echo implode(" &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;•&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ", $final_output);
?>