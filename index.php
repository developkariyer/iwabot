<?php

require_once('_login.php');

if (isset($_GET['logout'])) {
    session_destroy();
    header ('Location: login.php');
    exit;
}

require_once('_init.php');

include '_header.php';

?>
<div class="container mt-5">
    <div class="jumbotron m-5 p-5">
        <center>
            <h1>IWA Bot'a hoş geldiniz, <span class="username"><?= $_SESSION['user_info']['name'] ?></span></h1>
            <p>Lütfen yapmak istediğiniz işlemi seçiniz.</p>
        </center>
    </div>
    <div class="row">
        <div class="col-4 d-flex justify-content-center">
        <div class="m-3">
                <a href="iwalog.php" class="btn btn-primary">Kanal Arşivleri</a>
            </div>
            <div class="m-3">
                <a href="wh.php" class="btn btn-primary">Depo Yönetim</a>
            </div>
        </div>
        <div class="col-4 d-flex flex-column    ">
            <div class="m-3">
                <center><a href="iwaaudiourl.php" class="btn btn-primary">Audio URL Kütüphanesi</a></center>
            </div>
            <div class="m-3">
                <center><a href="iwainfluencers.php" class="btn btn-primary">Influencer Listesi</a></center>
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
        <?php endif; ?>
            <div class="justify-content-center m-3">
                <center><a href="./?logout=1" class="btn btn-danger">Logout</a></center>
            </div>
        </div>
    </div>
</div>
<?php

include '_footer.php';