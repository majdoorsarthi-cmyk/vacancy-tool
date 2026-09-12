<?php
// वर्तमान पेज का नाम पहचानें (Active State Detect करने के लिए)
$current_uri = basename($_SERVER['PHP_SELF']);

// वॉलेट बैलेंस डिफ़ॉल्ट (यदि बैकएंड से उपलब्ध न हो)
$wallet_balance = isset($wallet_balance) ? $wallet_balance : 0.00;
?>

<!-- Custom CSS for Custom Scrollbar & Smooth Styling -->
<style>
    .custom-sidebar-scroll::-webkit-scrollbar {
        width: 4px;
    }
    .custom-sidebar-scroll::-webkit-scrollbar-track {
        background: rgba(15, 23, 42, 0.6);
    }
    .custom-sidebar-scroll::-webkit-scrollbar-thumb {
        background: #334155;
        border-radius: 4px;
    }
    .custom-sidebar-scroll::-webkit-scrollbar-thumb:hover {
        background: #475569;
    }
</style>

<!-- ==========================================
     1. DESKTOP SIDEBAR (Large Screens)
=========================================== -->
<aside class="hidden lg:flex flex-col w-72 bg-slate-950 text-slate-300 h-screen sticky top-0 border-r border-slate-800/80 shrink-0 shadow-[4px_0_24px_rgba(0,0,0,0.5)] z-40">
    
    <!-- Workspace Branding Header -->
    <div class="h-20 flex items-center px-6 border-b border-slate-800/80 bg-slate-950/90 backdrop-blur-md shrink-0 gap-3.5">
        <div class="h-11 w-11 rounded-2xl bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500 p-[1px] shadow-lg shadow-indigo-500/20 overflow-hidden shrink-0">
            <div class="w-full h-full bg-slate-950 rounded-[15px] flex items-center justify-center">
                <img src="serve_logo.php" 
                     onerror="this.style.display='none'; this.parentElement.querySelector('.fallback-icon').style.display='flex';" 
                     alt="Logo" class="h-7 w-7 object-contain">
                <div class="fallback-icon h-7 w-7 hidden items-center justify-center">
                    <i class="fas fa-layer-group text-indigo-400"></i>
                </div>
            </div>
        </div>
        <div class="overflow-hidden">
            <h1 class="text-sm font-black text-white tracking-wider truncate">VACANCY PORTAL</h1>
            <span class="text-[9px] text-indigo-400 font-bold uppercase tracking-[0.2em] flex items-center gap-1.5 mt-0.5">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span>
                <span class="truncate">Operator Node</span>
            </span>
        </div>
    </div>

    <!-- Navigation Menu (Scrollable) -->
    <div class="flex-1 overflow-y-auto p-4 space-y-6 custom-sidebar-scroll">
        <nav class="space-y-1.5">
            
            <!-- SECTION 1: CORE WORKSPACE -->
            <p class="px-3 text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Main Console</p>
            
            <!-- Dashboard -->
            <a href="dashboard.php" class="relative flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 group <?php echo ($current_uri == 'dashboard.php') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-extrabold' : 'hover:bg-slate-900 text-slate-400 hover:text-white'; ?>">
                <i class="fas fa-chart-pie text-sm <?php echo ($current_uri == 'dashboard.php') ? 'text-white' : 'text-indigo-400 group-hover:scale-110 transition-transform'; ?>"></i>
                <span>मुख्य डैशबोर्ड</span>
            </a>

            <!-- Rank & Rewards (NEWLY SYNCED) -->
            <a href="rank_rewards.php" class="relative flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 group <?php echo ($current_uri == 'rank_rewards.php') ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20 font-black' : 'hover:bg-slate-900 text-amber-400 hover:text-amber-300'; ?>">
                <i class="fas fa-trophy text-sm <?php echo ($current_uri == 'rank_rewards.php') ? 'text-slate-950' : 'text-amber-400 group-hover:scale-110 transition-transform'; ?>"></i>
                <span class="flex-1">रैंक व प्रमोशन</span>
                <span class="px-1.5 py-0.5 text-[9px] bg-amber-400/20 text-amber-300 rounded font-black border border-amber-400/30 uppercase">NEW</span>
            </a>

            <!-- SECTION 2: CONTENT MANAGEMENT -->
            <p class="pt-4 px-3 text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Content Manager</p>

            <!-- Vacancy Management -->
            <a href="post_vacancy.php" class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 group <?php echo ($current_uri == 'post_vacancy.php' || $current_uri == 'manage_vacancies.php') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'hover:bg-slate-900 text-slate-400 hover:text-white'; ?>">
                <i class="fas fa-plus-circle text-sm <?php echo ($current_uri == 'post_vacancy.php') ? 'text-white' : 'text-emerald-400 group-hover:scale-110 transition-transform'; ?>"></i>
                <span class="flex-1">नई जॉब पोस्ट करें</span>
            </a>

            <!-- Post Posters -->
            <a href="manage_posters.php" class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 group <?php echo ($current_uri == 'manage_posters.php') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'hover:bg-slate-900 text-slate-400 hover:text-white'; ?>">
                <i class="fas fa-image text-sm <?php echo ($current_uri == 'manage_posters.php') ? 'text-white' : 'text-pink-400 group-hover:scale-110 transition-transform'; ?>"></i>
                <span>पोस्टर्स गैलरी</span>
            </a>

            <!-- Results -->
            <a href="manage_results.php" class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 group <?php echo ($current_uri == 'manage_results.php') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'hover:bg-slate-900 text-slate-400 hover:text-white'; ?>">
                <i class="fas fa-poll text-sm <?php echo ($current_uri == 'manage_results.php') ? 'text-white' : 'text-purple-400 group-hover:scale-110 transition-transform'; ?>"></i>
                <span>रिजल्ट अपडेट्स</span>
            </a>

            <!-- Admit Cards -->
            <a href="manage_admit_cards.php" class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 group <?php echo ($current_uri == 'manage_admit_cards.php') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'hover:bg-slate-900 text-slate-400 hover:text-white'; ?>">
                <i class="fas fa-id-card text-sm <?php echo ($current_uri == 'manage_admit_cards.php') ? 'text-white' : 'text-amber-400 group-hover:scale-110 transition-transform'; ?>"></i>
                <span>एडमिट कार्ड्स</span>
            </a>

            <!-- PDF Forms & Schemes -->
            <a href="manage_pdf_forms.php" class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 group <?php echo ($current_uri == 'manage_pdf_forms.php') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'hover:bg-slate-900 text-slate-400 hover:text-white'; ?>">
                <i class="fas fa-file-pdf text-sm <?php echo ($current_uri == 'manage_pdf_forms.php') ? 'text-white' : 'text-orange-400 group-hover:scale-110 transition-transform'; ?>"></i>
                <span>PDF फॉर्म्स / योजनाएं</span>
            </a>

            <!-- SECTION 3: FINANCE & WALLET -->
            <p class="pt-4 px-3 text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Finance & Billing</p>

            <!-- Wallet -->
            <a href="wallet.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 group <?php echo ($current_uri == 'wallet.php') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'hover:bg-slate-900 text-slate-400 hover:text-white'; ?>">
                <i class="fas fa-wallet text-sm <?php echo ($current_uri == 'wallet.php') ? 'text-white' : 'text-cyan-400 group-hover:scale-110 transition-transform'; ?>"></i>
                <span class="flex-1">वॉलेट खाता</span>
                <span class="bg-slate-900 group-hover:bg-emerald-950/80 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-lg font-mono text-[11px] font-black">
                    ₹<?php echo number_format((float)$wallet_balance, 2); ?>
                </span>
            </a>

            <!-- SECTION 4: ACCOUNT SETTINGS -->
            <p class="pt-4 px-3 text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Settings & Security</p>

            <!-- Profile -->
            <a href="profile.php" class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 group <?php echo ($current_uri == 'profile.php') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'hover:bg-slate-900 text-slate-400 hover:text-white'; ?>">
                <i class="fas fa-user-shield text-sm <?php echo ($current_uri == 'profile.php') ? 'text-white' : 'text-indigo-400 group-hover:scale-110 transition-transform'; ?>"></i>
                <span>प्रोफ़ाइल सेटिंग</span>
            </a>
        </nav>
    </div>

    <!-- Footer Logout Section -->
    <div class="p-4 border-t border-slate-800/80 bg-slate-950 shrink-0">
        <a href="logout.php" onclick="return confirm('क्या आप वास्तव में लॉग आउट करना चाहते हैं?');" class="flex items-center justify-center gap-2.5 w-full py-2.5 rounded-xl text-xs font-bold text-rose-400 bg-rose-500/10 hover:bg-rose-600 hover:text-white transition-all duration-200 border border-rose-500/20">
            <i class="fas fa-power-off text-sm"></i>
            <span>सुरक्षित लॉग आउट</span>
        </a>
    </div>
</aside>

<!-- ==========================================
     2. MOBILE TOP HEADER & DRAWER TOGGLE
=========================================== -->
<div class="lg:hidden bg-slate-950/90 backdrop-blur-md text-white px-4 py-3 flex justify-between items-center border-b border-slate-800 sticky top-0 z-40 w-full">
    <div class="flex items-center gap-3">
        <button id="mobileMenuBtn" class="p-2 rounded-xl bg-slate-900 text-slate-200 hover:text-white active:scale-95 transition-transform border border-slate-800">
            <i class="fas fa-bars text-base"></i>
        </button>
        <span class="text-xs font-extrabold tracking-wider uppercase text-slate-200">Operator Panel</span>
    </div>
    
    <a href="wallet.php" class="bg-slate-900 border border-slate-800 px-3 py-1 rounded-full text-xs font-mono text-emerald-400 font-bold flex items-center gap-1.5 shadow-inner">
        <i class="fas fa-wallet text-[10px] text-cyan-400"></i>
        <span>₹<?php echo number_format((float)$wallet_balance, 0); ?></span>
    </a>
</div>

<!-- Mobile Off-Canvas Menu Overlay -->
<div id="mobileBackdrop" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 hidden transition-opacity duration-300"></div>

<!-- Mobile Off-Canvas Menu Drawer -->
<div id="mobileDrawer" class="fixed top-0 left-0 h-full w-72 bg-slate-950 text-slate-300 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out flex flex-col border-r border-slate-800 shadow-2xl">
    
    <!-- Drawer Header -->
    <div class="p-4 border-b border-slate-800 flex items-center justify-between shrink-0 bg-slate-900/50">
        <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center font-black text-white text-xs shadow-md shadow-indigo-500/20">
                VP
            </div>
            <div>
                <h2 class="text-xs font-black text-white">Vacancy Portal</h2>
                <span class="text-[9px] text-emerald-400 font-bold">Online Operator</span>
            </div>
        </div>
        <button id="closeDrawerBtn" class="text-slate-400 hover:text-white p-2 rounded-lg hover:bg-slate-800 transition-colors">
            <i class="fas fa-xmark text-lg"></i>
        </button>
    </div>

    <!-- Drawer Navigation Links -->
    <div class="flex-1 overflow-y-auto p-4 space-y-1.5 text-xs font-bold custom-sidebar-scroll">
        <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?php echo ($current_uri == 'dashboard.php') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-900'; ?>">
            <i class="fas fa-chart-pie text-indigo-400 w-4"></i> डैशबोर्ड
        </a>

        <!-- Synced Mobile Link -->
        <a href="rank_rewards.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?php echo ($current_uri == 'rank_rewards.php') ? 'bg-amber-500 text-slate-950 font-black' : 'text-amber-400 hover:bg-slate-900'; ?>">
            <i class="fas fa-trophy text-amber-400 w-4"></i> रैंक व प्रमोशन
        </a>

        <a href="post_vacancy.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?php echo ($current_uri == 'post_vacancy.php') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-900'; ?>">
            <i class="fas fa-plus-circle text-emerald-400 w-4"></i> नई जॉब्स
        </a>
        <a href="manage_posters.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?php echo ($current_uri == 'manage_posters.php') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-900'; ?>">
            <i class="fas fa-image text-pink-400 w-4"></i> पोस्टर्स
        </a>
        <a href="manage_results.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?php echo ($current_uri == 'manage_results.php') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-900'; ?>">
            <i class="fas fa-poll text-purple-400 w-4"></i> रिजल्ट्स
        </a>
        <a href="manage_admit_cards.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?php echo ($current_uri == 'manage_admit_cards.php') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-900'; ?>">
            <i class="fas fa-id-card text-amber-400 w-4"></i> एडमिट कार्ड
        </a>
        <a href="manage_pdf_forms.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?php echo ($current_uri == 'manage_pdf_forms.php') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-900'; ?>">
            <i class="fas fa-file-pdf text-orange-400 w-4"></i> PDF फॉर्म्स
        </a>
        <a href="wallet.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?php echo ($current_uri == 'wallet.php') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-900'; ?>">
            <i class="fas fa-wallet text-cyan-400 w-4"></i> वॉलेट खाता
        </a>
        <a href="profile.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?php echo ($current_uri == 'profile.php') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-900'; ?>">
            <i class="fas fa-user-shield text-indigo-400 w-4"></i> प्रोफ़ाइल
        </a>
    </div>

    <!-- Drawer Footer -->
    <div class="p-4 border-t border-slate-800 shrink-0 bg-slate-950">
        <a href="logout.php" onclick="return confirm('क्या आप लॉग आउट करना चाहते हैं?');" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl text-rose-400 bg-rose-500/10 font-bold text-xs hover:bg-rose-600 hover:text-white transition-all">
            <i class="fas fa-power-off"></i>
            <span>लॉग आउट</span>
        </a>
    </div>
</div>

<!-- JavaScript Engine for Mobile Drawer Handling -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const menuBtn = document.getElementById("mobileMenuBtn");
    const closeBtn = document.getElementById("closeDrawerBtn");
    const drawer = document.getElementById("mobileDrawer");
    const backdrop = document.getElementById("mobileBackdrop");

    function openDrawer() {
        if (drawer && backdrop) {
            backdrop.classList.remove("hidden");
            setTimeout(() => {
                drawer.classList.remove("-translate-x-full");
            }, 10);
        }
    }

    function closeDrawer() {
        if (drawer && backdrop) {
            drawer.classList.add("-translate-x-full");
            setTimeout(() => {
                backdrop.classList.add("hidden");
            }, 300);
        }
    }

    if (menuBtn) menuBtn.addEventListener("click", openDrawer);
    if (closeBtn) closeBtn.addEventListener("click", closeDrawer);
    if (backdrop) backdrop.addEventListener("click", closeDrawer);
});
</script>