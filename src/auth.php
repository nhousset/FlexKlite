<?php
// auth.php
session_start();
$db_admin_file = __DIR__ . '/db/admin.json';

// Si l'utilisateur n'est pas authentifié dans sa session
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    // Et qu'il n'est pas déjà sur la page de login
    if (basename($_SERVER['PHP_SELF']) !== 'logon.php') {
        header('Location: logon.php');
        exit;
    }
}
?>
