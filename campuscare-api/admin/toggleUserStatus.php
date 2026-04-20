<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('POST');

$admin = requireRole(['admin']);
$input = getJsonInput();
requireFields($input, ['user_id', 'status']);

if (!in_array($input['status'], ['active', 'disabled'], true)) {
    errorResponse('Invalid status value.', 422);
}

if ((int) $input['user_id'] === (int) $admin['id'] && $input['status'] === 'disabled') {
    errorResponse('Admin cannot disable the current session account.', 422);
}

$pdo = getDbConnection();
$statement = $pdo->prepare(
    'UPDATE users
     SET status = :status
     WHERE id = :id'
);
$statement->execute([
    'status' => $input['status'],
    'id' => (int) $input['user_id'],
]);

if ($statement->rowCount() === 0) {
    errorResponse('User not found or status unchanged.', 404);
}

successResponse([
    'user_id' => (int) $input['user_id'],
    'status' => $input['status'],
], 'User status updated successfully.');

