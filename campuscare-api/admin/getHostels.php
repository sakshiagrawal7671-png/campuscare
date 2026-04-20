<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('GET');
requireRole(['admin']);

$pdo = getDbConnection();
$statement = $pdo->query('SELECT id, hostel_name FROM hostels ORDER BY hostel_name ASC');

successResponse([
    'items' => $statement->fetchAll(),
], 'Hostels fetched successfully.');

