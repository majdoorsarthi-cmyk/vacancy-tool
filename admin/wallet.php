<?php
session_start();

// ─── एडमिन सुरक्षा जाँच (REAL AUTHENTICATION CHECK) ───
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include '../includes/db_connect.php';

// वर्तमान URI निकालें (Sidebar एक्टिव स्टेटस के लिए)
$current_uri = basename($_SERVER['PHP_SELF']);

// ─── वॉलेट बैलेंस प्राप्त करने की वास्तविक क्वेरी ───
$wallet_q = $conn->query("SELECT SUM(amount) as total_bal FROM operator_wallet WHERE status='credited'");
$wallet_assoc = $wallet_q->fetch_assoc();
$wallet_balance = floatval($wallet_assoc['total_bal'] ?? 0.00);

// ─── पेजिनेशन और लाइव सर्च लॉजिक ───
$limit = 20; 
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_cond = "";
$search_param = "";

if ($search !== "") {
    $search_cond = " AND (o.username LIKE ? OR v.title LIKE ? OR w.id LIKE ?) ";
    $search_param = "%" . $search . "%";
}

// 1. कुल रिकॉर्ड्स की संख्या गिनें
if ($search !== "") {
    $count_sql = "SELECT COUNT(*) as total FROM operator_wallet w 
                  LEFT JOIN operators o ON w.operator_id = o.id
                  LEFT JOIN vacancies v ON w.vacancy_id = v.id
                  WHERE w.status='credited' $search_cond";
    $c_stmt = $conn->prepare($count_sql);
    $c_stmt->bind_param("sss", $search_param, $search_param, $search_param);
} else {
    $count_sql = "SELECT COUNT(*) as total FROM operator_wallet WHERE status='credited'";
    $c_stmt = $conn->prepare($count_sql);
}
$c_stmt->execute();
$total_rows = $c_stmt->get_result()->fetch_assoc()['total'];
$c_stmt->close();

$total_pages = ceil($total_rows / $limit);

// कुल स्वीकृत (Approved) पोस्ट्स
$total_paid_posts = $conn->query("SELECT COUNT(*) as total FROM operator_wallet WHERE status='credited'")->fetch_assoc()['total'];

// ─── वॉलेट लेजर हिस्ट्री प्राप्त करने की ऑप्टिमाइज्ड क्वेरी ───
$ledger_query = "SELECT w.*, o.username as operator_name, v.title as vacancy_title 
                 FROM operator_wallet w
                 LEFT JOIN operators o ON w.operator_id = o.id
                 LEFT JOIN vacancies v ON w.vacancy_id = v.id
                 WHERE w.status='credited' $search_cond
                 ORDER BY w.id DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($ledger_query);
if ($search !== "") {
    $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$ledger_result = $stmt->get_result();

// ─── [REAL-TIME SYNC] AJAX REQUEST DETECTOR ───
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    ob_start();
    ?>
    <?php if($ledger_result->num_rows > 0): ?>
        <?php while($row = $ledger_result->fetch_assoc()): ?>
            <tr class="text-sm hover:bg-slate-50/50 transition-colors searchable-row">
                <td class="p-4 font-mono text-xs text-slate-400 table-cell-highlight">#<?php echo $row['id']; ?></td>
                <td class="p-4 font-semibold text-slate-700">
                    <div class="flex items-center gap-2">
                        <div class="h-6 w-6 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-[10px] uppercase font-bold">
                            <?php echo substr($row['operator_name'] ?? 'O', 0, 1); ?>
                        </div>
                        <span class="table-cell-highlight"><?php echo htmlspecialchars($row['operator_name'] ?? 'अंजात ऑपरेटर'); ?></span>
                    </div>
                </td>
                <td class="p-4 max-w-xs truncate text-slate-600">
                    <span class="font-medium text-slate-800 block truncate table-cell-highlight"><?php echo htmlspecialchars($row['vacancy_title'] ?? 'N/A'); ?></span>
                    <span class="text-[10px] text-slate-400 font-mono">Ref ID: #<span class="table-cell-highlight"><?php echo $row['vacancy_id']; ?></span></span>
                </td>
                <td class="p-4 font-mono font-bold text-emerald-600">
                    +₹<?php echo number_format((float)$row['amount'], 2); ?>
                </td>
                <td class="p-4">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-wider">
                        <?php echo htmlspecialchars($row['status']); ?>
                    </span>
                </td>
                <td class="p-4 text-right text-xs text-slate-400 font-mono">
                    <?php echo date('d M Y, h:i A', strtotime($row['created_at'] ?? 'now')); ?>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="6" class="p-8 text-center text-slate-400 text-xs">कोई ट्रांजैक्शन रिकॉर्ड नहीं मिला।</td>
        </tr>
    <?php endif; ?>
    <?php
    $table_html = ob_get_clean();

    ob_start();
    ?>
    <?php if ($total_pages > 1): ?>
        <div class="p-4 border-t bg-slate-50/50 flex items-center justify-between text-sm">
            <div class="text-slate-500 text-xs">
                Showing page <b><?php echo $page; ?></b> of <b><?php echo $total_pages; ?></b>
            </div>
            <div class="flex gap-1 pagination-links">
                <a href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>" 
                   data-page="<?php echo max(1, $page - 1); ?>"
                   class="px-3 py-1.5 border bg-white text-slate-600 font-medium rounded-lg hover:bg-slate-50 transition-colors <?php echo ($page <= 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                    <i class="fa-solid fa-angle-left text-xs"></i>
                </a>
                <?php 
                $start_loop = max(1, $page - 2);
                $end_loop = min($total_pages, $page + 2);
                for ($i = $start_loop; $i <= $end_loop; $i++): 
                ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       data-page="<?php echo $i; ?>"
                       class="px-3 py-1.5 border font-mono font-bold text-xs rounded-lg transition-all <?php echo ($page == $i) ? 'bg-indigo-600 border-indigo-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                <a href="?page=<?php echo min($total_pages, $page + 1); ?>&search=<?php echo urlencode($search); ?>" 
                   data-page="<?php echo min($total_pages, $page + 1); ?>"
                   class="px-3 py-1.5 border bg-white text-slate-600 font-medium rounded-lg hover:bg-slate-50 transition-colors <?php echo ($page >= $total_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                    <i class="fa-solid fa-angle-right text-xs"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>
    <?php
    $pagination_html = ob_get_clean();

    header('Content-Type: application/json');
    echo json_encode([
        'table' => $table_html,
        'pagination' => $pagination_html,
        'total' => $total_rows
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>वॉलेट लेजर रिकॉर्ड (Admin Pro)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Segoe UI', system-ui, sans-serif; }
        .highlight-match { background-color: #fef08a; color: #854d0e; padding: 0px 2px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body class="flex min-h-screen overflow-hidden">

    <!-- ─── SIDEBAR CONTAINER ─── -->
    <div class="hidden md:flex flex-col w-64 bg-slate-950 text-slate-300 min-h-screen border-r border-slate-800/50 shrink-0 shadow-[4px_0_24px_rgba(0,0,0,0.3)]">
        <div class="h-20 flex items-center px-6 border-b border-white/5 bg-gradient-to-b from-slate-900 to-transparent gap-3">
            <div class="h-11 w-11 rounded-2xl bg-white/5 flex items-center justify-center border border-white/10 shadow-lg overflow-hidden">
                <div class="fallback-icon h-8 w-8 flex items-center justify-center"><i class="fas fa-briefcase text-indigo-400"></i></div>
            </div>
            <div>
                <h1 class="text-[13px] font-bold text-white tracking-wide">Vacancy Portal</h1>
                <span class="text-[9px] text-indigo-400 font-bold uppercase tracking-[0.2em]">Admin Node</span>
            </div>
        </div>

        <div class="flex-1 flex flex-col justify-between p-4 overflow-y-auto">
            <nav class="space-y-2">
                <p class="px-4 text-[10px] font-black text-slate-600 uppercase tracking-widest mb-3">Main Menu</p>
                <a href="dashboard.php" class="flex items-center gap-4 px-4 py-3 rounded-2xl text-xs font-bold transition-all duration-500 <?php echo ($current_uri == 'dashboard.php') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-900'; ?>">
                    <i class="fas fa-chart-pie text-sm"></i><span>मुख्य डैशबोर्ड</span>
                </a>
                <a href="post_vacancy.php" class="flex items-center gap-4 px-4 py-3 rounded-2xl text-xs font-bold transition-all duration-500 <?php echo ($current_uri == 'post_vacancy.php') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-900'; ?>">
                    <i class="fas fa-plus-circle text-sm"></i><span>नई वैकेंसी डालें / अप्रूवल</span>
                </a>
                <a href="wallet.php" class="flex items-center gap-4 px-4 py-3 rounded-2xl text-xs font-bold transition-all duration-500 <?php echo ($current_uri == 'wallet.php') ? 'bg-indigo-600 text-white shadow-[0_4px_20px_rgba(79,70,229,0.3)]' : 'hover:bg-slate-900'; ?>">
                    <i class="fas fa-wallet text-sm text-white"></i><span class="flex-1">वॉलेट लेजर</span>
                    <?php if($wallet_balance > 0): ?><span class="bg-white/10 px-2.5 py-1 rounded-lg border border-white/10 font-mono text-[10px]">₹<?php echo number_format($wallet_balance, 2); ?></span><?php endif; ?>
                </a>
            </nav>
            <div class="pt-6 border-t border-white/5">
                <a href="logout.php" class="flex items-center gap-4 px-4 py-3 rounded-2xl text-xs font-bold text-rose-400 hover:bg-rose-500/10 transition-all duration-500"><i class="fas fa-sign-out-alt text-sm"></i><span>सुरक्षित लॉग आउट</span></a>
            </div>
        </div>
    </div>

    <!-- ─── MAIN CONTENT AREA ─── -->
    <div class="flex-1 p-4 md:p-8 overflow-y-auto">
        <div class="max-w-6xl mx-auto">
            
            <div class="flex flex-col md:flex-row md:items-center md:justify-between bg-gradient-to-r from-slate-900 to-indigo-950 p-6 rounded-2xl shadow-xl mb-6 border border-slate-800">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-xl bg-emerald-500/20 flex items-center justify-center border border-emerald-500/30 text-emerald-400"><i class="fa-solid fa-wallet text-xl"></i></div>
                    <div>
                        <h1 class="text-xl font-extrabold tracking-tight text-white">ऑपरेटर वॉलेट लेजर (Wallet Ledger)</h1>
                        <p class="text-xs text-slate-400 mt-0.5">ऑरेटर्स को डिलीवर की गई अर्निंग और ट्रांजैक्शन लॉग्स</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">कुल वितरित राशि (Total Paid Out)</p>
                        <h3 class="text-2xl font-black text-slate-800 mt-1">₹<?php echo number_format($wallet_balance, 2); ?></h3>
                    </div>
                    <div class="h-12 w-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-lg shadow-inner"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">कुल पेड वैकेंसी (Paid Posts)</p>
                        <h3 class="text-2xl font-black text-slate-800 mt-1"><?php echo $total_paid_posts; ?> Tasks</h3>
                    </div>
                    <div class="h-12 w-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-lg shadow-inner"><i class="fa-solid fa-circle-check"></i></div>
                </div>
            </div>

            <!-- LIVE SEARCH INPUT -->
            <div class="mb-4">
                <div class="relative max-w-md">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-sm"></i>
                    <input type="text" id="live-search" value="<?php echo htmlspecialchars($search); ?>" autocomplete="off" placeholder="ऑपरेटर का नाम या आईडी खोजें (2+ शब्द टाइप करें)..." class="w-full pl-10 pr-10 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    <button id="clear-search" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 hidden"><i class="fa-solid fa-circle-xmark"></i></button>
                </div>
            </div>

            <!-- Ledger Table Grid -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b bg-slate-50/50 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-700"><i class="fa-solid fa-list-check mr-1.5 text-indigo-500"></i> ट्रांजैक्शन हिस्ट्री रिकॉर्ड</h3>
                    <span class="text-[10px] bg-indigo-50 text-indigo-600 border border-indigo-100 px-2 py-0.5 rounded-md font-mono font-bold">Total: <span id="total-count"><?php echo $total_rows; ?></span></span>
                </div>
                
                <div class="overflow-x-auto relative">
                    <!-- Loading Overlay Indicator -->
                    <div id="loading-overlay" class="absolute inset-0 bg-white/50 backdrop-blur-[1px] hidden items-center justify-center z-10">
                        <i class="fa-solid fa-spinner fa-spin text-indigo-600 text-xl"></i>
                    </div>

                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 text-xs font-bold uppercase tracking-wider">
                                <th class="p-4">ID</th>
                                <th class="p-4">ऑपरेटर</th>
                                <th class="p-4">वैकेंसी संदर्भ (Vacancy Details)</th>
                                <th class="p-4">राशि (Amount)</th>
                                <th class="p-4">स्टेटस (Status)</th>
                                <th class="p-4 text-right">समय (Timestamp)</th>
                            </tr>
                        </thead>
                        
                        <tbody class="divide-y divide-slate-100" id="table-rows">
                            <?php if($ledger_result->num_rows > 0): ?>
                                <?php while($row = $ledger_result->fetch_assoc()): ?>
                                    <tr class="text-sm hover:bg-slate-50/50 transition-colors searchable-row">
                                        <td class="p-4 font-mono text-xs text-slate-400 table-cell-highlight">#<?php echo $row['id']; ?></td>
                                        <td class="p-4 font-semibold text-slate-700">
                                            <div class="flex items-center gap-2">
                                                <div class="h-6 w-6 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-[10px] uppercase font-bold">
                                                    <?php echo substr($row['operator_name'] ?? 'O', 0, 1); ?>
                                                </div>
                                                <span class="table-cell-highlight"><?php echo htmlspecialchars($row['operator_name'] ?? 'अंजात ऑपरेटर'); ?></span>
                                            </div>
                                        </td>
                                        <td class="p-4 max-w-xs truncate text-slate-600">
                                            <span class="font-medium text-slate-800 block truncate table-cell-highlight"><?php echo htmlspecialchars($row['vacancy_title'] ?? 'N/A'); ?></span>
                                            <span class="text-[10px] text-slate-400 font-mono">Ref ID: #<span class="table-cell-highlight"><?php echo $row['vacancy_id']; ?></span></span>
                                        </td>
                                        <td class="p-4 font-mono font-bold text-emerald-600">+₹<?php echo number_format((float)$row['amount'], 2); ?></td>
                                        <td class="p-4">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-wider"><?php echo htmlspecialchars($row['status']); ?></span>
                                        </td>
                                        <td class="p-4 text-right text-xs text-slate-400 font-mono"><?php echo date('d M Y, h:i A', strtotime($row['created_at'] ?? 'now')); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="p-8 text-center text-slate-400 text-xs">कोई ट्रांजैक्शन रिकॉर्ड नहीं मिला।</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Target Container -->
                <div id="pagination-container">
                    <?php if ($total_pages > 1): ?>
                        <div class="p-4 border-t bg-slate-50/50 flex items-center justify-between text-sm">
                            <div class="text-slate-500 text-xs">Showing page <b><?php echo $page; ?></b> of <b><?php echo $total_pages; ?></b></div>
                            <div class="flex gap-1 pagination-links">
                                <a href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>" data-page="<?php echo max(1, $page - 1); ?>" class="px-3 py-1.5 border bg-white text-slate-600 font-medium rounded-lg hover:bg-slate-50 <?php echo ($page <= 1) ? 'opacity-50 pointer-events-none' : ''; ?>"><i class="fa-solid fa-angle-left text-xs"></i></a>
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" data-page="<?php echo $i; ?>" class="px-3 py-1.5 border font-mono font-bold text-xs rounded-lg transition-all <?php echo ($page == $i) ? 'bg-indigo-600 border-indigo-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50'; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                <a href="?page=<?php echo min($total_pages, $page + 1); ?>&search=<?php echo urlencode($search); ?>" data-page="<?php echo min($total_pages, $page + 1); ?>" class="px-3 py-1.5 border bg-white text-slate-600 font-medium rounded-lg hover:bg-slate-50 <?php echo ($page >= $total_pages) ? 'opacity-50 pointer-events-none' : ''; ?>"><i class="fa-solid fa-angle-right text-xs"></i></a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- ─── REAL-TIME AJAX LIVE SEARCH SCRIPT ─── -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById('live-search');
        const clearBtn = document.getElementById('clear-search');
        const tableRows = document.getElementById('table-rows');
        const paginationContainer = document.getElementById('pagination-container');
        const totalCount = document.getElementById('total-count');
        const loadingOverlay = document.getElementById('loading-overlay');
        
        let debounceTimer;
        let currentPage = 1;

        if(searchInput.value.length > 0) clearBtn.classList.remove('hidden');

        // Text Highlight करने का फ़ंक्शन
        function highlightText(query) {
            if (!query || query.length < 2) return;
            
            const targetCells = document.querySelectorAll('.table-cell-highlight');
            const regex = new RegExp(`(${query.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')})`, 'gi');

            targetCells.forEach(cell => {
                // पहले से मौजूद HTML से टेक्स्ट निकालकर केवल मैचिंग वर्ड को रिप्लेस करेंगे
                let text = cell.textContent;
                if (text.trim() !== "") {
                    cell.innerHTML = text.replace(regex, '<span class="highlight-match">$1</span>');
                }
            });
        }

        function fetchData(searchQuery = '', page = 1) {
            loadingOverlay.classList.remove('hidden');
            loadingOverlay.classList.add('flex');

            const url = `wallet.php?search=${encodeURIComponent(searchQuery)}&page=${page}`;

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                // टेबल बॉडी और कंटेंट रिप्लेसमेंट
                tableRows.innerHTML = data.table;
                paginationContainer.innerHTML = data.pagination;
                totalCount.textContent = data.total;
                
                window.history.pushState(null, '', url);
                
                // रिस्पॉन्स आने के तुरंत बाद हाईलाइट रन करें
                highlightText(searchQuery);
            })
            .catch(error => console.error('Error fetching data:', error))
            .finally(() => {
                loadingOverlay.classList.add('hidden');
                loadingOverlay.classList.remove('flex');
            });
        }

        // इनपुट लिसनर विथ 300ms डिबाउंस (Debounce)
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            currentPage = 1;

            if (query.length > 0) {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
            }

            clearTimeout(debounceTimer);
            
            // 2 या 2 से अधिक शब्द होने पर फ़ेच करें, खाली होने पर पूरा डेटा रीसेट करे
            if (query.length >= 2 || query.length === 0) {
                debounceTimer = setTimeout(() => {
                    fetchData(query, currentPage);
                }, 300);
            }
        });

        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            this.classList.add('hidden');
            currentPage = 1;
            fetchData('', currentPage);
            searchInput.focus();
        });

        document.addEventListener('click', function(e) {
            const pageLink = e.target.closest('#pagination-container a');
            if (pageLink) {
                e.preventDefault();
                currentPage = pageLink.getAttribute('data-page');
                const query = searchInput.value.trim();
                fetchData(query, currentPage);
            }
        });

        // पेज लोड पर अगर पहले से इनपुट में वैल्यू हो तो तुरंत हाईलाइट करें
        if(searchInput.value.trim().length >= 2) {
            highlightText(searchInput.value.trim());
        }
    });
    </script>
</body>
</html>