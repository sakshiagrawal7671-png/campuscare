<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function requireRole(array $allowedRoles): array
{
    $user = requireAuth();

    if (!in_array($user['role'], $allowedRoles, true)) {
        // Log out or redirect to generic dashboard
        header('Location: /campuscare/campuscare-api/auth/login.php?error=AccessDenied');
        exit;
    }

    return $user;
}

