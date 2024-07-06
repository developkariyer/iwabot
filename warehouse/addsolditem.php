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
        <select name="product_id" class="select2-select form-select btn-outline-success rounded-pill w-100 py-3" required>
            <option value="">Ürün Seçin</option>
            <?php foreach (WarehouseProduct::getAll() as $product): ?>
                <option value="<?= $product->id ?>"><?= $product->name ?> (<?= $product->fnsku ?>)</option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="description" class="form-control btn-outline-success rounded-pill w-100 py-3 mt-2" placeholder="Açıklama" required>
        <button id="submitbutton" type="submit" class="btn btn-success btn-lg rounded-pill w-100 py-3 mt-2">Yeni Çıkış Kaydı Ekle</button>
    </form>

    <?= wh_menu() ?>
</div>
<script>
    $(document).ready(function() {
        $('.select2-select').select2();
    });
</script>
<?php

include '../_footer.php';