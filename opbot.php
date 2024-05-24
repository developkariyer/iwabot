<?php

require_once('_init.php');

$postData = file_get_contents('php://input');
$eventData = json_decode($postData, true);

file_put_contents('oplog.txt', $postData . PHP_EOL, FILE_APPEND);

$payload = json_decode($postData, true);

$workpackageId = $payload['work_package']['id'] ?? null;

if (empty($workpackageId)) exit;

$userEmail = $payload['work_package']['_embedded']['assignee']['email'] ?? null;

$stmt = $GLOBALS['pdo']->prepare("SELECT wp_id, user_id FROM op_workpackages WHERE wp_id = ?");
$stmt->execute([$workpackageId]);
$opWorkpackage = $stmt->fetch(PDO::FETCH_ASSOC);

$sql = empty($opWorkpackage) ? 
        "INSERT INTO op_workpackages (user_id, json, wp_id) VALUES (?, ?, ?)" : 
        "UPDATE op_workpackages SET user_id = ?, json = ? WHERE wp_id = ?";
$stmt = $GLOBALS['pdo']->prepare($sql);
$stmt->execute([$userEmail, $postData, $workpackageId]);

$userId = userEmailToId($userEmail);

/*
if (empty($userId) || $userEmail === $opWorkpackage['user_id']) {
    error_log('No need to send slack message: '.$userId . ' | '.$userEmail.' ? '.$opWorkpackage['user_id']);
    exit;
}
*/
if (empty($userId)) exit;

$url = 'https://op.iwaconcept.com/work_packages/' . $workpackageId;

$msg = "Komutan Logar! Bir cisim yaklaşıyor: $url";

$response = curlPost(
    'https://slack.com/api/chat.postMessage', 
    [
        'channel' => $userId,
        'text' => $msg,
    ]
);

$msg = "<$userId>! Bir cisim yaklaşıyor: $url";

$response = curlPost(
    'https://slack.com/api/chat.postMessage', 
    [
        'channel' => 'C072ZHN5YUV', //'C074PP75YM6',
        'text' => $msg,
    ]
);

file_put_contents('oplog.txt', PHP_EOL . json_encode($response) . PHP_EOL, FILE_APPEND);