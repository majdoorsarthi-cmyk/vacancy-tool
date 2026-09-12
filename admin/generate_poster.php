<?php
session_start();
// सुनिश्चित करें कि एडमिन लॉग इन है
if (!isset($_SESSION['admin_logged_in'])) {
    header("location: login.php");
    exit;
}

include '../includes/db_connect.php';
include 'includes/admin_header.php'; 

$message = "";
$vacancy_id = null;
$poster_generated = false;

// 1. GD Library की उपलब्धता की जाँच करें
if (!extension_loaded('gd') || !function_exists('gd_info')) {
    $message = "<div style='color: red; background: #f8d7da; padding: 10px; border-radius: 5px;'>
                ❌ त्रुटि: PHP में GD Library enabled नहीं है। कृपया php.ini फ़ाइल में extension=gd को सक्षम करें।
                </div>";
} else {
    // 2. रिक्तियों को प्राप्त करें (केवल लाइव रिक्तियां)
    $vacancies_result = $conn->query("SELECT id, title, category FROM vacancies WHERE status='live' ORDER BY post_date DESC");

    // 3. पोस्टर जनरेशन लॉजिक (POST पर)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate_poster'])) {
        $vacancy_id = intval($_POST['vacancy_id']);
        
        $stmt = $conn->prepare("SELECT title, category FROM vacancies WHERE id = ?");
        $stmt->bind_param("i", $vacancy_id);
        $stmt->execute();
        $vacancy_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($vacancy_data) {
            $title = $vacancy_data['title'];
            $category = strtoupper($vacancy_data['category']);

            // --- GD Code Snippet (Working Implementation) ---
            $width = 800;
            $height = 450;
            $image = imagecreatetruecolor($width, $height);

            // रंग
            $white = imagecolorallocate($image, 255, 255, 255);
            $blue = imagecolorallocate($image, 0, 123, 255);
            $dark_blue = imagecolorallocate($image, 0, 50, 150);
            $red = imagecolorallocate($image, 255, 50, 50);

            // बैकग्राउंड और बॉर्डर
            imagefilledrectangle($image, 0, 0, $width, $height, $white);
            imagefilledrectangle($image, 10, 10, $width - 10, $height - 10, $blue); // बॉर्डर
            imagefilledrectangle($image, 20, 20, $width - 20, $height - 20, $white); // कंटेंट एरिया

            // सुनिश्चित करें कि यह फ़ॉन्ट पथ सही है!
            // यदि आपके पास .ttf फ़ाइल नहीं है, तो Arial.ttf का उपयोग करें
            $font = __DIR__ . '/assets/fonts/Arial.ttf'; 
            // यदि फ़ॉन्ट नहीं मिलता है, तो यह हिस्सा काम नहीं करेगा।
            // वैकल्पिक रूप से: $font = 5; (डिफ़ॉल्ट फ़ॉन्ट) का उपयोग करें और imagettftext को imagestring से बदलें।
            
            // Text Placement
            if (file_exists($font)) {
                // श्रेणी (Category)
                imagettftext($image, 25, 0, 50, 80, $dark_blue, $font, "🔥 NEW $category UPDATE");
                // शीर्षक (Title)
                imagettftext($image, 35, 0, 50, 150, $blue, $font, $title);
                // वेबसाइट URL
                imagettftext($image, 20, 0, 50, $height - 50, $red, $font, "Visit VacancyPortal.com for details!");
            } else {
                 // Fallback यदि फ़ॉन्ट न मिले
                 imagestring($image, 5, 50, 50, "🔥 NEW $category UPDATE", $dark_blue);
                 imagestring($image, 5, 50, 80, $title, $blue);
                 $message = "<div style='color: orange;'>⚠️ चेतावनी: फ़ॉन्ट फ़ाइल ($font) नहीं मिली। सादा टेक्स्ट उपयोग किया गया।</div>";
            }
            
            // छवि को 'posters/' फ़ोल्डर में सेव करें
            $filename = 'posters/poster_' . $vacancy_id . '_' . time() . '.png';
            $full_path = '../' . $filename; // vacancy_portal/posters/ तक पहुंचने के लिए

            // सुनिश्चित करें कि 'vacancy_portal/' फ़ोल्डर में 'posters' नामक फ़ोल्डर मौजूद है और लिखने योग्य है!
            if (imagepng($image, $full_path)) {
                 $message = "<div style='color: green; background: #d4edda; padding: 10px; border-radius: 5px;'>
                             ✅ पोस्टर सफलतापूर्वक जनरेट हुआ! 
                             <a href='../$filename' target='_blank'>यहां देखें</a>
                             </div>";
                 $poster_generated = true;
                 $poster_url = '../' . $filename;
            } else {
                 $message = "<div style='color: red; background: #f8d7da; padding: 10px; border-radius: 5px;'>
                             ❌ त्रुटि: पोस्टर फ़ोल्डर (vacancy_portal/posters) लिखने योग्य नहीं है या मौजूद नहीं है!
                             </div>";
            }
            
            imagedestroy($image);
            // --- End GD Code ---

        } else {
             $message = "<div style='color: red;'>अमान्य रिक्ति ID।</div>";
        }
    }
}
?>

<div id="page-content-wrapper">
    <div class="container-fluid">
        <h2>🖼️ पोस्टर जनरेटर</h2>
        <p>चयनित रिक्ति के लिए तुरंत सोशल मीडिया पोस्टर बनाएं।</p>
        
        <?php echo $message; ?>

        <?php if ($poster_generated): ?>
            <div style="margin-top: 20px; text-align: center;">
                <h3>जनरेटेड पोस्टर:</h3>
                <img src="<?php echo $poster_url; ?>" alt="Generated Poster" style="max-width: 100%; border: 1px solid #ccc; border-radius: 8px;">
            </div>
            <hr>
        <?php endif; ?>

        <form method="POST" class="admin-form">
            <label for="vacancy_id">वह रिक्ति चुनें जिसका पोस्टर बनाना है (केवल Live):</label>
            <select name="vacancy_id" required>
                <option value="">-- रिक्ति चुनें --</option>
                <?php while ($row = $vacancies_result->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>">
                        <?php echo htmlspecialchars($row['title']); ?> (<?php echo strtoupper($row['category']); ?>)
                    </option>
                <?php endwhile; ?>
            </select>
            
            <button type="submit" name="generate_poster">🖼️ पोस्टर जनरेट करें</button>
        </form>
    </div>
</div>

<?php 
// फूटर को सुरक्षित रूप से शामिल करें
include __DIR__ . '/includes/admin_footer.php'; 
?>