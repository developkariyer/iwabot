<?php

require_once('warehouse.php');

$client = new Predis\Client();
$client->set('foo', 'bar');
$value = $client->get('foo');

include '../_header.php';

?>
<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>IWA Depo Yönetim</h1>
        <p><span class="username"><?= $_SESSION['user_info']['name'] ?></span></p>
        <p>Lütfen yapmak istediğiniz işlemi seçiniz.</p>
    </div>

    <?= wh_menu() ?>
</div>

<?php

include '../_footer.php';
