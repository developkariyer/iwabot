<?php

session_start();

if (!isset($guestFree) && !isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$redirectUri = 'https://iwarden.iwaconcept.com/iwabot/login.php';
$loggedInUri = 'https://iwarden.iwaconcept.com/iwabot/';
