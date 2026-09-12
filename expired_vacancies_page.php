<?php
// 1. डेटाबेस फाइल चेक और शामिल करें
$db_file = 'includes/db_connect.php'; 
if (file_exists($db_file)) {
    include $db_file;
} else {
    die("<div style='padding:20px; background:#fee2e2; color:#b91c1c; font-family:sans-serif;'><b>Error:</b> db_connect.php नहीं मिली।</div>");
}

if (!isset($conn) || $conn->connect_error) {
    die("Database Connection Failed.");
}

include 'includes/header.php'; 

// --- PAGINATION LOGIC START ---
$limit = 10; // एक पेज पर कितनी वैकेंसी दिखानी हैं
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// सर्च लॉजिक
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = "";
$search_param_val = "";

if (!empty($search_query)) {
    $search_condition = " WHERE (title LIKE ? OR short_desc LIKE ? OR state LIKE ?)";
    $search_param_val = '%' . $search_query . '%';
}

// कुल रिकॉर्ड्स की गिनती करें (Total Pages निकालने के लिए)
$count_sql = "SELECT COUNT(*) FROM expired_vacancies" . $search_condition;
$count_stmt = $conn->prepare($count_sql);
if (!empty($search_query)) {
    $count_stmt->bind_param("sss", $search_param_val, $search_param_val, $search_param_val);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_records / $limit);
// --- PAGINATION LOGIC END ---

// मुख्य SQL Query (LIMIT और OFFSET के साथ)
$sql = "SELECT id, title, category, state, short_desc, post_date, last_date FROM expired_vacancies" . $search_condition . " ORDER BY last_date DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

if (!empty($search_query)) {
    $stmt->bind_param("sssii", $search_param_val, $search_param_val, $search_param_val, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

$expired_list = [];
while($row = $result->fetch_assoc()) {
    $expired_list[] = $row;
}

// राज्यवार सूची के लिए (बिना लिमिट के छोटी क्वेरी)
$states_res = $conn->query("SELECT DISTINCT state FROM expired_vacancies WHERE state != '' LIMIT 20");
?>

<div class="archive-hero" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 60px 0; color: white; text-align: center;">
    <div class="container" style="max-width: 800px; margin: 0 auto; padding: 0 20px;">
        <h1 style="font-size: 2.5rem; margin-bottom: 10px; font-weight: 700;">पुरानी (Expired) वैकेंसी आर्काइव</h1>
        <p style="opacity: 0.8; margin-bottom: 30px;">कुल भर्तियां: <?php echo $total_records; ?> | पेज <?php echo $page; ?> / <?php echo $total_pages; ?></p>
        
        <form action="" method="GET" style="display: flex; background: white; padding: 5px; border-radius: 50px; box-shadow: 0 10px 25px rgba(0,0,0,0.3);">
            <input type="text" name="search" placeholder="खोजें..." value="<?php echo htmlspecialchars($search_query); ?>" style="flex: 1; border: none; padding: 12px 25px; border-radius: 50px; outline: none; font-size: 16px;">
            <button type="submit" style="background: #ef4444; color: white; border: none; padding: 12px 30px; border-radius: 50px; cursor: pointer; font-weight: 600;">खोजें</button>
        </form>
    </div>
</div>

<div class="container" style="max-width: 1300px; margin: 40px auto; padding: 0 15px; display: grid; grid-template-columns: 1fr 350px; gap: 30px;">
    
    <main class="main-content">
        <div class="job-list">
            <?php if (!empty($expired_list)): ?>
                <?php foreach ($expired_list as $post): ?>
                    <div class="archive-card" style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 20px; transition: 0.3s;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 12px;">
                            <span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 4px; font-weight: 700;">EXPIRED</span>
                            <span style="color: #ef4444; font-weight: 600;"><i class="far fa-calendar-times"></i> अंतिम तिथि: <?php echo date('d M Y', strtotime($post['last_date'])); ?></span>
                        </div>
                        <h3 style="margin: 0 0 10px 0;"><a href="vacancy_details.php?id=<?php echo $post['id']; ?>" style="color: #1e293b; text-decoration: none; font-size: 18px; font-weight: 600;"><?php echo htmlspecialchars($post['title']); ?></a></h3>
                        <p style="color: #64748b; font-size: 14px;"><?php echo htmlspecialchars(mb_strimwidth($post['short_desc'], 0, 150, "...")); ?></p>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #f1f5f9; font-size: 13px; margin-top: 10px;">
                            <span style="color: #94a3b8;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($post['state']); ?></span>
                            <a href="vacancy_details.php?id=<?php echo $post['id']; ?>" style="color: #1e3a8a; font-weight: 600; text-decoration: none;">विवरण देखें &rarr;</a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 40px;">
                    <?php if($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search_query); ?>" style="padding: 10px 20px; background: white; border: 1px solid #dee2e6; border-radius: 8px; text-decoration: none; color: #1e3a8a;">&laquo; Previous</a>
                    <?php endif; ?>

                    <?php for($i = 1; $i <= $total_pages; $i++): 
                        if($i == $page || ($i <= $page + 2 && $i >= $page - 2)): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>" style="padding: 10px 15px; background: <?php echo ($i == $page) ? '#1e3a8a' : 'white'; ?>; color: <?php echo ($i == $page) ? 'white' : '#1e3a8a'; ?>; border: 1px solid #dee2e6; border-radius: 8px; text-decoration: none;">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; endfor; ?>

                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search_query); ?>" style="padding: 10px 20px; background: white; border: 1px solid #dee2e6; border-radius: 8px; text-decoration: none; color: #1e3a8a;">Next &raquo;</a>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div style="background: white; padding: 60px; border-radius: 12px; text-align: center; border: 1px solid #e2e8f0;">
                    <i class="fas fa-folder-open" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 20px;"></i>
                    <p style="color: #64748b;">कोई वैकेंसी नहीं मिली।</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <aside class="sidebar">
        <div class="sidebar-widget" style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
            <h3 style="font-size: 16px; margin-bottom: 15px; color: #1e3a8a;"><i class="fas fa-map-marked-alt"></i> राज्यवार आर्काइव</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                <?php while($st = $states_res->fetch_assoc()): ?>
                    <a href="?search=<?php echo urlencode($st['state']); ?>" style="font-size: 12px; background: #f1f5f9; padding: 6px 12px; border-radius: 6px; text-decoration: none; color: #475569; border: 1px solid #e2e8f0;">
                        <?php echo $st['state']; ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    </aside>
</div>

<style>
    .archive-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); border-color: #3b82f6; }
    .pagination a:hover { background: #f8fafc !important; border-color: #1e3a8a !important; }
    @media (max-width: 900px) {
        .container { grid-template-columns: 1fr; }
    }
</style>

<?php 
$stmt->close();
$conn->close();
include 'includes/footer.php'; 
?>