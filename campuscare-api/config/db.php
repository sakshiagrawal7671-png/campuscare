<?php
declare(strict_types=1);

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('CAMPUSCARE_DB_HOST') ?: '127.0.0.1';
    $dbName = getenv('CAMPUSCARE_DB_NAME') ?: 'campuscare';
    $username = getenv('CAMPUSCARE_DB_USER') ?: 'root';
    $password = getenv('CAMPUSCARE_DB_PASS') ?: '';
    $charset = 'utf8mb4';

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $host, $dbName, $charset);

    try {
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        errorResponse('Database connection failed.', 500);
    }

    return $pdo;
}
