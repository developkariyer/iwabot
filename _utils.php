<?php

function slackUsername($text) {
    return preg_replace_callback('/<@(U[0-9A-Z]+)>/', function ($matches) {
        return '<span class="slack-user">@'.username($matches[1]).'</span>';
    }, $text);
}

function slackUrl($text) {
    /*return preg_replace_callback(
        '/<(http[^|]*)\|([^>]+)>/',
        function ($matches) {
            // $matches[1] is the URL, $matches[2] is the link text
            return "<a href='{$matches[1]}' target='_blank'>{$matches[2]}</a>";
        },
        $text
    );*/

    return preg_replace_callback("/<https?:\/\/[^\s]+>/", function($matches) {
        $url = trim($matches[0], "<>");
        return '<a href="' . $url . '">' . $url . '</a>';
    }, $text);
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

function userEmailToId($email) {
    if (empty($email)) return null;
    $stmt = $GLOBALS['pdo']->prepare("SELECT user_id FROM users WHERE json->>'$.profile.email' = ?");
    $stmt->execute([$email]);
    return $stmt->fetchColumn();
}

function channelIdToName($channelId) {
    $stmt = $GLOBALS['pdo']->prepare('SELECT name FROM channels WHERE channel_id = ?');
    $stmt->execute([$channelId]);
    return $stmt->fetchColumn();
}

function channelNameToId($channelName) {
    $stmt = $GLOBALS['pdo']->prepare('SELECT channel_id FROM channels WHERE name = ?');
    $stmt->execute([$channelName]);
    return $stmt->fetchColumn();
}

function userInChannel($userId, $channelId) {
    $stmt = $GLOBALS['pdo']->prepare('SELECT * FROM channel_user WHERE user_id = ? AND channel_id = ?');
    $stmt->execute([$userId, $channelId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
}

function curlFileGet($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer '.$GLOBALS['slack']['botUserOAuthToken'],
    ));
    $response = curl_exec($ch);
    $curlError = curl_errno($ch);
    curl_close($ch);
    if ($curlError) {
        $error_msg = curl_error($ch);
        error_log("API error: $error_msg"); 
        return null;
    } else {
        return $response;
    }
}

function curlGet($url, $data) {
    $fullUrl = empty($data) ? $url : $url.'?'.http_build_query($data);
    $ch = curl_init($fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Bearer '.$GLOBALS['slack']['botUserOAuthToken'],
    ));
    $response = curl_exec($ch);
    $curlError = curl_errno($ch);
    curl_close($ch);
    if ($curlError) {
        $error_msg = curl_error($ch);
        error_log("API error: $error_msg"); 
        return [];
    } else {
        return json_decode($response, true);
    }
}

function curlPost($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Bearer '.$GLOBALS['slack']['botUserOAuthToken'],
    ));
    $response = curl_exec($ch);
    $curlError = curl_errno($ch);
    curl_close($ch);
    if ($curlError) {
        $error_msg = curl_error($ch);
        error_log("API error: $error_msg"); 
        return [];
    } else {
        return json_decode($response, true);
    }
}

function messageChannel($channelId, $msg, $thread_ts = null) {
    if ($thread_ts) {
        $payload = [
            'channel' => $channelId,
            'text' => $msg,
            'thread_ts' => $thread_ts,
        ];
    } else {
        $payload = [
            'channel' => $channelId,
            'text' => $msg,
        ];
    }
    $response = curlPost(
        'https://slack.com/api/chat.postMessage', 
        $payload
    );
    return $response['ts'] ?? null;
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

function canViewPage($appName, $userId=null) {
    $userId = $userId ?? $_SESSION['user_id'];
    if (isset($GLOBALS['slack'][$appName])) {
        if (in_array($userId, $GLOBALS['slack']['admins'])) {
            addMessage('Admin olduğunuz için bu sayfaya erişiminiz var.');
            return true;
        }
        foreach ($GLOBALS['slack'][$appName] as $channelId) {
            if (userInChannel($userId, $channelId)) {
                addMessage("<b>".channelIdToName($channelId).'</b> kanalında olduğunuz için bu sayfaya erişiminiz var.');
                return true;
            }
        }
    }
    return false;
}

function previewFile($file) {
    $id = htmlentities($file['id']);
    if (!file_exists('downloads/'.$file['id'].'_'.$file['name'])) {
        $stmt = $GLOBALS['pdo']->prepare("INSERT IGNORE INTO files (file_id, json) VALUES (?,?)");
        $stmt->execute([$file['id'], json_encode($file)]);
    }
    $thumbTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (in_array($file['mimetype'], $thumbTypes)) {
        return "<a href='iwa_file.php?id=$id' target='_blank'><img src='iwa_file.php?id=$id&thumb=1' alt='{$file['name']}' style='max-width: 360px; max-height: 360px;'></a>";
    }
    return "<a href='iwa_file.php?id=$id' target='_blank'>{$file['name']}</a>";
}

function openProjectApiGet($href, $params = []) {
    return openProjectApiCall($href, $params);
}

function openProjectApiCall($href, $params = []) {
    $url = "https://op.iwaconcept.com$href";
    if (!empty($params)) {
        $url .= '?'.http_build_query($params);
    }
    $apiKey ='a27fb9d4540f3bd402b6263e494668a5925f1f31615a4e434bb3232c25971bb7';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "apikey:$apiKey");
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('cURL error: ' . curl_error($ch));
        return false;
    }    
    curl_close($ch);
    return $response;
}

function openProjectApiPost($href, $payload) {
    // change content-type to application/json
    $url = "https://op.iwaconcept.com$href";
    $apiKey ='a27fb9d4540f3bd402b6263e494668a5925f1f31615a4e434bb3232c25971bb7';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "apikey:$apiKey");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('cURL error: ' . curl_error($ch));
        return false;
    }
    curl_close($ch);
    return $response;
}

function openProjectUpdateUsers() {
    if (isset($GLOBALS['opUsers'])) return;
    $GLOBALS['pdo']->query('DELETE FROM op_users');
    $GLOBALS['pdo']->beginTransaction();
    $response = openProjectApiGet('/api/v3/users', ['pageSize' => 200]);
    $response = json_decode($response, true);
    $GLOBALS['opUsers'] = [];
    foreach ($response['_embedded']['elements'] as $user) {
        $stmt = $GLOBALS['pdo']->prepare('INSERT INTO op_users (json) VALUES (?)');
        $stmt->execute([json_encode($user)]);
        $GLOBALS['opUsers'][$user['id']] = $user;
    }    
    $GLOBALS['pdo']->commit();
}

function openProjectUpdateGroups() {
    if (isset($GLOBALS['opGroups'])) return;
    $GLOBALS['pdo']->query('DELETE FROM op_groups');
    $GLOBALS['pdo']->beginTransaction();
    $response = openProjectApiGet('/api/v3/groups', ['pageSize' => 200]);
    $response = json_decode($response, true);
    $GLOBALS['opGroups'] = [];
    foreach ($response['_embedded']['elements'] as $group) {
        $stmt = $GLOBALS['pdo']->prepare('INSERT INTO op_groups (json) VALUES (?)');
        $stmt->execute([json_encode($group)]);
        $GLOBALS['opGroups'][$group['id']] = $group;
    }    
    $GLOBALS['pdo']->commit();
}

function openProjectUpdateWorkPackages() {
    $GLOBALS['pdo']->query('DELETE FROM op_workpackages');
    $GLOBALS['pdo']->beginTransaction();
    $response = openProjectApiGet('/api/v3/work_packages', ['pageSize' => 200]);
    $response = json_decode($response, true);
    foreach ($response['_embedded']['elements'] as $workpackage) {
        $response = openProjectApiGet('/api/v3/work_packages/'.$workpackage['id']);
        $stmt = $GLOBALS['pdo']->prepare('INSERT INTO op_workpackages (json) VALUES (?)');
        $stmt->execute([$response]);
    }
    $GLOBALS['pdo']->commit();
}