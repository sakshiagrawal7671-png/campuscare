<?php
// Warden-specific layout
if (session_status() === PHP_SESSION_NONE) session_start();

$user = $_SESSION['user'] ?? null;
$currentPage = $currentPage ?? 'dashboard';
$title = $pageTitle ?? 'Dashboard';
$subtitle = $pageSubtitle ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusCare Warden — <?= htmlspecialchars($title) ?></title>
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
                    <i data-lucide="home" class="w-5 h-5 text-[#0a1510]"></i>
                </div>
                <div>
                    <div class="text-white font-bold text-sm leading-tight tracking-tight">CampusCare</div>
                    <div class="text-[#13ec87] text-[10px] font-semibold tracking-[2px] uppercase">Warden Portal</div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto scrollbar-thin">
            <a href="/campuscare/campuscare-api/warden/dashboard.php"
               class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 font-medium">
                <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i> Dashboard
            </a>
            <!-- Room for future warden links (e.g. notices, attendance) -->
        </nav>

        <!-- Support + User -->
        <div class="p-3 border-t border-[#1a3a25] space-y-2">
            <div class="flex items-center gap-2 p-2">
                <div class="w-7 h-7 rounded-full bg-[#13ec87] flex items-center justify-center text-[#0a1510] text-xs font-bold shrink-0">
                    <?= strtoupper(substr($user['name'] ?? 'W', 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-white text-xs font-semibold truncate"><?= htmlspecialchars($user['name'] ?? 'Warden') ?></div>
                    <div class="text-gray-500 text-[10px]">Hostel Warden</div>
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
        <header class="h-14 shrink-0 bg-[#0a1510] border-b border-[#1a3a25] flex items-center px-6 gap-4 z-30 justify-between">
            <!-- Breadcrumb / Page title -->
            <div class="flex items-center gap-2 text-sm text-gray-400">
                <span class="text-gray-600">Warden Area</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-gray-700"></i>
                <span class="text-[#13ec87] font-semibold"><?= htmlspecialchars($title) ?></span>
            </div>

            <!-- Avatar -->
            <div class="w-8 h-8 rounded-full bg-[#13ec87] flex items-center justify-center text-[#0a1510] text-sm font-bold">
                <?= strtoupper(substr($user['name'] ?? 'W', 0, 1)) ?>
            </div>
        </header>

        <!-- Page Content Scroll Area -->
        <main class="flex-1 overflow-y-auto scrollbar-thin">
            <!-- Content -->
            <div class="p-8">
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
