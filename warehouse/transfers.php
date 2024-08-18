<?php

require_once('warehouse.php');

$soldOrders = WarehouseSold::getSoldItems();

$slackUsers = slackUsers();

$offset = $_GET['offset'] ?? 0;
$logCount = WarehouseLogger::getLogCount();
$logStep = 20;


include '../_header.php';

?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>İşlem Kayıtları</h1>
        <p>İşlem kayıtlarını ve sipariş karşılanma durumunu görüntüleyin. Depo Ana Menü için <a href="./">buraya basınız.</a></p>

    </div>
    <div class="accordion mb-3" id="mainAccordion">

        <!-- Third Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain3">
                <button class="accordion-button bg-success text-white  w-100 py-3" data-bs-toggle="collapse" data-bs-target="#transfersAccordion3" aria-expanded="true" aria-controls="transfersAccordion3">
                    <span><strong>Sipariş Kayıtları Karşılanma Durumu</strong></span>
                </button>
            </h2>
            <div id="transfersAccordion3" class="accordion-collapse collapse show" aria-labelledby="headingMain3" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <div class="mb-3">
                        <table class="table table-striped-columns table-hover table-border">
                            <thead>
                                <tr class="table-dark">
                                    <th scope="col">#</th>
                                    <th scope="col">Sipariş</th>
                                    <th scope="col">Açıklama</th>
                                    <th scope="col">Sipariş Giriş</th>
                                    <th scope="col">Depo Çıkış</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($soldOrders as $index => $order): ?>
                                    <?php 
                                        $logFulfil = WarehouseLogger::findLog(['action'=>'fulfilSoldItem', 'sold_id' => $order->id]);
                                        $logAdd = WarehouseLogger::findLog(['action'=>'addSoldItem', 'sold_id' => $order->id]);
                                    ?>
                                    <tr class="<?= !$order->fulfilled_at ? 'bg-danger text-white' : '' ?>">
                                        <td>#<?= $order->id ?></td>
                                        <td><strong><?= $order->item_type === 'WarehouseProduct' ? 'Ürün' : 'Koli' ?></strong><br><?= htmlspecialchars($order->object->name) ?><br>(<?= $order->object instanceof WarehouseProduct ? htmlspecialchars($order->object->fnsku) : htmlspecialchars($order->object->parent->name) ?>)</td>
                                        <td><?= nl2br(htmlspecialchars($order->description)) ?></td>
                                        <td><strong><?= $logAdd ? $logAdd->username() : '' ?></strong><br><?= htmlspecialchars($order->created_at) ?></td>
                                        <td class="<?= !$order->fulfilled_at ? 'bg-danger text-white' : '' ?>"><strong><?= $logFulfil ? $logFulfil->username() : '' ?></strong><br><?= htmlspecialchars($order->fulfilled_at) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Log Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMainLogs">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#logsAccordion" aria-expanded="false" aria-controls="logsAccordion">
                    <span><strong>İşlem Kayıtları</strong></span>
                </button>
            </h2>
            <div id="logsAccordion" class="accordion-collapse collapse" aria-labelledby="headingMainLogs" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <div id="logTable">
                        <!-- Log content will be loaded here dynamically -->
                        <p>Loglar yükleniyor...</p>
                    </div>
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

document.addEventListener('DOMContentLoaded', function() {
    loadLogs(0);
});

function loadLogs(offset) {
    $.ajax({
        url: 'controller.php',
        type: 'POST', // Changed to POST
        data: { action: 'handle_log', offset: offset },
        success: function(response) {
            $('#logTable').html(response);
        },
        error: function(xhr, status, error) {
            console.error('Error loading logs:', error);
            $('#logTable').html('<p class="text-danger">Log yüklenirken hata oluştu.</p>');
        }
    });
}


</script>
<?php

include '../_footer.php';

?>
