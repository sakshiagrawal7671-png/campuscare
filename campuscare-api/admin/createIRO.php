<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('POST');

requireRole(['admin']);
$input = getJsonInput();
$pdo = getDbConnection();

try {
    $iroId = createStaffAccount($pdo, $input, 'iro');

    successResponse([
        'user_id' => $iroId,
        'role' => 'iro',
    ], 'IRO account created successfully.', 201);
} catch (PDOException $exception) {
    $message = $exception->getCode() === '23000'
        ? 'IRO email or roll number already exists.'
        : 'IRO account could not be created.';

    errorResponse($message, 400, buildExceptionErrors($exception));
}
