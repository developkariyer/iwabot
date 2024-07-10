<?php

require_once('warehouse.php');

$soldOrders = WarehouseSold::getSoldItems(fulfilled: true);

$slackUsers = slackUsers();

function aciklama($log)
{
    switch($log->action) {
        case 'placeInContainer':
            $container_id = $log->data['container_id'];
            if ($container = WarehouseContainer::getById($container_id)) {
                $container_name = $container->name;
                $container_type = $container->type === 'Raf' ? 'rafına' : 'kolisine';
            } else {
                $container_name = 'Bilinmeyen';
                $container_type = 'yerine';
            }
            return "{$log->data['count']} adet \"{$log->object->name}\" \"{$container_name}\" $container_type yerleştirildi";
        case 'removeFromContainer':
            $container_id = $log->data['container_id'];
            if ($container = WarehouseContainer::getById($container_id)) {
                $container_name = $container->name;
                $container_type = $container->type === 'Raf' ? 'rafından' : 'kolisinden';
            } else {
                $container_name = 'Bilinmeyen';
                $container_type = 'yerine';
            }
            return "{$log->data['count']} adet \"{$log->object->name}\" \"{$container_name}\" $container_type alındı";
        case 'setParent':
            $newContainer = WarehouseContainer::getById($log->data['new_parent_id']);
            $oldContainer = WarehouseContainer::getById($log->data['old_parent_id']);
            $newContainerName = $newContainer ? $newContainer->name : 'Bilinmeyen';
            $oldContainerName = $oldContainer ? $oldContainer->name : 'Bilinmeyen';
            return "\"{$log->object->name}\" kolisi \"{$oldContainerName}\" rafından \"{$newContainerName}\" rafına taşındı";
        case 'fulfilSoldItem':
            return 'Sipariş karşılanma';
        case 'addSoldItem':
            return 'Sipariş oluşturma';
        case 'addNew':
            if ($log->object) {
                return (get_class($log->object) === 'WarehouseProduct') ? "\"{$log->object->name}\" ürünü eklendi" : "\"{$log->object->name}\" kolisi eklendi";
            }
            return 'Yeni ürün/koli eklendi';
        default:
            return 'Bilinmeyen işlem';
    }
}

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
                    <table class="table table-striped table-hover table-sm">
                        <thead>
                            <tr>
                                <th scope="col">İşlem</th>
                                <th scope="col">Açıklama</th>
                                <th scope="col">Kullanıcı</th>
                                <th scope="col">Zaman</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($logs = WarehouseLogger::findLogs([], 50)): ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log->action) ?></td>
                                        <td><?= htmlspecialchars(aciklama($log)) ?></td>
                                        <td><?= htmlspecialchars($log->username()) ?></td>
                                        <td><?= htmlspecialchars($log->created_at) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">İşlem kaydı bulunmamaktadır.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
                    <?= productSelect() ?>
                    <div id="selectedProduct" class="d-none">
                        <h4 class="pt-3">Ürün Bilgileri</h4>
                        <div class="p-3" id="product_info"></div>
                        <h4>Ürün Hareketleri</h4>
                        <div class="p-3" id="product_log"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fourth Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain4">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#transfersAccordion4" aria-expanded="false" aria-controls="transfersAccordion4">
                    <span><strong>Koli Bazlı İşlem Kayıtları</strong></span>
                </button>
            </h2>
            <div id="transfersAccordion4" class="accordion-collapse collapse" aria-labelledby="headingMain4" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
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
                                    <td><strong><?= $logAdd ? $logAdd->username() : '' ?></strong><br><?= htmlspecialchars($order->created_at) ?></td>
                                    <td><strong><?= $logFulfil ? $logFulfil->username() : '' ?></strong><br><?= htmlspecialchars($order->fulfilled_at) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($soldOrders)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">Sipariş kaydı bulunmamaktadır.</td>
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

<script defer>
$(document).ready(function() {
    $('#product_select').on('change', function() {
        var productId = $(this).val();
        if (productId) {
            $.ajax({
                url: 'controller.php',
                method: 'POST',
                data: { product_id: productId , action: 'product_log', csrf_token: '<?= $_SESSION['csrf_token'] ?>'},
                success: function(response) {
                    $('#product_info').html(response.info);

                    var logTable = '<table class="table table-striped table-sm table-hover">';
                    logTable += '<thead><tr><th>İşlem</th><th>Açıklama</th><th>Kullanıcı</th><th>Zaman</th></tr></thead>';
                    logTable += '<tbody>';
                    response.log.forEach(function(log) {
                        logTable += '<tr>';
                        logTable += '<td>' + log[0] + '</td>';
                        logTable += '<td>' + log[1] + '</td>';
                        logTable += '<td>' + log[2] + '</td>';
                        logTable += '<td>' + log[3] + '</td>';
                        logTable += '</tr>';
                    });
                    logTable += '</tbody></table>';

                    $('#product_log').html(logTable);
                    $('#selectedProduct').removeClass('d-none');
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching product information:', error);
                }
            });
        } else {
            $('#selectedProduct').addClass('d-none');
        }
    });
});


</script>
<?php

include '../_footer.php';

?>
