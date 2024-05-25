<?php

require_once('_init.php');

$postData = file_get_contents('php://input');
$eventData = json_decode($postData, true);
$payload = json_decode($postData, true);

$workpackageId = $payload['work_package']['id'] ?? null;
if (empty($workpackageId)) exit;
$url = 'https://op.iwaconcept.com/work_packages/' . $workpackageId;

$userEmail = $payload['work_package']['_embedded']['assignee']['email'] ?? null;
$userId = userEmailToId($userEmail);

$workpackageName = $payload['work_package']['subject'] ?? null;

$stmt = $GLOBALS['pdo']->prepare("SELECT thread_ts, wp_id, user_id FROM op_workpackages WHERE wp_id = ?");
$stmt->execute([$workpackageId]);
$opWorkpackage = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($opWorkpackage)) {
    $sql = "INSERT INTO op_workpackages (user_id, json, thread_ts, wp_id) VALUES (?, ?, ?, ?)";
} else {
    $sql = "UPDATE op_workpackages SET user_id = ?, json = ?, thread_ts = ? WHERE wp_id = ?";
}
$stmt = $GLOBALS['pdo']->prepare($sql);
$stmt->execute([$userEmail, $postData, 0, $workpackageId]);

if (!empty($userId) && $userEmail !== $opWorkpackage['user_id']) {
    messageChannel($userId, "Komutan Logar! Bir cisim yaklaşıyor: <$url|{$workpackageName}> ({$workpackageId})");
}
