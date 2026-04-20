<?php
declare(strict_types=1);

function appDebugEnabled(): bool
{
    $debugValue = getenv('CAMPUSCARE_DEBUG');

    if ($debugValue === false || $debugValue === '') {
        return false;
    }

    return filter_var($debugValue, FILTER_VALIDATE_BOOL) === true;
}

function buildExceptionErrors(Throwable $exception): array
{
    if (!appDebugEnabled()) {
        return [];
    }

    return [
        'details' => $exception->getMessage(),
    ];
}

function getPaginationParams(): array
{
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? max(1, min(50, (int) $_GET['per_page'])) : 10;
    $offset = ($page - 1) * $perPage;

    return [$page, $perPage, $offset];
}

function buildSearchClause(string $columnMapAlias, string $searchTerm, array &$params, array $columns): string
{
    if ($searchTerm === '') {
        return '';
    }

    $conditions = [];
    foreach ($columns as $index => $column) {
        $key = $columnMapAlias . '_search_' . $index;
        $conditions[] = sprintf('%s LIKE :%s', $column, $key);
        $params[$key] = '%' . $searchTerm . '%';
    }

    return ' AND (' . implode(' OR ', $conditions) . ')';
}

function countResultRows(PDO $pdo, string $sql, array $params = []): int
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return (int) $statement->fetchColumn();
}

function calculateResolutionRate(int $resolvedCount, int $closedCount, int $totalCount): float
{
    if ($totalCount === 0) {
        return 0.0;
    }

    return round((($resolvedCount + $closedCount) / $totalCount) * 100, 2);
}

function isStudentRole(string $role): bool
{
    return in_array($role, ['national', 'international'], true);
}

function isStaffRole(string $role): bool
{
    return in_array($role, ['mentor', 'warden', 'iro'], true);
}

function validateRoleValue(string $role, bool $allowAdmin = false): void
{
    $allowedRoles = ['mentor', 'warden', 'iro', 'national', 'international'];

    if ($allowAdmin) {
        array_unshift($allowedRoles, 'admin');
    }

    if (!in_array($role, $allowedRoles, true)) {
        errorResponse('Invalid role value.', 422, [
            'allowed_roles' => $allowedRoles,
        ]);
    }
}

function fetchUserById(PDO $pdo, int $userId): ?array
{
    $statement = $pdo->prepare(
        'SELECT id, name, email, role, hostel_id, status
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch();

    return $user ?: null;
}

function requireExistingUser(PDO $pdo, int $userId, string $message = 'User not found.'): array
{
    $user = fetchUserById($pdo, $userId);

    if ($user === null) {
        errorResponse($message, 404);
    }

    return $user;
}

function requireHostel(PDO $pdo, int $hostelId): array
{
    $statement = $pdo->prepare(
        'SELECT id, hostel_name
         FROM hostels
         WHERE id = :hostel_id
         LIMIT 1'
    );
    $statement->execute(['hostel_id' => $hostelId]);
    $hostel = $statement->fetch();

    if (!$hostel) {
        errorResponse('Selected hostel was not found.', 422);
    }

    return $hostel;
}

function ensureActiveUser(array $user, string $message = 'Target user must be active for reassignment.'): void
{
    if (($user['status'] ?? 'disabled') !== 'active') {
        errorResponse($message, 422);
    }
}

function ensureUserRoleIn(array $user, array $allowedRoles, string $message = 'User has an invalid role for this action.'): void
{
    if (!in_array($user['role'], $allowedRoles, true)) {
        errorResponse($message, 422, [
            'allowed_roles' => $allowedRoles,
        ]);
    }
}

function ensureWardenHostelAvailability(PDO $pdo, int $hostelId, ?int $excludeWardenId = null): void
{
    $sql = 'SELECT warden_id
            FROM hostel_wardens
            WHERE hostel_id = :hostel_id';
    $params = [
        'hostel_id' => $hostelId,
    ];

    if ($excludeWardenId !== null) {
        $sql .= ' AND warden_id <> :exclude_warden_id';
        $params['exclude_warden_id'] = $excludeWardenId;
    }

    $sql .= ' LIMIT 1';

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    if ($statement->fetchColumn() !== false) {
        errorResponse('The selected hostel already has an assigned warden.', 422);
    }
}

function ensureComplaintAssigneeCompatibility(array $complaint, array $assignee): void
{
    $routeTo = (string) ($complaint['route_to'] ?? '');
    $allowedRolesByRoute = [
        'mentor' => ['mentor', 'admin'],
        'warden' => ['warden', 'admin'],
        'iro' => ['iro', 'admin'],
        'admin' => ['admin'],
    ];

    if (!isset($allowedRolesByRoute[$routeTo])) {
        errorResponse('Complaint route is invalid for reassignment.', 422);
    }

    ensureActiveUser($assignee, 'Complaint can only be reassigned to an active user.');

    if (!in_array($assignee['role'], $allowedRolesByRoute[$routeTo], true)) {
        errorResponse('Target assignee is not compatible with this complaint category.', 422, [
            'allowed_roles' => $allowedRolesByRoute[$routeTo],
            'route_to' => $routeTo,
        ]);
    }
}
