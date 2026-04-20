<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/jsonResponse.php';
require_once __DIR__ . '/helpers/request.php';
require_once __DIR__ . '/helpers/token.php';
require_once __DIR__ . '/helpers/randomAssignment.php';
require_once __DIR__ . '/helpers/complaintActions.php';
require_once __DIR__ . '/helpers/adminUserManagement.php';

