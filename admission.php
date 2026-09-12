<?php 
include 'includes/db_connect.php'; 
include 'includes/header.php'; 

$limit = 12; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// SQL Query for Admission category
$query = "SELECT * FROM vacancies 
          WHERE category = 'admission' AND status = 'live' 
          ORDER BY post_date DESC LIMIT $offset, $limit";
$result = $conn->query($query);

$total_query = "SELECT COUNT(id) AS total FROM vacancies WHERE category = 'admission' AND status = 'live'";
$total_result = $conn->query($total_query);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
?>

<style>
    .admission-section { padding: 30px 0; background: #fdfdfd; min-height: 85vh; font-family: 'Segoe UI', Tahoma, sans-serif; }
    .container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }
    .page-heading { background: #065f46; color: white; padding: 18px; text-align: center; border-radius: 8px; margin-bottom: 30px; font-size: 22px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .adm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
    .adm-card { background: #fff; border-top: 4px solid #065f46; padding: 20px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: 0.3s; text-decoration: none; color: #333; display: block; }
    .adm-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .adm-card h3 { font-size: 17px; color: #065f46; margin-bottom: 10px; line-height: 1.5; height: 50px; overflow: hidden; }
    .adm-meta { font-size: 13px; color: #666; border-top: 1px solid #eee; pt: 10px; margin-top: 10px; padding-top: 10px; display: flex; justify-content: space-between; }
    .btn-apply { background: #065f46; color: #fff; padding: 6px 12px; border-radius: 4px; font-size: 12px; }
</style>

<div class="admission-section">
    <div class="container">
        <div class="page-heading"><i class="fas fa-university"></i> विश्वविद्यालय प्रवेश सूचना (Admission Updates 2025)</div>
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="adm-grid">
                <?php while($row = $result->fetch_assoc()): ?>
                    <a href="vacancy_details.php?id=<?php echo $row['id']; ?>" class="adm-card">
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <div class="adm-meta">
                            <span><i class="fas fa-clock"></i> अंतिम तिथि: <?php echo !empty($row['last_date']) ? date('d/m/Y', strtotime($row['last_date'])) : 'जल्द उपलब्ध'; ?></span>
                            <span class="btn-apply">विवरण देखें</span>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:100px;"><h3>अभी कोई एडमिशन सूचना नहीं है।</h3></div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>