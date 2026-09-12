<?php
session_start();
// सुनिश्चित करें कि एडमिन लॉग इन है
if (!isset($_SESSION['admin_logged_in'])) {
    header("location: login.php");
    exit;
}

include '../includes/db_connect.php'; // डेटाबेस कनेक्शन

$message = "";
$vacancy_data = null; 

// 1. URL से रिक्ति ID प्राप्त करें
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("location: manage_vacancies.php");
    exit;
}
$vacancy_id = intval($_GET['id']);

// 2. मौजूदा डेटा लोड करने का फ़ंक्शन
function load_vacancy_data($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM vacancies WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("location: manage_vacancies.php");
        exit;
    }
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

// 3. फॉर्म सबमिशन (POST) पर अपडेट करें
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $category = $_POST['category'];
    $state = $_POST['state']; 
    $last_date = !empty($_POST['last_date']) ? $_POST['last_date'] : NULL;
    $total_posts = trim($_POST['total_posts']);
    $short_desc = trim($_POST['short_desc']);
    $long_details = $_POST['long_details'];
    $description = $_POST['description']; // Instructions
    $full_details = $_POST['full_details']; // Extra Details
    $eligibility = $_POST['eligibility'];
    $important_dates = $_POST['important_dates']; // Dates Text
    $apply_link = trim($_POST['apply_link']);
    $official_website = trim($_POST['official_website']);
    $result_link = trim($_POST['result_link']);
    $status = $_POST['status'];
    $application_fee = trim($_POST['application_fee']);

    if (empty($title) || empty($category)) {
        $message = "<div class='alert error'>कृपया शीर्षक और श्रेणी भरें।</div>";
    } else {
        // SQL अपडेट
        $sql = "UPDATE vacancies SET 
                title=?, category=?, state=?, last_date=?, total_posts=?, 
                short_desc=?, long_details=?, description=?, full_details=?, 
                eligibility=?, important_dates=?, apply_link=?, official_website=?, 
                result_link=?, status=?, application_fee=? 
                WHERE id=?";
        
        $stmt_update = $conn->prepare($sql);
        
        // 16 strings (s) + 1 integer (i) = 17 parameters
        $stmt_update->bind_param("ssssssssssssssssi", 
            $title, $category, $state, $last_date, $total_posts, 
            $short_desc, $long_details, $description, $full_details, 
            $eligibility, $important_dates, $apply_link, $official_website, 
            $result_link, $status, $application_fee, $vacancy_id
        );

        if ($stmt_update->execute()) {
            $message = "<div class='alert success'>✅ डेटा सफलतापूर्वक अपडेट किया गया!</div>";
            $vacancy_data = load_vacancy_data($conn, $vacancy_id); 
        } else {
            $message = "<div class='alert error'>त्रुटि: " . $conn->error . "</div>";
        }
        $stmt_update->close();
    }
}

// यदि डेटा लोड नहीं हुआ है
if (!$vacancy_data) {
    $vacancy_data = load_vacancy_data($conn, $vacancy_id);
}

include 'includes/admin_header.php'; 
?>

<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>

<style>
    .admin-form label { display: block; margin-top: 15px; font-weight: bold; color: #334155; }
    .admin-form input, .admin-form select, .admin-form textarea { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
    .btn-submit { background: #2563eb; color: white; border: none; padding: 15px 25px; cursor: pointer; border-radius: 4px; margin-top: 25px; width: 100%; font-size: 16px; font-weight: bold; }
    .btn-submit:hover { background: #1d4ed8; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .alert { padding: 12px; border-radius: 4px; margin-bottom: 20px; font-weight: bold; }
    .success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
</style>

<div id="page-content-wrapper">
    <div class="container-fluid" style="max-width: 1000px; margin: auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2>✏️ वैकेंसी अपडेट करें (ID: <?php echo $vacancy_id; ?>)</h2>
        
        <?php echo $message; ?>

        <form method="POST" class="admin-form">
            <!-- Post Title (मुख्य शीर्षक) -->
            <label>Post Title (मुख्य शीर्षक):</label>
            <input type="text" name="title" required value="<?php echo htmlspecialchars($vacancy_data['title'] ?? ''); ?>">
            
            <div class="form-row">
                <!-- Category (श्रेणी) -->
                <div>
                    <label>Category (श्रेणी):</label>
                    <select name="category" required>
                        <?php $c = $vacancy_data['category'] ?? ''; ?>
                        <option value="job" <?php echo ($c == 'job' ? 'selected' : ''); ?>>🔥 नई सरकारी नौकरी</option>
                        <option value="result" <?php echo ($c == 'result' ? 'selected' : ''); ?>>✅ रिजल्ट</option>
                        <option value="admit_card" <?php echo ($c == 'admit_card' ? 'selected' : ''); ?>>🎟️ एडमिट कार्ड</option>
                        <option value="scheme" <?php echo ($c == 'scheme' ? 'selected' : ''); ?>>🎁 सरकारी योजना (Scheme)</option>
                        <option value="education" <?php echo ($c == 'education' ? 'selected' : ''); ?>>📚 शिक्षा (Education)</option>
                        <option value="admission" <?php echo ($c == 'admission' ? 'selected' : ''); ?>>🎓 एडमिशन (Admission)</option>
                        <option value="pdf_form" <?php echo ($c == 'pdf_form' ? 'selected' : ''); ?>>📄 PDF फॉर्म्स</option>
                        <option value="other" <?php echo ($c == 'other' ? 'selected' : ''); ?>>📁 अन्य अपडेट</option>
                    </select>
                </div>
                <!-- State (राज्य चुनें) -->
                <div>
                    <label>State (राज्य चुनें):</label>
                    <select name="state" required>
                        <?php $st = $vacancy_data['state'] ?? ''; ?>
                        <option value="All India" <?php echo ($st == 'All India' ? 'selected' : ''); ?>>All India</option>
                        <option value="Bihar" <?php echo ($st == 'Bihar' ? 'selected' : ''); ?>>Bihar</option>
                        <option value="Rajasthan" <?php echo ($st == 'Rajasthan' ? 'selected' : ''); ?>>Rajasthan</option>
                        <option value="Uttar Pradesh" <?php echo ($st == 'Uttar Pradesh' ? 'selected' : ''); ?>>Uttar Pradesh</option>
                        <option value="Haryana" <?php echo ($st == 'Haryana' ? 'selected' : ''); ?>>Haryana</option>
                        <option value="Madhya Pradesh" <?php echo ($st == 'Madhya Pradesh' ? 'selected' : ''); ?>>Madhya Pradesh</option>
                        <option value="Punjab" <?php echo ($st == 'Punjab' ? 'selected' : ''); ?>>Punjab</option>
                        <option value="Uttarakhand" <?php echo ($st == 'Uttarakhand' ? 'selected' : ''); ?>>Uttarakhand</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <!-- Last Date (अंतिम तिथि) -->
                <div>
                    <label style="color:red;">Last Date (अंतिम तिथि):</label>
                    <input type="date" name="last_date" value="<?php echo $vacancy_data['last_date'] ?? ''; ?>">
                </div>
                <!-- Total Posts (कुल पद) -->
                <div>
                    <label>Total Posts (कुल पद):</label>
                    <input type="text" name="total_posts" value="<?php echo htmlspecialchars($vacancy_data['total_posts'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <!-- Status & Application Fee -->
                <div>
                    <label>स्थिति (Status):</label>
                    <select name="status">
                        <?php $s = $vacancy_data['status'] ?? 'draft'; ?>
                        <option value="live" <?php echo ($s == 'live' ? 'selected' : ''); ?>>Live (Public)</option>
                        <option value="draft" <?php echo ($s == 'draft' ? 'selected' : ''); ?>>Draft (Private)</option>
                    </select>
                </div>
                <div>
                    <label>आवेदन शुल्क (Application Fee):</label>
                    <input type="text" name="application_fee" value="<?php echo htmlspecialchars($vacancy_data['application_fee'] ?? ''); ?>">
                </div>
            </div>

            <!-- Short Description (छोटा विवरण) -->
            <label>Short Description (छोटा विवरण):</label>
            <input type="text" name="short_desc" value="<?php echo htmlspecialchars($vacancy_data['short_desc'] ?? ''); ?>">

            <!-- भर्ती की पूरी जानकारी (Long Details) -->
            <label>भर्ती की पूरी जानकारी (Long Details):</label>
            <textarea name="long_details" id="long_editor"><?php echo htmlspecialchars($vacancy_data['long_details'] ?? ''); ?></textarea>
            
            <!-- आवेदन निर्देश (Instructions) -->
            <label>आवेदन निर्देश (Instructions):</label>
            <textarea name="description" id="desc_editor"><?php echo htmlspecialchars($vacancy_data['description'] ?? ''); ?></textarea>

            <!-- अन्तरिक्त जानकारी (Extra Details) -->
            <label>अन्तरिक्त जानकारी (Extra Details):</label>
            <textarea name="full_details" id="full_editor"><?php echo htmlspecialchars($vacancy_data['full_details'] ?? ''); ?></textarea>

            <div class="form-row">
                <!-- पात्रता (Eligibility) -->
                <div>
                    <label>पात्रता (Eligibility):</label>
                    <textarea name="eligibility" rows="4"><?php echo htmlspecialchars($vacancy_data['eligibility'] ?? ''); ?></textarea>
                </div>
                <!-- महत्वपूर्ण तिथियां (Dates Text) -->
                <div>
                    <label>महत्वपूर्ण तिथियां (Dates Text):</label>
                    <textarea name="important_dates" rows="4"><?php echo htmlspecialchars($vacancy_data['important_dates'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-row" style="grid-template-columns: 1fr 1fr 1fr;">
                <!-- Apply Link -->
                <div>
                    <label>Apply Link:</label>
                    <input type="text" name="apply_link" value="<?php echo htmlspecialchars($vacancy_data['apply_link'] ?? ''); ?>">
                </div>
                <!-- Official Website -->
                <div>
                    <label>Official Website:</label>
                    <input type="text" name="official_website" value="<?php echo htmlspecialchars($vacancy_data['official_website'] ?? ''); ?>">
                </div>
                <!-- Result/Other Link -->
                <div>
                    <label>Result/Other Link:</label>
                    <input type="text" name="result_link" value="<?php echo htmlspecialchars($vacancy_data['result_link'] ?? ''); ?>">
                </div>
            </div>
            
            <button type="submit" class="btn-submit">💾 सबमिट करें और अपडेट करें</button>
            <center><a href="manage_vacancies.php" style="display:inline-block; margin-top:20px; color:#666; text-decoration:none;">← वापस जाएँ</a></center>
        </form>
    </div>
</div>

<script>
    CKEDITOR.replace('long_editor', { height: 300 });
    CKEDITOR.replace('desc_editor', { height: 200 });
    CKEDITOR.replace('full_editor', { height: 200 });
</script>

<?php include 'includes/admin_footer.php'; ?>