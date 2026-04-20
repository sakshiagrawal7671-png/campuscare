<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();
header('Location: /campuscare/campuscare-api/auth/login.php?error=LoggedOut');
exit;
