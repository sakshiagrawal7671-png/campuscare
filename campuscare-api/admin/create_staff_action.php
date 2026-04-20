<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/roleGuard.php';

$user = requireRole(['admin']);
$pdo = getDbConnection();

// Simple POST handler for creating staff from modals
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';
    $phone    = trim($_POST['phone'] ?? '') ?: null;
    $hostelId = isset($_POST['hostel_id']) && $_POST['hostel_id'] !== '' ? (int)$_POST['hostel_id'] : null;

    if ($name && $email && strlen($password) >= 6 && in_array($role, ['mentor', 'warden', 'iro'], true)) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :e');
        $check->execute(['e' => $email]);
        if (!$check->fetch()) {
            $ins = $pdo->prepare(
                'INSERT INTO users (name, email, password, role, phone, hostel_id, status)
                 VALUES (:n, :e, :p, :r, :ph, :h, "active")'
            );
            $ins->execute([
                'n'  => $name,
                'e'  => $email,
                'p'  => password_hash($password, PASSWORD_ARGON2ID),
                'r'  => $role,
                'ph' => $phone,
                'h'  => ($role === 'warden') ? $hostelId : null,
            ]);
            // Insert warden assignment
            if ($role === 'warden' && $hostelId) {
                $wardId = (int)$pdo->lastInsertId();
                try {
                    $pdo->prepare('INSERT INTO hostel_wardens (hostel_id, warden_id) VALUES (:h, :w)')
                        ->execute(['h' => $hostelId, 'w' => $wardId]);
                } catch (\Exception $e) {}
            }
        }
    }
    header('Location: /campuscare/campuscare-api/admin/dashboard.php');
    exit;
}
header('Location: /campuscare/campuscare-api/admin/dashboard.php');
exit;
