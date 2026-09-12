<?php
// सत्र शुरू करें और सुरक्षा जाँच करें
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("location: login.php");
    exit;
}

// 1. डेटाबेस कनेक्शन और हेडर फ़ाइलें शामिल करें
// ध्यान दें: db_connect.php admin फोल्डर से एक लेवल ऊपर है
include '../includes/db_connect.php'; 
include 'includes/admin_header.php'; 

$category = 'result'; // श्रेणी को 'result' पर फिक्स करें
$vacancy_id = $_GET['id'] ?? null;
$post = null;
$error_message = '';
$is_edit = false;

// 2. संपादन मोड (Edit Mode) के लिए डेटा फ़ेच करें
if ($vacancy_id && is_numeric($vacancy_id)) {
    $is_edit = true;
    
    // केवल result श्रेणी की पोस्ट fetching 
    $sql = "SELECT * FROM vacancies WHERE id = ? AND category = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("is", $vacancy_id, $category);
        $stmt->execute();
        $result = $stmt->get_result();
        $post = $result->fetch_assoc();
        $stmt->close();

        if (!$post) {
            $error_message = "त्रुटि: परिणाम पोस्ट ID उपलब्ध नहीं है या यह Result श्रेणी में नहीं है।";
            $is_edit = false;
        }
    } else {
        $error_message = "डेटाबेस तैयारी त्रुटि।";
    }
}

// 3. फॉर्म में उपयोग के लिए मौजूदा/डिफ़ॉल्ट डेटा सेट करें
// NULL एक्सेस चेतावनी से बचने के लिए सुरक्षित जाँच
if ($post) {
    // Edit Mode: यदि डेटा सफलतापूर्वक फ़ेच हुआ है
    $form_title = $post['title'] ?? '';
    $form_description = $post['full_details'] ?? ''; 
    $form_post_date = $post['post_date'] ? date('Y-m-d', strtotime($post['post_date'])) : date('Y-m-d');
    $form_status = $post['status'] ?? 'live'; 
    $form_result_link = $post['result_link'] ?? ''; 
    $form_official_website = $post['official_website'] ?? ''; 
} else {
    // Add Mode (या Edit में रिकॉर्ड नहीं मिला)
    $form_title = '';
    $form_description = ''; 
    $form_post_date = date('Y-m-d');
    $form_status = 'live'; 
    $form_result_link = ''; 
    $form_official_website = ''; 
}

// 4. फॉर्म हेडिंग
$page_title = $is_edit ? "📋 परिणाम पोस्ट संपादित करें (ID: $vacancy_id)" : "📋 नई परिणाम पोस्ट जोड़ें";
?>

<div id="page-content-wrapper">
    <div class="container-fluid">
        <h2><?php echo $page_title; ?></h2>
        
        <?php if ($error_message): ?>
            <div class='alert alert-danger'><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form class="admin-form" action="process_result.php" method="POST">
            
            <?php if ($is_edit): ?>
                <input type="hidden" name="post_id" value="<?php echo $vacancy_id; ?>">
            <?php endif; ?>
            
            <input type="hidden" name="category" value="<?php echo $category; ?>">
            
            <label for="title">पोस्ट का शीर्षक (Title):</label>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($form_title); ?>" required>
            
            <label for="post_date">परिणाम/घोषणा की तिथि (Post Date):</label>
            <input type="date" name="post_date" id="post_date" value="<?php echo htmlspecialchars($form_post_date); ?>" required>

            <hr>
            
            <label for="result_link">✅ परिणाम डाउनलोड लिंक (Result Download Link):</label>
            <input type="url" name="result_link" id="result_link" placeholder="https://www.example.com/result_pdf_link" value="<?php echo htmlspecialchars($form_result_link); ?>">
            <small class="form-text text-muted">परिणाम फ़ाइल या मेरिट सूची का सीधा URL डालें।</small>

            <label for="official_website">🌐 आधिकारिक वेबसाइट लिंक (Official Website Link):</label>
            <input type="url" name="official_website" id="official_website" placeholder="https://www.example.com" value="<?php echo htmlspecialchars($form_official_website); ?>">
            <small class="form-text text-muted">संबंधित विभाग/बोर्ड की आधिकारिक वेबसाइट का URL।</small>

            <hr>

            <label for="description">परिणाम का विस्तृत विवरण (Description):</label>
            <textarea name="description" id="description" rows="6" placeholder="कटऑफ, अगले चरणों या महत्वपूर्ण नोटिस के बारे में लिखें।"><?php echo htmlspecialchars($form_description); ?></textarea>
            <small class="form-text text-muted">कटऑफ, अगले चरणों या महत्वपूर्ण नोटिस के बारे में लिखें।</small>

            <label for="status">स्थिति (Status):</label>
            <select name="status" id="status" required>
                <option value="live" <?php echo ($form_status == 'live') ? 'selected' : ''; ?>>Live (सक्रिय)</option>
                <option value="draft" <?php echo ($form_status == 'draft') ? 'selected' : ''; ?>>Draft (अधूरा)</option>
            </select>

            <button type="submit">
                <?php echo $is_edit ? "परिणाम अपडेट करें" : "परिणाम पोस्ट करें"; ?>
            </button>
            <a href="dashboard.php" class="btn-primary" style="background: #6c757d; margin-left: 10px;">रद्द करें</a>
        </form>
    </div>
</div>

<?php 
// फुटर शामिल करें
include 'includes/admin_footer.php'; 
// कनेक्शन बंद करें
if (isset($conn)) {
    $conn->close();
}
?>