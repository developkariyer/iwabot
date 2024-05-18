<?php

session_start();

if (!isset($guestFree) && !isset($_SESSION['logged_in'])) {
    $_SESSION['prev_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

$redirectUri = 'https://iwarden.iwaconcept.com/iwabot/login.php';
$loggedInUri = 'https://iwarden.iwaconcept.com/iwabot/';
