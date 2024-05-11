<?php

session_start();

if (isset($_POST['login'])) {
    if ($_POST['username'] === 'iwadmin' && $_POST['password'] === 'BeYpAzArI') {
        $_SESSION['logged_in'] = true;
    } 
}

if (!isset($_SESSION['logged_in'])) {
    echo '<form method="post">
        <input type="text" name="username" placeholder="Username">
        <input type="password" name="password" placeholder="Password">
        <input type="submit" name="login" value="Login">';
    exit;
}

require_once('_db.php');

function getUserChannelMsgCount($channelId, $userId=null) {
    global $pdo;
    if ($userId !== null) {
        $stmt = $pdo->prepare("SELECT msgcount FROM rawlogstats WHERE channel = :channel_id AND user = :user_id");
        $stmt->execute(['channel_id' => $channelId, 'user_id' => $userId]);
    } else {
        $stmt = $pdo->prepare("SELECT SUM(msgcount) FROM rawlogstats WHERE channel = :channel_id");
        $stmt->execute(['channel_id' => $channelId]);
    }
    return $stmt->fetchColumn();
}

$results = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
$users = [];
foreach ($results as $result) {
    $users[$result['user_id']] = json_decode($result['json'], true);
}

$results = $pdo->query("SELECT * FROM channels ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$channels = [];
$channelUsers = [];
foreach ($results as $result) {
    $channels[$result['channel_id']] = json_decode($result['json'], true);
    $channelUsers[$result['channel_id']] = [];
}

$results = $pdo->query("SELECT * FROM channel_user")->fetchAll(PDO::FETCH_ASSOC);
foreach ($results as $result) {
    if (!isset($channelUsers[$result['channel_id']]) || !is_array($channelUsers[$result['channel_id']])) {
        $channelUsers[$result['channel_id']] = [];
    }
    $channelUsers[$result['channel_id']][] = $result['user_id'];
}

$bgcolors = [
    '#FFFFAA',
    '#AAFFFF',    
];

?>

<table border="1">
    <tr>
        <td>Channel ID</td>
        <td>Channel Name</td>
        <td>Stats</td>
        <td>Real Name</td>
        <td>Username</td>
        <td>E-mail</td>
        <td>User ID</td>
    </tr>
    <?php foreach ($channels as $channelId => $channel) { ?>
        <tr style="background-color: <?= current($bgcolors) ?>">
            <td rowspan="<?= count($channelUsers[$channelId]) ?>"><?= htmlspecialchars($channelId) ?></td>
            <td rowspan="<?= count($channelUsers[$channelId]) ?>"><?= htmlspecialchars($channel['name']) ?></td>
            <td rowspan="<?= count($channelUsers[$channelId]) ?>"><?= count($channelUsers[$channelId]) ?> users<br><?= getUserChannelMsgCount($channelId) ?> messages</td>
            <?php foreach ($channelUsers[$channelId] as $userId) { ?>
                <td><?= htmlspecialchars($users[$userId]['real_name']) ?></td>
                <td><?= htmlspecialchars($users[$userId]['name']) ?></td>
                <td><?= htmlspecialchars($users[$userId]['profile']['email']) ?></td>
                <td><?= htmlspecialchars($userId) ?></td>
            </tr><tr style="background-color: <?= current($bgcolors) ?>">
            <?php } if (next($bgcolors) === false) reset($bgcolors); ?>
        </tr>
    <?php } ?>
</table>
<br>
<form method="post" action="iwacron.php">
    <input type="submit" name="update" value="Update">
</form>
<form method="post" action="addtochannel.php">
    <select name="channel_id">
        <?php foreach ($channels as $channelId => $channel) { ?>
            <option value="<?= $channelId ?>"><?= htmlspecialchars($channel['name']) ?></option>
        <?php } ?>
    </select>
    <select name="user_id">
        <?php foreach ($users as $userId => $user) { ?>
            <option value="<?= $userId ?>"><?= htmlspecialchars($user['real_name']) ?> (<?= htmlspecialchars($user['profile']['email']) ?>)</option>
        <?php } ?>
    </select>
    <input type="submit" name="update" value="Add To Channel">
</form>
