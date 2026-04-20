<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('GET');
requireRole(['admin']);

$pdo = getDbConnection();
[$page, $perPage, $offset] = getPaginationParams();

$search = trim((string) ($_GET['search'] ?? ''));
$studentType = trim((string) ($_GET['student_type'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

$params = [];
$where = " WHERE s.role IN ('national', 'international')";

if ($studentType !== '' && in_array($studentType, ['national', 'international'], true)) {
    $where .= ' AND s.role = :student_type';
    $params['student_type'] = $studentType;
}

if ($status !== '' && in_array($status, ['active', 'disabled'], true)) {
    $where .= ' AND s.status = :status';
    $params['status'] = $status;
}

$where .= buildSearchClause('student', $search, $params, [
    's.name',
    's.email',
    's.roll_number',
    'h.hostel_name',
]);

$countSql = 'SELECT COUNT(*)
             FROM users s
             LEFT JOIN hostels h ON h.id = s.hostel_id' . $where;
$total = countResultRows($pdo, $countSql, $params);

$sql = 'SELECT s.id,
               s.name,
               s.email,
               s.roll_number,
               s.role AS student_type,
               s.phone,
               s.status,
               s.hostel_id,
               h.hostel_name,
               mentor.name AS assigned_mentor,
               mentor.id AS assigned_mentor_id,
               iro.name AS assigned_iro,
               iro.id AS assigned_iro_id,
               warden.name AS assigned_warden,
               warden.id AS assigned_warden_id,
               COUNT(DISTINCT c.id) AS complaint_count
        FROM users s
        LEFT JOIN hostels h ON h.id = s.hostel_id
        LEFT JOIN mentor_students ms ON ms.student_id = s.id
        LEFT JOIN users mentor ON mentor.id = ms.mentor_id
        LEFT JOIN iro_students isr ON isr.student_id = s.id
        LEFT JOIN users iro ON iro.id = isr.iro_id
        LEFT JOIN hostel_wardens hw ON hw.hostel_id = s.hostel_id
        LEFT JOIN users warden ON warden.id = hw.warden_id
        LEFT JOIN complaints c ON c.student_id = s.id'
        . $where .
        ' GROUP BY s.id, s.name, s.email, s.roll_number, s.role, s.phone, s.status, s.hostel_id, h.hostel_name, mentor.name, mentor.id, iro.name, iro.id, warden.name, warden.id
          ORDER BY s.created_at DESC
          LIMIT :limit OFFSET :offset';

$statement = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $statement->bindValue(':' . $key, $value);
}
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$statement->execute();

successResponse([
    'items' => $statement->fetchAll(),
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => (int) ceil($total / $perPage),
    ],
], 'Students fetched successfully.');
