/**
 * Project: Tc Vacancy Portal - Public Vacancy Details Page Script
 * Optimized Engine: HD Poster Capture, Cross-Browser Copy, Safe Countdown
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // 1. COUNTDOWN TIMER SYSTEM (Cross-Browser Safe)
    // ==========================================
    
    function parseTargetDate(dateStr) {
        if (!dateStr || dateStr.trim() === '' || dateStr === 'N/A') return null;
        
        let s = dateStr.trim();

        // Standard ISO / Date parse
        let d = new Date(s);
        if (!isNaN(d.getTime())) return d.getTime();

        // Replace space with 'T'
        d = new Date(s.replace(/ /g, 'T'));
        if (!isNaN(d.getTime())) return d.getTime();

        // DD-MM-YYYY or DD/MM/YYYY
        let dmyMatch = s.match(/^(\d{1,2})[-/](\d{1,2})[-/](\d{4})(?:[\sT]+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/);
        if (dmyMatch) {
            let day = parseInt(dmyMatch[1], 10);
            let month = parseInt(dmyMatch[2], 10) - 1;
            let year = parseInt(dmyMatch[3], 10);
            let hr = dmyMatch[4] ? parseInt(dmyMatch[4], 10) : 23;
            let min = dmyMatch[5] ? parseInt(dmyMatch[5], 10) : 59;
            let sec = dmyMatch[6] ? parseInt(dmyMatch[6], 10) : 59;
            return new Date(year, month, day, hr, min, sec).getTime();
        }

        // YYYY-MM-DD or YYYY/MM/DD
        let ymdMatch = s.match(/^(\d{4})[-/](\d{1,2})[-/](\d{1,2})(?:[\sT]+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/);
        if (ymdMatch) {
            let year = parseInt(ymdMatch[1], 10);
            let month = parseInt(ymdMatch[2], 10) - 1;
            let day = parseInt(ymdMatch[3], 10);
            let hr = ymdMatch[4] ? parseInt(ymdMatch[4], 10) : 23;
            let min = ymdMatch[5] ? parseInt(ymdMatch[5], 10) : 59;
            let sec = ymdMatch[6] ? parseInt(ymdMatch[6], 10) : 59;
            return new Date(year, month, day, hr, min, sec).getTime();
        }

        return null;
    }

    function initCountdown() {
        const timerBox = document.getElementById('timerBox');
        const countdownTimer = document.getElementById('countdownTimer');
        if (!timerBox || !countdownTimer) return;

        const lastDateAttr = timerBox.getAttribute('data-lastdate');
        const targetDate = parseTargetDate(lastDateAttr);

        if (!targetDate) {
            countdownTimer.innerHTML = "<span style='color:#64748b; font-size:14px; font-weight:600;'>अंतिम तिथि उपलब्ध नहीं है</span>";
            return;
        }

        const daysEl = document.getElementById('days');
        const hoursEl = document.getElementById('hours');
        const minsEl = document.getElementById('mins');
        const secsEl = document.getElementById('secs');

        let intervalId = null;

        function updateTimer() {
            const now = new Date().getTime();
            const difference = targetDate - now;

            if (difference <= 0) {
                countdownTimer.innerHTML = "<div style='background:#ffe4e6; color:#e11d48; padding:8px 12px; border-radius:6px; font-weight:700; font-size:13px; width:100%; text-align:center;'><i class='fas fa-times-circle'></i> आवेदन की अंतिम तिथि समाप्त (Registration Closed)</div>";
                if (intervalId) clearInterval(intervalId);
                return;
            }

            const days = Math.floor(difference / (1000 * 60 * 60 * 24));
            const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((difference % (1000 * 60)) / 1000);

            if (daysEl) daysEl.innerText = String(days).padStart(2, '0');
            if (hoursEl) hoursEl.innerText = String(hours).padStart(2, '0');
            if (minsEl) minsEl.innerText = String(minutes).padStart(2, '0');
            if (secsEl) secsEl.innerText = String(seconds).padStart(2, '0');
        }

        updateTimer();
        intervalId = setInterval(updateTimer, 1000);
    }

    initCountdown();


    // ==========================================
    // 2. COPY MESSAGE TEXT TO CLIPBOARD
    // ==========================================
    const btnCopy = document.getElementById('btnCopy');
    let isCopying = false;

    if (btnCopy) {
        btnCopy.addEventListener('click', function () {
            if (isCopying) return;

            const copyText = document.getElementById("hiddenCopyText");
            if (!copyText) return;

            const textToCopy = copyText.value;
            isCopying = true;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy)
                    .then(() => showCopySuccess(btnCopy))
                    .catch(() => fallbackCopy(copyText, btnCopy));
            } else {
                fallbackCopy(copyText, btnCopy);
            }
        });
    }

    function fallbackCopy(element, btn) {
        element.select();
        element.setSelectionRange(0, 99999);
        try {
            document.execCommand("copy");
            showCopySuccess(btn);
        } catch (err) {
            alert("❌ टेक्स्ट कॉपी करने में समस्या आई, कृपया मैन्युअल कॉपी करें।");
            isCopying = false;
        }
    }

    function showCopySuccess(btn) {
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Copied!';
        btn.style.backgroundColor = '#16a34a';
        btn.style.color = '#ffffff';

        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.style.backgroundColor = '';
            btn.style.color = '';
            isCopying = false;
        }, 2000);
    }


    // ==========================================
    // 3. GENERATE STATUS POSTER (HD html2canvas)
    // ==========================================
    const btnPoster = document.getElementById('btnPoster');
    
    if (btnPoster) {
        btnPoster.addEventListener('click', function () {
            const posterElement = document.getElementById('statusPoster');
            const wrapper = document.getElementById('poster-template-wrapper');

            if (!posterElement || typeof html2canvas === 'undefined') {
                alert('पोस्टर लाइब्रेरी लोड नहीं हो सकी, कृपया पेज रिफ्रेश करें!');
                return;
            }

            const originalBtnText = btnPoster.innerHTML;
            btnPoster.disabled = true;
            btnPoster.innerHTML = '<i class="fas fa-spinner fa-spin"></i> HD पोस्टर बन रहा है...';

            // मेक टेम्पलेट विज़िबल फॉर कैप्चर
            if (wrapper) {
                wrapper.style.position = 'fixed';
                wrapper.style.top = '0';
                wrapper.style.left = '0';
                wrapper.style.zIndex = '-9999';
                wrapper.style.visibility = 'visible';
                wrapper.style.opacity = '1';
            }

            const prepareCanvas = document.fonts ? document.fonts.ready : Promise.resolve();

            prepareCanvas.then(() => {
                return html2canvas(posterElement, {
                    scale: 3,
                    useCORS: true,
                    allowTaint: false,
                    backgroundColor: '#0f172a',
                    logging: false,
                    width: 500
                });
            }).then(canvas => {
                const image = canvas.toDataURL("image/png", 1.0);
                const link = document.createElement('a');

                // FIX: Class name mapped to `.job-title-main` as per HTML
                const titleEl = document.querySelector('.job-title-main') || document.querySelector('h1');
                let pageTitle = titleEl ? titleEl.innerText.trim() : 'vacancy';
                
                let cleanSlug = pageTitle
                    .toLowerCase()
                    .replace(/[^a-z0-9\u0900-\u097F]/gi, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');

                if (!cleanSlug) cleanSlug = 'job-alert';

                link.download = `TC-${cleanSlug}.png`;
                link.href = image;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                btnPoster.disabled = false;
                btnPoster.innerHTML = '<i class="fas fa-check"></i> डाउनलोड हो गया!';

                setTimeout(() => {
                    btnPoster.innerHTML = originalBtnText;
                }, 3000);

            }).catch(err => {
                console.error("Poster Generation Error: ", err);
                alert('पोस्टर बनाने में समस्या आई, कृपया पुनः प्रयास करें।');
                btnPoster.disabled = false;
                btnPoster.innerHTML = originalBtnText;
            }).finally(() => {
                if (wrapper) {
                    wrapper.style.position = 'absolute';
                    wrapper.style.left = '-9999px';
                    wrapper.style.top = '0';
                }
            });
        });
    }

});