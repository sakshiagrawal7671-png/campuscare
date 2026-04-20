<?php
declare(strict_types=1);

function getRandomUserIdByRole(PDO $pdo, string $role): ?int
{
    $statement = $pdo->prepare(
        "SELECT id
         FROM users
         WHERE role = :role
           AND status = 'active'
         ORDER BY RAND()
         LIMIT 1"
    );
    $statement->execute(['role' => $role]);
    $userId = $statement->fetchColumn();

    return $userId === false ? null : (int) $userId;
}

function assignMentorToStudent(PDO $pdo, int $studentId): int
{
    $mentorId = getRandomUserIdByRole($pdo, 'mentor');

    if ($mentorId === null) {
        throw new RuntimeException('No mentor account is available for assignment.');
    }

    $statement = $pdo->prepare('INSERT INTO mentor_students (mentor_id, student_id) VALUES (:mentor_id, :student_id)');
    $statement->execute([
        'mentor_id' => $mentorId,
        'student_id' => $studentId,
    ]);

    return $mentorId;
}

function assignIroToStudent(PDO $pdo, int $studentId): int
{
    $iroId = getRandomUserIdByRole($pdo, 'iro');

    if ($iroId === null) {
        throw new RuntimeException('No IRO account is available for assignment.');
    }

    $statement = $pdo->prepare('INSERT INTO iro_students (iro_id, student_id) VALUES (:iro_id, :student_id)');
    $statement->execute([
        'iro_id' => $iroId,
        'student_id' => $studentId,
    ]);

    return $iroId;
}

function getStudentMentorId(PDO $pdo, int $studentId): ?int
{
    $statement = $pdo->prepare('SELECT mentor_id FROM mentor_students WHERE student_id = :student_id LIMIT 1');
    $statement->execute(['student_id' => $studentId]);
    $mentorId = $statement->fetchColumn();

    return $mentorId === false ? null : (int) $mentorId;
}

function getStudentIroId(PDO $pdo, int $studentId): ?int
{
    $statement = $pdo->prepare('SELECT iro_id FROM iro_students WHERE student_id = :student_id LIMIT 1');
    $statement->execute(['student_id' => $studentId]);
    $iroId = $statement->fetchColumn();

    return $iroId === false ? null : (int) $iroId;
}

function getStudentWardenId(PDO $pdo, int $studentId): ?int
{
    // Primary: find warden via hostel_wardens join on student's hostel_id
    $statement = $pdo->prepare(
        'SELECT hw.warden_id
         FROM users u
         INNER JOIN hostel_wardens hw ON hw.hostel_id = u.hostel_id
         INNER JOIN users w ON w.id = hw.warden_id
         WHERE u.id = :student_id
            AND w.status = :status
         ORDER BY hw.id ASC
         LIMIT 1'
    );
    $statement->execute([
        'student_id' => $studentId,
        'status' => 'active',
    ]);
    $wardenId = $statement->fetchColumn();

    if ($wardenId !== false) {
        return (int) $wardenId;
    }

    // Fallback: any active warden in ANY hostel (if hostel_wardens is empty)
    $fallback = $pdo->prepare(
        "SELECT id FROM users WHERE role = 'warden' AND status = 'active' ORDER BY RAND() LIMIT 1"
    );
    $fallback->execute();
    $anyWarden = $fallback->fetchColumn();

    return $anyWarden === false ? null : (int) $anyWarden;
}

function getAnyAdminId(PDO $pdo): ?int
{
    return getRandomUserIdByRole($pdo, 'admin');
}

function resolveComplaintAssignee(PDO $pdo, array $student, string $routeTo): int
{
    switch ($routeTo) {
        case 'mentor':
            $assigneeId = getStudentMentorId($pdo, (int) $student['id']);
            break;
        case 'warden':
            $assigneeId = getStudentWardenId($pdo, (int) $student['id']);
            // If no warden exists for this hostel, fall back to admin
            if ($assigneeId === null) {
                $assigneeId = getAnyAdminId($pdo);
            }
            break;
        case 'iro':
            if (($student['role'] ?? '') !== 'international') {
                throw new RuntimeException('IRO complaints are only available for international students.');
            }
            $assigneeId = getStudentIroId($pdo, (int) $student['id']);
            break;
        case 'admin':
            $assigneeId = getAnyAdminId($pdo);
            break;
        default:
            throw new RuntimeException('Unsupported complaint route.');
    }

    if ($assigneeId === null) {
        throw new RuntimeException('No assignee is available for the selected complaint category.');
    }

    return $assigneeId;
}
