<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/roleGuard.php';

$admin = requireRole(['admin']);
$pdo = getDbConnection();

$message = '';
$messageType = '';

// Handle Reassignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reassign') {
    $complaintId = (int) ($_POST['complaint_id'] ?? 0);
    $newAssigneeId = (int) ($_POST['new_assignee_id'] ?? 0);

    if ($complaintId > 0 && $newAssigneeId > 0) {
        $update = $pdo->prepare('UPDATE complaints SET assigned_to = :assigned_to WHERE id = :id');
        $update->execute(['assigned_to' => $newAssigneeId, 'id' => $complaintId]);
        $message = 'Complaint reassigned successfully.';
        $messageType = 'success';
    } else {
        $message = 'Invalid parameters for reassignment.';
        $messageType = 'error';
    }
}

// Fetch all staff members for the dropdown
$staffStatement = $pdo->query("SELECT id, name, role FROM users WHERE role IN ('mentor', 'warden', 'iro') ORDER BY name ASC");
$staffMembers = $staffStatement->fetchAll();

// Fetch complaints
$statement = $pdo->prepare(
    'SELECT c.id, c.title, c.description, c.status, c.created_at, 
            cat.name AS category_name,
            u_student.name AS student_name,
            u_assignee.id AS assignee_id,
            u_assignee.name AS assignee_name
     FROM complaints c
     INNER JOIN categories cat ON cat.id = c.category_id
     INNER JOIN users u_student ON u_student.id = c.student_id
     LEFT JOIN users u_assignee ON u_assignee.id = c.assigned_to
     ORDER BY c.created_at DESC'
);
$statement->execute();
$complaints = $statement->fetchAll();

function getStatusColorClass(string $status): string {
    switch ($status) {
        case 'submitted': return 'bg-blue-500/10 text-blue-400 border-blue-500/20';
        case 'in_progress': return 'bg-orange-500/10 text-orange-400 border-orange-500/20';
        case 'resolved': return 'bg-[#13ec87]/10 text-[#13ec87] border-[#13ec87]/20';
        case 'closed': return 'bg-gray-500/10 text-gray-400 border-gray-500/20';
        case 'escalated': return 'bg-red-500/10 text-red-500 border-red-500/20';
        default: return 'bg-gray-500/10 text-gray-400 border-gray-500/20';
    }
}

ob_start();
?>
<?php if ($message): ?>
    <div class="mb-6 p-4 rounded-xl flex items-center gap-3 <?= $messageType === 'success' ? 'bg-[#13ec87]/10 text-[#13ec87] border border-[#13ec87]/30' : 'bg-red-500/10 text-red-400 border border-red-500/20' ?>">
        <i data-lucide="<?= $messageType === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4 shrink-0"></i>
        <span class="text-sm font-medium"><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<div class="cc-card rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[800px]">
            <thead>
                <tr class="border-b border-[#1a3a25]">
                    <th class="px-6 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest">Date</th>
                    <th class="px-6 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest">Issue</th>
                    <th class="px-6 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest">Student</th>
                    <th class="px-6 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest">Status</th>
                    <th class="px-6 py-3 text-[10px] font-bold text-[#13ec87] uppercase tracking-widest">Assigned Staff</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#1a3a25]">
                <?php foreach ($complaints as $c): ?>
                <tr class="hover:bg-[#13ec87]/[0.03] transition-colors">
                    <td class="px-6 py-4 text-gray-500 whitespace-nowrap text-xs">
                        <?= date('M j, Y', strtotime($c['created_at'])) ?>
                    </td>
                    <td class="px-6 py-4 max-w-[250px]">
                        <a href="/campuscare/campuscare-api/shared/view_complaint.php?id=<?= $c['id'] ?>" class="font-medium text-white hover:text-[#13ec87] transition-colors truncate block"><?= htmlspecialchars($c['title']) ?></a>
                        <div class="text-xs text-gray-600 mt-0.5"><?= htmlspecialchars($c['category_name']) ?></div>
                    </td>
                    <td class="px-6 py-4 text-gray-400 text-xs"><?= htmlspecialchars($c['student_name']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2.5 py-1 text-[10px] rounded-full border uppercase font-bold tracking-wider <?= getStatusColorClass($c['status']) ?>">
                            <?= htmlspecialchars(str_replace('_', ' ', $c['status'])) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <form method="POST">
                            <input type="hidden" name="action" value="reassign">
                            <input type="hidden" name="complaint_id" value="<?= $c['id'] ?>">
                            <div class="relative">
                                <select name="new_assignee_id" onchange="this.form.submit()" class="cc-input text-xs rounded-lg px-3 py-1.5 max-w-[180px] w-full appearance-none cursor-pointer">
                                    <?php foreach ($staffMembers as $staff): ?>
                                        <option value="<?= $staff['id'] ?>" <?= ((int)$c['assignee_id'] === $staff['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($staff['name']) ?> (<?= htmlspecialchars($staff['role']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <i data-lucide="chevron-down" class="w-3 h-3 absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-600 pointer-events-none"></i>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($complaints)): ?>
                <tr><td colspan="5" class="px-6 py-10 text-center text-gray-500">No complaints found in the system.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="px-6 py-3 border-t border-[#1a3a25]">
        <p class="text-xs text-gray-600"><?= count($complaints) ?> total complaints</p>
    </div>
</div>

<?php
$pageContent = ob_get_clean();
$pageTitle = 'Complaints';
$pageSubtitle = 'Review, monitor, and reassign all active institutional tickets.';
$currentPage = 'complaints';
require_once __DIR__ . '/../components/admin_layout.php';
