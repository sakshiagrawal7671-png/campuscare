<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

function requireAuth(): array
{
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        header('Location: /campuscare/campuscare-api/auth/login.php');
        exit;
    }

    $pdo = getDbConnection();
    $statement = $pdo->prepare(
        'SELECT id, name, email, role, roll_number, gender, phone, hostel_id, status, created_at
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => (int) $_SESSION['user']['id']]);
    $user = $statement->fetch();

    if (!$user) {
        session_destroy();
        header('Location: /campuscare/campuscare-api/auth/login.php?error=AccountNotFound');
        exit;
    }

    if (($user['status'] ?? 'active') !== 'active') {
        session_destroy();
        header('Location: /campuscare/campuscare-api/auth/login.php?error=AccountDisabled');
        exit;
    }

    return $user;
}
