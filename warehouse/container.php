<?php

require_once('warehouse.php');

include '../_header.php';

$containersInShip = [
    // Sample data structure
    // 'ship' => [
    //     'container' => new WarehouseContainer(), // Example object
    //     'boxes' => [new WarehouseContainer(), new WarehouseContainer()] // Example objects
    // ]
];

?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>Depo İşlemleri</h1>
        <p>İşlem yapmak istediğiniz koliyi seçiniz. Depo Ana Menü için <a href="./">buraya basınız.</a></p>
    </div>
    <div class="accordion mb-3" id="mainAccordion">
        <!-- First Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain1">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#containerAccordion1" aria-expanded="false" aria-controls="containerAccordion1">
                    <span><strong>Gemi İle Gelen Koliler</strong></span>
                </button>
            </h2>
            <div id="containerAccordion1" class="accordion-collapse collapse" aria-labelledby="headingMain1" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-0">
                    <?php foreach (containersInShip() as $shipIndex => $shipData): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingShip<?= $shipIndex ?>">
                                <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseShip<?= $shipIndex ?>" aria-expanded="false" aria-controls="collapseShip<?= $shipIndex ?>">
                                    <span><strong><?= htmlspecialchars($shipData['container']->name) ?> (<?= htmlspecialchars($shipData['container']->id) ?>)</strong></span>
                                </button>
                            </h2>
                            <div id="collapseShip<?= $shipIndex ?>" class="accordion-collapse collapse" aria-labelledby="headingShip<?= $shipIndex ?>" data-bs-parent="#containerAccordion1">
                                <div class="accordion-body">
                                    <div class="accordion" id="shipAccordion<?= $shipIndex ?>">
                                        <?php foreach ($shipData['boxes'] as $boxIndex => $box): ?>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="headingBox<?= $shipIndex ?><?= $boxIndex ?>">
                                                    <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBox<?= $shipIndex ?><?= $boxIndex ?>" aria-expanded="false" aria-controls="collapseBox<?= $shipIndex ?><?= $boxIndex ?>">
                                                        <span><strong><?= htmlspecialchars($box->name) ?> (<?= htmlspecialchars($box->id) ?>)</strong></span>
                                                    </button>
                                                </h2>
                                                <div id="collapseBox<?= $shipIndex ?><?= $boxIndex ?>" class="accordion-collapse collapse" aria-labelledby="headingBox<?= $shipIndex ?><?= $boxIndex ?>" data-bs-parent="#shipAccordion<?= $shipIndex ?>">
                                                    <div class="accordion-body">
                                                        <p><strong>Box ID:</strong> <?= htmlspecialchars($box->id) ?></p>
                                                        <p><strong>Box Name:</strong> <?= htmlspecialchars($box->name) ?></p>
                                                        <!-- Add more box details here as needed -->
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty(containersInShip())): ?>
                        <p>Gemi ile gelen koli bulunmamaktadır.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Second Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain2">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#containerAccordion2" aria-expanded="false" aria-controls="containerAccordion2">
                    <span><strong>Kendiniz Koli Seçin</strong></span>
                </button>
            </h2>
            <div id="containerAccordion2" class="accordion-collapse collapse" aria-labelledby="headingMain2" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-0">
                    <!-- Dynamic content for selecting a container and performing actions will go here -->
                </div>
            </div>
        </div>

        <!-- Third Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain3">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#containerAccordion3" aria-expanded="false" aria-controls="containerAccordion3">
                    <span><strong>Depoya Yeni Raf Ekleyin</strong></span>
                </button>
            </h2>
            <div id="containerAccordion3" class="accordion-collapse collapse" aria-labelledby="headingMain3" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5 w-100">
                    <form action="controller.php" method="POST">
                        <input type="hidden" name="action" value="add_shelf">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-3">
                            <label for="shelf_name" class="form-label">Raf Adı</label>
                            <input type="text" class="form-control" id="shelf_name" name="shelf_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="location" class="form-label">Konum</label>
                            <input type="text" class="form-control" id="location" name="location" required>
                        </div>
                        <button type="submit" class="btn btn-primary rounded-pill w-100 py-3 mt-2">Yeni Raf Ekle</button>
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
