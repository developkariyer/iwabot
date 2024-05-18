<?php

require_once('_login.php');
require_once('_init.php');

$GLOBALS['pdo']->beginTransaction();

$stmt = $GLOBALS['pdo']->prepare('SELECT channel_id FROM channels');
$stmt->execute();
$channels = $stmt->fetchAll(PDO::FETCH_COLUMN);

$t = 0;
foreach ($channels as $channel) {
    $users = getUsersInChannel($channel);
    $t+=count($users);
    if (count($users)) {
        $GLOBALS['pdo']->prepare('DELETE FROM channel_user WHERE channel_id = ?')->execute([$channel]);
    }
    foreach ($users as $user) {
        $GLOBALS['pdo']->prepare('INSERT IGNORE INTO channel_user (channel_id, user_id) VALUES (?, ?)')->execute([$channel, $user]);
    }
}

$GLOBALS['pdo']->commit();

addMessage("$t user-channel relations updated.");

header('Location: ./');
