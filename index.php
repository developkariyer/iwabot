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
    <div class="jumbotron m-5 p-5 text-center">
        <h1>IWA Bot'a hoş geldiniz, <span class="username"><?= $_SESSION['user_info']['name'] ?></span></h1>
        <p>Lütfen yapmak istediğiniz işlemi seçiniz.</p>
    </div>
    <div class="d-grid gap-2 m-3">
        <a href="wh.php" class="btn btn-primary btn-lg">Depo Yönetim</a>
        <a href="iwalog.php" class="btn btn-primary btn-lg">Kanal Arşivleri</a>
    </div>
    <div class="d-grid gap-2 m-3 mt-4">
        <a href="iwaaudiourl.php" class="btn btn-primary btn-lg">Audio URL Kütüphanesi</a>
        <a href="iwainfluencers.php" class="btn btn-primary btn-lg">Influencer Listesi</a>
    </div>
    <?php if (in_array($_SESSION['user_info']['sub'], $GLOBALS['slack']['admins'])): ?>
        <div class="d-grid gap-2 m-3 mt-4">
            <a href="iwachannels.php" class="btn btn-success btn-lg">Reload Channels</a>
            <a href="iwausers.php" class="btn btn-success btn-lg">Assing Users to Channels</a>
            <a href="iwauserinfo.php" class="btn btn-success btn-lg">Reload Users</a>
            <a href="iwaemoji.php" class="btn btn-success btn-lg">Reload Emojis</a>
        </div>
    <?php endif; ?>
    <div class="d-grid gap-2 m-3 mt-4">
        <a href="./?logout=1" class="btn btn-danger btn-lg">Logout</a>
    </div>
</div>

<?php

include '_footer.php';