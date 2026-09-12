<?php
session_start();

// 1. सुरक्षा जाँच (Admin Authentication)
if (!isset($_SESSION['admin_logged_in'])) {
    header("location: login.php");
    exit;
}

include '../includes/db_connect.php';

// 2. पुश नोटिफिकेशन फ़ंक्शन (OneSignal)
function send_new_update_notification($title, $vacancy_id) {
    $ONESIGNAL_APP_ID = "01d46323-0ca7-4a01-a8de-7427e5fdd27c";
    $ONESIGNAL_REST_API_KEY = "YOUR_REST_API_KEY_SECRET"; 
    
    $base_url = "https://yourwebsite.com/"; 
    $url_to_open = $base_url . "vacancy_details.php?id=" . $vacancy_id;
    
    $content = array("en" => "नया अपडेट: " . $title . " लाइव है!");
    $fields = array(
        'app_id' => $ONESIGNAL_APP_ID,
        'included_segments' => array('All'),
        'headings' => array("en" => "🔥 नई सरकारी अपडेट 🔥"),
        'contents' => $content,
        'url' => $url_to_open
    );
    
    $headers = array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic ' . $ONESIGNAL_REST_API_KEY
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_exec($ch);
    curl_close($ch);
}

// 2.5 n8n टेलीग्राम ऑटोमेशन फ़ंक्शन
function send_to_n8n_telegram($title, $vacancy_id, $category, $eligibility, $total_posts, $last_date) {
    $webhook_url = 'https://n8n.vacancyportal.co.in/webhook/new-job'; 

    $post_data = [
        'title'         => $title, 
        'category'      => $category,      
        'eligibility'   => $eligibility,   
        'total_posts'   => $total_posts,   
        'last_date'     => $last_date,     
        'link'          => 'https://tc.vacancyportal.co.in/vacancy_details.php?id=' . $vacancy_id 
    ];

    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
    
    curl_exec($ch);
    curl_close($ch);
}

// 🚀 2.8 WhatsApp Channel Auto-Post Function (Direct Meta Cloud API)
function send_whatsapp_channel_notification($title, $vacancy_id, $eligibility, $total_posts) {
    // आपकी सेट की हुई सेटिंग्स
    $phone_number_id = "1110702558788418"; 
    $access_token    = "EAAS2fcIKUq0BSNkR6sv2T5KNaQcRoml1rUL7Nsv7N5xtwJPzPHXC80qeQnhOUlWP8PgoOnTBured1eFoRyCXakFsfzlBsriDBRZCzf0LGbyK1Au6DLIGCUcDrOSoZB9BAtWBY3Agy56yEab8e7ZAUvHRUwoG7MPty7Lym2F0FAGJc7VS34yd7EvcWaX9SZBWRj9KGc8lSsE7yCy9EbPkczvxLa8XLjGJfXWeQuYQun2O3HbAb92Rn3GaFeKttQ5vPoPZB7fpS3fgoSqdKSs27uFGF"; 
    $channel_id      = "0029Vb7Cpuy4SpkPg9U35c2x"; 

    $post_url = "https://tc.vacancyportal.co.in/vacancy_details.php?id=" . $vacancy_id;
    
    // HTML और एक्स्ट्रा टैग्स क्लीन करें
    $clean_title       = trim(strip_tags($title));
    $clean_eligibility = trim(strip_tags($eligibility));
    $clean_posts       = trim(strip_tags($total_posts));

    if (empty($clean_eligibility)) {
        $clean_eligibility = "संबंधित Official Notification में निर्धारित योग्यता पूरी करना आवश्यक होगा।";
    }

    if (empty($clean_posts)) {
        $clean_posts = "नोटिफिकेशन देखें";
    }

    // व्हाट्सएप Markdown फ़ॉर्मेट (सुंदर लेआउट)
    $message  = "🔥 *धमाकेदार भर्ती: नया नोटिफिकेशन जारी* 🔥\n";
    $message .= "🚨 *tc.vacancyportal.co.in* 🚨\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $message .= "💥 *भर्ती का नाम (Post Name):*\n";
    $message .= "💎 *" . $clean_title . "* 💎\n\n";
    
    $message .= "ℹ️ *महत्वपूर्ण जानकारी (Details):*\n";
    $message .= "```";
    $message .= "• योग्यता (Eligibility):\n" . $clean_eligibility . "\n\n";
    $message .= "• कुल पद (Total Posts): " . $clean_posts . "\n";
    $message .= "• फॉर्म मोड: ऑनलाइन (Online)\n";
    $message .= "• स्टेटस: लाइव (Official Update)\n";
    $message .= "```\n\n";
    
    $message .= "👇 *ऑफिशियल नोटिफिकेशन एवं अप्लाई लिंक:* 👇\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "🌐 *डायरेक्ट लिंक (Click Here):*\n";
    $message .= "🔗 " . $post_url . "\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $message .= "🌐 *सभी अपडेट्स के लिए गूगल पर सर्च करें:* tc.vacancyportal.co.in\n";
    $message .= "📢 *पल-पल की अपडेट के लिए चैनल को Follow करें!*\n";
    $message .= "🚀 *अपने दोस्तों के साथ ज़रूर शेयर करें!*";

    // Meta API Request
    $url = "https://graph.facebook.com/v18.0/{$phone_number_id}/messages";
    
    $payload = [
        "messaging_product" => "whatsapp",
        "recipient_type"    => "individual",
        "to"                => $channel_id,
        "type"              => "text",
        "text"              => [
            "preview_url" => true,
            "body"        => $message
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$access_token}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

$message = "";

// 3. फॉर्म सबमिशन हैंडलिंग
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $category = $_POST['category'];
    $state = $_POST['state']; 
    $short_desc = trim($_POST['short_desc']);
    $result_link = trim($_POST['result_link']);
    $long_details = $_POST['long_details']; 
    $description = $_POST['description'];  
    $important_dates = trim($_POST['important_dates']);
    $eligibility = trim($_POST['eligibility']);
    $apply_link = trim($_POST['apply_link']);
    $official_website = trim($_POST['official_website']);
    $last_date = !empty($_POST['last_date']) ? $_POST['last_date'] : NULL;
    
    $total_posts = isset($_POST['total_posts']) ? trim($_POST['total_posts']) : 'नोटीफिकेशन देखें';
    
    $status = $_POST['status'];
    $full_details = $_POST['full_details']; 
    $post_date = date('Y-m-d H:i:s');

    $sql = "INSERT INTO vacancies (title, category, state, short_desc, result_link, long_details, description, important_dates, eligibility, apply_link, official_website, post_date, last_date, status, full_details, total_posts)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssssssssssssss", 
            $title, $category, $state, $short_desc, $result_link, $long_details, 
            $description, $important_dates, $eligibility, $apply_link, 
            $official_website, $post_date, $last_date, $status, $full_details, $total_posts
        );

        if ($stmt->execute()) {
            $last_id = $conn->insert_id;
            $message = "<div class='alert success'>✅ Vacancy सफलतापूर्वक पब्लिश हो गई! (Category: $category)</div>";
            
            if ($status == 'live') {
                // 1. OneSignal Web Push
                send_new_update_notification($title, $last_id);
                
                // 2. n8n Telegram Channel
                send_to_n8n_telegram($title, $last_id, $category, $eligibility, $total_posts, $last_date);

                // 3. 🚀 Direct WhatsApp Channel Auto-Post
                send_whatsapp_channel_notification($title, $last_id, $eligibility, $total_posts);
            }
        } else {
            $message = "<div class='alert error'>❌ Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}

include 'includes/admin_header.php';
?>

<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>

<style>
    .admin-container { max-width: 1000px; margin: 20px auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    .form-group { margin-bottom: 15px; }
    label { font-weight: bold; display: block; margin-bottom: 5px; color: #444; }
    input[type="text"], input[type="date"], select, textarea {
        width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
    }
    .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
    .form-row > div { flex: 1; }
    .btn-submit { background: #28a745; color: white; border: none; padding: 12px 25px; cursor: pointer; border-radius: 4px; font-size: 16px; width: 100%; }
    .btn-submit:hover { background: #218838; }
    .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
</style>

<div class="admin-container">
    <h2>➕ नई वैकेंसी जोड़ें (State Wise Category)</h2>
    <?php echo $message; ?>

    <form method="POST" class="admin-form">
        <div class="form-group">
            <label>Post Title (मुख्य शीर्षक):</label>
            <input type="text" name="title" required placeholder="जैसे: UP Police Constable Recruitment 2025">
        </div>

        <div class="form-row">
            <div>
                <label>Category (श्रेणी):</label>
                <select name="category" required>
                    <option value="">--चुनें--</option>
                    <option value="job">🔥 नई सरकारी नौकरी</option>
                    <option value="result">✅ परीक्षा परिणाम</option>
                    <option value="admit_card">🎟️ एडमिट कार्ड</option>
                    <option value="scheme">🎁 Government Scheme (योजनाएं)</option>
                    <option value="education">📚 Education (शिक्षा)</option>
                    <option value="admission">🎓 Admission (प्रवेश)</option>
                    <option value="pdf_form">📄 Pdf Forms (फॉर्म्स)</option>
                    <option value="other">📁 अन्य अपडेट</option>
                </select>
            </div>
            <div>
                <label>State (राज्य चुनें):</label>
                <select name="state" required>
                    <option value="All India">All India (Central Job)</option>
                    <option value="Andhra Pradesh">Andhra Pradesh (आंध्र प्रदेश)</option>
                    <option value="Arunachal Pradesh">Arunachal Pradesh (अरुणाचल प्रदेश)</option>
                    <option value="Assam">Assam (असम)</option>
                    <option value="Bihar">Bihar (बिहार)</option>
                    <option value="Chhattisgarh">Chhattisgarh (छत्तीसगढ़)</option>
                    <option value="Goa">Goa (गोवा)</option>
                    <option value="Gujarat">Gujarat (गुजरात)</option>
                    <option value="Haryana">Haryana (हरियाणा)</option>
                    <option value="Himachal Pradesh">Himachal Pradesh (हिमाचल प्रदेश)</option>
                    <option value="Jharkhand">Jharkhand (झारखंड)</option>
                    <option value="Karnataka">Karnataka (कर्नाटक)</option>
                    <option value="Kerala">Kerala (केरल)</option>
                    <option value="Madhya Pradesh">Madhya Pradesh (मध्य प्रदेश)</option>
                    <option value="Maharashtra">Maharashtra (महाराष्ट्र)</option>
                    <option value="Manipur">Manipur (मणिपुर)</option>
                    <option value="Meghalaya">Meghalaya (मेघालय)</option>
                    <option value="Mizoram">Mizoram (मिजोरम)</option>
                    <option value="Nagaland">Nagaland (नागालैंड)</option>
                    <option value="Odisha">Odisha (ओडिशा)</option>
                    <option value="Punjab">Punjab (पंजाब)</option>
                    <option value="Rajasthan">Rajasthan (राजस्थान)</option>
                    <option value="Sikkim">Sikkim (सिक्किम)</option>
                    <option value="Tamil Nadu">Tamil Nadu (तमिलनाडु)</option>
                    <option value="Telangana">Telangana (तेलंगाना)</option>
                    <option value="Tripura">Tripura (त्रिपुरा)</option>
                    <option value="Uttar Pradesh">Uttar Pradesh (उत्तर प्रदेश)</option>
                    <option value="Uttarakhand">Uttarakhand (उत्तराखंड)</option>
                    <option value="West Bengal">West Bengal (पश्चिम बंगाल)</option>
                    <option value="Delhi">Delhi (दिल्ली)</option>
                    <option value="Other">Other (अन्य)</option>
                </select>
            </div>
            <div>
                <label>Status:</label>
                <select name="status">
                    <option value="live">Live (Website पर दिखाएँ)</option>
                    <option value="archived">Archived (पुराना/छुपाएँ)</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label>Last Date (अंतिम तिथि):</label>
                <input type="date" name="last_date">
            </div>
            <div>
                <label>Total Posts (कुल पद):</label>
                <input type="text" name="total_posts" placeholder="जैसे: 542 पद या नोटीफिकेशन देखें">
            </div>
        </div>
        
        <div class="form-group">
            <label>Short Description (छोटा विवरण):</label>
            <input type="text" name="short_desc" placeholder="एक लाइन में भर्ती का विवरण लिखें...">
        </div>

        <div class="form-group">
            <label>भर्ती की पूरी जानकारी (Long Details):</label>
            <textarea name="long_details" id="editor1"></textarea>
        </div>

        <div class="form-group">
            <label>आवेदन निर्देश (Instructions):</label>
            <textarea name="description" id="editor2"></textarea>
        </div>

        <div class="form-group">
            <label>अतिरिक्त जानकारी (Extra Details):</label>
            <textarea name="full_details" id="editor3"></textarea>
        </div>

        <div class="form-row">
            <div>
                <label>पात्रता (Eligibility):</label>
                <textarea name="eligibility" rows="3"></textarea>
            </div>
            <div>
                <label>महत्वपूर्ण तिथियां (Dates Text):</label>
                <textarea name="important_dates" rows="3"></textarea>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label>Apply Link:</label>
                <input type="text" name="apply_link">
            </div>
            <div>
                <label>Official Website:</label>
                <input type="text" name="official_website">
            </div>
            <div>
                <label>Result/Other Link:</label>
                <input type="text" name="result_link">
            </div>
        </div>

        <button type="submit" class="btn-submit">💾 वैकेंसी सुरक्षित करें और लाइव करें</button>
    </form>
</div>

<script>
    CKEDITOR.replace('editor1', { height: 300 });
    CKEDITOR.replace('editor2', { height: 200 });
    CKEDITOR.replace('editor3', { height: 200 });
</script>

<?php include 'includes/admin_footer.php'; ?>