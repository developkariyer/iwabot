<?php

require_once('_login.php');
require_once('_init.php');
require_once('wh_include.php');

$shelfList = StockShelf::allShelves($GLOBALS['pdo']);

include '_header.php';

?>

<div class="container mt-5">
    <div class="mt-5">
        <h2>Raflar ve Koliler</h2>
        <div class="accordion" id="shelfAccordion">
            <?php foreach ($shelfList as $index => $shelf): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $index ?>">
                        <button class="accordion-button collapsed d-flex justify-content-between align-items-center text-start" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                            <strong><?= htmlspecialchars($shelf->name) ?> / <?= htmlspecialchars($shelf->type) ?></strong>
                        </button>
                    </h2>
                    <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#shelfAccordion">
                        <div class="accordion-body">
                            <p>Raf içerikleri</p>
                            <a href="wh_shelf.php?shelf=<?= urlencode($shelf->id) ?>" class="btn btn-outline-success btn-lg w-100 py-2 mt-2">Seç</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?= wh_menu() ?>
</div>

<?php include '_footer.php'; ?>
