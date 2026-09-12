<?php
// 1. आवश्यक फाइलें शामिल करें
session_start();
include '../includes/db_connect.php'; 
include 'includes/admin_header.php'; 

// 2. एक्सेस नियंत्रण
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';
$filter_category = $_GET['category'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';

// 3. डिलीट लॉजिक हैंडल करें
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    $stmt = $conn->prepare("DELETE FROM vacancies WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $message = "✅ रिक्ति ID " . htmlspecialchars($delete_id) . " सफलतापूर्वक डिलीट कर दी गई।";
    } else {
        $error = "❌ रिक्ति डिलीट करने में त्रुटि: " . $stmt->error;
    }
    $stmt->close();
    
    // GET पैरामीटर्स के साथ रीडायरेक्ट करें
    header("Location: vacancy_list.php?status=" . ($error ? 'error' : 'success') . 
           "&category=" . urlencode($filter_category) . 
           "&status=" . urlencode($filter_status));
    exit;
}

// 4. फ़िल्टर के साथ डेटाबेस क्वेरी तैयार करें
$sql = "SELECT id, title, category, status, post_date FROM vacancies WHERE 1=1";
$params = [];
$types = '';

if ($filter_category !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $filter_category;
    $types .= 's';
}

if ($filter_status !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

$sql .= " ORDER BY post_date DESC";

// 5. स्टेटमेंट निष्पादित करें
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// 6. URL से स्टेटस मैसेज दिखाएं
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $message = $message ?: "✅ ऑपरेशन सफलतापूर्वक पूरा हुआ।";
    } elseif ($_GET['status'] == 'error') {
        $error = $error ?: "❌ ऑपरेशन में त्रुटि हुई।";
    }
}

// मैपिंग (तालिका में श्रेणी दिखाने के लिए)
$category_map = [
    'job' => 'Job (भर्ती)', 
    'result' => 'Result (परिणाम)', 
    'admit_card' => 'Admit Card', 
    'other' => 'Other (अन्य)'
];

?>

<div class="main-content">
    <div class="container">
        <h2 class="page-title">📜 रिक्ति सूची और प्रबंधन</h2>
        
        <?php if ($message): ?>
            <div class="alert success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="action-bar">
            <a href="add_vacancy.php" class="btn btn-add">➕ नई रिक्ति जोड़ें</a>
        </div>
        
        <div class="filter-controls">
            <form method="GET" action="vacancy_list.php" class="filter-form-admin">
                <div class="form-group-inline">
                    <label for="category">श्रेणी द्वारा फ़िल्टर करें:</label>
                    <select id="category" name="category" onchange="this.form.submit()">
                        <option value="all" <?php echo ($filter_category == 'all') ? 'selected' : ''; ?>>सभी श्रेणियाँ</option>
                        <option value="job" <?php echo ($filter_category == 'job') ? 'selected' : ''; ?>>Job</option>
                        <option value="result" <?php echo ($filter_category == 'result') ? 'selected' : ''; ?>>Result</option>
                        <option value="admit_card" <?php echo ($filter_category == 'admit_card') ? 'selected' : ''; ?>>Admit Card</option>
                        <option value="other" <?php echo ($filter_category == 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group-inline">
                    <label for="status">स्थिति द्वारा फ़िल्टर करें:</label>
                    <select id="status" name="status" onchange="this.form.submit()">
                        <option value="all" <?php echo ($filter_status == 'all') ? 'selected' : ''; ?>>सभी स्थितियाँ</option>
                        <option value="live" <?php echo ($filter_status == 'live') ? 'selected' : ''; ?>>Live</option>
                        <option value="draft" <?php echo ($filter_status == 'draft') ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>
            </form>
        </div>


        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>शीर्षक</th>
                            <th>श्रेणी</th>
                            <th>पोस्ट तिथि</th>
                            <th>स्थिति</th>
                            <th>एक्शन</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td class="title-column"><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><span class="category-badge-admin <?php echo $row['category']; ?>"><?php echo $category_map[$row['category']]; ?></span></td>
                                <td><?php echo date('d M, Y', strtotime($row['post_date'])); ?></td>
                                <td><span class="status-badge <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                <td>
                                    <a href="edit_vacancy.php?id=<?php echo $row['id']; ?>" class="btn btn-action btn-edit" title="एडिट करें">✏️</a>
                                    
                                    <a href="vacancy_list.php?delete_id=<?php echo $row['id']; ?>&category=<?php echo htmlspecialchars($filter_category); ?>&status=<?php echo htmlspecialchars($filter_status); ?>" 
                                       onclick="return confirm('क्या आप वाकई इस रिक्ति को डिलीट करना चाहते हैं?');" 
                                       class="btn btn-action btn-delete" 
                                       title="डिलीट करें">
                                        🗑️
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert info">इस फ़िल्टर के तहत कोई रिक्ति उपलब्ध नहीं है।</div>
        <?php endif; ?>

    </div>
</div>

<?php 
$stmt->close();
$conn->close();
include 'includes/admin_footer.php'; 
?>