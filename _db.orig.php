<?php
$dbHost = 'localhost';
$dbName = 'dbname';
$dbUser = 'dbuser';
$dbPassword = 'dbpassword';

try {
    $GLOBALS['pdo'] = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPassword);
    $GLOBALS['pdo']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode for better debugging
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

