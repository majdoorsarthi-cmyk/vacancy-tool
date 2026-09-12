<?php 
// सही पाथ: चूँकि privacy.php रूट में है, इसलिए सीधे includes फोल्डर को एक्सेस करें
$header_path = 'includes/header.php';
$footer_path = 'includes/footer.php';

if (file_exists($header_path)) {
    include $header_path;
} else {
    // अगर फाइल नहीं मिलती तो बेसिक HTML स्टार्ट करें ताकि पेज टूटे नहीं
    echo '<!DOCTYPE html><html lang="hi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Privacy Policy</title></head><body>';
}
?>

<style>
    :root {
        --primary: #1a73e8;
        --bg-light: #f8f9fa;
        --text-main: #202124;
        --text-muted: #5f6368;
    }

    .policy-wrapper {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--bg-light);
        padding: 40px 15px;
        color: var(--text-main);
    }

    .policy-card {
        max-width: 850px;
        margin: 0 auto;
        background: #ffffff;
        padding: 40px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
    }

    .policy-title {
        color: var(--primary);
        font-size: 2.5rem;
        font-weight: bold;
        text-align: center;
        margin-bottom: 10px;
    }

    .policy-date {
        text-align: center;
        color: var(--text-muted);
        margin-bottom: 30px;
        font-size: 0.9rem;
    }

    .policy-content h2 {
        font-size: 1.4rem;
        color: var(--text-main);
        margin-top: 30px;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
    }

    .policy-content p, .policy-content li {
        line-height: 1.7;
        color: #3c4043;
    }

    .ad-box {
        background: #f1f3f4;
        border: 1px dashed #dadce0;
        margin: 25px 0;
        padding: 15px;
        text-align: center;
        color: #70757a;
        font-size: 0.8rem;
    }

    .highlight-section {
        background: #e8f0fe;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
    }

    @media (max-width: 600px) {
        .policy-card {
            padding: 20px;
        }
        .policy-title {
            font-size: 1.8rem;
        }
    }
</style>

<div class="policy-wrapper">
    <div class="policy-card">
        <h1 class="policy-title">🔐 गोपनीयता नीति</h1>
        <p class="policy-date">अंतिम अपडेट: 15 दिसंबर, 2025</p>

        <div class="ad-box">
            <!-- Google AdSense Top Ad Code Here -->
            विज्ञापन (AdSense Top)
        </div>

        <div class="policy-content">
            <p>हमारी वेबसाइट पर आपका स्वागत है। हम आपकी गोपनीयता (Privacy) का सम्मान करते हैं और आपकी व्यक्तिगत जानकारी की सुरक्षा के लिए प्रतिबद्ध हैं।</p>

            <h2>1. सूचना संग्रह (Information Collection)</h2>
            <p>हम निम्नलिखित जानकारी एकत्र कर सकते हैं:</p>
            <ul>
                <li><strong>व्यक्तिगत विवरण:</strong> नाम और ईमेल (जब आप संपर्क करते हैं या सब्सक्राइब करते हैं)।</li>
                <li><strong>तकनीकी डेटा:</strong> IP पता, ब्राउज़र का प्रकार, और हमारी साइट पर बिताया गया समय।</li>
            </ul>

            <div class="highlight-section">
                <h2>2. Google AdSense और कुकीज़ (Cookies)</h2>
                <p>हमारी वेबसाइट विज्ञापन दिखाने के लिए <strong>Google AdSense</strong> का उपयोग करती है:</p>
                <ul>
                    <li>Google, एक तृतीय-पक्ष विक्रेता के रूप में, विज्ञापन दिखाने के लिए कुकीज़ का उपयोग करता है।</li>
                    <li>Google द्वारा <strong>DART कुकी</strong> का उपयोग इसे हमारे उपयोगकर्ताओं को उनकी पसंद के आधार पर विज्ञापन दिखाने की अनुमति देता है।</li>
                    <li>आप Google की विज्ञापन नीति पर जाकर इसे प्रबंधित कर सकते हैं।</li>
                </ul>
            </div>

            <h2>3. तृतीय-पक्ष सेवाएँ</h2>
            <p>हम सूचनाएं भेजने के लिए <strong>OneSignal</strong> और ट्रैफिक विश्लेषण के लिए <strong>Google Analytics</strong> का उपयोग करते हैं। ये सेवाएँ अपनी नीतियों के अनुसार डेटा एकत्र कर सकती हैं।</p>

            <div class="ad-box">
                <!-- Google AdSense Middle Ad Code Here -->
                विज्ञापन (AdSense Middle)
            </div>

            <h2>4. डेटा की सुरक्षा</h2>
            <p>हम आपकी जानकारी को सुरक्षित रखने के लिए सुरक्षित सर्वर और एन्क्रिप्शन का उपयोग करते हैं। हम आपका डेटा किसी भी तृतीय पक्ष को नहीं बेचते हैं।</p>

            <h2>5. बच्चों की सुरक्षा</h2>
            <p>यह वेबसाइट 13 वर्ष से कम उम्र के बच्चों से जानबूझकर जानकारी एकत्र नहीं करती है।</p>

            <h2>6. हमसे संपर्क करें</h2>
            <p>यदि आपके पास कोई प्रश्न है, तो आप हमें <strong>Contact Us</strong> पेज के माध्यम से संपर्क कर सकते हैं।</p>
        </div>

        <div style="margin-top: 40px; text-align: center;">
            <a href="index.php" style="text-decoration: none; color: var(--primary); font-weight: bold;">← होम पेज पर वापस जाएं</a>
        </div>
    </div>
</div>

<div class="ad-box" style="max-width: 850px; margin: 20px auto;">
    <!-- Google AdSense Bottom Ad Code Here -->
    विज्ञापन (AdSense Bottom)
</div>

<?php 
if (file_exists($footer_path)) {
    include $footer_path; 
} else {
    echo '</body></html>';
}
?>