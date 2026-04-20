<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('POST');

$admin = requireRole(['admin']);
$input = getJsonInput();
requireFields($input, ['complaint_id', 'assigned_to']);

$pdo = getDbConnection();
$complaint = fetchComplaintById($pdo, (int) $input['complaint_id']);

if ($complaint === null) {
    errorResponse('Complaint not found.', 404);
}

$assigneeStatement = $pdo->prepare(
    'SELECT id, name, role, status
     FROM users
     WHERE id = :id
     LIMIT 1'
);
$assigneeStatement->execute([
    'id' => (int) $input['assigned_to'],
]);
$assignee = $assigneeStatement->fetch();

if (!$assignee) {
    errorResponse('Target assignee was not found.', 404);
}

$allowedRoles = ['admin', 'mentor', 'warden', 'iro'];
if (!in_array($assignee['role'], $allowedRoles, true)) {
    errorResponse('Complaint can only be reassigned to admin, mentor, warden, or IRO users.', 422);
}

ensureComplaintAssigneeCompatibility($complaint, $assignee);

$status = $input['status'] ?? $complaint['status'];
$allowedStatuses = ['submitted', 'in_progress', 'resolved', 'closed', 'escalated'];

if (!in_array($status, $allowedStatuses, true)) {
    errorResponse('Invalid complaint status.', 422, [
        'allowed_statuses' => $allowedStatuses,
    ]);
}

$statement = $pdo->prepare(
    'UPDATE complaints
     SET assigned_to = :assigned_to, status = :status, updated_at = CURRENT_TIMESTAMP
     WHERE id = :complaint_id'
);
$statement->execute([
    'assigned_to' => (int) $assignee['id'],
    'status' => $status,
    'complaint_id' => (int) $input['complaint_id'],
]);

addComplaintComment(
    $pdo,
    (int) $input['complaint_id'],
    (int) $admin['id'],
    sprintf('Complaint reassigned to %s (%s).', $assignee['name'], $assignee['role'])
);

successResponse([
    'complaint_id' => (int) $input['complaint_id'],
    'assigned_to' => (int) $assignee['id'],
    'assigned_to_role' => $assignee['role'],
    'status' => $status,
], 'Complaint reassigned successfully.');
