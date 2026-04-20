<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/auth.php';

$user = requireAuth();
$pdo  = getDbConnection();

$complaintId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isStudent   = in_array($user['role'], ['national','international'], true);
$dashBack    = $isStudent ? '/campuscare/campuscare-api/student/dashboard.php'
                          : '/campuscare/campuscare-api/' . $user['role'] . '/dashboard.php';

if ($complaintId === 0) { header('Location: ' . $dashBack); exit; }

$message = ''; $messageType = '';

// ── POST: update status or post comment ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? '';
        $chkAuth   = $pdo->prepare('SELECT assigned_to FROM complaints WHERE id=:id');
        $chkAuth->execute(['id' => $complaintId]);
        $authRec = $chkAuth->fetch();
        if ($authRec && ($authRec['assigned_to'] == $user['id'] || $user['role'] === 'admin')) {
            $valid = ['submitted','in_progress','resolved','closed','escalated'];
            if (in_array($newStatus, $valid, true)) {
                $pdo->prepare('UPDATE complaints SET status=:s WHERE id=:id')->execute(['s'=>$newStatus,'id'=>$complaintId]);
                $message = 'Status updated successfully.'; $messageType = 'success';
            } else { $message = 'Invalid status.'; $messageType = 'error'; }
        } else { $message = 'No permission.'; $messageType = 'error'; }
    } 
    elseif ($action === 'post_comment') {
        $msgTxt = trim($_POST['message'] ?? '');
        if ($msgTxt !== '') {
            $pdo->prepare('INSERT INTO complaint_comments (complaint_id, user_id, message) VALUES (?, ?, ?)')
                ->execute([$complaintId, $user['id'], $msgTxt]);
            // Bump updated_at
            $pdo->prepare('UPDATE complaints SET updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$complaintId]);
            // Redirect to prevent form resubmission
            header("Location: view_complaint.php?id=$complaintId");
            exit;
        }
    }
}

// ── Fetch complaint ────────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT c.id, c.title, c.description, c.status, c.created_at, c.updated_at, c.student_id,
            cat.name AS category_name,
            s.name AS student_name, s.role AS student_role, s.email AS student_email, s.phone AS student_phone, s.roll_number AS student_roll_number,
            a.id   AS assigned_to_id, a.name AS assigned_to_name,
            a.email AS assigned_email, a.phone AS assigned_phone, a.role AS assigned_role
     FROM complaints c
     INNER JOIN categories cat ON cat.id = c.category_id
     INNER JOIN users s ON s.id = c.student_id
     LEFT  JOIN users a ON a.id = c.assigned_to
     WHERE c.id = :id"
);
$stmt->execute(['id' => $complaintId]);
$c = $stmt->fetch();

if (!$c) { header('Location: ' . $dashBack); exit; }

// Security: students can only see their own complaints
if ($isStudent && (int)$c['student_id'] !== (int)$user['id']) {
    header('Location: ' . $dashBack . '?error=AccessDenied'); exit;
}

// Fetch comments
$cmtStmt = $pdo->prepare("
    SELECT cc.message, cc.created_at, u.name, u.role, u.id AS user_id
    FROM complaint_comments cc
    INNER JOIN users u ON u.id = cc.user_id
    WHERE cc.complaint_id = :id
    ORDER BY cc.created_at ASC
");
$cmtStmt->execute(['id' => $complaintId]);
$comments = $cmtStmt->fetchAll();

$canUpdate = ($c['assigned_to_id'] == $user['id'] || $user['role'] === 'admin');

$statusMap = [
    'submitted'   => ['label'=>'Submitted',   'color'=>'#3b82f6', 'bg'=>'rgba(59,130,246,.12)', 'idx'=>0],
    'in_progress' => ['label'=>'In Progress', 'color'=>'#f97316', 'bg'=>'rgba(249,115,22,.12)', 'idx'=>1],
    'escalated'   => ['label'=>'Escalated',   'color'=>'#ef4444', 'bg'=>'rgba(239,68,68,.12)',  'idx'=>1],
    'resolved'    => ['label'=>'Resolved',    'color'=>'#13ec87', 'bg'=>'rgba(19,236,135,.12)', 'idx'=>3],
    'closed'      => ['label'=>'Closed',      'color'=>'#6b7280', 'bg'=>'rgba(107,114,128,.12)','idx'=>3],
];
$sc  = $statusMap[$c['status']] ?? $statusMap['submitted'];
$steps = [
    ['Submitted',    'check-circle'],
    ['Under Review', 'eye'],
    ['Investigation','search'],
    ['Resolution',   'check-square'],
];
$assignedInit = $c['assigned_to_name']
    ? implode('', array_map(fn($w)=>strtoupper($w[0]), array_slice(explode(' ',$c['assigned_to_name']),0,2)))
    : 'SY';
$assignedRoleLabel = match($c['assigned_role'] ?? '') {
    'admin'  => 'System Administrator',
    'mentor' => 'Faculty Mentor',
    'warden' => 'Hostel Warden',
    'iro'    => 'IRO Officer',
    default  => 'Assigned Staff',
};

// ── Render Page ────────────────────────────────────────────────
ob_start();
?>
<style>
.sc-card { background: #0c1a10; border: 1px solid #1a2e1d; border-radius: 16px; }
.sc-btn { background: #13ec87; color: #0a1510; font-weight: 700; transition: all .2s; }
.sc-btn:hover { background: #0fce74; }
.sc-input { background: #071009; border: 1px solid #1a3a25; color: #e8f5ec; outline: none; transition: border-color .2s; }
.sc-input:focus { border-color: #13ec87; }
</style>

<!-- Breadcrumb -->
<div class="flex items-center gap-2 text-xs mb-5" style="color:#6b7280;">
    <a href="<?= $dashBack ?>" style="color:#6b7280;" class="hover:text-white transition-colors">Home</a>
    <i data-lucide="chevron-right" class="w-3 h-3"></i>
    <span style="color:#13ec87;">Ticket #CS-<?= str_pad((string)$c['id'],4,'0',STR_PAD_LEFT) ?></span>
</div>

<?php if ($message): ?>
<div class="flex items-center gap-3 p-4 rounded-xl mb-5 border"
     style="<?= $messageType==='success'
         ? 'background:rgba(19,236,135,.1);border-color:rgba(19,236,135,.3);color:#13ec87;'
         : 'background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.2);color:#f87171;' ?>">
    <i data-lucide="<?= $messageType==='success'?'check-circle':'alert-circle' ?>" class="w-4 h-4 shrink-0"></i>
    <span class="text-sm font-medium"><?= htmlspecialchars($message) ?></span>
</div>
<?php endif; ?>

<div class="grid gap-6 grid-cols-1 lg:grid-cols-[1fr_360px]">

<!-- ── LEFT MAIN ───────────────────────────────────────────── -->
<div class="space-y-6">

    <!-- Header Card -->
    <div class="sc-card p-6 md:p-8">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-4 flex-wrap">
                    <span class="text-[10px] font-black px-3 py-1.5 rounded-md uppercase tracking-widest border"
                          style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;border-color:<?= $sc['color'] ?>33;">
                        <?= $sc['label'] ?>
                    </span>
                    <span class="text-xs font-bold" style="color:#6b7280;">
                        #CS-<?= str_pad((string)$c['id'],4,'0',STR_PAD_LEFT) ?>
                    </span>
                    <span class="text-xs px-2.5 py-1 rounded w-fit" style="background:#112417;color:#c8dbc9;border:1px solid #1a3a25;">
                        <?= htmlspecialchars($c['category_name']) ?>
                    </span>
                </div>
                <h1 class="text-2xl font-black text-white mb-3 tracking-tight"><?= htmlspecialchars($c['title']) ?></h1>
                <div class="flex items-center gap-5 text-sm flex-wrap" style="color:#8a9c8f;">
                    <span class="flex items-center gap-2">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        <?= date('M j, Y · g:i A', strtotime($c['created_at'])) ?>
                    </span>
                    <span class="flex items-center gap-2">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        <?= htmlspecialchars($c['student_name']) ?> <span style="color:#4b7553;">(<?= ucfirst($c['student_role']) ?>)</span>
                    </span>
                </div>
            </div>
            <!-- Action buttons -->
            <div class="flex gap-2 flex-wrap">
                <?php if ($isStudent && $c['status'] === 'submitted'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="status" value="escalated">
                    <button type="submit" class="flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-bold border transition-all hover:bg-red-500/20"
                            style="background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.3);color:#fca5a5;"
                            onclick="return confirm('Escalate this complaint to Admin?')">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i> Escalate to Admin
                    </button>
                </form>
                <?php endif; ?>
                <a href="<?= $dashBack ?>" class="flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-bold border transition-all hover:bg-[#1a3a25]"
                   style="background:#0a1510;border-color:#1a3a25;color:#c8dbc9;">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                </a>
            </div>
        </div>

        <!-- Progress Tracker -->
        <div class="mt-8 pt-6 border-t" style="border-color:#1a3a25;">
            <p class="text-[11px] font-bold mb-5 flex items-center gap-2" style="color:#13ec87;letter-spacing:.1em;text-transform:uppercase;">
                <i data-lucide="activity" class="w-4 h-4"></i> Resolution Progress
            </p>
            <div class="flex items-center">
                <?php foreach ($steps as $i => [$stepLabel, $stepIcon]):
                    $done   = $i <= $sc['idx'];
                    $active = $i === $sc['idx'];
                ?>
                <div class="flex items-center <?= $i < count($steps)-1 ? 'flex-1' : '' ?>">
                    <div class="flex flex-col items-center gap-2 relative">
                        <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center transition-all bg-[#0a1510]"
                             style="border-color:<?= $done ? $sc['color'] : '#1a3a25' ?>;
                                    <?= $active ? 'box-shadow:0 0 16px '.$sc['color'].'44;' : '' ?>">
                            <?php if ($done): ?>
                                <i data-lucide="<?= $stepIcon ?>" class="w-4.5 h-4.5" style="color:<?= $sc['color'] ?>;"></i>
                            <?php else: ?>
                                <div class="w-2.5 h-2.5 rounded-full" style="background:#1a3a25;"></div>
                            <?php endif; ?>
                        </div>
                        <div class="text-center absolute top-12">
                            <span class="text-[11px] font-bold whitespace-nowrap"
                                  style="color:<?= $done ? $sc['color'] : '#6b7280' ?>;"><?= $stepLabel ?></span>
                        </div>
                    </div>
                    <?php if ($i < count($steps)-1): ?>
                    <div class="flex-1 h-1 mx-2 rounded-full transition-all"
                         style="background:<?= $i < $sc['idx'] ? $sc['color'].'88' : '#1a3a25' ?>;"></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="h-8"></div>
        </div>
    </div>

    <!-- Description -->
    <div class="sc-card p-6 md:p-8">
        <h3 class="text-[11px] font-bold mb-4 flex items-center gap-2"
            style="color:#13ec87;text-transform:uppercase;letter-spacing:.1em;">
            <i data-lucide="align-left" class="w-4 h-4"></i> Issue Description
        </h3>
        <div class="p-5 rounded-xl border" style="background:#0a1510;border-color:#1a3a25;">
            <p class="text-[15px] leading-relaxed whitespace-pre-wrap" style="color:#e8ede9;"><?= htmlspecialchars($c['description']) ?></p>
        </div>
    </div>

    <!-- Chat / Discussion Thread -->
    <div class="sc-card overflow-hidden">
        <div class="p-5 md:px-8 border-b flex justify-between items-center" style="border-color:#1a3a25;background:#0c1a10;">
            <h3 class="text-[11px] font-bold flex items-center gap-2"
                style="color:#13ec87;text-transform:uppercase;letter-spacing:.1em;">
                <i data-lucide="message-square" class="w-4 h-4"></i> Communication Log
            </h3>
            <span class="text-xs font-semibold px-2.5 py-1 rounded" style="background:#112417;color:#4b7553;">
                <?= count($comments) ?> messages
            </span>
        </div>
        
        <div class="p-6 md:p-8 space-y-6" style="background:#071009;">
            <?php if (empty($comments)): ?>
                <div class="text-center py-10">
                    <div class="w-12 h-12 rounded-full mx-auto mb-3 flex items-center justify-center border" style="background:#0c1a10;border-color:#1a3a25;">
                        <i data-lucide="messages-square" class="w-5 h-5" style="color:#4b7553;"></i>
                    </div>
                    <p class="text-[13px] font-semibold text-white">No messages yet</p>
                    <p class="text-xs mt-1" style="color:#6b7280;">Start the conversation below.</p>
                </div>
            <?php else: ?>
                <?php foreach($comments as $cmt): 
                    $isMe = (int)$cmt['user_id'] === (int)$user['id'];
                    $align = $isMe ? 'flex-row-reverse' : 'flex-row';
                    $bubbleBg = $isMe ? '#13ec87' : '#1a3a25';
                    $bubbleColor = $isMe ? '#0a1510' : '#e8ede9';
                    $initial = strtoupper(substr($cmt['name'], 0, 1));
                    $time = date('M j, g:i A', strtotime($cmt['created_at']));
                ?>
                <div class="flex gap-4 <?= $align ?>">
                    <div class="w-8 h-8 rounded-full flex shrink-0 items-center justify-center text-xs font-bold border"
                         style="background:<?= $isMe ? '#0a1510' : '#0c1a10' ?>; color:<?= $isMe ? '#13ec87' : '#c8dbc9' ?>; border-color:<?= $isMe ? '#13ec87' : '#1a2e1d' ?>;">
                        <?= $initial ?>
                    </div>
                    <div class="flex flex-col <?= $isMe ? 'items-end' : 'items-start' ?> max-w-[80%]">
                        <div class="flex items-center gap-2 mb-1.5 <?= $isMe ? 'flex-row-reverse' : '' ?>">
                            <span class="text-xs font-bold text-white"><?= htmlspecialchars($isMe ? 'You' : $cmt['name']) ?></span>
                            <?php if (!$isMe): ?>
                            <span class="text-[10px] px-1.5 py-0.5 rounded uppercase tracking-wider font-semibold" style="background:#112417;color:#4b7553;">
                                <?= htmlspecialchars($cmt['role']) ?>
                            </span>
                            <?php endif; ?>
                            <span class="text-[10px]" style="color:#6b7280;"><?= $time ?></span>
                        </div>
                        <div class="px-5 py-3 rounded-2xl text-[14px] leading-relaxed whitespace-pre-wrap"
                             style="background:<?= $bubbleBg ?>;color:<?= $bubbleColor ?>;
                                    border-<?= $isMe ? 'top-right' : 'top-left' ?>-radius: 4px;">
                            <?= htmlspecialchars($cmt['message']) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!in_array($c['status'], ['closed', 'resolved'])): ?>
        <div class="p-5 md:p-6 border-t" style="border-color:#1a3a25;background:#0c1a10;">
            <form method="POST" class="flex gap-3">
                <input type="hidden" name="action" value="post_comment">
                <input type="text" name="message" required autocomplete="off" placeholder="Write a message..."
                       class="sc-input flex-1 px-5 py-3 rounded-xl text-[14px]">
                <button type="submit" class="sc-btn px-6 rounded-xl flex items-center gap-2 shrink-0">
                    <span class="hidden sm:inline">Send</span>
                    <i data-lucide="send" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="p-5 md:p-6 border-t text-center" style="border-color:#1a2e1d;background:#0a1510;">
            <p class="text-xs" style="color:#4b5563;">This ticket is marked as <?= htmlspecialchars($c['status']) ?>. Thread is locked.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── RIGHT SIDEBAR ───────────────────────────────────────── -->
<div class="space-y-6">

    <?php if ($isStudent): ?>
    <!-- Assigned Authority -->
    <div class="sc-card p-6 border-t-4" style="border-top-color:#13ec87;">
        <p class="text-[10px] font-bold mb-5 flex items-center gap-2" style="color:#13ec87;text-transform:uppercase;letter-spacing:.1em;">
            <i data-lucide="shield-check" class="w-4 h-4"></i> Assigned Coordinator
        </p>
        <div class="flex flex-col items-center text-center">
            <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-2xl font-black mb-4 border-2"
                 style="background:#0a1510;color:#13ec87;border-color:#1a3a25;">
                <?= htmlspecialchars($assignedInit) ?>
            </div>
            <p class="font-bold text-white text-lg tracking-tight"><?= htmlspecialchars($c['assigned_to_name'] ?? 'System Admin') ?></p>
            <p class="text-xs mt-1 font-semibold px-3 py-1 rounded-full border mb-5" style="background:#112417;color:#4b7553;border-color:#1a3a25;">
                <?= $assignedRoleLabel ?>
            </p>
        </div>
        
        <?php if ($c['assigned_email']): ?>
        <div class="space-y-2.5">
            <a href="mailto:<?= htmlspecialchars($c['assigned_email']) ?>"
               class="w-full flex items-center justify-center gap-2 py-3 rounded-xl text-[13px] font-bold border transition-all hover:bg-[#1a3a25]"
               style="background:#071009;border-color:#1a3a25;color:#c8dbc9;">
                <i data-lucide="mail" class="w-4 h-4"></i> Send Email
            </a>
            <?php if ($c['assigned_phone']): ?>
            <a href="tel:<?= htmlspecialchars($c['assigned_phone']) ?>"
               class="w-full flex items-center justify-center gap-2 py-3 rounded-xl text-[13px] font-bold border transition-all hover:bg-[#1a3a25]"
               style="background:#071009;border-color:#1a3a25;color:#c8dbc9;">
                <i data-lucide="phone" class="w-4 h-4"></i> Call Official
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <?php $studentInit = strtoupper(substr($c['student_name'] ?? 'S', 0, 1)); ?>
    <!-- Student Details -->
    <div class="sc-card p-6 border-t-4" style="border-top-color:#13ec87;">
        <p class="text-[10px] font-bold mb-5 flex items-center gap-2" style="color:#13ec87;text-transform:uppercase;letter-spacing:.1em;">
            <i data-lucide="user" class="w-4 h-4"></i> Student Details
        </p>
        <div class="flex flex-col items-center text-center">
            <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-2xl font-black mb-4 border-2"
                 style="background:#0a1510;color:#13ec87;border-color:#1a3a25;">
                <?= htmlspecialchars($studentInit) ?>
            </div>
            <p class="font-bold text-white text-lg tracking-tight"><?= htmlspecialchars($c['student_name']) ?></p>
            <p class="text-xs mt-1 font-semibold px-3 py-1 rounded-full border mb-2" style="background:#112417;color:#4b7553;border-color:#1a3a25;">
                <?= ucfirst($c['student_role']) ?>
            </p>
            <?php if (!empty($c['student_roll_number'])): ?>
            <p class="text-xs font-semibold mb-5" style="color:#8a9c8f;">Roll No: <?= htmlspecialchars($c['student_roll_number']) ?></p>
            <?php else: ?>
            <div class="mb-5"></div>
            <?php endif; ?>
        </div>
        
        <?php if ($c['student_email']): ?>
        <div class="space-y-2.5">
            <a href="mailto:<?= htmlspecialchars($c['student_email']) ?>"
               class="w-full flex items-center justify-center gap-2 py-3 rounded-xl text-[13px] font-bold border transition-all hover:bg-[#1a3a25]"
               style="background:#071009;border-color:#1a3a25;color:#c8dbc9;">
                <i data-lucide="mail" class="w-4 h-4"></i> Send Email
            </a>
            <?php if ($c['student_phone']): ?>
            <a href="tel:<?= htmlspecialchars($c['student_phone']) ?>"
               class="w-full flex items-center justify-center gap-2 py-3 rounded-xl text-[13px] font-bold border transition-all hover:bg-[#1a3a25]"
               style="background:#071009;border-color:#1a3a25;color:#c8dbc9;">
                <i data-lucide="phone" class="w-4 h-4"></i> Call Student
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Quick Actions (assigned staff / admin only) -->
    <?php if ($canUpdate): ?>
    <div class="sc-card p-6">
        <p class="text-[10px] font-bold mb-4" style="color:#13ec87;text-transform:uppercase;letter-spacing:.1em;">Manage Ticket</p>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_status">
            <div>
                <p class="text-xs mb-2 font-medium" style="color:#8a9c8f;">Update Current Status</p>
                <div class="relative">
                    <select name="status" class="sc-input w-full pl-4 pr-10 py-3 rounded-xl text-[13px] appearance-none font-medium cursor-pointer">
                        <?php foreach (['submitted'=>'Submitted','in_progress'=>'In Progress','resolved'=>'Resolved','closed'=>'Closed','escalated'=>'Escalated'] as $val=>$lab): ?>
                        <option value="<?= $val ?>" <?= $c['status']===$val?'selected':'' ?>><?= $lab ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i data-lucide="chevron-down" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" style="color:#4b7553;"></i>
                </div>
            </div>
            <button type="submit" class="w-full sc-btn py-3 rounded-xl text-[13px] flex items-center justify-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Save Changes
            </button>
        </form>
    </div>
    <?php endif; ?>

</div>
</div>

<?php
$pageContent = ob_get_clean();
$pageTitle   = 'Complaint Details';
$currentPage = 'complaints';

// Select the appropriate layout based on user role
$roleLayouts = [
    'national'      => 'student_layout.php',
    'international' => 'student_layout.php',
    'admin'         => 'admin_layout.php',
    'warden'        => 'warden_layout.php',
    'mentor'        => 'mentor_layout.php',
    'iro'           => 'iro_layout.php',
];
$layoutFile = $roleLayouts[$user['role']] ?? 'layout.php';
require_once __DIR__ . '/../components/' . $layoutFile;
