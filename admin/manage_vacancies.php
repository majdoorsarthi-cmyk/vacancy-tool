<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("location: login.php");
    exit;
}
include '../includes/db_connect.php';
include 'includes/admin_header.php'; 

$delete_message = '';
$filter_category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// 1. DELETE लॉजिक
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM vacancies WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $delete_message = "<div class='alert alert-success'>✅ पोस्ट ID $id को सफलतापूर्वक हटा दिया गया है।</div>";
    } else {
        $delete_message = "<div class='alert alert-danger'>❌ त्रुटि: पोस्ट को हटाया नहीं जा सका।</div>";
    }
    $stmt->close();
}

// 2. SQL Query अपडेट (state और last_date को शामिल किया गया है)
$sql = "SELECT id, title, category, post_date, status, state, last_date FROM vacancies WHERE 1=1";

if ($filter_category) {
    $sql .= " AND category = '$filter_category'";
}

if ($search_query) {
    $sql .= " AND title LIKE '%$search_query%'";
}

$sql .= " ORDER BY post_date DESC";
$result = $conn->query($sql);

$page_title = $filter_category ? strtoupper($filter_category) . " प्रबंधन" : "📝 सभी पोस्ट प्रबंधित करें";
?>

<style>
    #page-content-wrapper {
        background: #f8f9fa;
        padding: 30px;
        min-height: 100vh;
    }

    .header-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .search-filter-box {
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 25px;
    }

    .filter-btns {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        flex-wrap: wrap;
    }

    .filter-link {
        padding: 6px 15px;
        border-radius: 20px;
        text-decoration: none;
        font-size: 14px;
        background: #eee;
        color: #555;
        transition: 0.3s;
    }

    .filter-link.active {
        background: #4361ee;
        color: #fff;
    }

    .admin-table-container {
        background: #fff;
        border-radius: 12px;
        overflow-x: auto; /* मोबाइल के लिए स्क्रॉल */
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px; /* टेबल सिकुड़े नहीं */
    }

    .admin-table th {
        background: #f1f3f9;
        color: #333;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #eee;
    }

    .admin-table td {
        padding: 15px;
        border-bottom: 1px solid #f1f1f1;
        vertical-align: middle;
    }

    .category-badge {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }

    /* नई श्रेणियों के लिए रंग */
    .badge-job { background: #e3f2fd; color: #1976d2; }
    .badge-result { background: #fff3e0; color: #f57c00; }
    .badge-admit { background: #fce4ec; color: #c2185b; }
    .badge-scheme { background: #e8f5e9; color: #2e7d32; }
    .badge-education { background: #f3e5f5; color: #7b1fa2; }
    .badge-admission { background: #fffde7; color: #fbc02d; }
    .badge-pdf_form { background: #efebe9; color: #5d4037; }

    .state-badge {
        background: #f0f0f0;
        color: #444;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
    }

    .status-dot {
        height: 10px;
        width: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }

    .status-live { background: #2ecc71; }
    .status-draft { background: #bdc3c7; }

    .btn-action {
        padding: 6px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
    }

    .btn-edit { background: #eef2ff; color: #4361ee; margin-right: 5px; }
    .btn-delete { background: #fff1f0; color: #e74c3c; }

    .btn-edit:hover { background: #4361ee; color: #fff; }
    .btn-delete:hover { background: #e74c3c; color: #fff; }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .alert-success { background: #d4edda; color: #155724; }
    .alert-danger { background: #f8d7da; color: #721c24; }
</style>

<div id="page-content-wrapper">
    <div class="container-fluid">
        
        <div class="header-section">
            <h2 style="margin:0; font-weight:700; color:#2c3e50;"><?php echo $page_title; ?></h2>
            <a href="add_vacancy.php" class="btn-action" style="background:#2ecc71; color:#fff; padding:10px 20px;">➕ नई पोस्ट जोड़ें</a>
        </div>

        <?php if ($delete_message) echo $delete_message; ?>

        <div class="search-filter-box">
            <form action="" method="GET" style="display:flex; gap:10px; flex-wrap:wrap;">
                <input type="text" name="search" placeholder="शीर्षक से खोजें..." value="<?php echo htmlspecialchars($search_query); ?>" style="flex:1; min-width:250px; padding:10px; border:1px solid #ddd; border-radius:8px;">
                <button type="submit" class="btn-action" style="background:#4361ee; color:#fff; cursor:pointer; border:none; padding:0 25px;">खोजें</button>
                <a href="manage_vacancies.php" class="btn-action" style="background:#eee; color:#333;">रीसेट</a>
            </form>

            <div class="filter-btns">
                <span>फ़िल्टर:</span>
                <a href="manage_vacancies.php" class="filter-link <?php echo !$filter_category ? 'active' : ''; ?>">सभी</a>
                <a href="?category=job" class="filter-link <?php echo $filter_category == 'job' ? 'active' : ''; ?>">Jobs</a>
                <a href="?category=result" class="filter-link <?php echo $filter_category == 'result' ? 'active' : ''; ?>">Results</a>
                <a href="?category=admit_card" class="filter-link <?php echo $filter_category == 'admit_card' ? 'active' : ''; ?>">Admit Card</a>
                <a href="?category=scheme" class="filter-link <?php echo $filter_category == 'scheme' ? 'active' : ''; ?>">Schemes</a>
                <a href="?category=education" class="filter-link <?php echo $filter_category == 'education' ? 'active' : ''; ?>">Education</a>
                <a href="?category=admission" class="filter-link <?php echo $filter_category == 'admission' ? 'active' : ''; ?>">Admission</a>
                <a href="?category=pdf_form" class="filter-link <?php echo $filter_category == 'pdf_form' ? 'active' : ''; ?>">PDF Forms</a>
            </div>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>शीर्षक</th>
                        <th width="120">श्रेणी</th>
                        <th width="120">राज्य</th>
                        <th width="130">अंतिम तिथि</th>
                        <th width="100">स्टेटस</th>
                        <th width="180">कार्य</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            // डायनामिक कैटेगरी क्लास
                            $cat = $row['category'];
                            $cat_class = 'badge-' . ($cat == 'admit_card' ? 'admit' : $cat);
                            
                            $status_class = $row['status'] == 'live' ? 'status-live' : 'status-draft';
                            $l_date = ($row['last_date'] && $row['last_date'] != '0000-00-00') ? date('d/m/Y', strtotime($row['last_date'])) : 'N/A';
                            ?>
                            <tr>
                                <td>#<?php echo $row["id"]; ?></td>
                                <td style="font-weight: 500; color: #2c3e50;">
                                    <?php echo htmlspecialchars($row["title"]); ?>
                                </td>
                                <td>
                                    <span class="category-badge <?php echo $cat_class; ?>">
                                        <?php echo str_replace('_', ' ', ucfirst($row["category"])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="state-badge"><?php echo htmlspecialchars($row["state"] ?: 'All India'); ?></span>
                                </td>
                                <td style="color: #e74c3c; font-weight: 600;">
                                    <?php echo $l_date; ?>
                                </td>
                                <td>
                                    <span class="status-dot <?php echo $status_class; ?>"></span>
                                    <?php echo ucfirst($row["status"]); ?>
                                </td>
                                <td>
                                    <a href="edit_vacancy.php?id=<?php echo $row["id"]; ?>" class="btn-action btn-edit">संपादित</a>
                                    <a href="?action=delete&id=<?php echo $row["id"]; ?>" class="btn-action btn-delete" onclick="return confirm('क्या आप वाकई इस पोस्ट को हटाना चाहते हैं?')">हटाएं</a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center; padding:30px; color:#888;'>कोई पोस्ट नहीं मिली।</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php 
include 'includes/admin_footer.php'; 
?>