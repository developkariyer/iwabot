<?php

require_once('warehouse.php');

if (!userCan(['view'])) {
    addMessage('Bu sayfaya erişim izniniz yok!', 'alert-danger');
    header('Location: ./');
    exit;
}

$icon = [
    'Gemi' => '🚢', //\u{1F6A2}
    'Raf' => '🗄️', // \u{1F5C4}
    'Koli' => '📦', //\u{1F4E6}
];

function rafContainers() {
    $containers = WarehouseContainer::getContainers('Raf');
    $retval = [];
    foreach ($containers as $container) {
        if (!isset($retval[substr($container->name, 0, 1)])) {
            $retval[substr($container->name, 0, 1)] = [];
        }
        $retval[substr($container->name, 0, 1)][] = $container;
    }
    return $retval;
}

include '../_header.php';

?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>Envanter Yönetimi</h1>
        <p>Depo envanterini görüntüleyin. Depo Ana Menü için <a href="./">buraya basınız.</a></p>
    </div>
    <div class="accordion mb-3" id="mainAccordion">

        <!-- Second Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain2">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#inventoryAccordion2" aria-expanded="false" aria-controls="inventoryAccordion2">
                    <span><strong>Ürün Bilgisine Göre Envanter</strong></span>
                </button>
            </h2>
            <div id="inventoryAccordion2" class="accordion-collapse collapse" aria-labelledby="headingMain2" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <?php if (!($cache = WarehouseAbstract::getCache('allProductsCategorized'))): ?>
                        <?php ob_start(); ?>
                        <?php foreach (WarehouseProduct::getAllCategorized() as $category => $products): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingCategory<?= $category ?>">
                                    <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCategory<?= $category ?>" aria-expanded="false" aria-controls="collapseCategory<?= $category ?>">
                                        <span><strong><?= htmlspecialchars($category) ?> (<?= count($products) ?> çeşit ürün)</strong></span>
                                    </button>
                                </h2>
                                <div id="collapseCategory<?= $category ?>" class="accordion-collapse collapse" aria-labelledby="headingCategory<?= $category ?>" data-bs-parent="#inventoryAccordion2">
                                    <div class="accordion-body">
                                        <?php foreach ($products as $index => $product): ?>
                                            <?php if ($product->getTotalCount() == 0) continue; ?>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="headingProduct<?= $category . $index ?>">
                                                    <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProduct<?= $category . $index ?>" aria-expanded="false" aria-controls="collapseProduct<?= $category . $index ?>">
                                                        <span><strong><?= htmlspecialchars($product->name) ?> (<?= htmlspecialchars($product->fnsku) ?>)</strong> (Toplam: <?= $product->getTotalCount() ?> adet) (<?= count($product->getContainers()) ?> koli/rafta)</span>
                                                    </button>
                                                </h2>
                                                <div id="collapseProduct<?= $category . $index ?>" class="accordion-collapse collapse" aria-labelledby="headingProduct<?= $category . $index ?>" data-bs-parent="#collapseCategory<?= $category ?>">
                                                    <div class="accordion-body">
                                                        <p><?= productInfo($product) ?></p>
                                                        <h4>Ürünün Bulunduğu Raflar ve Koli Bilgileri</h4>
                                                        <ul>
                                                            <?= empty($product->getContainers()) ? "<p>Bu ürün hiçbir raf veya koli içinde bulunmamaktadır.</p>" : "" ?>
                                                            <?php foreach ($product->getContainers() as $container): ?>
                                                                <?php if ($container->type === 'Gemi' || ($container->parent && $container->parent->type === 'Gemi')) continue; ?>
                                                                <li><?= $icon[$container->type] ?> <?= $container->name ?> (<?= $container->type === 'Raf' ? 'Rafta açık' : $container->parent->name ?>) (<?= $product->getInContainerCount($container) ?> adet)</li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (empty($products)): ?>
                                            <p>Ürün bilgisi bulunmamaktadır.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php $cache = ob_get_clean(); WarehouseAbstract::setCache('allProductsCategorized', $cache); ?>
                    <?php endif; ?>
                    <?= $cache ?>
                </div>
            </div>
        </div>

        <!-- First Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain1">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#inventoryAccordion1" aria-expanded="false" aria-controls="inventoryAccordion1">
                    <span><strong>Raf / Koli Bilgisine Göre Envanter</strong></span>
                </button>
            </h2>
            <div id="inventoryAccordion1" class="accordion-collapse collapse" aria-labelledby="headingMain1" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <?php if (!($cache = WarehouseAbstract::getCache('allContainersHtml'))): ?>
                        <?php ob_start(); ?>
                        <?php foreach (rafContainers() as $grup => $raflar): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingGrup<?= $grup ?>">
                                    <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGrup<?= $grup ?>" aria-expanded="false" aria-controls="collapseGrup<?= $grup ?>">
                                        <span><strong><?= htmlspecialchars($grup) ?> (<?= count($raflar) ?> raf)</strong></span>
                                    </button>
                                </h2>
                                <div id="collapseGrup<?= $grup ?>" class="accordion-collapse collapse" aria-labelledby="headingGrup<?= $grup ?>" data-bs-parent="#inventoryAccordion1">
                                    <div class="accordion-body">
                                        <?php foreach ($raflar as $index => $raf): ?>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="headingRaf<?= $grup . $index ?>">
                                                    <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRaf<?= $grup . $index ?>" aria-expanded="false" aria-controls="collapseRaf<?= $grup . $index ?>">
                                                        <span><strong><?= $icon['Raf'] ?> <?= htmlspecialchars($raf->name) ?></strong> (<?= count($raf->getChildren()) ?> koli, <?= count($raf->getProducts()) ?> açık ürün)</span>
                                                    </button>
                                                </h2>
                                                <div id="collapseRaf<?= $grup . $index ?>" class="accordion-collapse collapse" aria-labelledby="headingRaf<?= $grup . $index ?>" data-bs-parent="#collapseGrup<?= $grup ?>">
                                                    <div class="accordion-body">
                                                        <?= containerInfo($raf) ?><br>
                                                        <strong>Raftaki Koliler:</strong><br>
                                                        <?php foreach ($raf->getChildren() as $childIndex => $child): ?>
                                                            <div class="accordion-item">
                                                                <h2 class="accordion-header" id="headingChild<?= $grup . $index ?>-<?= $childIndex ?>">
                                                                    <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseChild<?= $grup . $index ?>-<?= $childIndex ?>" aria-expanded="false" aria-controls="collapseChild<?= $grup . $index ?>-<?= $childIndex ?>">
                                                                        <span><strong><?= $icon['Koli'] ?> <?= htmlspecialchars($child->name) ?> (<?= htmlspecialchars($child->getTotalCount()) ?> ürün)</strong></span>
                                                                    </button>
                                                                </h2>
                                                                <div id="collapseChild<?= $grup . $index ?>-<?= $childIndex ?>" class="accordion-collapse collapse" aria-labelledby="headingChild<?= $grup . $index ?>-<?= $childIndex ?>" data-bs-parent="#collapseRaf<?= $grup . $index ?>">
                                                                    <div class="accordion-body">
                                                                        <?= containerInfo($child) ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <?php if (empty($raf->getChildren())): ?>
                                                            <p>Bu raf altında koli bulunmamaktadır.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php $cache = ob_get_clean(); WarehouseAbstract::setCache('allContainersHtml', $cache); ?>
                    <?php endif; ?>
                    <?= $cache ?>
                    <?php if (empty(rafContainers())): ?>
                        <p>Envanter bilgisi bulunmamaktadır.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Third Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain3">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#inventoryAccordion3" aria-expanded="false" aria-controls="inventoryAccordion3">
                    <span><strong>Ürün Bilgisi Girerek Ara</strong></span>
                </button>
            </h2>
            <div id="inventoryAccordion3" class="accordion-collapse collapse" aria-labelledby="headingMain3" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <?= productSelect() ?>
                    <div id="selectedProduct" class="d-none">
                        <h4>Seçilen Ürün Bilgileri</h4>
                        <div id="product_info"></div>
                        <h4>Raflar ve Koliler</h4>
                        <div id="dynamic_container_list"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <hr>

    <?= wh_menu() ?>
</div>
<script>
    
    function copyToClipboard(elementId) {
        console.log('copyToClipboard', elementId);
        var text = document.getElementById(elementId).innerText;
        var textArea = document.createElement("textarea");
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("Copy");
        document.body.removeChild(textArea);
        var copyMessage = document.getElementById('copyMessage');
        copyMessage.style.display = 'inline';
        setTimeout(function() {
            copyMessage.style.display = 'none';
        }, 1000);
    }

    $(document).ready(function() {
        $('#inventoryAccordion2').on('shown.bs.collapse', function () {
            $('#filterInput2').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#inventoryAccordion2 .accordion-item').each(function() {
                    var headerText = $(this).find('.accordion-header').text().toLowerCase();
                    if (value === "") {
                        $(this).hide();
                    } else {
                        $(this).toggle(headerText.indexOf(value) > -1);
                    }
                });
            });
        });

        $(document).ready(function() {
            $('#product_select').on('change', function() {
                var productId = $(this).val();
                if (productId) {
                    $.ajax({
                        url: 'controller.php',
                        method: 'POST',
                        data: { product_id: productId , action: 'product_info', csrf_token: '<?= $_SESSION['csrf_token'] ?>'},
                        success: function(response) {
                            $('#product_info').html(response.info);
                            $('#selectedProduct').removeClass('d-none');
                            // Convert the response.container HTML to <ul><li> format
                            var ulList = convertToUlLi(response.container);
                            $('#dynamic_container_list').html(ulList);
                        },
                        error: function(xhr, status, error) {
                            console.error('Error fetching product information:', error);
                        }
                    });
                } else {
                    $('#selectedProduct').addClass('d-none');
                }
            });

            function convertToUlLi(containerHtml) {
                var ul = $('<ul></ul>');
                var containerDiv = $('<div></div>').html(containerHtml);

                containerDiv.find('optgroup').each(function() {
                    var optgroup = $(this);
                    var li = $('<li></li>').text(optgroup.attr('label'));
                    var subUl = $('<ul></ul>');

                    optgroup.find('option').each(function() {
                        var option = $(this);
                        var subLi = $('<li></li>').text(option.text()).attr('data-value', option.attr('value'));
                        subUl.append(subLi);
                    });

                    li.append(subUl);
                    ul.append(li);
                });

                return ul;
            }
        });

    });
    
</script>

<?php

include '../_footer.php';
