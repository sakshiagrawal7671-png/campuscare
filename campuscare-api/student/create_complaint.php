<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/roleGuard.php';

$student = requireRole(['national', 'international']);
$pdo     = getDbConnection();
$isIntl  = $student['role'] === 'international';

// Type: 'mentor' or 'iro' (international only can pick iro)
$type = (isset($_GET['type']) && $_GET['type'] === 'iro' && $isIntl) ? 'iro' : 'mentor';

if ($type === 'iro') {
    $categories = $pdo->query("SELECT id, name FROM categories WHERE route_to = 'iro' ORDER BY name ASC")->fetchAll();
} else {
    // For normal complaints, show all non-IRO categories (Mentor, Warden, Admin)
    $categories = $pdo->query("SELECT id, name FROM categories WHERE route_to != 'iro' ORDER BY name ASC")->fetchAll();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $routeTo     = $_POST['route_to'] ?? $type; // 'mentor' or 'iro'

    if (!$title || !$description || !$categoryId) {
        $message = 'All fields are required.';
        $messageType = 'error';
    } else {
        $catStmt = $pdo->prepare('SELECT id, route_to FROM categories WHERE id = :id LIMIT 1');
        $catStmt->execute(['id' => $categoryId]);
        $category = $catStmt->fetch();

        if (!$category) {
            $message = 'Invalid category.'; $messageType = 'error';
        } else {
            try {
                // Resolve assignee based on routing choice
                if ($routeTo === 'iro' && $isIntl) {
                    $iroStmt = $pdo->prepare("SELECT iro_id FROM iro_students WHERE student_id = :sid LIMIT 1");
                    $iroStmt->execute(['sid' => $student['id']]);
                    $iroRow    = $iroStmt->fetch();
                    $assigneeId = $iroRow ? $iroRow['iro_id'] : null;
                } else {
                    $assigneeId = resolveComplaintAssignee($pdo, $student, $category['route_to']);
                }

                $insert = $pdo->prepare(
                    'INSERT INTO complaints (student_id, category_id, title, description, assigned_to, status)
                     VALUES (:sid, :cid, :title, :desc, :assigned, "submitted")'
                );
                $insert->execute([
                    'sid'     => (int)$student['id'],
                    'cid'     => (int)$category['id'],
                    'title'   => $title,
                    'desc'    => $description,
                    'assigned'=> $assigneeId,
                ]);

                $message = 'Complaint submitted and routed to the appropriate authority.';
                $messageType = 'success';
                unset($_POST['title'], $_POST['description'], $_POST['category_id']);
            } catch (\Exception $e) {
                $message = 'Error: ' . $e->getMessage(); $messageType = 'error';
            }
        }
    }
}

ob_start();
?>
<div class="max-w-2xl mx-auto">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs mb-6" style="color:#6b7280;">
        <a href="dashboard.php" class="hover:text-white transition-colors">Home</a>
        <i data-lucide="chevron-right" class="w-3 h-3"></i>
        <a href="dashboard.php" class="hover:text-white transition-colors">Complaints</a>
        <i data-lucide="chevron-right" class="w-3 h-3"></i>
        <span style="color:#13ec87;">New Complaint</span>
    </div>

    <!-- Type Tabs (International only) -->
    <?php if ($isIntl): ?>
    <div class="flex gap-2 mb-6 p-1.5 rounded-xl" style="background:#0f2318;">
        <a href="?type=mentor" class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-bold transition-all
           <?= $type==='mentor' ? 'text-[#0a1510]' : '' ?>"
           style="<?= $type==='mentor' ? 'background:#13ec87;' : 'color:#6b7280;' ?>">
            <i data-lucide="user-check" class="w-4 h-4"></i> General Complaint
        </a>
        <a href="?type=iro" class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-bold transition-all"
           style="<?= $type==='iro' ? 'background:#7c3aed;color:#fff;' : 'color:#6b7280;' ?>">
            <i data-lucide="globe" class="w-4 h-4"></i> IRO Complaint
        </a>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="sc-card p-6 mb-5" style="<?= $type==='iro' ? 'border-color:rgba(124,58,237,.4);background:linear-gradient(135deg,#0f0718,#0a1510);' : '' ?>">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0"
                 style="background:<?= $type==='iro' ? 'rgba(124,58,237,.2);border:1px solid rgba(124,58,237,.4)' : '#13ec8722;border:1px solid #13ec8744' ?>;">
                <i data-lucide="<?= $type==='iro' ? 'globe' : 'message-square' ?>" class="w-6 h-6"
                   style="color:<?= $type==='iro' ? '#a78bfa' : '#13ec87' ?>;"></i>
            </div>
            <div>
                <h1 class="text-xl font-black text-white">
                    <?= $type==='iro' ? 'IRO Complaint' : 'File a New Complaint' ?>
                </h1>
                <p class="text-xs mt-1" style="color:#6b7280;">
                    <?= $type==='iro'
                        ? 'For international student concerns: visa, accommodation, academic, or welfare issues routed to your IRO Officer.'
                        : 'Submit detailed information about your issue. It will be routed to the appropriate authority.' ?>
                </p>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="flex items-center gap-3 p-4 rounded-xl mb-5 border
        <?= $messageType==='success' ? '' : '' ?>"
        style="<?= $messageType==='success'
            ? 'background:rgba(19,236,135,.1);border-color:rgba(19,236,135,.3);color:#13ec87;'
            : 'background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.2);color:#f87171;' ?>">
        <i data-lucide="<?= $messageType==='success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4 shrink-0"></i>
        <span class="text-sm font-medium"><?= htmlspecialchars($message) ?></span>
        <?php if ($messageType==='success'): ?>
        <a href="dashboard.php" class="ml-auto text-xs font-bold px-3 py-1 rounded-lg" style="background:#13ec87;color:#0a1510;">← Dashboard</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" class="sc-card p-6 space-y-5">
        <input type="hidden" name="route_to" value="<?= $type ?>">

        <div>
            <label class="sc-label" style="<?= $type==='iro'?'color:#a78bfa;':'' ?>">Issue Category *</label>
            <div class="relative">
                <select name="category_id" required class="sc-input w-full px-4 py-3 rounded-xl text-sm appearance-none">
                    <option value="" disabled <?= empty($_POST['category_id'])?'selected':'' ?>>Select category...</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id'])&&(int)$_POST['category_id']===$cat['id'])?'selected':'' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <i data-lucide="chevron-down" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" style="color:#6b7280;"></i>
            </div>
            <p class="text-[10px] mt-1.5" style="color:#4b5563;">Category determines which staff member receives your complaint.</p>
        </div>

        <div>
            <label class="sc-label" style="<?= $type==='iro'?'color:#a78bfa;':'' ?>">Complaint Title *</label>
            <input type="text" name="title" maxlength="255"
                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                   placeholder="Brief summary of the issue..."
                   required class="sc-input w-full px-4 py-3 rounded-xl text-sm">
        </div>

        <div>
            <label class="sc-label" style="<?= $type==='iro'?'color:#a78bfa;':'' ?>">Detailed Description *</label>
            <textarea name="description" rows="5" required
                      placeholder="Provide all necessary details, steps taken, dates, and relevant context..."
                      class="sc-input w-full px-4 py-3 rounded-xl text-sm resize-y"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <?php if ($type === 'iro'): ?>
        <div class="p-4 rounded-xl border" style="background:rgba(124,58,237,.05);border-color:rgba(124,58,237,.2);">
            <div class="flex items-start gap-2">
                <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5" style="color:#a78bfa;"></i>
                <p class="text-xs" style="color:#9ca3af;">
                    This complaint will be routed <strong style="color:#a78bfa;">directly to your assigned IRO Officer</strong> for handling international student-specific concerns.
                </p>
            </div>
        </div>
        <?php endif; ?>

        <div class="flex gap-3 pt-2">
            <a href="dashboard.php" class="px-5 py-3 rounded-xl text-sm font-bold border transition-all"
               style="background:#071009;border-color:#1a3a25;color:#9ca3af;">← Back</a>
            <button type="submit" class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-bold transition-all"
                    style="<?= $type==='iro'
                        ? 'background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;'
                        : 'background:linear-gradient(135deg,#13ec87,#0aab62);color:#0a1510;' ?>">
                <i data-lucide="send" class="w-4 h-4"></i>
                <?= $type==='iro' ? 'Submit to IRO Officer' : 'Submit Complaint' ?>
            </button>
        </div>
    </form>
</div>

<?php
$pageContent = ob_get_clean();
$pageTitle   = $type === 'iro' ? 'IRO Complaint' : 'New Complaint';
$currentPage = $type === 'iro' ? 'iro' : 'create';
require_once __DIR__ . '/../components/student_layout.php';
