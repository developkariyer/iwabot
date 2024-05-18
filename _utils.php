<?php

function slackUsername($text) {
    return preg_replace_callback('/<@(U[0-9A-Z]+)>/', function ($matches) {
        return '<span class="slack-user">@'.username($matches[1]).'</span>';
    }, $text);
}

function slackUrl($text) {
    return preg_replace_callback(
        '/<(http[^|]*)\|([^>]+)>/',
        function ($matches) {
            // $matches[1] is the URL, $matches[2] is the link text
            return "<a href='{$matches[1]}' target='_blank'>{$matches[2]}</a>";
        },
        $text
    );
}

function slackTags($text) {
    return preg_replace_callback(
        '/<!([\w+-]*)>/',
        function ($matches) {
            return "<b>{$matches[1]}</b>";
        },
        $text
    );
}

function slacknl2br($text) {
    return preg_replace(['/(\n\n)/', '/\n/'], ['<br>', '<br>'], $text);
}

function slackEmoji($text) {
    return preg_replace_callback(
        '/:([\w+-]*):/',
        function ($matches) {
            return getEmoji($matches[1]);
        },
        $text
    );
}

function slackBold($text) {
    return preg_replace_callback(
        '/\*([^\*]*)\*/',
        function ($matches) {
            return "<b>{$matches[1]}</b>";
        },
        $text
    );

}

function slackize($text) {
    $text = slackUsername($text);
    $text = slackUrl($text);
    $text = slackTags($text);
    $text = slacknl2br($text);
    $text = slackBold($text);
    return slackEmoji($text);
}

function addMessage($message) {
    if (!isset($_SESSION['messages'])) {
        $_SESSION['messages'] = [];
    }
    $_SESSION['messages'][] = $message;
}


function username($userId) {
    if (!isset($GLOBALS['users'])) {
        require_once('_db.php');
        $sql = "SELECT user_id, json->>'$.real_name' as name FROM users";
        $stmt = $GLOBALS['pdo']->prepare($sql);
        $stmt->execute();
        $dbusers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $GLOBALS['users'] = [];
        foreach ($dbusers as $user) {
            $GLOBALS['users'][$user['user_id']] = $user['name'];
        }
    }
    return $GLOBALS['users'][$userId] ?? $userId;
}


function channelNameToId($channelName) {
    $stmt = $GLOBALS['pdo']->prepare('SELECT channel_id FROM channels WHERE name = ?');
    $stmt->execute([$channelName]);
    $channel = $stmt->fetch(PDO::FETCH_ASSOC);
    return $channel['channel_id'] ?? '';
}

function userInChannel($userId, $channelId) {
    $stmt = $GLOBALS['pdo']->prepare('SELECT * FROM channel_user WHERE user_id = ? AND channel_id = ?');
    $stmt->execute([$userId, $channelId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
}
