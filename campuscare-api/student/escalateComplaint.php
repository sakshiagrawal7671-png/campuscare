<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('POST');

$student = requireRole(['national', 'international']);
$input = getJsonInput();
requireFields($input, ['complaint_id']);

$pdo = getDbConnection();
$complaint = fetchComplaintById($pdo, (int) $input['complaint_id']);

if ($complaint === null || (int) $complaint['student_id'] !== (int) $student['id']) {
    errorResponse('Complaint not found.', 404);
}

if (in_array($complaint['status'], ['resolved', 'closed'], true)) {
    errorResponse('Resolved or closed complaints cannot be escalated.', 422);
}

$adminId = getAnyAdminId($pdo);
if ($adminId === null) {
    errorResponse('No admin account is available for escalation.', 500);
}

$updateStatement = $pdo->prepare(
    'UPDATE complaints
     SET status = :status, assigned_to = :assigned_to, updated_at = CURRENT_TIMESTAMP
     WHERE id = :complaint_id'
);
$updateStatement->execute([
    'status' => 'escalated',
    'assigned_to' => $adminId,
    'complaint_id' => (int) $input['complaint_id'],
]);

addComplaintComment(
    $pdo,
    (int) $input['complaint_id'],
    (int) $student['id'],
    'Complaint escalated by the student.'
);

successResponse([
    'complaint_id' => (int) $input['complaint_id'],
    'assigned_to' => $adminId,
    'status' => 'escalated',
], 'Complaint escalated successfully.');

