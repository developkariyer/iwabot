<?php

require_once('_init.php');

$postData = file_get_contents('php://input');
$eventData = json_decode($postData, true);
$payload = json_decode($postData, true);

$workpackageId = $payload['work_package']['id'] ?? null;
if (empty($workpackageId)) exit;
$url = 'https://op.iwaconcept.com/work_packages/' . $workpackageId;

if  (isset($payload['work_package']['_embedded']['assignee']['_type']) && $payload['work_package']['_embedded']['assignee']['_type'] === 'Group') {
    $members = $payload['work_package']['_embedded']['assignee']['_links']['members'] ?? [];
    $userEmail = [];
    foreach ($members as $member) {
        $memberHref = openProjectApiCall($member['href']);
        $memberJson = json_decode($memberHref, true);        
        $email = $memberJson['email'] ?? null;
        $userEmail[$email] = userEmailToId($email);
    }
} else {
    $userEmail = $payload['work_package']['_embedded']['assignee']['email'] ?? null;
    $userId = userEmailToId($userEmail);
}

$workpackageName = $payload['work_package']['subject'] ?? null;


if (!is_array($userEmail) && !empty($userId)) {
    $stmt = $GLOBALS['pdo']->prepare('SELECT thread_ts FROM op_slack_threads WHERE wp_id = :workpackage_id AND slack_id = :slack_id');
    $stmt->execute(['workpackage_id' => $workpackageId, 'slack_id' => $userId]);
    $thread_ts = $stmt->fetchColumn();
    if (empty($thread_ts)) {
        $thread_ts = messageChannel($userId, "Sevgili <@$userId>, sizinle ilgili görevde bir gelişme var. Detaylarına şuradan bakabilirsiniz: <$url|{$workpackageName}> ({$workpackageId})");
        $stmt = $GLOBALS['pdo']->prepare('INSERT INTO op_slack_threads (wp_id, slack_id, thread_ts) VALUES (:workpackage_id, :slack_id, :thread_ts)');
        $stmt->execute(['workpackage_id' => $workpackageId, 'slack_id' => $userId, 'thread_ts' => $thread_ts]);
    } else {
        messageChannel($userId, "Sevgili <@$userId>, sizinle ilgili görevde bir gelişme var. Detaylarına şuradan bakabilirsiniz: <$url|{$workpackageName}> ({$workpackageId})", $thread_ts);
    }
}
