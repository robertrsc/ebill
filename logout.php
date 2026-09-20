<?php
/**
 * Encerramento de Sessão (Logout)
 * eBill Mini ERP Web
 */
require_once __DIR__ . '/config/db.php';

$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
set_flash_message('info', 'Você encerrou sua sessão no sistema.');
header("Location: login.php");
exit;
