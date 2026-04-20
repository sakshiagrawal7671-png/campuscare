<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('POST');

$warden = requireRole(['warden']);
$input = getJsonInput();
requireFields($input, ['complaint_id', 'status']);

$pdo = getDbConnection();
updateComplaintForAssignee(
    $pdo,
    (int) $input['complaint_id'],
    (int) $warden['id'],
    (int) $warden['id'],
    (string) $input['status'],
    $input['message'] ?? null
);

successResponse([
    'complaint_id' => (int) $input['complaint_id'],
    'status' => $input['status'],
], 'Complaint status updated successfully.');

