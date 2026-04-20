<?php
declare(strict_types=1);

function fetchComplaintById(PDO $pdo, int $complaintId): ?array
{
    $statement = $pdo->prepare(
        'SELECT c.*, cat.name AS category_name, cat.route_to, s.name AS student_name, s.email AS student_email
         FROM complaints c
         INNER JOIN categories cat ON cat.id = c.category_id
         INNER JOIN users s ON s.id = c.student_id
         WHERE c.id = :complaint_id
         LIMIT 1'
    );
    $statement->execute(['complaint_id' => $complaintId]);
    $complaint = $statement->fetch();

    return $complaint ?: null;
}

function addComplaintComment(PDO $pdo, int $complaintId, int $userId, string $message): void
{
    $statement = $pdo->prepare(
        'INSERT INTO complaint_comments (complaint_id, user_id, message)
         VALUES (:complaint_id, :user_id, :message)'
    );
    $statement->execute([
        'complaint_id' => $complaintId,
        'user_id' => $userId,
        'message' => $message,
    ]);
}

function updateComplaintForAssignee(PDO $pdo, int $complaintId, int $assigneeId, int $actorId, string $status, ?string $message = null): void
{
    $allowedStatuses = ['in_progress', 'resolved', 'closed'];

    if (!in_array($status, $allowedStatuses, true)) {
        errorResponse('Invalid complaint status.', 422, [
            'allowed_statuses' => $allowedStatuses,
        ]);
    }

    $statement = $pdo->prepare(
        'UPDATE complaints
         SET status = :status, updated_at = CURRENT_TIMESTAMP
         WHERE id = :complaint_id AND assigned_to = :assigned_to'
    );
    $statement->execute([
        'status' => $status,
        'complaint_id' => $complaintId,
        'assigned_to' => $assigneeId,
    ]);

    if ($statement->rowCount() === 0) {
        errorResponse('Complaint not found or not assigned to you.', 404);
    }

    if ($message !== null && trim($message) !== '') {
        addComplaintComment($pdo, $complaintId, $actorId, trim($message));
    }
}

function createStaffAccount(PDO $pdo, array $payload, string $role): int
{
    requireFields($payload, ['name', 'email', 'password', 'phone']);

    if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
        errorResponse('A valid email address is required.', 422);
    }

    $statement = $pdo->prepare(
        'INSERT INTO users (name, email, password, role, roll_number, gender, phone, hostel_id, status)
         VALUES (:name, :email, :password, :role, :roll_number, :gender, :phone, :hostel_id, :status)'
    );
    $statement->execute([
        'name' => trim($payload['name']),
        'email' => strtolower(trim($payload['email'])),
        'password' => password_hash((string) $payload['password'], PASSWORD_DEFAULT),
        'role' => $role,
        'roll_number' => $payload['roll_number'] ?? null,
        'gender' => $payload['gender'] ?? null,
        'phone' => $payload['phone'],
        'hostel_id' => $payload['hostel_id'] ?? null,
        'status' => 'active',
    ]);

    return (int) $pdo->lastInsertId();
}
