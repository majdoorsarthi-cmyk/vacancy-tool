<?php
// यह फ़ाइल केवल शामिल (include) की जाएगी।
// सुरक्षा जाँच कॉलिंग फ़ाइल (dashboard.php, add_vacancy.php, आदि) में होनी चाहिए।

// डिफ़ॉल्ट सुरक्षित वेरिएबल्स सेट करें
$pending_withdrawals_count = 0;
$pending_operators_count   = 0;
$pending_vacancies_count   = 0;
$pending_posters_count     = 0;

if (isset($conn)) {
    // 🛡️ PHP 8.1+ Fatal Error से बचाने के लिए MySQLi एक्सेप्शन रिपोर्टिंग ऑफ करें
    @mysqli_report(MYSQLI_REPORT_OFF);

    // 1. पेंडिंग विड्रॉल काउंट
    try {
        $count_query = "SELECT COUNT(*) as total FROM withdrawal_requests WHERE status = 'pending'";
        $count_result = @mysqli_query($conn, $count_query);
        if ($count_result && $row = mysqli_fetch_assoc($count_result)) {
            $pending_withdrawals_count = intval($row['total'] ?? 0);
        }
    } catch (Throwable $e) {
        $pending_withdrawals_count = 0;
    }

    // 2. पेंडिंग ऑपरेटर वेरिफिकेशन काउंट
    try {
        $op_count_query = "SELECT COUNT(*) as total FROM operators WHERE is_active = 0";
        $op_count_result = @mysqli_query($conn, $op_count_query);
        if ($op_count_result && $row = mysqli_fetch_assoc($op_count_result)) {
            $pending_operators_count = intval($row['total'] ?? 0);
        }
    } catch (Throwable $e) {
        $pending_operators_count = 0;
    }

    // 3. पेंडिंग वैकेंसी काउंट
    try {
        $vac_count_query = "SELECT COUNT(*) as total FROM vacancies WHERE status = 'pending'";
        $vac_count_result = @mysqli_query($conn, $vac_count_query);
        if ($vac_count_result && $row = mysqli_fetch_assoc($vac_count_result)) {
            $pending_vacancies_count = intval($row['total'] ?? 0);
        }
    } catch (Throwable $e) {
        $pending_vacancies_count = 0;
    }

    // 4. पेंडिंग पोस्टर काउंट
    try {
        $post_count_query = "SELECT COUNT(*) as total FROM posters WHERE status = 'pending'";
        $post_count_result = @mysqli_query($conn, $post_count_query);
        if ($post_count_result && $row = mysqli_fetch_assoc($post_count_result)) {
            $pending_posters_count = intval($row['total'] ?? 0);
        }
    } catch (Throwable $e) {
        $pending_posters_count = 0;
    }
}

// --- एक्टिव स्टेट लॉजिक ---
$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$current_category = $_GET['category'] ?? '';

$active_files = ['add_vacancy.php', 'add_result.php', 'admin_approve.php', 'manage_vacancies.php', 'manage_categories.php', 'verify_operator.php', 'manage_operators.php', 'manage_withdrawals.php'];

function is_active_file($file_name) {
    global $current_page;
    return ($current_page == $file_name) ? 'active' : '';
}

function is_dropdown_open($files, $categories = []) {
    global $current_page, $current_category;
    if (in_array($current_page, $files)) {
        if ($current_page == 'manage_vacancies.php' && !empty($categories)) {
            if (in_array($current_category, $categories)) {
                return ' active';
            }
        } elseif (empty($categories)) {
            return ' active';
        }
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="vp.png">
    <link rel="apple-touch-icon" href="vp.png">
    <title>एडमिन पैनल | Vacancy Portal</title>
    <link rel="stylesheet" href="assets/css/admin_style.css?v=<?php echo time(); ?>"> 
    
    <style>
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        .admin-table th, .admin-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .admin-table th { background-color: #f2f2f2; color: #333; }
        .btn-edit { background: #ffc107; color: #333; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 0.9em; display: inline-block; }
        .btn-delete { background: #dc3545; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 0.9em; margin-left: 5px; display: inline-block; }
        
        .status-badge { padding: 3px 8px; border-radius: 3px; font-size: 0.75em; font-weight: bold; color: white; }
        .status-badge.pending { background: #ffc107; color: #333; }
        .status-badge.live { background: #28a745; }
        
        .category-badge { padding: 3px 8px; border-radius: 3px; font-size: 0.75em; font-weight: bold; color: white; }
        .category-badge.job { background: #28a745; }
        .category-badge.result { background: #ffc107; color: #333;}
        .category-badge.admit_card { background: #dc3545; }
        .category-badge.other { background: #6c757d; }

        /* 🚨 रेड अलर्ट नोटिफिकेशन काउंट बैज */
        .notification-count {
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 7px;
            font-size: 0.75em;
            font-weight: bold;
            margin-left: 8px;
            display: inline-block;
            animation: pulse 2s infinite;
        }
        
        .hide-badge {
            display: none !important;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
<div id="wrapper">
    <div id="sidebar-wrapper">
    <ul class="sidebar-nav">
        <li class="sidebar-brand">
            <a href="dashboard.php">⚙️ Admin Panel</a>
        </li>

        <li class="<?php echo is_active_file('dashboard.php'); ?>">
            <a href="dashboard.php">🏠 डैशबोर्ड</a>
        </li>

        <?php 
            $post_management_files = ['add_vacancy.php', 'add_result.php', 'admin_approve.php', 'manage_vacancies.php'];
            $post_management_is_open = is_dropdown_open($post_management_files);
        ?>
        <li class="sidebar-dropdown<?php echo $post_management_is_open; ?>">
            <a href="javascript:void(0);" class="dropdown-toggle">
                📢 पोस्ट प्रबंधन
                <!-- 🌟 मुख्य मेनू पर भी अलर्ट काउंट दिखेगा -->
                <span class="notification-count js-vacancy-count <?php echo ($pending_vacancies_count > 0) ? '' : 'hide-badge'; ?>">
                    <?php echo $pending_vacancies_count; ?>
                </span>
            </a>
            <ul class="submenu">
                <li class="<?php echo is_active_file('add_vacancy.php'); ?>"><a href="add_vacancy.php">➕ नई जॉब रिक्ति जोड़ें</a></li>
                <li class="<?php echo is_active_file('add_result.php'); ?>"><a href="add_result.php">➕ नई परिणाम पोस्ट जोड़ें</a></li> 
                <li class="<?php echo is_active_file('manage_vacancies.php') && empty($current_category) ? 'active' : ''; ?>"><a href="manage_vacancies.php">📝 सभी पोस्ट प्रबंधित करें</a></li>
            </ul>
        </li>
        
        <?php 
            $category_management_files = ['manage_categories.php', 'manage_vacancies.php'];
            $category_management_categories = ['job', 'result', 'admit_card', 'other'];
            $category_management_is_open = is_dropdown_open($category_management_files, $category_management_categories) || is_active_file('manage_categories.php');
        ?>
        <li class="sidebar-dropdown<?php echo $category_management_is_open; ?>">
            <a href="javascript:void(0);" class="dropdown-toggle">🏷️ श्रेणी के अनुसार प्रबंधन</a>
            <ul class="submenu">
                <li class="<?php echo is_active_file('manage_categories.php'); ?>"><a href="manage_categories.php">🛠️ श्रेणियाँ सेटिंग्स</a></li> 
                <li class="<?php echo ($current_page == 'manage_vacancies.php' && $current_category == 'job') ? 'active' : ''; ?>"><a href="manage_vacancies.php?category=job">💼 रिक्ति (Job)</a></li>
                <li class="<?php echo ($current_page == 'manage_vacancies.php' && $current_category == 'result') ? 'active' : ''; ?>"><a href="manage_vacancies.php?category=result">📋 परिणाम (Result)</a></li>
                <li class="<?php echo ($current_page == 'manage_vacancies.php' && $current_category == 'admit_card') ? 'active' : ''; ?>"><a href="manage_vacancies.php?category=admit_card">🎟️ एडमिट कार्ड</a></li>
                <li class="<?php echo ($current_page == 'manage_vacancies.php' && $current_category == 'other') ? 'active' : ''; ?>"><a href="manage_vacancies.php?category=other">📂 अन्य पोस्ट</a></li>
            </ul>
        </li>

        <!-- 👥 ऑपरेटर प्रबंधन -->
        <?php 
            $operator_management_files = ['manage_operators.php', 'verify_operator.php'];
            $operator_management_is_open = is_dropdown_open($operator_management_files);
        ?>
        <li class="sidebar-dropdown<?php echo $operator_management_is_open; ?>">
            <a href="javascript:void(0);" class="dropdown-toggle">👥 ऑपरेटर प्रबंधन</a>
            <ul class="submenu">
              <li class="<?php echo is_active_file('edit_operator_rates.php'); ?>">
            <a href="edit_operator_rates.php">🛠️ ऑपरेटर डिटेल्स एवं रेट्स</a>
        </li>
                <li class="<?php echo is_active_file('manage_operators.php'); ?>"><a href="manage_operators.php">⚙️ सक्रिय ऑपरेटर लिस्ट</a></li>
                
            </ul>
        </li>
      <li class="<?php echo is_active_file('admin_approve.php'); ?>">
                    <a href="admin_approve.php">
                        ✔️ वैकेंसी अप्रूवल पैनल
                        <!-- 🌟 विशिष्ट अप्रूवल पैनल के अंदर भी लाल नंबर दिखेगा -->
                        <span class="notification-count js-vacancy-count <?php echo ($pending_vacancies_count > 0) ? '' : 'hide-badge'; ?>">
                            <?php echo $pending_vacancies_count; ?>
                        </span>
                    </a>
                </li>
<li class="<?php echo is_active_file('verify_operator.php'); ?>">
                    <a href="verify_operator.php">
                        🧑‍💻 ऑपरेटर सत्यापन पैनल
                        <span class="notification-count js-operator-count <?php echo ($pending_operators_count > 0) ? '' : 'hide-badge'; ?>">
                            <?php echo $pending_operators_count; ?>
                        </span>
                    </a>
                </li>
      <li class="<?php echo is_active_file('manage_promotions.php'); ?>">
    <a href="manage_promotions.php">
        🏆 ऑपरेटर रैंक व प्रमोशन
        <span class="notification-count" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff;">
            PRO
        </span>
    </a>
</li>
        <!-- ✨ निकासी प्रबंधन -->
        <li class="<?php echo is_active_file('manage_withdrawals.php'); ?>">
            <a href="manage_withdrawals.php">
                💸 ऑपरेटर निकासी प्रबंधन 
                <span class="notification-count js-withdrawal-count <?php echo ($pending_withdrawals_count > 0) ? '' : 'hide-badge'; ?>">
                    <?php echo $pending_withdrawals_count; ?>
                </span>
            </a>
        </li>
      <li class="<?php echo is_active_file('upload_poster.php'); ?>">
    <a href="upload_poster.php">
        <i class="fas fa-image me-2"></i> अपलोड पोस्टर प्रबंधन 
        <span class="notification-count js-poster-count <?php echo ($pending_posters_count > 0) ? '' : 'hide-badge'; ?>">
            <?php echo $pending_posters_count; ?>
        </span>
    </a>
</li>

        <li>
            <a href="logout.php">➡️ लॉग आउट</a>
        </li>
    </ul>
</div>

<!-- ─── जावास्क्रिप्ट क्लिक एवं लाइव रिफ्रेश लॉजिक ─── -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. साइडबार ड्रॉपडाउन ओपन/क्लोज
    document.querySelectorAll('.sidebar-dropdown > a.dropdown-toggle').forEach(function(toggleLink) {
        toggleLink.addEventListener('click', function(e) {
            e.preventDefault(); 
            var parentLi = this.parentElement;
            if (parentLi.classList.contains('active')) {
                parentLi.classList.remove('active');
            } else {
                document.querySelectorAll('.sidebar-dropdown').forEach(function(otherLi) {
                    otherLi.classList.remove('active');
                });
                parentLi.classList.add('active');
            }
        });
    });

    // 2. ⏳ बिना रिफ्रेश लाइव रेड अलर्ट नंबर अपडेट करने का AJAX लॉजिक
    function updateLiveAlertCounts() {
        fetch('includes/get_counts.php') 
            .then(response => {
                if (!response.ok) throw new Error('Network response error');
                return response.json();
            })
            .then(data => {
                // 1. ऑपरेटर वेरिफिकेशन काउंट अपडेट
                const operatorBadge = document.querySelector('.js-operator-count');
                if (operatorBadge) {
                    operatorBadge.textContent = data.pending_operators;
                    if (parseInt(data.pending_operators) > 0) operatorBadge.classList.remove('hide-badge');
                    else operatorBadge.classList.add('hide-badge');
                }

                // 2. विड्रॉल काउंट अपडेट
                const withdrawalBadge = document.querySelector('.js-withdrawal-count');
                if (withdrawalBadge) {
                    withdrawalBadge.textContent = data.pending_withdrawals;
                    if (parseInt(data.pending_withdrawals) > 0) withdrawalBadge.classList.remove('hide-badge');
                    else withdrawalBadge.classList.add('hide-badge');
                }

                // 🌟 3. वैकेंसी अप्रूवल काउंट लाइव अपडेट (सभी जगह जहाँ .js-vacancy-count है)
                const vacancyBadges = document.querySelectorAll('.js-vacancy-count');
                vacancyBadges.forEach(badge => {
                    badge.textContent = data.pending_vacancies;
                    if (parseInt(data.pending_vacancies) > 0) badge.classList.remove('hide-badge');
                    else badge.classList.add('hide-badge');
                });
            })
            .catch(error => console.error('Error fetching live counts:', error));
    }

    // हर 5 सेकंड में सिंक करें
    setInterval(updateLiveAlertCounts, 5000);
});
</script>
</body>
</html>