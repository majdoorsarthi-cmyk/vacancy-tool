<?php 
// फाइल पाथ्स को डिफाइन करें
$header_path = 'includes/header.php';
$footer_path = 'includes/footer.php';

if (file_exists($header_path)) {
    include $header_path;
} else {
    // बैकअप HTML अगर हेडर न मिले
    echo '<!DOCTYPE html><html lang="hi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>अस्वीकरण (Disclaimer)</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50">';
}
?>

<style>
    .disclaimer-container {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
    }
    .disclaimer-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        padding: 40px;
        border-top: 5px solid #ef4444; /* Red accent for warning/disclaimer */
    }
    .disclaimer-header h1 {
        color: #1f2937;
        font-size: 2.25rem;
        font-weight: 800;
        text-align: center;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }
    .disclaimer-section {
        margin-bottom: 30px;
    }
    .disclaimer-section h2 {
        color: #dc2626;
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 12px;
        border-left: 4px solid #dc2626;
        padding-left: 15px;
    }
    .disclaimer-section p {
        color: #4b5563;
        line-height: 1.8;
        font-size: 1.1rem;
    }
    .warning-box {
        background-color: #fef2f2;
        border: 1px solid #fee2e2;
        padding: 20px;
        border-radius: 8px;
        margin: 25px 0;
    }
    .back-btn {
        display: inline-block;
        margin-top: 20px;
        color: #3b82f6;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.2s;
    }
    .back-btn:hover {
        color: #2563eb;
        text-decoration: underline;
    }
    .ad-placeholder {
        background: #f9fafb;
        border: 1px dashed #d1d5db;
        text-align: center;
        padding: 20px;
        margin: 20px 0;
        color: #9ca3af;
        font-size: 0.875rem;
    }
</style>

<div class="disclaimer-container">
    <div class="disclaimer-card">
        <header class="disclaimer-header">
            <h1>⚠️ अस्वीकरण (Disclaimer)</h1>
        </header>

        <div class="ad-placeholder">
            [Google AdSense - Top Banner]
        </div>

        <div class="disclaimer-content">
            <section class="disclaimer-section">
                <h2>1. सूचना की सटीकता (Accuracy of Information)</h2>
                <p>हमारा पोर्टल सभी सरकारी और निजी भर्तियों की जानकारी विभिन्न स्रोतों जैसे आधिकारिक वेबसाइटों, रोजगार समाचार और विश्वसनीय समाचार पत्रों से एकत्र करता है। हालांकि हम जानकारी की सटीकता सुनिश्चित करने का हर संभव प्रयास करते हैं, लेकिन हम सामग्री की पूर्ण सटीकता, पूर्णता या विश्वसनीयता की गारंटी नहीं देते हैं।</p>
            </section>

            <section class="warning-box">
                <h2 style="border:none; padding-left:0;">2. गैर-सरकारी संस्था (Non-Governmental Entity)</h2>
                <p><strong>ध्यान दें:</strong> हम एक निजी शैक्षिक पोर्टल हैं और <strong>किसी भी सरकारी संगठन का प्रतिनिधित्व नहीं करते हैं।</strong> हमारा किसी भी सरकारी विभाग के साथ कोई सीधा संबंध नहीं है। सभी उपयोगकर्ताओं को सलाह दी जाती है कि किसी भी भर्ती के लिए आवेदन करने से पहले संबंधित विभाग की <strong>आधिकारिक वेबसाइट</strong> पर दी गई जानकारी का मिलान अवश्य करें।</p>
            </section>

            <section class="disclaimer-section">
                <h2>3. बाह्य लिंक (External Links)</h2>
                <p>हमारी वेबसाइट पर अन्य वेबसाइटों के लिंक हो सकते हैं। हम इन बाहरी साइटों की सामग्री या उनकी गोपनीयता नीतियों पर कोई नियंत्रण नहीं रखते हैं और उनके लिए जिम्मेदार नहीं हैं।</p>
            </section>

            <div class="ad-placeholder">
                [Google AdSense - In-Article Ad]
            </div>

            <section class="disclaimer-section">
                <h2>4. दायित्व की सीमा (Limitation of Liability)</h2>
                <p>इस वेबसाइट पर दी गई जानकारी का उपयोग करने के परिणामस्वरूप होने वाले किसी भी प्रकार के नुकसान, त्रुटि या निर्णय के लिए पोर्टल का प्रबंधन जिम्मेदार नहीं होगा। किसी भी विज्ञापन या भर्ती के लिए भुगतान करने से पहले सावधानी बरतें, हम कभी भी नौकरी के बदले पैसे की मांग नहीं करते हैं।</p>
            </section>
        </div>

        <div class="text-center mt-8">
            <a href="index.php" class="back-btn">← मुख्य पृष्ठ (Home) पर वापस जाएं</a>
        </div>
    </div>
</div>

<?php 
if (file_exists($footer_path)) {
    include $footer_path; 
} else {
    echo '</body></html>';
}
?>