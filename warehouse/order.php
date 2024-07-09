<?php

require_once 'warehouse.php';

if (!userCan(['manage', 'order'])) {
    addMessage('Bu sayfaya erişim izniniz yok!', 'alert-danger');
    header('Location: ./');
    exit;
}

$unfulfilledBoxes = WarehouseContainer::getUnfulfilledBoxes();
$unfulfilledProducts = WarehouseProduct::getUnfulfilledProducts();

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
                                    <?php foreach ($unfulfilledBoxes as $index => $item): ?>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="headingBox<?= $index ?>">
                                                    <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBox<?= $index ?>" aria-expanded="false" aria-controls="collapseBox<?= $index ?>">
                                                        <span><strong><?= htmlspecialchars($item['container']->name) ?></strong></span>
                                                    </button>
                                                </h2>
                                                <div id="collapseBox<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="headingBox<?= $index ?>" data-bs-parent="#orderAccordion4">
                                                    <div class="accordion-body">
                                                        <form action="controller.php" method="post">
                                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                            <input type="hidden" name="sold_id" value="<?= htmlspecialchars($item['id']) ?>">
                                                            <div class="mb-3">
                                                                <p>
                                                                    <?= containerInfo($item['container']) ?>
                                                                </p>
                                                            </div>
                                                            <p><strong>Aynı İçerikli Koliler</strong></p>
                                                            <ul>
                                                                <?php foreach ($item['container']->findSimilar() as $sameContainer): ?>
                                                                    <li><?= htmlspecialchars($sameContainer->name) ?> (<?= htmlspecialchars($sameContainer->parent->name) ?>)</li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                            <div class="mb-3">
                                                                <label for="description" class="form-label">Açıklama</label>
                                                                <textarea id="description" name="description" rows="5" class="form-control btn-outline-success w-100 py-3" placeholder="Açıklama" required><?= htmlspecialchars($item['description']) ?></textarea>
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

                                    <div class="accordion mb-3" id="subNestedAccordion2">
                                        <?php foreach ($unfulfilledProducts as $product): ?>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="headingProduct<?= $product->id ?>">
                                                    <button class="accordion-button bg-light text-dark collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#collapseProduct<?= $product->id ?>" aria-expanded="false" aria-controls="collapseProduct<?= $product->id ?>">
                                                        <span><strong><?= htmlspecialchars($product->name) ?></strong></span>
                                                    </button>
                                                </h2>
                                                <div id="collapseProduct<?= $product->id ?>" class="accordion-collapse collapse" aria-labelledby="headingProduct<?= $product->id ?>" data-bs-parent="#subNestedAccordion2">
                                                    <div class="accordion-body">
                                                        <p>Placeholder for <?= htmlspecialchars($product->name) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (empty($unfulfilledProducts)): ?>
                                            <p>İşlem bekleyen ürün bulunmamaktadır.</p>
                                        <?php endif; ?>
                                    </div>

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
