<?php
session_start();
// Le statut de connexion est vrai si la variable de session est définie
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
?>
