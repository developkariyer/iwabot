<?php

require_once('_login.php');
require_once('_slack.php');

include '_header.php';

?>
<div class="container mt-5">
    <div class="jumbotron m-5 p-5">
        <center>
            <h1>Welcome to IWA Bot, <span class="username"><?= $_SESSION['user_info']['name'] ?></span></h1>
            <p>Click the buttons below to see IWA Bot in action.</p>
        </center>
    </div>
    <div class="row">
        <div class="col-4 d-flex justify-content-center">
            <div class="m-3">
                <a href="iwalog.php" class="btn btn-primary">Show My Channels' Logs</a>
            </div>
        </div>
        <div class="col-4 d-flex justify-content-center">
            <div class="m-3">
                <a href="iwaudio.php" class="btn btn-primary">Browse Audio Library</a>
            </div>
        </div>
        <div class="col-4 d-flex flex-column justify-content-center">
        <?php if (in_array($_SESSION['user_info']['sub'], $GLOBALS['slack']['admins'])): ?>
            <div class="justify-content-center m-3">
                <center><a href="iwachannels.php" class="btn btn-success">Reload Channels</a></center>
            </div>
            <div class="justify-content-center m-3">
                <center><a href="iwausers.php" class="btn btn-success">Assing Users to Channels</a></center>
            </div>
            <div class="justify-content-center m-3">
                <center><a href="iwauserinfo.php" class="btn btn-success">Reload Users</a></center>
            </div>
            <div class="justify-content-center m-3">
                <center><a href="iwaemoji.php" class="btn btn-success">Reload Emojis</a></center>
            </div>
        <?php else: ?>
            <div class="justify-content-center m-3">
                <center><a href="#" class="btn btn-primary">Reserved for Future Actions</a></center>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>
<?php

include '_footer.php';