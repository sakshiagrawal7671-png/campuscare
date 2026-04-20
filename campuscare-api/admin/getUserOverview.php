<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('GET');
requireRole(['admin']);

$pdo = getDbConnection();

$roles = [
    'students' => ['national', 'international'],
    'mentors' => ['mentor'],
    'wardens' => ['warden'],
    'iro_officers' => ['iro'],
];

$overview = [];

foreach ($roles as $key => $roleSet) {
    $placeholders = implode(', ', array_fill(0, count($roleSet), '?'));

    $totalStatement = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role IN ($placeholders)");
    $totalStatement->execute($roleSet);
    $total = (int) $totalStatement->fetchColumn();

    $activeStatement = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role IN ($placeholders) AND status = 'active'");
    $activeStatement->execute($roleSet);
    $active = (int) $activeStatement->fetchColumn();

    $overview[$key] = [
        'total' => $total,
        'active' => $active,
        'disabled' => max(0, $total - $active),
    ];
}

successResponse([
    'overview' => $overview,
], 'User overview fetched successfully.');

