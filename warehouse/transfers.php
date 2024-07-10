<?php

require_once('warehouse.php');

$soldOrders = WarehouseSold::getSoldItems(fulfilled: true);

$slackUsers = slackUsers();

include '../_header.php';

?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>İşlem Kayıtları</h1>
        <p>İşlem kayıtlarını ve sipariş karşılanma durumunu görüntüleyin. Depo Ana Menü için <a href="./">buraya basınız.</a></p>
    </div>
    <div class="accordion mb-3" id="mainAccordion">

        <!-- First Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain1">
                <button class="accordion-button bg-success text-white w-100 py-3" data-bs-toggle="collapse" data-bs-target="#transfersAccordion1" aria-expanded="true" aria-controls="transfersAccordion1">
                    <span><strong>Kronolojik İşlem Kayıtları</strong></span>
                </button>
            </h2>
            <div id="transfersAccordion1" class="accordion-collapse collapse show" aria-labelledby="headingMain1" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <p>Placeholder text for Kronolojik İşlem Kayıtları</p>
                </div>
            </div>
        </div>

        <!-- Second Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain2">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#transfersAccordion2" aria-expanded="false" aria-controls="transfersAccordion2">
                    <span><strong>Ürün Bazlı İşlem Kayıtları</strong></span>
                </button>
            </h2>
            <div id="transfersAccordion2" class="accordion-collapse collapse" aria-labelledby="headingMain2" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <p>Placeholder text for Ürün Bazlı İşlem Kayıtları</p>
                </div>
            </div>
        </div>

        <!-- Third Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain3">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#transfersAccordion3" aria-expanded="false" aria-controls="transfersAccordion3">
                    <span><strong>Sipariş Kayıtları Karşılanma Durumu</strong></span>
                </button>
            </h2>
            <div id="transfersAccordion3" class="accordion-collapse collapse" aria-labelledby="headingMain3" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <table class="table table-striped table-hover table-sm">
                        <thead>
                            <tr>
                                <th scope="col">Sipariş</th>
                                <th scope="col">Açıklama</th>
                                <th scope="col">Kayıt</th>
                                <th scope="col">Kapatma</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($soldOrders as $index => $order): ?>
                                <?php 
                                    $logFulfil = WarehouseLogger::findLog(['action'=>'fulfilSoldItem', 'sold_id' => $order->id]);
                                    $logAdd = WarehouseLogger::findLog(['action'=>'addSoldItem', 'sold_id' => $order->id]);
                                ?>
                                <tr class="<?= empty($order->fulfilled_at) ? 'table-danger' : 'table-success' ?>">
                                    <td><strong><?= $order->item_type === 'WarehouseProduct' ? 'Ürün' : 'Koli' ?></strong><br><?= htmlspecialchars($order->object->name) ?></td>
                                    <td><?= nl2br(htmlspecialchars($order->description)) ?></td>
                                    <td><?= $logAdd ? $logAdd->username() : '' ?><br><?= htmlspecialchars($order->created_at) ?></td>
                                    <td><?= $logFulfil ? $logFulfil->username() : '' ?><br><?= htmlspecialchars($order->fulfilled_at) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($soldOrders)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Sipariş kaydı bulunmamaktadır.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>



    </div>

    <hr>

    <?= wh_menu() ?>
</div>

<?php

include '../_footer.php';

?>
