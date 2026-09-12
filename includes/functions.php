<?php
// विज्ञापन कोड प्रदर्शित करने के लिए फंक्शन
function display_ad_slot($conn, $slot_name) {
    // विज्ञापन सेटिंग्स को कैश करने के लिए स्टैटिक वैरिएबल का उपयोग करें
    static $ads_cache = [];

    // यदि डेटाबेस कनेक्शन मौजूद नहीं है, तो खाली स्ट्रिंग लौटाएँ
    if (!$conn) {
        return "";
    }

    // यदि कैश में पहले से ही सेटिंग्स लोड नहीं हैं, तो उन्हें लोड करें
    if (empty($ads_cache)) {
        $result = $conn->query("SELECT ad_slot_name, ad_code, is_active FROM ads_settings WHERE is_active = 1");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // यहाँ हम ad_code को DANGER-FREE रखते हैं क्योंकि इसे admin/ads_settings.php में
                // htmlspecialchars() का उपयोग करके एन्क्रिप्ट नहीं किया गया था
                // (विज्ञापन कोड को सीधा HTML/JS होना चाहिए)
                $ads_cache[$row['ad_slot_name']] = $row['ad_code'];
            }
        }
    }

    // यदि स्लॉट सक्रिय है, तो कोड प्रदर्शित करें
    if (isset($ads_cache[$slot_name]) && !empty($ads_cache[$slot_name])) {
        // विज्ञापन कंटेनर के लिए आवश्यक स्टाइलिंग जोड़ें
        return "<div class='adsense-unit adsense-{$slot_name}'>" . $ads_cache[$slot_name] . "</div>";
    }

    return "";
}
?>