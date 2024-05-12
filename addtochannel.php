<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !isset($_POST['channel_id']) || !isset($_POST['user_id'])) {
    header('Location: ./');
    exit;
}

require_once('_slack.php');
require_once('_db.php');

function curlPost($url, $data) {
    global $botUserOAuthToken;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer '.$botUserOAuthToken
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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

function addUserToChannel($channelId, $userId) {
    global $lastError;
    $response = curlPost(
        'https://slack.com/api/conversations.invite',
        [
            'channel' => $channelId,
            'users' => $userId,
        ]
    );
    if (isset($response['ok']) && $response['ok']) {
        $lastError = null;
        return true;
    }
    $lastError = $response;
    return false;
}

$stmt = $GLOBALS['pdo']->prepare("SELECT channel_id FROM channels WHERE channel_id = ?");
$stmt->execute([$_POST['channel_id']]);
if (!$stmt->rowCount()) {
    header('Location: ./');
    exit;
}

$stmt = $GLOBALS['pdo']->prepare("SELECT user_id FROM users WHERE user_id = ?");
$stmt->execute([$_POST['user_id']]);
if (!$stmt->rowCount()) {
    header('Location: ./');
    exit;
}

if (addUserToChannel($_POST['channel_id'], $_POST['user_id'])) {
    header('Location: ./');
    exit;
}

echo "Channel ID: ".$_POST['channel_id']."<br>";
echo "User ID: ".$_POST['user_id']."<br>";
echo "<pre>";
print_r($lastError);
