document.addEventListener('DOMContentLoaded', function() {
    const notificationContainer = document.getElementById('live-notification-bar');
    if (!notificationContainer) return;

    function fetchLiveUpdates() {
        // ध्यान दें: फ़ाइल पथ सही होना चाहिए जब index.php या अन्य फ्रंट-एंड पेज से कॉल किया जाए
        fetch('includes/check_updates.php') 
            .then(response => response.json())
            .then(data => {
                const newCount = parseInt(data.new_count);
                
                if (newCount > 0) {
                    notificationContainer.innerHTML = 
                        `<a href="index.php?filter=new" class="notification-link">
                            🔔 **LIVE:** पिछले 24 घंटों में **${newCount}** नई भर्तियां पोस्ट की गई हैं! जल्दी देखें!
                        </a>`;
                    notificationContainer.style.display = 'block';
                } else {
                    notificationContainer.style.display = 'none';
                }

            })
            .catch(error => {
                console.error('Error fetching live updates:', error);
            });
    }

    fetchLiveUpdates(); // Initial fetch
    setInterval(fetchLiveUpdates, 30000); // 30 सेकंड में दोबारा जाँच करें
});