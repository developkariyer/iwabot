<?php

require_once 'warehouse.php';

if (!userCan(['manage', 'order'])) {
    addMessage('Bu sayfaya erişim izniniz yok!', 'alert-danger');
    header('Location: ./');
    exit;
}

$icon = [
    'Gemi' => '🚢', //\u{1F6A2}
    'Raf' => '🗄️', // \u{1F5C4}
    'Koli' => '📦', //\u{1F4E6}
];

$unfulfilledBoxes = WarehouseSold::getSoldContainers();
$unfulfilledProducts = WarehouseSold::getSoldProducts();

include '../_header.php';

?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>Sipariş İşlemleri</h1>
        <p>Sipariş/ürün çıkış işlemleri aşağıdan seçiniz. Depo Ana Menü için <a href="./">buraya basınız.</a></p>
    </div>
    <div class="accordion mb-3" id="mainAccordion">

        <!-- First Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain1">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#orderAccordion1" aria-expanded="false" aria-controls="orderAccordion1">
                    <span><strong>İşlem Bekleyen Siparişler</strong></span>
                </button>
            </h2>
            <div id="orderAccordion1" class="accordion-collapse collapse" aria-labelledby="headingMain1" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">

                    <div class="accordion mb-3" id="nestedAccordion1">
                        <!-- First Sub Accordion: İşlem Bekleyen Koliler -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingSub1">
                                <button class="accordion-button collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#subAccordion1" aria-expanded="false" aria-controls="subAccordion1">
                                    <span><strong>İşlem Bekleyen Koliler</strong></span>
                                </button>
                            </h2>
                            <div id="subAccordion1" class="accordion-collapse collapse" aria-labelledby="headingSub1" data-bs-parent="#nestedAccordion1">
                                <div class="accordion-body">

                                    <div class="accordion mb-3" id="subNestedAccordion1">
                                    <?php foreach ($unfulfilledBoxes as $index => $soldItem): ?>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="headingBox<?= $index ?>">
                                                    <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBox<?= $index ?>" aria-expanded="false" aria-controls="collapseBox<?= $index ?>">
                                                        <span><strong><?= $icon[$soldItem->object->type] ?> <?= htmlspecialchars($soldItem->object->name) ?></strong></span>
                                                    </button>
                                                </h2>
                                                <div id="collapseBox<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="headingBox<?= $index ?>" data-bs-parent="#orderAccordion4">
                                                    <div class="accordion-body">
                                                        <form action="controller.php" method="post">
                                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                            <input type="hidden" name="sold_id" value="<?= htmlspecialchars($soldItem->id) ?>">
                                                            <div class="mb-3">
                                                                <p>
                                                                    <?= containerInfo($soldItem->object) ?>
                                                                </p>
                                                            </div>
                                                            <p><strong>Aynı İçerikli Koliler</strong></p>
                                                            <ul>
                                                                <?php foreach ($soldItem->object->findSimilar() as $sameContainer): ?>
                                                                    <li><?= $icon[$sameContainer->type] ?> <?= htmlspecialchars($sameContainer->name) ?> (<?= htmlspecialchars($sameContainer->parent->name) ?>)</li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                            <div class="mb-3">
                                                                <label for="description" class="form-label">Açıklama</label>
                                                                <textarea id="description" name="description" rows="5" class="form-control btn-outline-success w-100 py-3" placeholder="Açıklama" required><?= htmlspecialchars($soldItem->description) ?></textarea>
                                                            </div>
                                                            <button type="submit" name="action" value="fulfil_box_update" class="btn btn-primary w-100 py-3 mt-2">Çıkış Bilgilerini Güncelle</button>
                                                            <button type="submit" name="action" value="fulfil_box_delete" class="btn btn-danger w-100 py-3 mt-2">Çıkış Bilgilerini Sil</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (empty($unfulfilledBoxes)): ?>
                                            <p>İşlem bekleyen koli bulunmamaktadır.</p>
                                        <?php endif; ?>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Second Sub Accordion: İşlem Bekleyen Ürünler -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingSub2">
                                <button class="accordion-button collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#subAccordion2" aria-expanded="false" aria-controls="subAccordion2">
                                    <span><strong>İşlem Bekleyen Ürünler</strong></span>
                                </button>
                            </h2>
                            <div id="subAccordion2" class="accordion-collapse collapse" aria-labelledby="headingSub2" data-bs-parent="#nestedAccordion1">
                                <div class="accordion-body">
                                    <?php foreach ($unfulfilledProducts as $index => $soldItem): ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading<?= $index ?>">
                                                <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                                                    <span><strong><?= htmlspecialchars($soldItem->object->name) ?> (<?= htmlspecialchars($soldItem->object->fnsku) ?>)</strong></span>
                                                </button>
                                            </h2>
                                            <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#productAccordion">
                                                <div class="accordion-body p-5">
                                                    <form action="controller.php" method="post">
                                                        <input type="hidden" name="product_id" value="<?= $soldItem->object->id ?>">
                                                        <input type="hidden" name="sold_id" value="<?= $soldItem->id ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                        <p><?= productInfo($soldItem->object) ?></p>
                                                        <p><strong>Bulunduğu Raflar:</strong></p>
                                                        <ul>
                                                        <?php foreach ($soldItem->object->getContainers() as $container): ?>
                                                            <li><?= $icon[$container->type] ?> <?= $container->name ?> (<?= $container->type === 'Raf' ? 'Rafta açık' : $container->parent->name ?>) (<?= $soldItem->object->getInContainerCount($container) ?> adet)</li>
                                                        <?php endforeach; ?>
                                                        </ul>
                                                        <div class="mb-3">
                                                            <label for="description" class="form-label">Açıklama</label>
                                                            <textarea id="description" name="description" rows="5" class="form-control btn-outline-success w-100 py-3" placeholder="Açıklama" required><?= htmlspecialchars($soldItem->description) ?></textarea>
                                                        </div>
                                                        <button name="action" value="fulfil_update" id="Submit<?= $index ?>Product<?= $soldItem->object->id?>" type="submit" class="btn btn-primary w-100 py-3 mt-2">Ürün Çıkış Bilgilerini Güncelle</button>
                                                        <button name="action" value="fulfil_delete" id="Delete<?= $index ?>Product<?= $soldItem->object->id ?>" type="submit" class="btn btn-danger w-100 py-3 mt-2">Ürün Çıkış Bilgilerini Sil</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($unfulfilledProducts) || count($unfulfilledProducts) == 0): ?>
                                        <p>Çıkış için bekleyen ürün bulunmamaktadır.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> 
        </div>  


        <!-- Second Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain2">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#orderAccordion2" aria-expanded="false" aria-controls="orderAccordion2">
                    <span><strong>Yeni Sipariş/Ürün Çıkış Kaydı Gir</strong></span>
                </button>
            </h2>
            <div id="orderAccordion2" class="accordion-collapse collapse" aria-labelledby="headingMain2" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <form action="controller.php" method="post">
                        <input type="hidden" name="action" value="add_sold_item">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <?= productSelect() ?>
                        <textarea rows="5" name="description" class="form-control btn-outline-success w-100 py-3 mt-2" placeholder="İsim - Adres - Açıklama - Ürün Kodları" required></textarea>
                        <button id="submitbutton" type="submit" class="btn btn-primary w-100 py-3 mt-2">Yeni Çıkış Kaydı Ekle</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Third Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain3">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#orderAccordion3" aria-expanded="false" aria-controls="orderAccordion3">
                    <span><strong>Yeni Koli Çıkışı Gir</strong></span>
                </button>
            </h2>
            <div id="orderAccordion3" class="accordion-collapse collapse" aria-labelledby="headingMain3" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <form action="controller.php" method="post">
                        <input type="hidden" name="action" value="add_sold_box">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-3">
                            <label for="container_id" class="form-label">Koli Seçin</label>
                            <select id="container_id" name="container_id" class="form-select" required>
                                <option value="">Koli Seçin</option>
                                <?= containersInOpt('Raf') ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Açıklama</label>
                            <textarea id="description" name="description" rows="5" class="form-control btn-outline-success w-100 py-3" placeholder="Açıklama" required></textarea>
                        </div>
                        <button id="submitbutton" type="submit" class="btn btn-primary rounded-pill w-100 py-3 mt-2">Yeni Çıkış Kaydı Ekle</button>
                    </form>
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
