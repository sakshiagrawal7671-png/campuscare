<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('GET');
requireRole(['admin']);

$pdo = getDbConnection();
[$page, $perPage, $offset] = getPaginationParams();

$search = trim((string) ($_GET['search'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

$params = [];
$where = " WHERE u.role = 'iro'";

if ($status !== '' && in_array($status, ['active', 'disabled'], true)) {
    $where .= ' AND u.status = :status';
    $params['status'] = $status;
}

$where .= buildSearchClause('iro', $search, $params, [
    'u.name',
    'u.email',
    'u.phone',
]);

$countSql = 'SELECT COUNT(*) FROM users u' . $where;
$total = countResultRows($pdo, $countSql, $params);

$sql = 'SELECT u.id,
               u.name,
               u.email,
               u.phone,
               u.status,
               COUNT(DISTINCT isr.student_id) AS assigned_international_students,
               COUNT(DISTINCT CASE WHEN c.status IN (\'submitted\', \'in_progress\', \'escalated\') THEN c.id END) AS active_complaints,
               COUNT(DISTINCT CASE WHEN c.status = \'resolved\' THEN c.id END) AS resolved_complaints,
               COUNT(DISTINCT CASE WHEN c.status = \'closed\' THEN c.id END) AS closed_complaints,
               COUNT(DISTINCT c.id) AS total_complaints
        FROM users u
        LEFT JOIN iro_students isr ON isr.iro_id = u.id
        LEFT JOIN complaints c ON c.assigned_to = u.id'
        . $where .
        ' GROUP BY u.id, u.name, u.email, u.phone, u.status
          ORDER BY u.created_at DESC
          LIMIT :limit OFFSET :offset';

$statement = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $statement->bindValue(':' . $key, $value);
}
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$statement->execute();
$items = $statement->fetchAll();

foreach ($items as &$item) {
    $item['resolution_rate'] = calculateResolutionRate(
        (int) $item['resolved_complaints'],
        (int) $item['closed_complaints'],
        (int) $item['total_complaints']
    );
    $item['workload_status'] = (int) $item['active_complaints'] >= 10 ? 'high' : 'normal';
}
unset($item);

successResponse([
    'items' => $items,
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => (int) ceil($total / $perPage),
    ],
], 'IRO officers fetched successfully.');

