<?php
// हेडर और डेटाबेस कनेक्शन
include 'includes/db_connect.php'; 
include 'includes/header.php'; 
?>

<style>
    :root {
        --primary: #2563eb;
        --secondary: #64748b;
        --dark: #1e293b;
        --text-muted: #475569;
        --light-bg: #f8fafc;
    }

    .legal-wrapper {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
        font-family: 'Inter', -apple-system, sans-serif;
        color: var(--dark);
        line-height: 1.8;
    }

    .legal-header {
        text-align: center;
        margin-bottom: 50px;
        padding-bottom: 30px;
        border-bottom: 2px solid #e2e8f0;
    }

    .legal-header h1 {
        font-size: 2.5rem;
        color: var(--dark);
        margin-bottom: 10px;
        font-weight: 800;
    }

    .last-updated {
        color: var(--primary);
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .legal-card {
        background: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--dark);
        margin-top: 35px;
        margin-bottom: 15px;
    }

    .section-title span {
        background: var(--primary);
        color: white;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-size: 0.9rem;
    }

    .legal-content p {
        margin-bottom: 20px;
        color: var(--text-muted);
    }

    .legal-list {
        list-style: none;
        padding-left: 0;
        margin-bottom: 25px;
    }

    .legal-list li {
        position: relative;
        padding-left: 30px;
        margin-bottom: 12px;
        color: var(--text-muted);
    }

    .legal-list li::before {
        content: '✓';
        position: absolute;
        left: 0;
        color: var(--primary);
        font-weight: bold;
    }

    .disclaimer-box {
        background: #fff7ed;
        border-left: 4px solid #f97316;
        padding: 20px;
        border-radius: 8px;
        margin: 30px 0;
    }

    .disclaimer-box strong {
        color: #9a3412;
    }

    @media (max-width: 640px) {
        .legal-header h1 { font-size: 1.8rem; }
        .legal-card { padding: 25px; }
    }
</style>

<div class="legal-wrapper">
    <div class="legal-header">
        <h1>नियम और शर्तें (Terms & Conditions)</h1>
        <p class="last-updated">अंतिम अपडेट: <?php echo date('d M, Y'); ?></p>
    </div>

    <div class="legal-card">
        <div class="legal-content">
            <p><strong>Vacancy Portal</strong> में आपका स्वागत है। यदि आप इस वेबसाइट का उपयोग करना जारी रखते हैं, तो आप निम्नलिखित नियमों और शर्तों का पालन करने के लिए सहमत हैं।</p>

            <div class="section-title"><span>1</span> परिचय</div>
            <p>इस वेबसाइट पर दी गई जानकारी केवल सामान्य सूचना के उद्देश्यों के लिए है। "Vacancy Portal" शब्द वेबसाइट के मालिक को संदर्भित करता है, और "आप" शब्द हमारी वेबसाइट के उपयोगकर्ता या दर्शक को संदर्भित करता है।</p>

            <div class="section-title"><span>2</span> सूचना की सटीकता</div>
            <p>यद्यपि हम सही और अद्यतन (Up-to-date) जानकारी प्रदान करने का हर संभव प्रयास करते हैं, लेकिन हम वेबसाइट पर उपलब्ध जानकारी की पूर्णता, सटीकता या विश्वसनीयता के बारे में कोई गारंटी नहीं देते हैं।</p>
            <ul class="legal-list">
                <li>उपयोगकर्ताओं को सलाह दी जाती है कि वे किसी भी फॉर्म को भरने से पहले आधिकारिक अधिसूचना (Official Notification) को जरूर पढ़ें।</li>
                <li>जानकारी में किसी भी त्रुटि के लिए हम उत्तरदायी नहीं होंगे।</li>
            </ul>

            <div class="section-title"><span>3</span> बाहरी लिंक (Third Party Links)</div>
            <p>हमारी वेबसाइट में अन्य सरकारी या निजी वेबसाइटों के लिंक हो सकते हैं। ये लिंक आपकी सुविधा के लिए दिए गए हैं। हमारा इन वेबसाइटों की सामग्री पर कोई नियंत्रण नहीं है और हम उनकी गोपनीयता नीतियों या सामग्री के लिए जिम्मेदार नहीं हैं।</p>

            <div class="disclaimer-box">
                <strong>महत्वपूर्ण सूचना:</strong> हम किसी भी सरकारी संस्था से जुड़े हुए नहीं हैं। हम केवल सार्वजनिक रूप से उपलब्ध सूचनाओं को एकत्रित करके आप तक पहुँचाते हैं। हम नौकरियों के लिए कोई पैसा नहीं मांगते हैं।
            </div>

            <div class="section-title"><span>4</span> बौद्धिक संपदा (Intellectual Property)</div>
            <p>इस वेबसाइट की सामग्री, लोगो, डिज़ाइन और डेटा Vacancy Portal की संपत्ति है। बिना लिखित अनुमति के इस सामग्री का व्यावसायिक उपयोग करना वर्जित है।</p>

            <div class="section-title"><span>5</span> उपयोगकर्ता आचरण</div>
            <p>आप सहमत हैं कि आप वेबसाइट का उपयोग केवल वैध उद्देश्यों के लिए करेंगे और किसी भी ऐसी गतिविधि में शामिल नहीं होंगे जिससे वेबसाइट को नुकसान पहुँचे या अन्य उपयोगकर्ताओं का अनुभव खराब हो।</p>

            <div class="section-title"><span>6</span> शर्तों में बदलाव</div>
            <p>Vacancy Portal बिना किसी पूर्व सूचना के इन नियमों और शर्तों को कभी भी बदलने का अधिकार सुरक्षित रखता है। बदलाव के बाद वेबसाइट का उपयोग जारी रखना नई शर्तों की स्वीकृति माना जाएगा।</p>

            <div class="section-title"><span>7</span> संपर्क करें</div>
            <p>यदि इन शर्तों के संबंध में आपके कोई प्रश्न हैं, तो आप हमारे <a href="contact.php" style="color: var(--primary); text-decoration: none; font-weight: bold;">संपर्क पेज</a> के माध्यम से हमसे जुड़ सकते हैं।</p>
        </div>
    </div>
</div>

<?php 
include 'includes/footer.php'; 
?>