<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('POST');

requireRole(['admin']);
$input = getJsonInput();
requireFields($input, ['hostel_id']);

$pdo = getDbConnection();
$hostelId = (int) $input['hostel_id'];
$hostel = requireHostel($pdo, $hostelId);
ensureWardenHostelAvailability($pdo, $hostelId);

try {
    $pdo->beginTransaction();

    $wardenId = createStaffAccount($pdo, $input, 'warden');

    $mappingStatement = $pdo->prepare(
        'INSERT INTO hostel_wardens (hostel_id, warden_id)
         VALUES (:hostel_id, :warden_id)'
    );
    $mappingStatement->execute([
        'hostel_id' => $hostelId,
        'warden_id' => $wardenId,
    ]);

    $pdo->commit();

    successResponse([
        'user_id' => $wardenId,
        'role' => 'warden',
        'hostel_id' => $hostelId,
        'hostel_name' => $hostel['hostel_name'],
    ], 'Warden account created successfully.', 201);
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $message = $exception->getCode() === '23000'
        ? 'Warden email, roll number, or hostel mapping already exists.'
        : 'Warden account could not be created.';

    errorResponse($message, 400, buildExceptionErrors($exception));
}
