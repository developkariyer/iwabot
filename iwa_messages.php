<?php

require_once('_login.php');

if (!isset($_GET['channel_id'])) {
    echo "No channel selected...";
    exit;
}

require_once '_db.php';
require_once '_slack.php';
require_once '_emoji.php';
require_once '_utils.php';

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

$sql = "SELECT id, json FROM rawlog WHERE json->>'$.event.channel' = :channel_id ORDER BY id ASC";
$stmt = $GLOBALS['pdo']->prepare($sql);
$stmt->bindParam(':channel_id', $channel['channel_id']);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$displayMessages = [];

foreach ($messages as $message) {
    $msg = json_decode($message['json'], true);

    if (isset($msg['event']['hidden'])) continue;

    $text = isset($msg['event']['text']) ?  : '';

    $userId = $msg['event']['user'] ?? $msg['event']['previous_message']['user'] ?? $msg['event']['message']['user'] ?? '';
    $date = date('Y-m-d H:i:s', $msg['event_time']);
    $event_ts = $msg['event']['event_ts'];
    $eventType = $msg['event']['type'];
    $eventSubType = $msg['event']['subtype'] ?? '';

    switch ($eventType) {
        case 'message':
            if ($eventSubType === 'message_deleted') {
                $logItem = "</i>Message has been deleted.</i>";
                $event_ts = $msg['event']['deleted_ts'];
                break;
            }
            if ($eventSubType === 'message_changed') {
                $logItem = slackize($msg['event']['message']['text'])." (edited)";
                break;
            }
            $logItem = slackize($msg['event']['text']);
            break;
        case 'member_joined_channel':
            $logItem = "<i>User has joined channel</i>";
            break;
        default:
            $logItem = $msg['event']['type'];
            break;
    }
    $logItem = "<div class='col-auto'>
                    <div class='no-wrap'>   
                        <span class='username'>".username($userId).":</span>
                    </div>
                </div>
                <div class='col ps-0'>
                    <div>
                        $logItem <small>[$date/{$message['id']}]</small>
                    </div>
                </div>";

    if (isset($msg['event']['thread_ts'])) {
        if (!isset($displayMessages[$msg['event']['thread_ts']]['thread'])) $displayMessages[$msg['event']['thread_ts']]['thread'] = [];
        $displayMessages[$msg['event']['thread_ts']]['thread'][$event_ts] = $logItem;
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
    echo $msg['msg'];
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
