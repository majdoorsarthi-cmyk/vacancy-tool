<?php 
// सत्र शुरू करें और जांचें कि एडमिन लॉग इन है या नहीं
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("location: login.php");
    exit;
}

// 1. डेटाबेस कनेक्शन और हेडर फ़ाइलें शामिल करें
// सुनिश्चित करें कि db_connect.php का पथ सही है
include '../includes/db_connect.php'; 
include 'includes/admin_header.php'; 

$category_slug = '';
$category_name = 'सभी पोस्ट';
$error = '';
$conn_ok = isset($conn);

// URL से श्रेणी (Category) प्राप्त करें
if (isset($_GET['category'])) {
    $category_slug = htmlspecialchars(trim($_GET['category']));
    
    // प्रदर्शन के लिए श्रेणी का नाम सेट करें
    if ($category_slug == 'result') {
        $category_name = 'परिणाम (Results)';
    } elseif ($category_slug == 'admit_card') {
        $category_name = 'एडमिट कार्ड';
    } elseif ($category_slug == 'other') {
        $category_name = 'अन्य पोस्ट';
    } else {
        $category_name = ucwords(str_replace('_', ' ', $category_slug));
    }
} else {
    // यदि कोई श्रेणी निर्दिष्ट नहीं है, तो सभी पोस्ट दिखाएं
    $category_slug = '%'; 
    $category_name = 'सभी लाइव पोस्ट';
}

// 2. उस श्रेणी से संबंधित रिक्तियां प्राप्त करें
$vacancies = [];

if ($conn_ok) {
    // विशिष्ट श्रेणी की पोस्ट लोड करें
    $stmt = $conn->prepare("SELECT id, title, post_date, status, category FROM vacancies WHERE category LIKE ? ORDER BY post_date DESC");
    $stmt->bind_param("s", $category_slug);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $vacancies[] = $row;
    }
    $stmt->close();
} else {
    $error = "❌ डेटाबेस कनेक्शन विफल।";
}
?>

<style>
    /* Table Styling for professional look */
    .admin-table {
        width: 100%;
        margin-top: 20px;
        border-collapse: separate; /* For rounded corners */
        border-spacing: 0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border-radius: 8px; /* Slightly rounded table */
        overflow: hidden; /* Ensures shadow/border apply correctly */
    }
    .admin-table th, .admin-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }
    .admin-table th {
        background-color: #343a40; /* Dark header */
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9em;
    }
    .admin-table tr:hover {
        background-color: #f5f5f5; /* Hover effect */
    }
    .status-live { background-color: #28a745; }
    .status-draft { background-color: #dc3545; }
    .status-badge {
        color: white; 
        padding: 4px 8px; 
        border-radius: 3px; 
        font-size: 0.8em;
        font-weight: bold;
    }
    .action-btn {
        padding: 6px 12px;
        text-decoration: none;
        border-radius: 4px;
        margin-right: 5px;
        font-size: 0.9em;
        display: inline-block;
        transition: opacity 0.2s;
    }
    .action-btn:hover {
        opacity: 0.85;
    }
    .btn-edit { background-color: #007bff; color: white; }
    .btn-delete { background-color: #dc3545; color: white; }

    .add-post-btn {
        background: #2ecc71; 
        color: white; 
        padding: 10px 20px; 
        border-radius: 5px; 
        text-decoration: none; 
        font-weight: bold;
        transition: background-color 0.2s;
    }
    .add-post-btn:hover {
        background: #27ae60;
    }
</style>

<div id="page-content-wrapper">
    <div class="container-fluid">
        <h2>📝 **<?php echo $category_name; ?>** प्रबंधन</h2>
        <p>इस श्रेणी के तहत कुल पोस्ट: **<?php echo count($vacancies); ?>**</p>
        
        <?php if (!empty($error)) echo "<div class='alert alert-danger' style='padding: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 20px;'>{$error}</div>"; ?>

        <div style="margin-top: 20px;">
            <a href="add_vacancy.php?category=<?php echo $category_slug; ?>" class="add-post-btn">➕ नई <?php echo $category_name; ?> पोस्ट जोड़ें</a>
        </div>
        
        <div style="margin-top: 30px; overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 50%;">शीर्षक (Title)</th>
                        <th style="width: 15%;">पोस्ट तिथि</th>
                        <th style="width: 10%;">स्टेटस</th>
                        <th style="width: 20%;">एक्शन</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($vacancies)): ?>
                        <?php foreach ($vacancies as $vacancy): ?>
                            <tr>
                                <td><?php echo $vacancy['id']; ?></td>
                                <td><?php echo htmlspecialchars($vacancy['title']); ?></td>
                                <td><?php echo date('d M, Y', strtotime($vacancy['post_date'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo ($vacancy['status'] == 'live' ? 'status-live' : 'status-draft'); ?>">
                                        <?php echo ($vacancy['status'] == 'live' ? 'LIVE' : 'DRAFT'); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_vacancy.php?id=<?php echo $vacancy['id']; ?>" class="action-btn btn-edit">📝 एडिट</a>
                                    <a href="delete_vacancy.php?id=<?php echo $vacancy['id']; ?>" class="action-btn btn-delete" onclick="return confirm('क्या आप वाकई इस पोस्ट को डिलीट करना चाहते हैं? यह कार्रवाई वापस नहीं ली जा सकती।')">🗑️ डिलीट</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 20px; color: #6c757d;">
                                इस श्रेणी में कोई पोस्ट नहीं मिली।
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php 
if ($conn_ok) {
    $conn->close();
}
include __DIR__ . '/includes/admin_footer.php'; 
?>