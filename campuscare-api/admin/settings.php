<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/roleGuard.php';

$admin = requireRole(['admin']);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = 'Settings updated successfully.';
}

ob_start();
?>
<div class="max-w-2xl space-y-5">

    <?php if ($message): ?>
    <div class="p-4 rounded-xl flex items-center gap-3 bg-[#13ec87]/10 text-[#13ec87] border border-[#13ec87]/30 mb-2">
        <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
        <span class="text-sm font-medium"><?= htmlspecialchars($message) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">
        <!-- Notifications -->  
        <div class="cc-card rounded-2xl overflow-hidden">
            <div class="p-5 border-b border-[#1a3a25] flex items-center gap-2">
                <div class="w-7 h-7 bg-[#13ec87]/10 border border-[#13ec87]/20 rounded-lg flex items-center justify-center">
                    <i data-lucide="bell" class="w-3.5 h-3.5 text-[#13ec87]"></i>
                </div>
                <h3 class="text-base font-bold text-white">Notifications</h3>
            </div>
            <div class="p-5 space-y-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-semibold text-white">Email Routing</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Send assignment alerts to staff emails automatically.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="email_alerts" value="1" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-[#1a3a25] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#13ec87]"></div>
                    </label>
                </div>
                <div class="h-px bg-[#1a3a25]"></div>
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-semibold text-white">Escalation Thresholds</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Automatically escalate complaints unresolved after 7 days.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="auto_escalate" value="1" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-[#1a3a25] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#13ec87]"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Security -->
        <div class="cc-card rounded-2xl overflow-hidden">
            <div class="p-5 border-b border-[#1a3a25] flex items-center gap-2">
                <div class="w-7 h-7 bg-orange-500/10 border border-orange-500/20 rounded-lg flex items-center justify-center">
                    <i data-lucide="shield" class="w-3.5 h-3.5 text-orange-400"></i>
                </div>
                <h3 class="text-base font-bold text-white">Security</h3>
            </div>
            <div class="p-5">
                <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Session Timeout (Minutes)</label>
                <input type="number" name="timeout" value="60" class="cc-input w-full max-w-xs px-4 py-2.5 rounded-xl text-sm">
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="cc-btn-primary px-6 py-2.5 rounded-xl flex items-center gap-2 text-sm">
                <i data-lucide="save" class="w-4 h-4"></i> Save Configuration
            </button>
        </div>
    </form>
</div>
<?php
$pageContent = ob_get_clean();
$pageTitle = 'Settings';
$pageSubtitle = 'Manage global platform behaviors and integration parameters.';
$currentPage = 'settings';
require_once __DIR__ . '/../components/admin_layout.php';
