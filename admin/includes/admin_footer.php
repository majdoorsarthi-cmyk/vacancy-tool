    </div> 
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var dropdowns = document.querySelectorAll('.sidebar-dropdown > a');
        
        dropdowns.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault(); // # URL को आने से रोकने के लिए
                
                var parentLi = this.parentElement;
                var submenu = parentLi.querySelector('.submenu');
                
                // सबमेन्यू को टॉगल करें
                if (submenu) {
                    // यदि सबमेन्यू visible है, तो उसे hide करें, वरना show करें
                    if (submenu.style.display === 'block') {
                        submenu.style.display = 'none';
                        parentLi.classList.remove('active');
                    } else {
                        // सभी अन्य खुले सबमेन्यू को बंद करें
                        document.querySelectorAll('.submenu').forEach(function(sub) {
                            sub.style.display = 'none';
                            sub.parentElement.classList.remove('active');
                        });
                        
                        // वर्तमान सबमेन्यू खोलें
                        submenu.style.display = 'block';
                        parentLi.classList.add('active');
                    }
                }
            });
        });
    });
</script>
</body>
</html>