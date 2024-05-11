<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./');
    exit;
}

require_once('_slack.php');
require_once('_db.php');

function curlGet($url, $data) {
    global $botUserOAuthToken;
    $fullUrl = $url.'?'.http_build_query($data);
    $ch = curl_init($fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer '.$botUserOAuthToken
    ));
    $response = curl_exec($ch);
    $curlError = curl_errno($ch);
    curl_close($ch);
    if ($curlError) {
        $error_msg = curl_error($ch);
        echo "API error: $error_msg"; 
    } else {
        return json_decode($response, true);
    }
    return  [];
}

function getChannelList() {
    $response = curlGet(
        'https://slack.com/api/conversations.list',
        [
            'types' => 'public_channel,private_channel',
        ]
    );
    if (isset($response['channels']) && is_array($response['channels'])) {
        return $response['channels'];
    }
    return [];
}

function getUsersInChannel($channelId) {
    $response = curlGet(
        'https://slack.com/api/conversations.members',
        [
            'channel' => $channelId,
        ]
    );
    if (isset($response['members']) && is_array($response['members'])) {
        return $response['members'];
    }
    return [];
}

function getUserInfo($userId) {
    $response = curlGet(
        'https://slack.com/api/users.info',
        [
            'user' => $userId,
        ]
    );
    if (isset($response['user']) && is_array($response['user'])) {
        return $response['user'];
    }
    return [];
}

$pdo->beginTransaction();

$channels = getChannelList();

foreach ($channels as $channel) {
    $stmt = $pdo->prepare('SELECT channel_id FROM channels WHERE channel_id = ?');
    $stmt->execute([$channel['id']]);
    $channelId = $stmt->fetchColumn();

    if ($channelId) {
        $stmt = $pdo->prepare('UPDATE channels SET name = ?, json = ?, updated_at = NOW() WHERE channel_id = ?');
        $stmt->execute([$channel['name'], json_encode($channel), $channelId]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO channels (channel_id, name, json) VALUES (?, ?, ?)');
        $stmt->execute([$channel['id'], $channel['name'], json_encode($channel)]);
    }

    $users = getUsersInChannel($channel['id']);
    if (count($users)) {
        $pdo->prepare('DELETE FROM channel_user WHERE channel_id = ?')->execute([$channel['id']]);
    }
    foreach ($users as $user) {
        $pdo->prepare('INSERT IGNORE INTO channel_user (channel_id, user_id) VALUES (?, ?)')->execute([$channel['id'], $user]);
    }
}

$user_ids = $pdo->query('SELECT DISTINCT user_id FROM channel_user')->fetchAll(PDO::FETCH_COLUMN);
foreach ($user_ids as $user_id) {
    $user = getUserInfo($user_id);
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $userId = $stmt->fetchColumn();

    if ($userId) {
        $stmt = $pdo->prepare('UPDATE users SET json = ?, updated_at = NOW() WHERE user_id = ?');
        $stmt->execute([json_encode($user), $user_id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (user_id, json) VALUES (?, ?)');
        $stmt->execute([$user_id, json_encode($user)]);
    }
}

$pdo->commit();

header('Location: ./');
