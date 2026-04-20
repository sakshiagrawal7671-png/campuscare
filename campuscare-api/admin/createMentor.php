<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('POST');

requireRole(['admin']);
$input = getJsonInput();
$pdo = getDbConnection();

try {
    $mentorId = createStaffAccount($pdo, $input, 'mentor');

    successResponse([
        'user_id' => $mentorId,
        'role' => 'mentor',
    ], 'Mentor account created successfully.', 201);
} catch (PDOException $exception) {
    $message = $exception->getCode() === '23000'
        ? 'Mentor email or roll number already exists.'
        : 'Mentor account could not be created.';

    errorResponse($message, 400, buildExceptionErrors($exception));
}
