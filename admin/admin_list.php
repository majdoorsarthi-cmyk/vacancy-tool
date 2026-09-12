<?php
// 1. आवश्यक फाइलें शामिल करें
session_start();
include '../includes/db_connect.php'; 
include 'includes/admin_header.php'; // एडमिन हेडर शामिल करें

// 2. एक्सेस नियंत्रण: सुनिश्चित करें कि केवल लॉग इन एडमिन ही एक्सेस कर सकता है
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// 3. डिलीट लॉजिक हैंडल करें
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $current_admin_id = $_SESSION['admin_id'];

    // सुरक्षा जाँच: सुनिश्चित करें कि एडमिन खुद को डिलीट नहीं कर सकता
    if ($delete_id == $current_admin_id) {
        $error = "❌ आप स्वयं के एडमिन अकाउंट को डिलीट नहीं कर सकते।";
    } else {
        $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        
        if ($stmt->execute()) {
            $message = "✅ एडमिन आईडी " . htmlspecialchars($delete_id) . " सफलतापूर्वक डिलीट कर दिया गया।";
        } else {
            $error = "❌ एडमिन डिलीट करने में त्रुटि: " . $stmt->error;
        }
        $stmt->close();
    }
    // GET पैरामीटर्स साफ़ करें ताकि पेज रीलोड पर दोबारा डिलीट न हो
    // यह POST/Redirect/GET पैटर्न का एक सरल रूप है
    header("Location: admin_list.php?status=" . ($error ? 'error' : 'success'));
    exit;
}

// 4. सभी एडमिन को फेच करें
$sql = "SELECT id, username, created_at FROM admins ORDER BY id ASC";
$result = $conn->query($sql);

// 5. URL से स्टेटस मैसेज दिखाएं
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $message = $message ?: "✅ ऑपरेशन सफलतापूर्वक पूरा हुआ।";
    } elseif ($_GET['status'] == 'error') {
        $error = $error ?: "❌ ऑपरेशन में त्रुटि हुई।";
    }
}
?>

<div class="main-content">
    <div class="container">
        <h2 class="page-title">👥 एडमिन अकाउंट सूची</h2>
        
        <?php if ($message): ?>
            <div class="alert success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="action-bar">
            <a href="add_admin.php" class="btn btn-add">➕ नया एडमिन जोड़ें</a>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>यूजरनेम</th>
                            <th>बनाने की तिथि</th>
                            <th>एक्शन</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="<?php echo ($row['id'] == $_SESSION['admin_id']) ? 'current-user' : ''; ?>">
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['username']); ?>
                                    <?php if ($row['id'] == $_SESSION['admin_id']): ?>
                                        <span class="badge current"> (आप)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M, Y H:i', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <a href="edit_admin.php?id=<?php echo $row['id']; ?>" class="btn btn-action btn-edit" title="एडिट करें">✏️</a>
                                    
                                    <a href="admin_list.php?delete_id=<?php echo $row['id']; ?>" 
                                       onclick="return confirm('क्या आप वाकई इस एडमिन को डिलीट करना चाहते हैं?');" 
                                       class="btn btn-action btn-delete" 
                                       title="डिलीट करें"
                                       <?php echo ($row['id'] == $_SESSION['admin_id']) ? 'disabled' : ''; ?>>
                                        🗑️
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert info">अभी कोई एडमिन अकाउंट मौजूद नहीं है। कृपया एक नया एडमिन अकाउंट जोड़ें।</div>
        <?php endif; ?>

    </div>
</div>

<?php 
if (isset($conn)) {
    $conn->close();
}
include 'includes/admin_footer.php'; 
?>