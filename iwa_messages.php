<?php

require_once('_login.php');

if (!isset($_GET['channel_id'])) {
    echo "No channel selected...";
    exit;
}

require_once '_init.php';

if (in_array($_SESSION['user_info']['sub'], $GLOBALS['slack']['admins'])) {
    $sql = "SELECT c.channel_id as channel_id, c.name as channel_name FROM channels c WHERE c.channel_id = ? ORDER BY name LIMIT 1";
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute([$_GET['channel_id']]);
} else {
    $sql = "SELECT c.channel_id as channel_id, c.name as channel_name FROM channels c JOIN channel_user cu ON c.channel_id = cu.channel_id WHERE cu.user_id = ? AND c.channel_id = ? ORDER BY name LIMIT 1";
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute([$_SESSION['user_info']['sub'], $_GET['channel_id']]);
    }
$channel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$channel) {
    echo "Channel not found...";
    exit;
}

$sql = "SELECT json, json->>'$.event.event_ts' AS event_ts FROM rawlog WHERE json->>'$.event.channel' = :channel_id ORDER BY event_ts ASC";
$stmt = $GLOBALS['pdo']->prepare($sql);
$stmt->bindParam(':channel_id', $channel['channel_id']);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$messageArray = [];
foreach ($messages as $message) {
    $msg = json_decode($message['json'], true);
    if (isset($msg['event']['hidden'])) continue;
    if ($msg['event']['type'] !== 'message') continue;
    $subType = $msg['event']['subtype'] ?? '';
    switch ($subType) {
        case 'message_changed':
            $previous_ts = $msg['event']['previous_message']['event_ts'];
            $messageArray[$previous_ts] = $msg['event']['message'];
            break;
        case 'message_deleted':
            $deleted_ts = $msg['event']['deleted_ts'];
            if (isset($messageArray[$deleted_ts])) unset($messageArray[$deleted_ts]);
            break;
        default:
            $messageArray[$message['event_ts']] = $msg;
            break;
    }
}

$displayMessages = [];
foreach ($messageArray as $event_ts => $message) {
    $text = $message['event']['text'] ?? '';
    $userId = $message['event']['user'] ?? $message['event']['message']['user'] ?? '';
    $date = date('Y-m-d H:i:s', $event_ts);
    $files = $message['event']['files'] ?? [];

    $logItem = "<div class='col-auto'><div class='no-wrap'><span class='username'>".username($userId).":</span></div></div>".
                "<div class='col ps-0'><div>".slackize($text)."<br><small>($date)</small>";

    foreach ($files as $file) {
        $logItem .= previewFile($file).'<br>';
    }
    $logItem .="</div></div>";

    if (isset($message['event']['thread_ts'])) {
        if (!isset($displayMessages[$message['event']['thread_ts']]['thread'])) $displayMessages[$message['event']['thread_ts']]['thread'] = [];
        $displayMessages[$message['event']['thread_ts']]['thread'][$event_ts] = $logItem;
    } else {
        $displayMessages[$event_ts] = ['msg' => $logItem];
    }
}


echo "<h3>Messages for channel: {$channel['channel_name']}</h3>";
echo "<div style='width: 97%;'>";

$bgcolors = [
    '#e0e0e0',
    '#c0c0c0',
];

foreach ($displayMessages as $msg) {
    echo "<div class='row' style='background-color: ".current($bgcolors).";'>";
    echo $msg['msg'] ?? '';
    if (isset($msg['thread'])) {
        foreach ($msg['thread'] as $submsg) {
            echo "<div class='row ms-1 ps-5'>";
            echo $submsg;
            echo "</div>";
        }
    }
    echo "</div>";
    if (!next($bgcolors)) reset($bgcolors);
}
echo "</div>";
echo "End of messages";
