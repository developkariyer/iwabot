<?php

require_once('_slack.php');

$postData = file_get_contents('php://input');
$eventData = json_decode($postData, true);

if (isset($eventData['type']) && $eventData['type'] === 'url_verification') {
    header('Content-Type: application/json');
    echo json_encode(['challenge' => $eventData['challenge']]);
    exit;
}

if (!isset($eventData['token']) || $eventData['token'] !== $GLOBALS['slack']['verificationToken']) {
    die('Unauthorized request');
}

require_once('_init.php');

if ($eventData['event']['type'] === 'app_home_opened') {
    curlPost('https://slack.com/api/views.publish', [
        'user_id' => $eventData['event']['user'],
        'view' => homeBlock($eventData['event']['user']),
    ]);
    exit;
}

$stmt = $GLOBALS['pdo']->prepare("INSERT INTO rawlog (json) VALUES (?)");
$stmt->execute([json_encode($eventData)]);

