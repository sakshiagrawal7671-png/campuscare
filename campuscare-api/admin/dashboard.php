<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/roleGuard.php';

$user = requireRole(['admin']);
$pdo = getDbConnection();

$counts = [
    'students'   => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('national','international') AND status='active'")->fetchColumn(),
    'mentors'    => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='mentor' AND status='active'")->fetchColumn(),
    'iro'        => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='iro' AND status='active'")->fetchColumn(),
    'wardens'    => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='warden' AND status='active'")->fetchColumn(),
    'complaints' => (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE status IN ('submitted','in_progress','escalated')")->fetchColumn(),
];

$recentActivity = $pdo->query(
    "SELECT u.name, u.role, u.created_at 
     FROM users u 
     ORDER BY u.created_at DESC 
     LIMIT 5"
)->fetchAll();

ob_start();
?>
<!-- 4 Directory Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">

    <!-- Students -->
    <div class="cc-card rounded-2xl p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-1">Directory</p>
                <h3 class="text-2xl font-black text-white">Students</h3>
            </div>
            <div class="w-10 h-10 bg-[#13ec87]/10 border border-[#13ec87]/20 rounded-xl flex items-center justify-center">
                <i data-lucide="graduation-cap" class="w-5 h-5 text-[#13ec87]"></i>
            </div>
        </div>
        <p class="text-5xl font-black text-white mb-1"><?= number_format($counts['students']) ?></p>
        <p class="text-xs text-gray-500 mb-5"><span class="text-[#13ec87]">↑ Active</span> enrolled this session</p>
        <div class="flex gap-2">
            <a href="/campuscare/campuscare-api/admin/users.php" class="flex-1 cc-btn-primary text-xs py-2 rounded-lg flex items-center justify-center gap-1.5">
                <i data-lucide="eye" class="w-3.5 h-3.5"></i> View Directory
            </a>
            <a href="/campuscare/campuscare-api/auth/register.php" class="w-9 h-8 bg-[#0d1a12] border border-[#1a3a25] hover:border-[#13ec87]/50 rounded-lg flex items-center justify-center text-gray-400 hover:text-[#13ec87] transition-colors">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
            </a>
        </div>
    </div>

    <!-- Mentors -->
    <div class="cc-card rounded-2xl p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-1">Directory</p>
                <h3 class="text-2xl font-black text-white">Mentors</h3>
            </div>
            <div class="w-10 h-10 bg-blue-500/10 border border-blue-500/20 rounded-xl flex items-center justify-center">
                <i data-lucide="user-check" class="w-5 h-5 text-blue-400"></i>
            </div>
        </div>
        <p class="text-5xl font-black text-white mb-1"><?= $counts['mentors'] ?></p>
        <p class="text-xs text-gray-500 mb-5"><span class="text-blue-400">↑ 5%</span> VERIFIED EXPERTS</p>
        <div class="flex gap-2">
            <a href="/campuscare/campuscare-api/admin/staff.php?tab=mentor" class="flex-1 cc-btn-primary text-xs py-2 rounded-lg flex items-center justify-center gap-1.5">
                <i data-lucide="eye" class="w-3.5 h-3.5"></i> View Directory
            </a>
            <button onclick="openModal('addMentorModal')" class="w-9 h-8 bg-[#0d1a12] border border-[#1a3a25] hover:border-[#13ec87]/50 rounded-lg flex items-center justify-center text-gray-400 hover:text-[#13ec87] transition-colors">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <!-- IRO Officers -->
    <div class="cc-card rounded-2xl p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-1">Directory</p>
                <h3 class="text-2xl font-black text-white">IRO Officers</h3>
            </div>
            <div class="w-10 h-10 bg-purple-500/10 border border-purple-500/20 rounded-xl flex items-center justify-center">
                <i data-lucide="globe" class="w-5 h-5 text-purple-400"></i>
            </div>
        </div>
        <p class="text-5xl font-black text-white mb-1"><?= $counts['iro'] ?></p>
        <p class="text-xs text-gray-500 mb-5"><span class="text-xs text-gray-400 bg-gray-800 px-2 py-0.5 rounded-md">Stable</span> INTERNATIONAL RELATIONS</p>
        <div class="flex gap-2">
            <a href="/campuscare/campuscare-api/admin/staff.php?tab=iro" class="flex-1 cc-btn-primary text-xs py-2 rounded-lg flex items-center justify-center gap-1.5">
                <i data-lucide="eye" class="w-3.5 h-3.5"></i> View Directory
            </a>
            <button onclick="openModal('addIROModal')" class="w-9 h-8 bg-[#0d1a12] border border-[#1a3a25] hover:border-[#13ec87]/50 rounded-lg flex items-center justify-center text-gray-400 hover:text-[#13ec87] transition-colors">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <!-- Wardens -->
    <div class="cc-card rounded-2xl p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-1">Directory</p>
                <h3 class="text-2xl font-black text-white">Wardens</h3>
            </div>
            <div class="w-10 h-10 bg-orange-500/10 border border-orange-500/20 rounded-xl flex items-center justify-center">
                <i data-lucide="shield" class="w-5 h-5 text-orange-400"></i>
            </div>
        </div>
        <p class="text-5xl font-black text-white mb-1"><?= $counts['wardens'] ?></p>
        <p class="text-xs text-gray-500 mb-5"><span class="text-orange-400">↑ 2%</span> CAMPUS SECURITY</p>
        <div class="flex gap-2">
            <a href="/campuscare/campuscare-api/admin/staff.php?tab=warden" class="flex-1 cc-btn-primary text-xs py-2 rounded-lg flex items-center justify-center gap-1.5">
                <i data-lucide="eye" class="w-3.5 h-3.5"></i> View Directory
            </a>
            <button onclick="openModal('addWardenModal')" class="w-9 h-8 bg-[#0d1a12] border border-[#1a3a25] hover:border-[#13ec87]/50 rounded-lg flex items-center justify-center text-gray-400 hover:text-[#13ec87] transition-colors">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
</div>

<!-- Global Administration -->
<div class="cc-card rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-white">Global Administration</h2>
            <p class="text-xs text-gray-500 mt-0.5">Batch operations and system-wide configurations</p>
        </div>
        <div class="flex gap-2">
            <a href="/campuscare/campuscare-api/admin/complaints.php" class="flex items-center gap-1.5 px-3 py-2 border border-[#1a3a25] hover:border-[#13ec87]/50 text-gray-300 hover:text-[#13ec87] rounded-lg text-xs font-medium transition-colors">
                <i data-lucide="download" class="w-3.5 h-3.5"></i> Export Registry
            </a>
            <a href="/campuscare/campuscare-api/admin/staff.php" class="cc-btn-primary text-xs px-3 py-2 rounded-lg flex items-center gap-1.5">
                <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> Bulk Permissions
            </a>
        </div>
    </div>
</div>

<!-- Recent System Activity -->
<div class="cc-card rounded-2xl">
    <div class="p-5 border-b border-[#1a3a25] flex items-center justify-between">
        <h2 class="text-base font-bold text-white">Recent System Activity</h2>
        <a href="/campuscare/campuscare-api/admin/users.php" class="text-[#13ec87] text-xs font-semibold hover:underline">View All Logs</a>
    </div>
    <div class="divide-y divide-[#1a3a25]">
        <?php if (empty($recentActivity)): ?>
        <p class="p-5 text-center text-gray-500 text-sm">No recent activity.</p>
        <?php endif; ?>
        <?php foreach ($recentActivity as $act): ?>
        <div class="p-4 flex items-center gap-4 hover:bg-[#13ec87]/[0.03] transition-colors">
            <div class="w-9 h-9 bg-[#13ec87]/10 border border-[#13ec87]/20 rounded-xl flex items-center justify-center shrink-0">
                <i data-lucide="user" class="w-4 h-4 text-[#13ec87]"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-white font-medium truncate">
                    New <?= ucfirst(htmlspecialchars($act['role'])) ?> registered: <?= htmlspecialchars($act['name']) ?>
                </p>
                <p class="text-xs text-gray-500">Admin / Account Registry</p>
            </div>
            <span class="text-xs text-gray-600 shrink-0">
                <?= date('d M, H:i', strtotime($act['created_at'])) ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- === MODALS === -->
<!-- Add Mentor Modal -->
<div id="addMentorModal" class="hidden fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4">
    <div class="bg-[#0f2318] border border-[#1a3a25] rounded-2xl w-full max-w-lg shadow-2xl">
        <div class="p-6 border-b border-[#1a3a25] flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-white">Add New Mentor</h3>
                <p class="text-sm text-gray-500">Create a new faculty account and credentials.</p>
            </div>
            <button onclick="closeModal('addMentorModal')" class="text-gray-600 hover:text-white transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="/campuscare/campuscare-api/admin/create_staff_action.php" class="p-6 space-y-4">
            <input type="hidden" name="role" value="mentor">
            <div>
                <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Full Name</label>
                <div class="relative">
                    <i data-lucide="user" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-600"></i>
                    <input type="text" name="name" required placeholder="e.g. Dr. Robert Vance" class="cc-input w-full pl-10 pr-4 py-3 rounded-xl text-sm">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Email Address</label>
                    <div class="relative">
                        <i data-lucide="mail" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-600"></i>
                        <input type="email" name="email" required placeholder="r.vance@campus.edu" class="cc-input w-full pl-10 pr-4 py-3 rounded-xl text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Phone Number</label>
                    <div class="relative">
                        <i data-lucide="phone" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-600"></i>
                        <input type="tel" name="phone" placeholder="+1 (000) 000-0000" class="cc-input w-full pl-10 pr-4 py-3 rounded-xl text-sm">
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Temporary Password</label>
                <div class="relative">
                    <i data-lucide="lock" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-600"></i>
                    <input type="password" name="password" required placeholder="Min. 6 characters" class="cc-input w-full pl-10 pr-4 py-3 rounded-xl text-sm">
                </div>
                <p class="text-[10px] text-gray-600 mt-1.5">Must be at least 6 characters.</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('addMentorModal')" class="flex-1 py-2.5 border border-[#1a3a25] text-gray-300 hover:text-white hover:border-gray-500 rounded-xl text-sm font-medium transition-colors">Cancel</button>
                <button type="submit" class="flex-1 cc-btn-primary py-2.5 rounded-xl text-sm">Create Mentor Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Add IRO Modal -->
<div id="addIROModal" class="hidden fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4">
    <div class="bg-[#0f2318] border border-[#1a3a25] rounded-2xl w-full max-w-lg shadow-2xl">
        <div class="p-6 border-b border-[#1a3a25] flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-white">Add IRO Officer</h3>
                <p class="text-sm text-gray-500">Create an international relations account.</p>
            </div>
            <button onclick="closeModal('addIROModal')" class="text-gray-600 hover:text-white transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="/campuscare/campuscare-api/admin/create_staff_action.php" class="p-6 space-y-4">
            <input type="hidden" name="role" value="iro">
            <div>
                <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Full Name</label>
                <input type="text" name="name" required placeholder="e.g. Marcus Chen" class="cc-input w-full px-4 py-3 rounded-xl text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Email</label>
                    <input type="email" name="email" required placeholder="m.chen@campus.edu" class="cc-input w-full px-4 py-3 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Phone</label>
                    <input type="tel" name="phone" placeholder="+1 (000) 000-0000" class="cc-input w-full px-4 py-3 rounded-xl text-sm">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Password</label>
                <input type="password" name="password" required placeholder="Min. 6 characters" class="cc-input w-full px-4 py-3 rounded-xl text-sm">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('addIROModal')" class="flex-1 py-2.5 border border-[#1a3a25] text-gray-300 hover:text-white rounded-xl text-sm font-medium transition-colors">Cancel</button>
                <button type="submit" class="flex-1 cc-btn-primary py-2.5 rounded-xl text-sm">Create IRO Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Warden Modal -->
<div id="addWardenModal" class="hidden fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4">
    <div class="bg-[#0f2318] border border-[#1a3a25] rounded-2xl w-full max-w-md shadow-2xl">
        <div class="p-6 border-b border-[#1a3a25] flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-white">Add New Warden</h3>
                <p class="text-sm text-gray-500">Assign a new official to a hostel block.</p>
            </div>
            <button onclick="closeModal('addWardenModal')" class="text-gray-600 hover:text-white transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="/campuscare/campuscare-api/admin/create_staff_action.php" class="p-6 space-y-4">
            <input type="hidden" name="role" value="warden">
            <div>
                <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Full Name</label>
                <input type="text" name="name" required placeholder="e.g. Elena Rodriguez" class="cc-input w-full px-4 py-3 rounded-xl text-sm">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Institutional Email</label>
                <input type="email" name="email" required placeholder="elena.r@campus.edu" class="cc-input w-full px-4 py-3 rounded-xl text-sm">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Password</label>
                <input type="password" name="password" required placeholder="Min. 6 characters" class="cc-input w-full px-4 py-3 rounded-xl text-sm">
            </div>
            <?php
            $hostels = $pdo->query("SELECT id, hostel_name FROM hostels ORDER BY hostel_name")->fetchAll();
            ?>
            <div>
                <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Hostel Assignment</label>
                <div class="relative">
                    <select name="hostel_id" required class="cc-input w-full px-4 py-3 rounded-xl text-sm appearance-none">
                        <option value="">Select Hostel Block</option>
                        <?php foreach ($hostels as $h): ?>
                            <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['hostel_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i data-lucide="chevron-down" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 pointer-events-none"></i>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('addWardenModal')" class="flex-1 py-2.5 border border-[#1a3a25] text-gray-300 hover:text-white rounded-xl text-sm font-medium transition-colors">Discard</button>
                <button type="submit" class="flex-1 cc-btn-primary py-2.5 rounded-xl text-sm">Confirm Assignment</button>
            </div>
        </form>
    </div>
</div>

<?php
$pageContent = ob_get_clean();
$pageTitle = 'User Management';
$pageSubtitle = 'Configure access levels, monitor engagement metrics, and orchestrate the digital workspace for all campus constituents.';
$currentPage = 'dashboard';
$pageScript = <<<JS
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.body.style.overflow = '';
}
document.querySelectorAll('.modal-backdrop').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});
JS;
require_once __DIR__ . '/../components/admin_layout.php';
