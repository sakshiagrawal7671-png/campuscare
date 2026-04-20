<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('POST');

requireRole(['admin']);
$input = getJsonInput();
requireFields($input, ['user_id', 'name', 'email', 'phone', 'role']);

$userId = (int) $input['user_id'];
$newRole = (string) $input['role'];

validateRoleValue($newRole, true);

if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    errorResponse('A valid email address is required.', 422);
}

$pdo = getDbConnection();
$existingUser = requireExistingUser($pdo, $userId);

if ($userId === 1 && $newRole !== 'admin') {
    errorResponse('Primary admin role cannot be changed.', 422);
}

if (($existingUser['role'] ?? '') === 'admin' || $newRole === 'admin') {
    if (($existingUser['role'] ?? '') !== 'admin' || $newRole !== 'admin') {
        errorResponse('Admin role changes are not supported from this endpoint.', 422);
    }
}

if (isStudentRole((string) $existingUser['role']) !== isStudentRole($newRole)) {
    errorResponse('Student and staff/admin roles cannot be switched through this endpoint.', 422);
}

$hostelId = null;
if (in_array($newRole, ['national', 'international', 'warden'], true)) {
    if (!isset($input['hostel_id']) || (int) $input['hostel_id'] <= 0) {
        errorResponse('A valid hostel is required for this role.', 422);
    }

    $hostelId = (int) $input['hostel_id'];
    requireHostel($pdo, $hostelId);
}

try {
    $pdo->beginTransaction();

    $updateUser = $pdo->prepare(
        'UPDATE users
         SET name = :name,
             email = :email,
             phone = :phone,
             role = :role,
             hostel_id = :hostel_id
         WHERE id = :id'
    );
    $updateUser->execute([
        'name' => trim((string) $input['name']),
        'email' => strtolower(trim((string) $input['email'])),
        'phone' => trim((string) $input['phone']),
        'role' => $newRole,
        'hostel_id' => $hostelId,
        'id' => $userId,
    ]);

    $existingRole = (string) ($existingUser['role'] ?? '');

    if ($existingRole === 'mentor' && $newRole !== 'mentor') {
        $pdo->prepare('DELETE FROM mentor_students WHERE mentor_id = :id')
            ->execute(['id' => $userId]);
    }

    if ($existingRole === 'iro' && $newRole !== 'iro') {
        $pdo->prepare('DELETE FROM iro_students WHERE iro_id = :id')
            ->execute(['id' => $userId]);
    }

    if ($existingRole === 'warden' && $newRole !== 'warden') {
        $pdo->prepare('DELETE FROM hostel_wardens WHERE warden_id = :id')
            ->execute(['id' => $userId]);
    }

    if ($existingRole === 'international' && $newRole === 'national') {
        $pdo->prepare('DELETE FROM iro_students WHERE student_id = :id')
            ->execute(['id' => $userId]);
    }

    if ($newRole === 'warden') {
        ensureWardenHostelAvailability($pdo, (int) $hostelId, $userId);

        $pdo->prepare('DELETE FROM hostel_wardens WHERE warden_id = :warden_id')
            ->execute(['warden_id' => $userId]);

        $pdo->prepare(
            'INSERT INTO hostel_wardens (hostel_id, warden_id)
             VALUES (:hostel_id, :warden_id)'
        )->execute([
            'hostel_id' => (int) $hostelId,
            'warden_id' => $userId,
        ]);
    }

    if (isStudentRole($newRole)) {
        $mentorStatement = $pdo->prepare('SELECT 1 FROM mentor_students WHERE student_id = :student_id LIMIT 1');
        $mentorStatement->execute(['student_id' => $userId]);
        if ($mentorStatement->fetchColumn() === false) {
            assignMentorToStudent($pdo, $userId);
        }
    }

    if ($newRole === 'international') {
        $iroStatement = $pdo->prepare('SELECT 1 FROM iro_students WHERE student_id = :student_id LIMIT 1');
        $iroStatement->execute(['student_id' => $userId]);
        if ($iroStatement->fetchColumn() === false) {
            assignIroToStudent($pdo, $userId);
        }
    }

    $pdo->commit();

    successResponse([
        'user_id' => $userId,
    ], 'User updated successfully.');
} catch (RuntimeException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    errorResponse($exception->getMessage(), 422);
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $message = $exception->getCode() === '23000'
        ? 'Email, roll number, or assignment conflicts with an existing record.'
        : 'User could not be updated.';

    errorResponse($message, 400, buildExceptionErrors($exception));
}
