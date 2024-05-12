<?php

require_once('_login.php');
require_once('_slack.php');
require_once('_db.php');
require_once('_utils.php');

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
}

$pdo->commit();

addMessage(count($channels).' channels updated.');

header('Location: ./');
