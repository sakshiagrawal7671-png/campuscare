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

    if ($action === 'update_user') {
        $id     = (int)($_POST['user_id'] ?? 0);
        $name   = trim($_POST['name'] ?? '');
        $email  = strtolower(trim($_POST['email'] ?? ''));
        $phone  = trim($_POST['phone'] ?? '') ?: null;
        $roll   = trim($_POST['roll_number'] ?? '') ?: null;
        if ($id && $name && $email) {
            $stmt = $pdo->prepare('UPDATE users SET name=:n, email=:e, phone=:ph, roll_number=:r WHERE id=:id');
            $stmt->execute(['n'=>$name,'e'=>$email,'ph'=>$phone,'r'=>$roll,'id'=>$id]);
            $message = 'Student updated.';
            $messageType = 'success';
        }
    }

    if ($action === 'delete_user') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id) {
            $pdo->prepare('DELETE FROM users WHERE id=:id AND role IN ("national","international")')->execute(['id'=>$id]);
            $message = 'Student deleted.';
            $messageType = 'success';
        }
    }

    if ($action === 'toggle_status') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id) {
            $pdo->prepare('UPDATE users SET status = IF(status="active","disabled","active") WHERE id=:id')->execute(['id'=>$id]);
            $message = 'Status updated.';
            $messageType = 'success';
        }
    }
}

// ── FETCH ──────────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$type   = $_GET['type'] ?? 'all';
$where  = "u.role IN ('national','international')";
$params = [];
if ($type === 'national')      { $where .= " AND u.role='national'"; }
elseif ($type === 'international') { $where .= " AND u.role='international'"; }
if ($search) {
    $where .= " AND (u.name LIKE :q OR u.roll_number LIKE :q OR u.email LIKE :q)";
    $params['q'] = "%$search%";
}
$stmt = $pdo->prepare(
    "SELECT u.id, u.name, u.email, u.role AS student_type, u.roll_number,
            u.phone, u.gender, u.status,
            h.hostel_name,
            m_user.name AS mentor_name,
            iro_user.name AS iro_name
     FROM users u
     LEFT JOIN hostels h ON h.id = u.hostel_id
     LEFT JOIN mentor_students ms ON ms.student_id = u.id
     LEFT JOIN users m_user ON m_user.id = ms.mentor_id
     LEFT JOIN iro_students iross ON iross.student_id = u.id
     LEFT JOIN users iro_user ON iro_user.id = iross.iro_id
     WHERE $where ORDER BY u.created_at DESC"
);
$stmt->execute($params);
$students = $stmt->fetchAll();

ob_start();
?>
<!-- Header -->
<div class="flex items-center justify-between mb-5">
    <form method="GET" class="flex gap-2 flex-1 max-w-2xl">
        <div class="relative flex-1">
            <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-600 pointer-events-none"></i>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                placeholder="Search by name, roll no, email..."
                class="cc-input w-full pl-10 pr-4 py-2.5 rounded-xl text-sm">
        </div>
        <div class="relative">
            <select name="type" onchange="this.form.submit()" class="cc-input px-4 py-2.5 rounded-xl text-sm pr-9 appearance-none">
                <option value="all" <?= $type==='all'?'selected':'' ?>>All Types</option>
                <option value="national" <?= $type==='national'?'selected':'' ?>>National</option>
                <option value="international" <?= $type==='international'?'selected':'' ?>>International</option>
            </select>
            <i data-lucide="chevron-down" class="w-3.5 h-3.5 absolute right-3 top-1/2 -translate-y-1/2 text-gray-600 pointer-events-none"></i>
        </div>
    </form>
    <a href="/campuscare/campuscare-api/auth/register.php"
       class="cc-btn-primary text-xs px-4 py-2.5 rounded-xl flex items-center gap-1.5 ml-3">
        <i data-lucide="user-plus" class="w-3.5 h-3.5"></i> Add Student
    </a>
</div>

<?php if ($message): ?>
<div class="mb-4 p-4 rounded-xl flex items-center gap-3 <?= $messageType==='success' ? 'bg-[#13ec87]/10 text-[#13ec87] border border-[#13ec87]/30' : 'bg-red-500/10 text-red-400 border border-red-500/20' ?>">
    <i data-lucide="<?= $messageType==='success'?'check-circle':'alert-circle' ?>" class="w-4 h-4 shrink-0"></i>
    <span class="text-sm font-medium"><?= htmlspecialchars($message) ?></span>
</div>
<?php endif; ?>

<!-- Table -->
<div class="cc-card rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[900px]">
            <thead>
                <tr class="border-b border-[#1a3a25]">
                    <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest">Student</th>
                    <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest">Roll No</th>
                    <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest">Type</th>
                    <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest">Hostel</th>
                    <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest">Mentor</th>
                    <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest">IRO</th>
                    <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest">Status</th>
                    <th class="px-5 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#1a3a25]">
                <?php if (empty($students)): ?>
                <tr><td colspan="8" class="px-5 py-10 text-center text-gray-500">No students found.</td></tr>
                <?php endif; ?>
                <?php foreach ($students as $s):
                    $initials = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $s['name']), 0, 2)));
                ?>
                <tr class="hover:bg-[#13ec87]/[0.03] transition-colors" id="row-<?= $s['id'] ?>">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-[#13ec87] flex items-center justify-center text-[#0a1510] text-xs font-bold shrink-0">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                            <div>
                                <div class="font-semibold text-white text-sm"><?= htmlspecialchars($s['name']) ?></div>
                                <div class="text-xs text-gray-600"><?= htmlspecialchars($s['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3.5 text-gray-400 font-mono text-xs"><?= htmlspecialchars($s['roll_number'] ?? '—') ?></td>
                    <td class="px-5 py-3.5">
                        <?php if ($s['student_type'] === 'national'): ?>
                            <span class="cc-badge-nat text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider">National</span>
                        <?php else: ?>
                            <span class="cc-badge-int text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider">International</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5 text-gray-400 text-xs"><?= htmlspecialchars($s['hostel_name'] ?? '—') ?></td>
                    <td class="px-5 py-3.5 italic text-gray-300 text-xs"><?= htmlspecialchars($s['mentor_name'] ?? '—') ?></td>
                    <td class="px-5 py-3.5 text-gray-400 text-xs"><?= htmlspecialchars($s['iro_name'] ?? 'N/A') ?></td>
                    <td class="px-5 py-3.5">
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="flex items-center gap-1.5 text-xs font-medium <?= $s['status']==='active' ? 'text-[#13ec87]' : 'text-red-400' ?> hover:opacity-70 transition-opacity">
                                <span class="w-1.5 h-1.5 rounded-full <?= $s['status']==='active' ? 'bg-[#13ec87]' : 'bg-red-400' ?>"></span>
                                <?= ucfirst($s['status']) ?>
                            </button>
                        </form>
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2 justify-end">
                            <button onclick='openEdit(<?= json_encode([
                                "id"=>$s["id"],"name"=>$s["name"],"email"=>$s["email"],
                                "phone"=>$s["phone"],"roll_number"=>$s["roll_number"]
                            ]) ?>)' class="p-1.5 rounded-lg border border-[#1a3a25] text-gray-500 hover:text-[#13ec87] hover:border-[#13ec87]/50 transition-colors">
                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                            </button>
                            <form method="POST" onsubmit="return confirm('Delete this student? This cannot be undone.')">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                                <button type="submit" class="p-1.5 rounded-lg border border-[#1a3a25] text-gray-500 hover:text-red-400 hover:border-red-500/30 transition-colors">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-[#1a3a25] flex items-center justify-between">
        <p class="text-xs text-gray-600"><strong class="text-gray-400"><?= count($students) ?></strong> students</p>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4">
    <div class="bg-[#0f2318] border border-[#1a3a25] rounded-2xl w-full max-w-md shadow-2xl">
        <div class="p-6 border-b border-[#1a3a25] flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-white">Edit Student</h3>
                <p class="text-sm text-gray-500">Update student profile details.</p>
            </div>
            <button onclick="closeEdit()" class="text-gray-600 hover:text-white transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" id="edit_id">
            <div>
                <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Full Name</label>
                <input type="text" name="name" id="edit_name" required class="cc-input w-full px-4 py-3 rounded-xl text-sm">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Email Address</label>
                <input type="email" name="email" id="edit_email" required class="cc-input w-full px-4 py-3 rounded-xl text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Roll Number</label>
                    <input type="text" name="roll_number" id="edit_roll" class="cc-input w-full px-4 py-3 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-[#13ec87] uppercase tracking-widest mb-2">Phone</label>
                    <input type="tel" name="phone" id="edit_phone" class="cc-input w-full px-4 py-3 rounded-xl text-sm">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeEdit()" class="flex-1 py-2.5 border border-[#1a3a25] text-gray-300 hover:text-white rounded-xl text-sm font-medium transition-colors">Cancel</button>
                <button type="submit" class="flex-1 cc-btn-primary py-2.5 rounded-xl text-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php
$pageContent  = ob_get_clean();
$pageTitle    = 'Student Account Directory';
$pageSubtitle = 'Centralized oversight for national and international enrollment cycles.';
$currentPage  = 'users';
$pageScript = <<<JS
function openEdit(data) {
    document.getElementById('edit_id').value    = data.id;
    document.getElementById('edit_name').value  = data.name;
    document.getElementById('edit_email').value = data.email;
    document.getElementById('edit_roll').value  = data.roll_number || '';
    document.getElementById('edit_phone').value = data.phone || '';
    document.getElementById('editModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeEdit() {
    document.getElementById('editModal').classList.add('hidden');
    document.body.style.overflow = '';
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
});
JS;
require_once __DIR__ . '/../components/admin_layout.php';
