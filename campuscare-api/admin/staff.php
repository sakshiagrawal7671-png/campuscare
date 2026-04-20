<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/roleGuard.php';

$user = requireRole(['admin']);
$pdo  = getDbConnection();

$message = '';
$messageType = '';

// ── POST HANDLERS ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_staff') {
        $name     = trim($_POST['name'] ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? '';
        $phone    = trim($_POST['phone'] ?? '') ?: null;
        $hostelId = ($_POST['hostel_id'] ?? '') !== '' ? (int)$_POST['hostel_id'] : null;

        if (!$name || !$email || strlen($password) < 6 || !in_array($role, ['mentor','warden','iro'], true)) {
            $message = 'All required fields must be filled.'; $messageType = 'error';
        } elseif ($role === 'warden' && !$hostelId) {
            $message = 'Wardens must be assigned to a hostel.'; $messageType = 'error';
        } else {
            $chk = $pdo->prepare('SELECT id FROM users WHERE email=:e'); $chk->execute(['e'=>$email]);
            if ($chk->fetch()) {
                $message = 'Email already exists.'; $messageType = 'error';
            } else {
                try {
                    $ins = $pdo->prepare('INSERT INTO users (name,email,password,role,phone,hostel_id,status) VALUES (:n,:e,:p,:r,:ph,:h,"active")');
                    $ins->execute(['n'=>$name,'e'=>$email,'p'=>password_hash($password,PASSWORD_ARGON2ID),'r'=>$role,'ph'=>$phone,'h'=>$role==='warden'?$hostelId:null]);
                    $newId = (int)$pdo->lastInsertId();
                    if ($role === 'warden' && $hostelId) {
                        try { $pdo->prepare('INSERT INTO hostel_wardens (hostel_id,warden_id) VALUES (:h,:w)')->execute(['h'=>$hostelId,'w'=>$newId]); } catch(\Exception $e){}
                    }
                    $message = ucfirst($role).' account created!'; $messageType = 'success';
                } catch(\Exception $e) { $message = 'Error: '.$e->getMessage(); $messageType = 'error'; }
            }
        }
    }

    if ($action === 'update_staff') {
        $id    = (int)($_POST['user_id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '') ?: null;
        if ($id && $name && $email) {
            $pdo->prepare('UPDATE users SET name=:n, email=:e, phone=:ph WHERE id=:id')
                ->execute(['n'=>$name,'e'=>$email,'ph'=>$phone,'id'=>$id]);
            $message = 'Staff updated.'; $messageType = 'success';
        }
    }

    if ($action === 'delete_staff') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id) {
            $pdo->prepare('DELETE FROM users WHERE id=:id AND role IN ("mentor","warden","iro")')->execute(['id'=>$id]);
            $message = 'Staff member deleted.'; $messageType = 'success';
        }
    }

    if ($action === 'toggle_status') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id) {
            $pdo->prepare('UPDATE users SET status=IF(status="active","disabled","active") WHERE id=:id')->execute(['id'=>$id]);
            $message = 'Status updated.'; $messageType = 'success';
        }
    }
}

// ── FETCH ──────────────────────────────────────────────────────
$tab     = $_GET['tab'] ?? 'mentor';
$mentors = $pdo->query("SELECT u.id,u.name,u.email,u.status,u.phone,COUNT(ms.student_id) AS students FROM users u LEFT JOIN mentor_students ms ON ms.mentor_id=u.id WHERE u.role='mentor' GROUP BY u.id ORDER BY u.name")->fetchAll();
$iros    = $pdo->query("SELECT u.id,u.name,u.email,u.status,u.phone,COUNT(is2.student_id) AS students FROM users u LEFT JOIN iro_students is2 ON is2.iro_id=u.id WHERE u.role='iro' GROUP BY u.id ORDER BY u.name")->fetchAll();
$wardens = $pdo->query("SELECT u.id,u.name,u.email,u.status,u.phone,h.hostel_name FROM users u LEFT JOIN hostel_wardens hw ON hw.warden_id=u.id LEFT JOIN hostels h ON h.id=hw.hostel_id WHERE u.role='warden' ORDER BY u.name")->fetchAll();
$hostels = $pdo->query("SELECT id, hostel_name FROM hostels ORDER BY hostel_name")->fetchAll();

// Helper: render a staff table section
function staffTable(string $role, array $list, string $color, string $icon, string $btnLabel): string {
    $roleLabel = ucfirst($role);
    $activeTab = $_GET['tab'] ?? 'mentor';

    $rows = '';
    if (empty($list)) {
        $rows = '<tr><td colspan="5" class="px-5 py-8 text-center text-gray-500">No '.$roleLabel.'s yet. Add one above.</td></tr>';
    }
    foreach ($list as $m) {
        $ini = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $m['name']), 0, 2)));
        $statusCls = $m['status']==='active' ? 'text-[#13ec87]' : 'text-red-400';
        $dotCls    = $m['status']==='active' ? 'bg-[#13ec87]' : 'bg-red-400';
        $extra = isset($m['students'])
            ? '<td class="px-5 py-3.5 text-gray-300 text-sm font-semibold">'.htmlspecialchars((string)$m['students']).'</td>'
            : '<td class="px-5 py-3.5 text-xs text-[#13ec87]">'.($m['hostel_name'] ? '<i data-lucide="home" class="w-3 h-3 inline"></i> '.htmlspecialchars((string)$m['hostel_name']) : '<span class="text-gray-600">Unassigned</span>').'</td>';

        $editData = json_encode(['id'=>$m['id'],'name'=>$m['name'],'email'=>$m['email'],'phone'=>(string)($m['phone']??'')]);
        $rows .= <<<HTML
        <tr class="hover:bg-[#13ec87]/[0.03] transition-colors">
            <td class="px-5 py-3.5">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-{$color}-500/20 flex items-center justify-center text-{$color}-400 text-xs font-bold">{$ini}</div>
                    <div>
                        <div class="font-semibold text-white text-sm">{$m['name']}</div>
                        <div class="text-xs text-gray-600">{$m['email']}</div>
                    </div>
                </div>
            </td>
            <td class="px-5 py-3.5 text-gray-500 text-xs">{$m['phone']}</td>
            {$extra}
            <td class="px-5 py-3.5">
                <form method="POST">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="user_id" value="{$m['id']}">
                    <button type="submit" class="flex items-center gap-1.5 text-xs font-medium {$statusCls} hover:opacity-70 transition-opacity">
                        <span class="w-1.5 h-1.5 rounded-full {$dotCls}"></span>{$m['status']}
                    </button>
                </form>
            </td>
            <td class="px-5 py-3.5">
                <div class="flex items-center gap-2 justify-end">
                    <button onclick='openStaffEdit({$editData})' class="p-1.5 rounded-lg border border-[#1a3a25] text-gray-500 hover:text-[#13ec87] hover:border-[#13ec87]/50 transition-colors">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                    </button>
                    <form method="POST" onsubmit="return confirm('Delete this staff member?')">
                        <input type="hidden" name="action" value="delete_staff">
                        <input type="hidden" name="user_id" value="{$m['id']}">
                        <button type="submit" class="p-1.5 rounded-lg border border-[#1a3a25] text-gray-500 hover:text-red-400 hover:border-red-500/30 transition-colors">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        HTML;
    }
    return $rows;
}

ob_start();
?>
<?php if ($message): ?>
<div class="mb-5 p-4 rounded-xl flex items-center gap-3 <?= $messageType==='success' ? 'bg-[#13ec87]/10 text-[#13ec87] border border-[#13ec87]/30' : 'bg-red-500/10 text-red-400 border border-red-500/20' ?>">
    <i data-lucide="<?= $messageType==='success'?'check-circle':'alert-circle' ?>" class="w-4 h-4 shrink-0"></i>
    <span class="text-sm font-medium"><?= htmlspecialchars($message) ?></span>
</div>
<?php endif; ?>

<!-- Tab Navigation -->
<div class="flex gap-2 mb-5 border-b border-[#1a3a25] pb-0">
    <?php foreach (['mentor'=>'Mentors','iro'=>'IRO Officers','warden'=>'Wardens'] as $t=>$label): ?>
    <a href="?tab=<?= $t ?>"
       class="px-4 py-2.5 text-sm font-semibold rounded-t-lg -mb-px border-b-2 transition-colors
              <?= $tab===$t ? 'text-[#13ec87] border-[#13ec87]' : 'text-gray-500 border-transparent hover:text-gray-300' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Mentor Tab -->
<?php if ($tab === 'mentor'): ?>
<div class="cc-card rounded-2xl overflow-hidden mb-6">
    <div class="p-5 border-b border-[#1a3a25] flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 bg-blue-500/10 border border-blue-500/20 rounded-lg flex items-center justify-center">
                <i data-lucide="user-check" class="w-3.5 h-3.5 text-blue-400"></i>
            </div>
            <h2 class="text-base font-bold text-white">Mentor Directory</h2>
            <span class="text-xs text-gray-600 bg-[#1a3a25] px-2 py-0.5 rounded-full"><?= count($mentors) ?></span>
        </div>
        <button onclick="openModal('addMentorModal')" class="cc-btn-primary text-xs px-3 py-1.5 rounded-lg flex items-center gap-1.5">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Mentor
        </button>
    </div>
    <table class="w-full text-sm"><thead><tr class="border-b border-[#1a3a25]">
        <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest text-left">Name</th>
        <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest text-left">Phone</th>
        <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest text-left">Students</th>
        <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest text-left">Status</th>
        <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest text-right">Actions</th>
    </tr></thead><tbody class="divide-y divide-[#1a3a25]">
    <?php echo staffTable('mentor', $mentors, 'blue', 'user-check', 'Add Mentor'); ?>
    </tbody></table>
</div>

<!-- IRO Tab -->
<?php elseif ($tab === 'iro'): ?>
<div class="cc-card rounded-2xl overflow-hidden mb-6">
    <div class="p-5 border-b border-[#1a3a25] flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 bg-purple-500/10 border border-purple-500/20 rounded-lg flex items-center justify-center">
                <i data-lucide="globe" class="w-3.5 h-3.5 text-purple-400"></i>
            </div>
            <h2 class="text-base font-bold text-white">IRO Officers</h2>
            <span class="text-xs text-gray-600 bg-[#1a3a25] px-2 py-0.5 rounded-full"><?= count($iros) ?></span>
        </div>
        <button onclick="openModal('addIROModal')" class="cc-btn-primary text-xs px-3 py-1.5 rounded-lg flex items-center gap-1.5">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add IRO
        </button>
    </div>
    <table class="w-full text-sm"><thead><tr class="border-b border-[#1a3a25]">
        <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest text-left">Name</th>
        <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest text-left">Phone</th>
        <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest text-left">Int'l Students</th>
        <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest text-left">Status</th>
        <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest text-right">Actions</th>
    </tr></thead><tbody class="divide-y divide-[#1a3a25]">
    <?php echo staffTable('iro', $iros, 'purple', 'globe', 'Add IRO'); ?>
    </tbody></table>
</div>

<!-- Warden Tab -->
<?php elseif ($tab === 'warden'): ?>
<div class="cc-card rounded-2xl overflow-hidden">
    <div class="p-5 border-b border-[#1a3a25] flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 bg-orange-500/10 border border-orange-500/20 rounded-lg flex items-center justify-center">
                <i data-lucide="shield" class="w-3.5 h-3.5 text-orange-400"></i>
            </div>
            <h2 class="text-base font-bold text-white">Hostel Wardens</h2>
            <span class="text-xs text-gray-600 bg-[#1a3a25] px-2 py-0.5 rounded-full"><?= count($wardens) ?></span>
        </div>
        <button onclick="openModal('addWardenModal')" class="cc-btn-primary text-xs px-3 py-1.5 rounded-lg flex items-center gap-1.5">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Warden
        </button>
    </div>
    <table class="w-full text-sm"><thead><tr class="border-b border-[#1a3a25]">
        <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest text-left">Name</th>
        <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest text-left">Phone</th>
        <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest text-left">Hostel</th>
        <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest text-left">Status</th>
        <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest text-right">Actions</th>
    </tr></thead><tbody class="divide-y divide-[#1a3a25]">
    <?php echo staffTable('warden', $wardens, 'orange', 'shield', 'Add Warden'); ?>
    </tbody></table>
</div>
<?php endif; ?>

<!-- Add Mentor Modal -->
<div id="addMentorModal" class="hidden fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4">
    <div class="bg-[#0f2318] border border-[#1a3a25] rounded-2xl w-full max-w-lg shadow-2xl">
        <div class="p-6 border-b border-[#1a3a25] flex items-center justify-between">
            <div><h3 class="text-xl font-bold text-white">Add New Mentor</h3><p class="text-sm text-gray-500">Create a new faculty account.</p></div>
            <button onclick="closeModal('addMentorModal')" class="text-gray-600 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="create_staff">
            <input type="hidden" name="role" value="mentor">
            <div>
                <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Full Name</label>
                <div class="relative"><i data-lucide="user" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-600"></i>
                <input type="text" name="name" required placeholder="e.g. Dr. Robert Vance" class="cc-input w-full pl-10 pr-4 py-3 rounded-xl text-sm"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Email</label><input type="email" name="email" required class="cc-input w-full px-4 py-3 rounded-xl text-sm"></div>
                <div><label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Phone</label><input type="tel" name="phone" class="cc-input w-full px-4 py-3 rounded-xl text-sm"></div>
            </div>
            <div><label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Password</label>
            <div class="relative"><i data-lucide="lock" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-600"></i>
            <input type="password" name="password" required placeholder="Min. 6 characters" class="cc-input w-full pl-10 pr-4 py-3 rounded-xl text-sm"></div></div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('addMentorModal')" class="flex-1 py-2.5 border border-[#1a3a25] text-gray-300 hover:text-white rounded-xl text-sm transition-colors">Cancel</button>
                <button type="submit" class="flex-1 cc-btn-primary py-2.5 rounded-xl text-sm">Create Mentor Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Add IRO Modal -->
<div id="addIROModal" class="hidden fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4">
    <div class="bg-[#0f2318] border border-[#1a3a25] rounded-2xl w-full max-w-lg shadow-2xl">
        <div class="p-6 border-b border-[#1a3a25] flex items-center justify-between">
            <div><h3 class="text-xl font-bold text-white">Add IRO Officer</h3><p class="text-sm text-gray-500">Create an international relations account.</p></div>
            <button onclick="closeModal('addIROModal')" class="text-gray-600 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="create_staff">
            <input type="hidden" name="role" value="iro">
            <div><label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Full Name</label><input type="text" name="name" required class="cc-input w-full px-4 py-3 rounded-xl text-sm"></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Email</label><input type="email" name="email" required class="cc-input w-full px-4 py-3 rounded-xl text-sm"></div>
                <div><label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Phone</label><input type="tel" name="phone" class="cc-input w-full px-4 py-3 rounded-xl text-sm"></div>
            </div>
            <div><label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Password</label><input type="password" name="password" required placeholder="Min. 6 characters" class="cc-input w-full px-4 py-3 rounded-xl text-sm"></div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('addIROModal')" class="flex-1 py-2.5 border border-[#1a3a25] text-gray-300 hover:text-white rounded-xl text-sm transition-colors">Cancel</button>
                <button type="submit" class="flex-1 cc-btn-primary py-2.5 rounded-xl text-sm">Create IRO Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Warden Modal -->
<div id="addWardenModal" class="hidden fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4">
    <div class="bg-[#0f2318] border border-[#1a3a25] rounded-2xl w-full max-w-md shadow-2xl">
        <div class="p-6 border-b border-[#1a3a25] flex items-center justify-between">
            <div><h3 class="text-xl font-bold text-white">Add New Warden</h3><p class="text-sm text-gray-500">Assign a new official to a hostel block.</p></div>
            <button onclick="closeModal('addWardenModal')" class="text-gray-600 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="create_staff">
            <input type="hidden" name="role" value="warden">
            <div><label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Full Name</label><input type="text" name="name" required class="cc-input w-full px-4 py-3 rounded-xl text-sm"></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Email</label><input type="email" name="email" required class="cc-input w-full px-4 py-3 rounded-xl text-sm"></div>
                <div><label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Password</label><input type="password" name="password" required class="cc-input w-full px-4 py-3 rounded-xl text-sm"></div>
            </div>
            <div><label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Phone Number</label><input type="tel" name="phone" placeholder="e.g. +91 98765 43210" class="cc-input w-full px-4 py-3 rounded-xl text-sm"></div>
            <div>
                <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Hostel Assignment</label>
                <div class="relative">
                    <select name="hostel_id" required class="cc-input w-full px-4 py-3 rounded-xl text-sm appearance-none">
                        <option value="">Select Hostel Block</option>
                        <?php foreach ($hostels as $h): ?><option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['hostel_name']) ?></option><?php endforeach; ?>
                    </select>
                    <i data-lucide="chevron-down" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 pointer-events-none"></i>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('addWardenModal')" class="flex-1 py-2.5 border border-[#1a3a25] text-gray-300 hover:text-white rounded-xl text-sm transition-colors">Discard</button>
                <button type="submit" class="flex-1 cc-btn-primary py-2.5 rounded-xl text-sm">Confirm Assignment</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Staff Modal -->
<div id="editStaffModal" class="hidden fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4">
    <div class="bg-[#0f2318] border border-[#1a3a25] rounded-2xl w-full max-w-md shadow-2xl">
        <div class="p-6 border-b border-[#1a3a25] flex items-center justify-between">
            <div><h3 class="text-xl font-bold text-white">Edit Staff Member</h3><p class="text-sm text-gray-500">Update contact and profile information.</p></div>
            <button onclick="closeModal('editStaffModal')" class="text-gray-600 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update_staff">
            <input type="hidden" name="user_id" id="sedit_id">
            <div><label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Full Name</label>
            <input type="text" name="name" id="sedit_name" required class="cc-input w-full px-4 py-3 rounded-xl text-sm"></div>
            <div><label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Email</label>
            <input type="email" name="email" id="sedit_email" required class="cc-input w-full px-4 py-3 rounded-xl text-sm"></div>
            <div><label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Phone</label>
            <input type="tel" name="phone" id="sedit_phone" class="cc-input w-full px-4 py-3 rounded-xl text-sm"></div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('editStaffModal')" class="flex-1 py-2.5 border border-[#1a3a25] text-gray-300 hover:text-white rounded-xl text-sm transition-colors">Cancel</button>
                <button type="submit" class="flex-1 cc-btn-primary py-2.5 rounded-xl text-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php
$pageContent  = ob_get_clean();
$pageTitle    = 'Staff Registry';
$pageSubtitle = 'Manage all faculty mentors, hostel wardens, and IRO officers.';
$currentPage  = 'staff';
$pageScript = <<<JS
function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.body.style.overflow=''; }
document.querySelectorAll('.modal-backdrop').forEach(m => { m.addEventListener('click', function(e) { if(e.target===this) closeModal(this.id); }); });
function openStaffEdit(data) {
    document.getElementById('sedit_id').value    = data.id;
    document.getElementById('sedit_name').value  = data.name;
    document.getElementById('sedit_email').value = data.email;
    document.getElementById('sedit_phone').value = data.phone || '';
    openModal('editStaffModal');
}
JS;
require_once __DIR__ . '/../components/admin_layout.php';
