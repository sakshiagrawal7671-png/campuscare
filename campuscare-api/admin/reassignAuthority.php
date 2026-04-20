<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('POST');

requireRole(['admin']);
$input = getJsonInput();
requireFields($input, ['type', 'source_user_id', 'target_user_id']);

$type = (string) $input['type'];
$sourceUserId = (int) $input['source_user_id'];
$targetUserId = (int) $input['target_user_id'];

$allowedTypes = ['mentor_students', 'iro_students', 'warden_hostel', 'complaints'];
if (!in_array($type, $allowedTypes, true)) {
    errorResponse('Invalid reassignment type.', 422, [
        'allowed_types' => $allowedTypes,
    ]);
}

$pdo = getDbConnection();
$sourceUser = requireExistingUser($pdo, $sourceUserId, 'Source user was not found.');
$targetUser = requireExistingUser($pdo, $targetUserId, 'Target user was not found.');

try {
    $pdo->beginTransaction();

    switch ($type) {
        case 'mentor_students':
            ensureUserRoleIn($sourceUser, ['mentor'], 'Source user must be a mentor.');
            ensureUserRoleIn($targetUser, ['mentor'], 'Target user must be a mentor.');
            ensureActiveUser($targetUser, 'Target mentor must be active.');
            $sql = 'UPDATE mentor_students SET mentor_id = :target WHERE mentor_id = :source';
            break;
        case 'iro_students':
            ensureUserRoleIn($sourceUser, ['iro'], 'Source user must be an IRO officer.');
            ensureUserRoleIn($targetUser, ['iro'], 'Target user must be an IRO officer.');
            ensureActiveUser($targetUser, 'Target IRO officer must be active.');
            $sql = 'UPDATE iro_students SET iro_id = :target WHERE iro_id = :source';
            break;
        case 'complaints':
            ensureUserRoleIn($sourceUser, ['admin', 'mentor', 'warden', 'iro'], 'Source user cannot own complaint assignments.');
            ensureUserRoleIn($targetUser, ['admin', 'mentor', 'warden', 'iro'], 'Target user cannot own complaint assignments.');
            ensureActiveUser($targetUser, 'Target complaint owner must be active.');

            if ($sourceUser['role'] !== $targetUser['role']) {
                errorResponse('Complaint responsibilities can only be moved to a user with the same role.', 422);
            }

            $sql = 'UPDATE complaints SET assigned_to = :target, updated_at = CURRENT_TIMESTAMP WHERE assigned_to = :source';
            break;
        case 'warden_hostel':
            ensureUserRoleIn($sourceUser, ['warden'], 'Source user must be a warden.');
            ensureUserRoleIn($targetUser, ['warden'], 'Target user must be a warden.');
            ensureActiveUser($targetUser, 'Target warden must be active.');

            $sourceHostelStatement = $pdo->prepare(
                'SELECT hostel_id
                 FROM hostel_wardens
                 WHERE warden_id = :warden_id'
            );
            $sourceHostelStatement->execute(['warden_id' => $sourceUserId]);
            $sourceHostels = $sourceHostelStatement->fetchAll(PDO::FETCH_COLUMN);

            if ($sourceHostels === []) {
                errorResponse('Source warden has no hostel assignment to move.', 404);
            }

            if (count($sourceHostels) > 1) {
                errorResponse('Source warden has multiple hostel assignments. Resolve the data inconsistency before reassignment.', 422);
            }

            $targetHostelStatement = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM hostel_wardens
                 WHERE warden_id = :warden_id'
            );
            $targetHostelStatement->execute(['warden_id' => $targetUserId]);
            if ((int) $targetHostelStatement->fetchColumn() > 0) {
                errorResponse('Target warden already has a hostel assignment.', 422);
            }

            $hostelId = (int) $sourceHostels[0];
            ensureWardenHostelAvailability($pdo, $hostelId, $sourceUserId);

            $sql = 'UPDATE hostel_wardens SET warden_id = :target WHERE warden_id = :source';
            break;
        default:
            throw new RuntimeException('Unsupported reassignment type.');
    }

    $statement = $pdo->prepare($sql);
    $statement->execute([
        'target' => $targetUserId,
        'source' => $sourceUserId,
    ]);

    $affected = $statement->rowCount();

    if ($type === 'warden_hostel' && $affected > 0) {
        $pdo->prepare('UPDATE users SET hostel_id = NULL WHERE id = :id')
            ->execute(['id' => $sourceUserId]);
        $pdo->prepare('UPDATE users SET hostel_id = :hostel_id WHERE id = :id')
            ->execute([
                'hostel_id' => $hostelId,
                'id' => $targetUserId,
            ]);
    }

    $pdo->commit();

    successResponse([
        'type' => $type,
        'moved_records' => $affected,
        'source_user_id' => $sourceUserId,
        'target_user_id' => $targetUserId,
    ], 'Responsibilities reassigned successfully.');
} catch (PDOException | RuntimeException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    errorResponse('Reassignment failed.', 400, buildExceptionErrors($exception));
}
