<?php 
// 1. Database connection include
include 'includes/db_connect.php'; 
// 2. Header include
include 'includes/header.php'; 

// Pagination setup
$limit = 12; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// SQL Query: Admin code ke hisab se 'title' aur 'post_date' ka use kiya gaya hai
$query = "SELECT * FROM vacancies 
          WHERE (category = 'education') 
          AND status = 'live' 
          ORDER BY post_date DESC 
          LIMIT $offset, $limit";
$result = $conn->query($query);

// Total records count for pagination
$total_query = "SELECT COUNT(id) AS total FROM vacancies WHERE category = 'education' AND status = 'live'";
$total_result = $conn->query($total_query);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
?>

<style>
    /* Professional look for Education Section */
    .education-section { padding: 30px 0; background: #f4f7f6; min-height: 85vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }
    
    .page-heading { 
        background: #1e3a8a; 
        color: white; 
        padding: 18px; 
        text-align: center; 
        border-radius: 8px;
        margin-bottom: 30px;
        font-size: 22px;
        font-weight: 600;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .edu-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); 
        gap: 20px; 
    }

    .edu-list-item {
        background: #fff;
        border-left: 5px solid #1e3a8a; 
        padding: 18px;
        border-radius: 6px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: 0.3s;
        text-decoration: none;
        color: #333;
    }

    .edu-list-item:hover { 
        transform: translateY(-3px); 
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        border-left-color: #ef4444; /* Hover color change */
    }

    .edu-info h3 { 
        font-size: 16px; 
        margin: 0 0 8px 0; 
        color: #1e3a8a;
        line-height: 1.5;
        font-weight: 600;
    }

    .edu-date { font-size: 13px; color: #64748b; font-weight: 500; }
    .edu-date i { margin-right: 5px; color: #ef4444; }

    .new-tag {
        background: #ff0000;
        color: white;
        font-size: 10px;
        padding: 2px 7px;
        border-radius: 3px;
        font-weight: bold;
        text-transform: uppercase;
        margin-left: 8px;
        display: inline-block;
        vertical-align: middle;
        animation: blinker 1s linear infinite;
    }

    @keyframes blinker {
        50% { opacity: 0; }
    }

    .view-btn {
        background: #1e3a8a;
        color: #fff;
        padding: 8px 14px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 500;
        transition: 0.3s;
    }
    
    .edu-list-item:hover .view-btn { background: #ef4444; }

    .no-posts {
        text-align: center;
        padding: 80px 20px;
        background: white;
        border-radius: 10px;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    /* Pagination */
    .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 40px; }
    .pagination a { 
        padding: 10px 18px; 
        background: #fff; 
        color: #1e3a8a; 
        border: 1px solid #e2e8f0;
        text-decoration: none; 
        border-radius: 5px; 
        font-weight: 600;
        transition: 0.3s;
    }
    .pagination a.active { background: #1e3a8a; color: white; border-color: #1e3a8a; }
    .pagination a:hover:not(.active) { background: #f8fafc; border-color: #1e3a8a; }
</style>

<div class="education-section">
    <div class="container">
        
        <div class="page-heading">
            <i class="fas fa-graduation-cap"></i> शिक्षा समाचार एवं अपडेट (Education Updates 2025)
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="edu-grid">
                <?php while($row = $result->fetch_assoc()): ?>
                    <a href="vacancy_details.php?id=<?php echo $row['id']; ?>" class="edu-list-item">
                        <div class="edu-info">
                            <h3>
                                <?php echo htmlspecialchars($row['title']); ?>
                                <?php 
                                // Logic for NEW tag based on post_date
                                $post_time = strtotime($row['post_date']);
                                if (time() - $post_time < (2 * 24 * 60 * 60)) {
                                    echo '<span class="new-tag">New</span>';
                                }
                                ?>
                            </h3>
                            <div class="edu-date">
                                <i class="fas fa-calendar-alt"></i> अपडेट तिथि: <?php echo date('d M, Y', strtotime($row['post_date'])); ?>
                            </div>
                        </div>
                        <div class="view-btn">देखें <i class="fas fa-chevron-right"></i></div>
                    </a>
                <?php endwhile; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo ($page == $i) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="no-posts">
                <i class="fas fa-university fa-4x" style="margin-bottom: 20px; opacity: 0.2;"></i>
                <h3>अभी कोई एजुकेशन अपडेट उपलब्ध नहीं है।</h3>
                <p>नई जानकारी के लिए एडमिन पैनल से 'Education' कैटेगरी में पोस्ट जोड़ें।</p>
                <a href="index.php" style="color: #1e3a8a; font-weight:bold; text-decoration: none; margin-top: 20px; display: inline-block;">
                    <i class="fas fa-home"></i> होम पेज पर जाएं
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'includes/footer.php'; ?>