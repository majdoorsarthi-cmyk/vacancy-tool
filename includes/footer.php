<footer class="main-footer">
    <div class="container footer-grid">
        
        <!-- Section 1: About -->
        <div class="footer-col footer-about">
            <h4 class="footer-title">Vacancy Portal</h4>
            <p>हमारा उद्देश्य भारत के सभी छात्रों तक सरकारी नौकरी, एडमिट कार्ड और परीक्षा परिणाम की जानकारी सबसे तेज और सही भाषा में पहुँचाना है।</p>
            <div class="footer-social">
                <a href="#" class="social-icon" title="Telegram"><i class="fab fa-telegram"></i></a>
               <a href="https://chat.whatsapp.com/GF1BDKhdBVZ5AH59aVmcRJ"
   target="_blank"
   class="social-icon"
   title="WhatsApp Group">
    <i class="fab fa-whatsapp"></i>
</a>
                <a href="#" class="social-icon" title="Facebook"><i class="fab fa-facebook-f"></i></a>
            </div>
        </div>

        <!-- Section 2: Quick Links -->
        <div class="footer-col footer-links">
            <h4 class="footer-title">त्वरित लिंक</h4>
            <ul>
                <li><a href="index.php">मुख्य पृष्ठ (Home)</a></li>
                <li><a href="job.php">नई भर्तियां</a></li>
                <li><a href="results.php">सरकारी रिजल्ट</a></li>
                <li><a href="admit_cards.php">एडमिट कार्ड</a></li>
            </ul>
        </div>

        <!-- Section 3: Legal (AdSense Must-Have) -->
        <div class="footer-col footer-policy">
            <h4 class="footer-title">कानूनी सहायता</h4>
            <ul>
                <li><a href="about.php">हमारे बारे में</a></li>
                <li><a href="privacy.php">गोपनीयता नीति</a></li>
                <li><a href="terms.php">नियम और शर्तें</a></li>
                <li><a href="disclaimer.php">अस्वीकरण (Disclaimer)</a></li>
                <li><a href="contact.php">संपर्क करें</a></li>
            </ul>
        </div>

        <!-- Section 4: Contact/Help -->
        <div class="footer-col footer-contact">
            <h4 class="footer-title">सहायता केंद्र</h4>
            <p><i class="fas fa-envelope"></i> info@vacancyportal.co.in</p>
            <p><i class="fas fa-map-marker-alt"></i> नई दिल्ली, भारत</p>
            <div class="disclaimer-mini">
                <small>* कृपया किसी भी आवेदन से पहले आधिकारिक अधिसूचना अवश्य पढ़ें।</small>
            </div>
        </div>
        
    </div>
    
    <!-- Footer Bottom Bar -->
    <div class="footer-bottom">
        <div class="container footer-bottom-content">
            <p class="copyright">© <?php echo date('Y'); ?> <strong>Vacancy Portal</strong>. सभी अधिकार सुरक्षित।</p>
            <div class="footer-bottom-links">
                <a href="admin/login.php" class="admin-link"><i class="fas fa-user-shield"></i> एडमिन</a>
            </div>
        </div>
    </div>
</footer>

<!-- CSS for Perfect Display on All Devices -->
<style>
    :root {
        --footer-bg: #1a202c;
        --footer-text: #e2e8f0;
        --accent-color: #3b82f6;
        --border-color: #2d3748;
    }

    .main-footer {
        background-color: var(--footer-bg);
        color: var(--footer-text);
        padding: 50px 0 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr 1fr 1fr;
        gap: 40px;
        padding-bottom: 40px;
    }

    .footer-title {
        color: #fff;
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 20px;
        position: relative;
        padding-bottom: 10px;
    }

    .footer-title::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 40px;
        height: 3px;
        background: var(--accent-color);
    }

    .footer-col p {
        line-height: 1.6;
        font-size: 0.95rem;
    }

    .footer-col ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-col ul li {
        margin-bottom: 12px;
    }

    .footer-col ul li a {
        color: var(--footer-text);
        text-decoration: none;
        transition: color 0.3s ease, padding-left 0.3s ease;
        display: inline-block;
    }

    .footer-col ul li a:hover {
        color: var(--accent-color);
        padding-left: 5px;
    }

    .footer-social {
        margin-top: 20px;
        display: flex;
        gap: 15px;
    }

    .social-icon {
        background: var(--border-color);
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #fff;
        text-decoration: none;
        transition: transform 0.3s, background 0.3s;
    }

    .social-icon:hover {
        background: var(--accent-color);
        transform: translateY(-3px);
    }

    .disclaimer-mini {
        margin-top: 15px;
        padding: 10px;
        background: rgba(255,255,255,0.05);
        border-radius: 5px;
        border-left: 3px solid #f59e0b;
    }

    .footer-bottom {
        border-top: 1px solid var(--border-color);
        padding: 20px 0;
        background-color: #111827;
    }

    .footer-bottom-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }

    .copyright {
        margin: 0;
        font-size: 0.9rem;
    }

    .admin-link {
        color: #718096;
        text-decoration: none;
        font-size: 0.85rem;
    }

    /* Mobile Responsive Logic */
    @media (max-width: 992px) {
        .footer-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 600px) {
        .footer-grid {
            grid-template-columns: 1fr;
            text-align: center;
            gap: 30px;
        }

        .footer-title::after {
            left: 50%;
            transform: translateX(-50%);
        }

        .footer-social {
            justify-content: center;
        }

        .footer-bottom-content {
            flex-direction: column;
            gap: 10px;
        }
    }
</style>

<!-- Font Awesome for Icons (Make sure this is in your head or here) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

 
</body>
</html>