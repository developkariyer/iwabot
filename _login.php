<?php
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_lifetime', 86400);
session_start();

$redirectUri = 'https://iwarden.iwaconcept.com/iwabot/login.php';
$loggedInUri = 'https://iwarden.iwaconcept.com/iwabot/';

$redirectUri1 = 'https://depo.iwa.web.tr/login.php';
$loggedInUri1 = 'https://depo.iwa.web.tr/';

$serverName = $_SERVER['HTTP_HOST'];

if (strpos($serverName, 'depo.iwa.web.tr') !== false) {
    error_log("Switching to depo.iwa.web.tr ($serverName)");
    $redirectUri = $redirectUri1;
    $loggedInUri = $loggedInUri1;
} else {
    error_log("Switching to iwarden.iwaconcept.com ($serverName)");
}

if (!isset($guestFree) && !isset($_SESSION['logged_in'])) {
    $_SESSION['prev_url'] = $_SERVER['REQUEST_URI'];
    header("Location: $redirectUri");
    exit;
}
