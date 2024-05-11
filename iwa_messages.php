<?php

if (!isset($_GET['channel_id'])) {
    echo "No channel selected...";
    exit;
}

session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: https://iwarden.iwaconcept.com/iwabot/iwalogin.php');
    exit;
}

require_once '_db.php';

if ($_SESSION['user_info']['sub']==='U071R4SR7U0') {
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

$sql = "SELECT user_id, json->>'$.real_name' as name FROM users";
$stmt = $GLOBALS['pdo']->prepare($sql);
$stmt->execute();
$dbusers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$users = [];
foreach ($dbusers as $user) {
    $users[$user['user_id']] = $user['name'];
}

function slackUsername($text) {
    global $users;
    return preg_replace_callback('/<@(U[0-9A-Z]+)>/', function ($matches) use ($users) {
        $userId = $matches[1];
        if (isset($users[$userId])) {
            return '<b>'.$users[$userId].'</b>';
        }
        return $matches[0];
    }, $text);
}

$displayMessages = [];
$hashTags = [];

foreach ($messages as $message) {
    $msg = json_decode($message['json'], true);
    $text = isset($msg['event']['text']) ? slackUsername($msg['event']['text']) : '?';
    preg_match_all('/#(\w+)/', $text, $matches);
    foreach ($matches[1] as $tag) {
        $hashTags[$tag] = $tag;
    }

    $user = isset($msg['event']['user']) ? $users[$msg['event']['user']] : 'System';
    $date = date('Y-m-d H:i:s', $msg['event_time']);
    switch ($msg['event']['type']) {
        case 'message':
            if (isset($msg['event']['thread_ts'])) {
                if (!isset($displayMessages[$msg['event']['thread_ts']]['thread'])) $displayMessages[$msg['event']['thread_ts']]['thread'] = [];
                $displayMessages[$msg['event']['thread_ts']]['thread'][] = "$date <span class='username'>$user:</span> $text <small>{$message['id']}</small>";
            } else {
                $displayMessages[$msg['event']['event_ts']] = ['msg' => "$date <span class='username'>$user:</span> $text <small>{$message['id']}</small>"];
            }
            break;
        case 'message_changed':
            break;
        case 'message_deleted':
            break;
        case 'member_joined_channel':
            $displayMessages[$msg['event']['event_ts']] = ['msg' => "<b>$user</b> has joined channel"];
            break;
        default:
            break;
    }
    
}

echo "<h3>Messages for channel: {$channel['channel_name']}</h3>";
echo "<h4>Hash Tags</h4>";
foreach ($hashTags as $tag) {
    echo "<a href='#' class='btn btn-primary btn-sm messageload'>$tag</a> ";
}

echo "<ul>";
foreach ($displayMessages as $msg) {
    echo "<li>{$msg['msg']}</li>";
    if (isset($msg['thread'])) {
        echo '<ul>';
        foreach ($msg['thread'] as $submsg) {
            echo "<li>{$submsg}</li>";
        }
        echo '</ul>';
    }
}
echo "</ul>";
