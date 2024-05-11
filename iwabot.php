<?php

require_once('_slack.php');

$postData = file_get_contents('php://input');
$eventData = json_decode($postData, true);

if (isset($eventData['type']) && $eventData['type'] === 'url_verification') {
    header('Content-Type: application/json');
    echo json_encode(['challenge' => $eventData['challenge']]);
    exit;
}

if (!isset($eventData['token']) || $eventData['token'] !== $verificationToken) {
    die('Unauthorized request');
}

require_once('_db.php');

$stmt = $pdo->prepare("INSERT INTO rawlog (json) VALUES (?)");
$stmt->execute([json_encode($eventData)]);

