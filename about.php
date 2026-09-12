<?php
// हेडर और अन्य आवश्यक फाइलें
include 'includes/db_connect.php'; 
include 'includes/header.php'; 
?>

<style>
    :root {
        --primary: #2563eb;
        --secondary: #64748b;
        --dark: #1e293b;
        --light-bg: #f8fafc;
    }

    .about-wrapper {
        max-width: 1100px;
        margin: 40px auto;
        padding: 0 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.7;
    }

    .hero-section {
        text-align: center;
        padding: 60px 20px;
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: white;
        border-radius: 20px;
        margin-bottom: 50px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .hero-section h1 {
        font-size: 2.8rem;
        margin-bottom: 15px;
        font-weight: 800;
    }

    .hero-section p {
        font-size: 1.2rem;
        opacity: 0.9;
        max-width: 700px;
        margin: 0 auto;
    }

    .about-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        align-items: center;
        margin-bottom: 60px;
    }

    @media (max-width: 768px) {
        .about-grid { grid-template-columns: 1fr; }
        .hero-section h1 { font-size: 2rem; }
    }

    .content-box h2 {
        color: var(--dark);
        font-size: 2rem;
        margin-bottom: 20px;
        position: relative;
    }

    .content-box h2::after {
        content: '';
        display: block;
        width: 60px;
        height: 4px;
        background: var(--primary);
        margin-top: 10px;
        border-radius: 2px;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-top: 40px;
    }

    .feature-card {
        background: white;
        padding: 30px;
        border-radius: 15px;
        border: 1px solid #e2e8f0;
        transition: transform 0.3s, box-shadow 0.3s;
        text-align: center;
    }

    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        border-color: var(--primary);
    }

    .feature-icon {
        font-size: 2.5rem;
        margin-bottom: 15px;
        display: block;
    }

    .feature-card h3 {
        color: var(--dark);
        margin-bottom: 10px;
        font-size: 1.3rem;
    }

    .mission-vision {
        background: var(--light-bg);
        padding: 50px;
        border-radius: 20px;
        margin-top: 40px;
    }

    .stat-box {
        display: flex;
        justify-content: space-around;
        text-align: center;
        background: var(--primary);
        color: white;
        padding: 40px;
        border-radius: 20px;
        margin-top: 50px;
    }

    .stat-item h3 { font-size: 2.5rem; margin: 0; }
    .stat-item p { margin: 0; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; }

    .cta-box {
        text-align: center;
        margin-top: 60px;
        padding: 40px;
        border: 2px dashed #cbd5e1;
        border-radius: 20px;
    }

    .btn-contact {
        display: inline-block;
        background: var(--primary);
        color: white;
        padding: 12px 35px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 700;
        margin-top: 20px;
        transition: background 0.3s;
    }

    .btn-contact:hover { background: #1d4ed8; }
</style>

<div class="about-wrapper">
    
    <!-- Hero Section -->
    <section class="hero-section">
        <h1>हमारे बारे में (About Us)</h1>
        <p>Vacancy Portal भारत का उभरता हुआ नौकरी सूचना केंद्र है, जहाँ हम सरकारी और प्राइवेट नौकरियों की सटीक जानकारी सबसे पहले पहुँचाते हैं।</p>
    </section>

    <!-- Main Content Section -->
    <div class="about-grid">
        <div class="content-box">
            <h2>हम कौन हैं?</h2>
            <p><strong>Vacancy Portal</strong> की शुरुआत नौकरी चाहने वाले युवाओं की मदद के लिए की गई थी। अक्सर देखा जाता है कि इंटरनेट पर बहुत सी भ्रामक जानकारी होती है, जिससे उम्मीदवार परेशान हो जाते हैं। हमारा उद्देश्य इस समस्या को खत्म करना और हर पात्र उम्मीदवार तक सही अवसर पहुँचाना है।</p>
            <p>हम केवल वेकेंसी की जानकारी ही नहीं देते, बल्कि आवेदन करने की प्रक्रिया, सिलेबस, एडमिट कार्ड और रिजल्ट की अपडेट भी प्रदान करते हैं।</p>
        </div>
        <div class="content-image">
            <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=600&q=80" alt="Team Working" style="width: 100%; border-radius: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
        </div>
    </div>

    <!-- Features Section -->
    <section>
        <h2 style="text-align:center; color: var(--dark);">हम क्या प्रदान करते हैं?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <span class="feature-icon">📝</span>
                <h3>सरकारी नौकरियाँ</h3>
                <p>SSC, UPSC, रेलवे, बैंक और राज्य स्तरीय भर्तियों की सबसे तेज जानकारी।</p>
            </div>
            <div class="feature-card">
                <span class="feature-icon">💼</span>
                <h3>प्राइवेट जॉब्स</h3>
                <p>भारत की टॉप कंपनियों में फ्रेशर्स और अनुभवी लोगों के लिए करियर अवसर।</p>
            </div>
            <div class="feature-card">
                <span class="feature-icon">📚</span>
                <h3>एग्जाम रिसोर्सेज</h3>
                <p>भर्तियों के सिलेबस, पिछले वर्षों के पेपर और तैयारी के लिए महत्वपूर्ण टिप्स।</p>
            </div>
            <div class="feature-card">
                <span class="feature-icon">🔔</span>
                <h3>इंस्टेंट अलर्ट्स</h3>
                <p>एडमिट कार्ड और रिजल्ट घोषित होते ही तुरंत सूचना प्राप्त करें।</p>
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="mission-vision">
        <div class="about-grid" style="margin-bottom: 0;">
            <div class="content-box">
                <h2>हमारा मिशन</h2>
                <p>हमारा मिशन भारत के हर गाँव और शहर के युवा को रोजगार के अवसरों से जोड़ना है। हम पारदर्शिता और सटीकता पर विश्वास करते हैं ताकि आपका समय और मेहनत सही दिशा में लगे।</p>
            </div>
            <div class="content-box">
                <h2>हमारा विजन</h2>
                <p>हम आने वाले समय में भारत का सबसे विश्वसनीय "करियर गाइडेंस" प्लेटफॉर्म बनना चाहते हैं, जहाँ नौकरी की खबर से लेकर जॉइनिंग तक की पूरी सहायता एक ही छत के नीचे मिले।</p>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stat-box">
        <div class="stat-item">
            <h3>50K+</h3>
            <p>मंथली विजिटर्स</p>
        </div>
        <div class="stat-item">
            <h3>500+</h3>
            <p>भर्तियाँ पोस्ट कीं</p>
        </div>
        <div class="stat-item">
            <h3>24/7</h3>
            <p>सपोर्ट & अपडेट</p>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-box">
        <h3>क्या आपके पास हमारे लिए कोई सुझाव है?</h3>
        <p>हम हमेशा खुद को बेहतर बनाने के लिए तैयार हैं। यदि आप हमसे कुछ पूछना चाहते हैं या सुझाव देना चाहते हैं, तो बेझिझक संपर्क करें।</p>
        <a href="contact.php" class="btn-contact">हमसे संपर्क करें ✉️</a>
    </section>

</div>

<?php 
include 'includes/footer.php'; 
?>