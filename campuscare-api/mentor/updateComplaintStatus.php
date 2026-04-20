<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('POST');

$mentor = requireRole(['mentor']);
$input = getJsonInput();
requireFields($input, ['complaint_id', 'status']);

$pdo = getDbConnection();
updateComplaintForAssignee(
    $pdo,
    (int) $input['complaint_id'],
    (int) $mentor['id'],
    (int) $mentor['id'],
    (string) $input['status'],
    $input['message'] ?? null
);

successResponse([
    'complaint_id' => (int) $input['complaint_id'],
    'status' => $input['status'],
], 'Complaint status updated successfully.');

