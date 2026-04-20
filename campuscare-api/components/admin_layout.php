<?php
// Admin-specific layout with top navbar + left sidebar matching the enterprise design
if (session_status() === PHP_SESSION_NONE) session_start();

$adminUser = $_SESSION['user'] ?? null;
$currentPage = $currentPage ?? 'dashboard';
$title = $pageTitle ?? 'Dashboard';
$subtitle = $pageSubtitle ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusCare Enterprise — <?= htmlspecialchars($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        *, body { font-family: 'Inter', sans-serif; }
        body { background-color: #0d1a12; color: #e8f5ec; }
        ::selection { background-color: #13ec87; color: #0d1a12; }
        .nav-link { transition: all .15s ease; }
        .nav-link:hover, .nav-link.active { background: rgba(19,236,135,0.10); color: #13ec87; }
        .nav-link.active { border-left: 3px solid #13ec87; }
        .cc-card { background: #0f2318; border: 1px solid #1a3a25; transition: border-color .2s; }
        .cc-card:hover { border-color: rgba(19,236,135,0.35); }
        .cc-input { background: #0f2318; border: 1px solid #1a3a25; color: #e8f5ec; transition: border-color .2s; }
        .cc-input:focus { outline: none; border-color: #13ec87; }
        .cc-btn-primary { background: #13ec87; color: #0a1510; font-weight: 700; transition: all .2s; }
        .cc-btn-primary:hover { background: #0fae62; box-shadow: 0 0 20px rgba(19,236,135,.3); }
        .cc-badge-nat { background: rgba(19,236,135,.15); color: #13ec87; border: 1px solid rgba(19,236,135,.3); }
        .cc-badge-int { background: rgba(59,130,246,.15); color: #60a5fa; border: 1px solid rgba(59,130,246,.3); }
        .cc-badge-inactive { background: rgba(239,68,68,.1); color: #f87171; border:1px solid rgba(239,68,68,.25); }
        .modal-backdrop { background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); }
        .scrollbar-thin::-webkit-scrollbar { width: 4px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #1a3a25; border-radius: 99px; }
    </style>
</head>
<body class="min-h-screen">

<div class="flex h-screen overflow-hidden">
    <!-- ===== LEFT SIDEBAR ===== -->
    <aside class="w-56 shrink-0 bg-[#0a1510] border-r border-[#1a3a25] flex flex-col">
        <!-- Logo -->
        <div class="p-5 border-b border-[#1a3a25]">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-[#13ec87] flex items-center justify-center">
                    <i data-lucide="shield-check" class="w-5 h-5 text-[#0a1510]"></i>
                </div>
                <div>
                    <div class="text-white font-bold text-sm leading-tight tracking-tight">CampusCare</div>
                    <div class="text-[#13ec87] text-[10px] font-semibold tracking-[2px] uppercase">Enterprise</div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto scrollbar-thin">
            <a href="/campuscare/campuscare-api/admin/dashboard.php"
               class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 font-medium">
                <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i> Dashboard
            </a>
            <a href="/campuscare/campuscare-api/admin/users.php"
               class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 font-medium">
                <i data-lucide="users" class="w-4 h-4 shrink-0"></i> User Management
            </a>
            <a href="/campuscare/campuscare-api/admin/complaints.php"
               class="nav-link <?= $currentPage === 'complaints' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 font-medium">
                <i data-lucide="message-square-warning" class="w-4 h-4 shrink-0"></i> Complaints
            </a>
            <a href="/campuscare/campuscare-api/admin/staff.php"
               class="nav-link <?= $currentPage === 'staff' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 font-medium">
                <i data-lucide="user-cog" class="w-4 h-4 shrink-0"></i> Staff Registry
            </a>
            <a href="/campuscare/campuscare-api/admin/settings.php"
               class="nav-link <?= $currentPage === 'settings' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 font-medium">
                <i data-lucide="settings" class="w-4 h-4 shrink-0"></i> Settings
            </a>
        </nav>

        <!-- Support + User -->
        <div class="p-3 border-t border-[#1a3a25] space-y-2">
            <a href="" class="block w-full cc-btn-primary text-center text-xs py-2.5 rounded-xl">
                <i data-lucide="headphones" class="w-3.5 h-3.5 inline mr-1"></i> Support Center
            </a>
            <div class="flex items-center gap-2 p-2">
                <div class="w-7 h-7 rounded-full bg-[#13ec87] flex items-center justify-center text-[#0a1510] text-xs font-bold shrink-0">
                    <?= strtoupper(substr($adminUser['name'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-white text-xs font-semibold truncate"><?= htmlspecialchars($adminUser['name'] ?? 'Admin') ?></div>
                    <div class="text-gray-500 text-[10px]">Administrator</div>
                </div>
                <a href="/campuscare/campuscare-api/auth/logout.php" class="text-gray-600 hover:text-red-400 transition-colors">
                    <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- ===== MAIN AREA ===== -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Top Navbar -->
        <header class="h-14 shrink-0 bg-[#0a1510] border-b border-[#1a3a25] flex items-center px-6 gap-4 z-30">
            <!-- Breadcrumb / Page title -->
            <div class="flex items-center gap-2 text-sm text-gray-400 flex-1">
                <span class="text-gray-600">Admin Portal</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-gray-700"></i>
                <span class="text-[#13ec87] font-semibold"><?= htmlspecialchars($title) ?></span>
            </div>

            <!-- Search -->
            <div class="relative hidden md:block">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-600 pointer-events-none"></i>
                <input type="text" placeholder="Search users, ID..." class="cc-input text-sm pl-8 pr-4 py-1.5 rounded-lg w-52 text-xs">
            </div>

            <!-- Notification + Avatar -->
            <button class="relative text-gray-500 hover:text-[#13ec87] transition-colors">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-[#13ec87] rounded-full"></span>
            </button>
            <div class="w-8 h-8 rounded-full bg-[#13ec87] flex items-center justify-center text-[#0a1510] text-sm font-bold">
                <?= strtoupper(substr($adminUser['name'] ?? 'A', 0, 1)) ?>
            </div>
        </header>

        <!-- Page Content Scroll Area -->
        <main class="flex-1 overflow-y-auto scrollbar-thin">
            <!-- Page Header -->
            <div class="px-8 pt-8 pb-4">
                <h1 class="text-3xl font-black text-white"><?= htmlspecialchars($title) ?></h1>
                <?php if ($subtitle): ?>
                    <p class="text-gray-500 text-sm mt-1 max-w-xl"><?= htmlspecialchars($subtitle) ?></p>
                <?php endif; ?>
            </div>

            <!-- Content -->
            <div class="px-8 pb-12">
                <?= $pageContent ?? '' ?>
            </div>
        </main>
    </div>
</div>

<script>lucide.createIcons();</script>
<?php if (!empty($pageScript)): ?>
<script><?= $pageScript ?></script>
<?php endif; ?>
</body>
</html>
