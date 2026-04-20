<?php
require_once __DIR__ . '/header.php';

// Ensure $pageTitle and $pageSubtitle are set before including layout.php
$title = $pageTitle ?? 'Dashboard';
$subtitle = $pageSubtitle ?? '';
?>

<div class="min-h-screen bg-[#121212] flex text-[#f5f5f5]">
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-y-auto">
        <header class="min-h-[80px] bg-[#1e1e1e]/50 backdrop-blur-md border-b border-[#333] shrink-0 flex items-center px-8 sticky top-0 z-40">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight"><?= htmlspecialchars($title) ?></h1>
                <?php if ($subtitle): ?>
                    <p class="text-sm text-gray-400 mt-1"><?= htmlspecialchars($subtitle) ?></p>
                <?php endif; ?>
            </div>
        </header>
        
        <div class="p-8 pb-20">
            <!-- Included Page Content Goes Here via ob_start/get_clean or just echoing directly like a template -->
            <?= $pageContent ?? '' ?>
        </div>
    </main>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
