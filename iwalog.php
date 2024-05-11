<?php
session_start();
require_once('_slack.php');

$redirectUri = 'https://iwarden.iwaconcept.com/iwabot/iwalogin.php';
$loggedInUri = 'https://iwarden.iwaconcept.com/iwabot/iwalog.php';

if (isset($_GET['logout'])) {
    unset($_SESSION['logged_in']);
    session_destroy();
    header('Location: '.$redirectUri);
    exit;
}

if (!isset($_SESSION['logged_in'])) {
    header('Location: '.$redirectUri);
    exit;
} 


require_once '_db.php';
if ($_SESSION['user_info']['sub']==='U071R4SR7U0') {
    $sql = "SELECT c.channel_id, c.name FROM channels c ORDER BY name";
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute();
} else {
    $sql = "SELECT c.channel_id, c.name FROM channels c JOIN channel_user cu ON c.channel_id = cu.channel_id WHERE cu.user_id = ? ORDER BY name";
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute([$_SESSION['user_info']['sub']]);
}

$channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slack Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden; /* Prevent scrolling on the entire page */
        }
        .username {
            color: navy;
            font-weight: bold;
        }
        .row {
            flex-grow: 1; /* Allows the row to fill the available space */
            overflow: hidden; /* Prevent scrolling within the row */
        }
        .container-fluid {
            height: 100%; /* Make sure the outer container takes full height of the viewport */
            display: flex;
            flex-direction: column;
        }
        .channel-container {
            height: 100vh; /* 75% of the viewport height */
            overflow-y: auto; /* Add scrollbar if content overflows */
        }
        #message-container {
            height: 100vh; /* 75% of the viewport height */
            overflow-y: auto; /* Add scrollbar if content overflows */
        }
        #messagesDisplay {
            height: 100%;
        }
        #channelsList {
            height: 100%; /* Make the list take up the full height of its container */
        }        
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-2">
                <div class="channel-container">
                    <h3><?= $_SESSION['user_info']['name'] ?></h3>
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
                    <div id="messagesDisplay">
                        Messages will be displayed here
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
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
                    document.getElementById('messagesDisplay').innerHTML = html;
                })
                .catch(error => console.error('Error loading the messages:', error));
        }
    </script>
</body>
</html>

