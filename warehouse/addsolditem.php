<?php

require_once('warehouse.php');

include '../_header.php';

?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>Yeni Sipariş Çıkışı</h1>
        <p>Lütfen tüm bilgileri eksiksiz doldurun. Bir defada tek ürün çıkışı yapılabilir.</p>
    </div>

    <form action = "controller.php" method = "post">
        <input type="hidden" name="action" value="add_sold_item">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <?= productSelect() ?>
        <input type="text" name="description" class="form-control btn-outline-success rounded-pill w-100 py-3 mt-2" placeholder="Açıklama" required>
        <button id="submitbutton" type="submit" class="btn btn-success btn-lg rounded-pill w-100 py-3 mt-2">Yeni Çıkış Kaydı Ekle</button>
    </form>

    <?= wh_menu() ?>
</div>

<?php

include '../_footer.php';