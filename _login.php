<?php
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_lifetime', 86400);
session_start();

$redirectUri = 'https://iwarden.iwaconcept.com/iwabot/login.php';
$loggedInUri = 'https://iwarden.iwaconcept.com/iwabot/';

$redirectUri1 = 'https://depo.iwa.web.tr/login.php';
$loggedInUri1 = 'https://depo.iwa.web.tr/';

if (strpos($_SERVER['REQUEST_URI'], 'depo.iwa.web.tr') !== false) {
    $redirectUri = $redirectUri1;
    $loggedInUri = $loggedInUri1;
}

if (!isset($guestFree) && !isset($_SESSION['logged_in'])) {
    $_SESSION['prev_url'] = $_SERVER['REQUEST_URI'];
    header("Location: $redirectUri");
    exit;
}
