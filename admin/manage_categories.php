<?php 
// सत्र शुरू करें और जांचें कि एडमिन लॉग इन है या नहीं
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("location: login.php");
    exit;
}

// -------------------------------------------------------------------------
// ✅ त्रुटि सुधार 1: सही पथ (Correct Path)
// 'admin' फ़ोल्डर से 'includes' फ़ोल्डर तक पहुँचने के लिए केवल एक स्तर ऊपर जाएँ
// -------------------------------------------------------------------------
include '../includes/db_connect.php'; 
include 'includes/admin_header.php'; 

$message = '';
$error = '';
$conn_ok = isset($conn); // $conn कनेक्शन वेरिएबल की उपलब्धता की जाँच करें

// यदि डेटाबेस कनेक्शन विफल हो जाए
if (!$conn_ok) {
    $error = "❌ गंभीर त्रुटि: डेटाबेस कनेक्शन विफल। कृपया सुनिश्चित करें कि 'db_connect.php' फ़ाइल मौजूद है और उसमें कनेक्शन विवरण सही हैं।";
}

// --- 1. नई श्रेणी जोड़ने का लॉजिक (Add New Category Logic) ---
if ($conn_ok && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $new_category = trim(strtolower($_POST['category_name']));

    // श्रेणी का नाम URL-फ्रेंडली स्लॉग (slug) में बदलें
    $category_slug = str_replace(' ', '_', $new_category);

    // यह सुनिश्चित करने के लिए कि यह डुप्लिकेट नहीं है, हम मौजूदा 'vacancies' तालिका से जांच करेंगे
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM vacancies WHERE category = ?");
    $check_stmt->bind_param("s", $category_slug);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    // चूंकि हम सीधे 'vacancies' तालिका का उपयोग कर रहे हैं, इसलिए यह जांच थोड़ी जटिल है।
    if (in_array($category_slug, ['job', 'result', 'admit_card', 'other'])) {
        $error = "यह श्रेणी (Category) पहले से ही सिस्टम में मौजूद है या आरक्षित है।";
    } else {
        $message = "नई श्रेणी '{$new_category}' को सीधे 'vacancies' तालिका में नहीं जोड़ा जा सकता। कृपया DB में एक 'categories' तालिका बनाएँ और फिर से प्रयास करें।";
    }
}


// --- 2. श्रेणी हटाने का लॉजिक (Delete Category Logic) ---
if ($conn_ok && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['slug'])) {
    $slug_to_delete = $_GET['slug'];

    // सुरक्षा: आरक्षित श्रेणियों को हटाने की अनुमति न दें
    if (in_array($slug_to_delete, ['job', 'result', 'admit_card', 'other'])) {
        $error = "आप आरक्षित या मुख्य श्रेणियों को हटा नहीं सकते।";
    } else {
        $error = "डिलीट ऑपरेशन की अनुमति नहीं है क्योंकि 'categories' तालिका मौजूद नहीं है। कृपया DB स्कीमा बदलें।";
    }
}


// --- 3. मौजूदा श्रेणियाँ प्राप्त करें (Fetch Existing Categories) ---
$categories_result = false;
if ($conn_ok) {
    // हम उन सभी अद्वितीय श्रेणियों को प्राप्त करते हैं जो वर्तमान में 'vacancies' तालिका में उपयोग में हैं
    $categories_query = "SELECT DISTINCT category, COUNT(id) as post_count FROM vacancies GROUP BY category ORDER BY post_count DESC";
    // ⚠️ यहां $conn->query() विफल हो रहा था जब $conn अनडिफ़ाइंड था। अब हमने इसे if ($conn_ok) ब्लॉक के अंदर ले लिया है।
    $categories_result = $conn->query($categories_query);
}

?>

<div id="page-content-wrapper">
    <div class="container-fluid">
        <h2>🏷️ श्रेणी प्रबंधन (Category Management)</h2>
        <p>आप यहाँ से विभिन्न प्रकार की पोस्ट (जैसे: परिणाम, एडमिट कार्ड) के प्रदर्शन को नियंत्रित कर सकते हैं।</p>

        <?php 
        if ($message) echo "<div class='alert alert-success' style='padding: 15px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 20px;'>{$message}</div>";
        if ($error) echo "<div class='alert alert-danger' style='padding: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 20px;'>{$error}</div>";
        ?>
        
        <div class="row" style="display: flex; gap: 30px; flex-wrap: wrap;">
            
            <div style="flex: 1; min-width: 300px; background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6;">
                <h3>➕ नई श्रेणी जोड़ें</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="category_name">श्रेणी का नाम (जैसे: Answer Key):</label>
                        <input type="text" id="category_name" name="category_name" class="form-control" required style="margin-bottom: 15px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 100%;">
                    </div>
                    <button type="submit" name="add_category" class="btn btn-success" style="background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">श्रेणी जोड़ें</button>
                    <p class="text-danger mt-2" style="color: #dc3545; margin-top: 10px;"><small>सिस्टम DB में 'categories' तालिका के बिना यह सुविधा सीमित है।</small></p>
                </form>
            </div>

            <div style="flex: 2; min-width: 450px;">
                <h3>📋 सक्रिय श्रेणियाँ</h3>
                <table class="table table-bordered table-striped" style="background: white; width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #e9ecef;">
                            <th style="padding: 10px; border: 1px solid #dee2e6;">श्रेणी का नाम</th>
                            <th style="padding: 10px; border: 1px solid #dee2e6;">स्लग (Slug)</th>
                            <th style="padding: 10px; border: 1px solid #dee2e6;">पोस्ट की संख्या</th>
                            <th style="padding: 10px; border: 1px solid #dee2e6;">एक्शन</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                            <?php while($row = $categories_result->fetch_assoc()): ?>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid #dee2e6;"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $row['category']))); ?></td>
                                    <td style="padding: 10px; border: 1px solid #dee2e6;"><span style="font-family: monospace; background: #eee; padding: 2px 5px; border-radius: 3px;"><?php echo htmlspecialchars($row['category']); ?></span></td>
                                    <td style="padding: 10px; border: 1px solid #dee2e6;"><?php echo $row['post_count']; ?></td>
                                    <td style="padding: 10px; border: 1px solid #dee2e6;">
                                        <?php if (in_array($row['category'], ['job', 'result', 'admit_card', 'other'])): ?>
                                            <span style="color: #6c757d;" title="ये मुख्य श्रेणियाँ हैं और इन्हें हटाया नहीं जा सकता।">आरक्षित</span>
                                        <?php else: ?>
                                            <span style="color: #6c757d;">(एडिट/डिलीट अक्षम)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php elseif ($conn_ok): ?>
                            <tr>
                                <td colspan="4" class="text-center" style="padding: 10px; border: 1px solid #dee2e6;">कोई सक्रिय श्रेणी नहीं मिली।</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php 
// कनेक्शन बंद करें
if (isset($conn)) {
    $conn->close();
}
include __DIR__ . '/includes/admin_footer.php'; 
?>