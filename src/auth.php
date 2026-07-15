<?php
session_start();

// Le statut de connexion administrateur (Droits d'écriture)
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Le statut d'accès en lecture (Autorisé si admin OU si connecté en visiteur)
$has_read_access = $is_logged_in || (isset($_SESSION['readonly_logged_in']) && $_SESSION['readonly_logged_in'] === true);
?>
