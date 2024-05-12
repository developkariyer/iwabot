<?php

require_once('_login.php');
require_once('_slack.php');
require_once('_db.php');
require_once('_utils.php');

$GLOBALS['pdo']->beginTransaction();

$user_ids = $GLOBALS['pdo']->query('SELECT DISTINCT user_id FROM channel_user')->fetchAll(PDO::FETCH_COLUMN);
foreach ($user_ids as $user_id) {
    $user = getUserInfo($user_id);
    $stmt = $GLOBALS['pdo']->prepare('SELECT user_id FROM users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $userId = $stmt->fetchColumn();

    if ($userId) {
        $stmt = $GLOBALS['pdo']->prepare('UPDATE users SET json = ?, updated_at = NOW() WHERE user_id = ?');
        $stmt->execute([json_encode($user), $user_id]);
    } else {
        $stmt = $GLOBALS['pdo']->prepare('INSERT INTO users (user_id, json) VALUES (?, ?)');
        $stmt->execute([$user_id, json_encode($user)]);
    }
}

$GLOBALS['pdo']->commit();

addMessage(count($user_ids).' users updated.');

header('Location: ./');
