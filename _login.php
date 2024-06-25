<?php
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_lifetime', 86400);
session_start();

if (!isset($guestFree) && !isset($_SESSION['logged_in'])) {
    $_SESSION['prev_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

$redirectUri = 'https://iwarden.iwaconcept.com/iwabot/login.php';
$loggedInUri = 'https://iwarden.iwaconcept.com/iwabot/';
