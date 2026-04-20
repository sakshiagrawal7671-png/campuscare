<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;
if (!$user) return;

$role = $user['role'];
$currentPath = basename($_SERVER['PHP_SELF']);

// Absolute base paths per role so sidebar works from ANY subfolder (shared/, student/, admin/ etc.)
$roleBase = match(true) {
    in_array($role, ['national','international']) => '/campuscare/campuscare-api/student/',
    $role === 'admin'   => '/campuscare/campuscare-api/admin/',
    $role === 'mentor'  => '/campuscare/campuscare-api/mentor/',
    $role === 'warden'  => '/campuscare/campuscare-api/warden/',
    $role === 'iro'     => '/campuscare/campuscare-api/iro/',
    default             => '/campuscare/campuscare-api/',
};

$links = [];
$links[] = ['name' => 'Dashboard', 'path' => $roleBase . 'dashboard.php', 'icon' => 'layout-dashboard'];

if ($role === 'national' || $role === 'international') {
    $links[] = ['name' => 'Create Complaint', 'path' => $roleBase . 'create_complaint.php', 'icon' => 'plus-circle'];
} elseif ($role === 'admin') {
    $links[] = ['name' => 'All Complaints', 'path' => $roleBase . 'complaints.php', 'icon' => 'file-text'];
    $links[] = ['name' => 'Manage Staff',   'path' => $roleBase . 'staff.php',       'icon' => 'users'];
    $links[] = ['name' => 'System Settings','path' => $roleBase . 'settings.php',    'icon' => 'settings'];
}
?>

<aside class="w-64 bg-[#1e1e1e] border-r border-[#333] h-screen sticky top-0 flex flex-col shrink-0">
    <div class="p-6 flex items-center gap-3 border-b border-[#333]">
        <i data-lucide="shield-check" class="w-8 h-8 text-[#13ec87]"></i>
        <span class="text-xl font-bold text-white tracking-tight">CampusCare</span>
    </div>

    <div class="p-4 overflow-y-auto">
        <div class="text-xs uppercase text-gray-500 font-semibold tracking-wider mb-2 px-3">
            Menu
        </div>
        <nav class="flex flex-col gap-1">
            <?php foreach ($links as $link): 
                // Basic active state check. Assuming they are in the same folder as currentPath
                $isActive = ($currentPath === $link['path']);
                $activeClasses = 'bg-[#13ec87]/10 text-[#13ec87] border border-[#13ec87]/20';
                $inactiveClasses = 'text-gray-400 hover:text-white hover:bg-[#2a2a2a] border border-transparent';
            ?>
                <a href="<?= htmlspecialchars($link['path']) ?>" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= $isActive ? $activeClasses : $inactiveClasses ?>">
                    <i data-lucide="<?= $link['icon'] ?>" class="w-5 h-5"></i> <?= htmlspecialchars($link['name']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="mt-auto p-4 border-t border-[#333]">
        <div class="flex items-center gap-3 mb-4 px-3">
            <div class="w-10 h-10 rounded-full bg-[#2a2a2a] border border-[#444] flex items-center justify-center text-[#13ec87] font-bold">
                <?= htmlspecialchars(strtoupper(substr($user['name'], 0, 1))) ?>
            </div>
            <div class="overflow-hidden">
                <p class="text-sm font-medium text-white truncate max-w-[120px]"><?= htmlspecialchars($user['name']) ?></p>
                <p class="text-xs text-gray-500 capitalize"><?= htmlspecialchars($role) ?></p>
            </div>
        </div>
        <a href="/campuscare/campuscare-api/auth/logout.php"
            class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-[#2a2a2a] hover:bg-red-500/10 hover:text-red-500 text-gray-400 border border-[#333] hover:border-red-500/30 rounded-lg text-sm font-medium transition-all"
        >
            <i data-lucide="log-out" class="w-4 h-4"></i>
            Sign Out
        </a>
    </div>
</aside>
