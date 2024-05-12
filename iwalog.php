<?php

require_once('_login.php');

if (isset($_GET['logout'])) {
    unset($_SESSION['logged_in']);
    session_destroy();
    header('Location: '.$redirectUri);
    exit;
}

require_once('_slack.php');
require_once '_db.php';

if (in_array($_SESSION['user_info']['sub'], $GLOBALS['slack']['admins'])) {
    $sql = "SELECT c.channel_id, c.name FROM channels c ORDER BY name";
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute();
} else {
    $sql = "SELECT c.channel_id, c.name FROM channels c JOIN channel_user cu ON c.channel_id = cu.channel_id WHERE cu.user_id = ? ORDER BY name";
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute([$_SESSION['user_info']['sub']]);
}

$channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '_header.php';
?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-2">
                <div class="channel-container">
                    <a href="./">Home Page</a>
                    <h3><?= $_SESSION['user_info']['name'] ?></a></h3>
                    <h4>Channels</h4>
                    <ul id="channelsList" class="list-group">
                        <?php foreach ($channels as $channel): ?>
                            <li class="list-group">
                                <a href="#" class="list-group-item messageload list-group-item-action" data-channel="<?php echo $channel['channel_id']; ?>"><?php echo $channel['name']; ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="col-10">
                <div id="message-container">
                    <div id="messagesDisplay" class="">
                        Select a channel to see logs...
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const links = document.querySelectorAll('.messageload');

            links.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault(); // Prevent the default anchor behavior

                    const channelId = this.getAttribute('data-channel');
                    fetchMessages(channelId);
                });
            });
        });

        function fetchMessages(channelId) {
            fetch('iwa_messages.php?channel_id=' + channelId)
                .then(response => response.text())
                .then(html => {
                    const messagesDisplay = document.getElementById('messagesDisplay');
                    messagesDisplay.innerHTML = html;
                    messagesDisplay.scrollTop = messagesDisplay.scrollHeight;
                    document.getElementById('message-container').scrollTop = document.getElementById('message-container').scrollHeight;
                })
                .catch(error => console.error('Error loading the messages:', error));
        }
    </script>
<?php
include '_footer.php';
