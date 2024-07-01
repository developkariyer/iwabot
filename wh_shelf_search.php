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
                            <div class="accordion" id="childAccordion<?= $index ?>">
                                <?php foreach ($shelf->getChildren() as $childIndex => $child): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="childHeading<?= $index ?>_<?= $childIndex ?>">
                                            <button class="accordion-button collapsed d-flex justify-content-between align-items-center text-start" type="button" data-bs-toggle="collapse" data-bs-target="#childCollapse<?= $index ?>_<?= $childIndex ?>" aria-expanded="false" aria-controls="childCollapse<?= $index ?>_<?= $childIndex ?>">
                                                <strong><?= htmlspecialchars($child->name) ?> / <?= htmlspecialchars($child->type) ?></strong> (<?= count($child->getProducts()) ?> ürün)
                                            </button>
                                        </h2>
                                        <div id="childCollapse<?= $index ?>_<?= $childIndex ?>" class="accordion-collapse collapse" aria-labelledby="childHeading<?= $index ?>_<?= $childIndex ?>" data-bs-parent="#childAccordion<?= $index ?>">
                                            <div class="accordion-body">
                                                <p>Ürün Listesi</p>
                                                <button type="button" class="btn btn-outline-success btn-lg w-100 py-2 mt-2 select-shelf-btn" data-shelf-id="<?= htmlspecialchars($child->id) ?>" data-bs-toggle="modal" data-bs-target="#shelfSelectModal">Seç</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?= wh_menu() ?>
</div>

<!-- Modal for shelf selection -->
<div class="modal fade" id="shelfSelectModal" tabindex="-1" aria-labelledby="shelfSelectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shelfSelectModalLabel">Raf/Koli Seçin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="shelfSelectForm" action="wh_move_shelf.php" method="post">
                    <input type="hidden" id="selectedShelfId" name="selected_shelf_id">
                    <div class="mb-3">
                        <label for="shelfSelect" class="form-label">Raf/Koli</label>
                        <select class="form-select" id="shelfSelect" name="shelf_id" required>
                            <option value="">Raf/Koli seçin...</option>
                            <?php foreach ($shelfList as $shelf): ?>
                                <option value="<?= htmlspecialchars($shelf->id) ?>"><?= htmlspecialchars($shelf->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Geri Dön</button>
                        <button type="submit" class="btn btn-primary">Rafa Taşı</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.select-shelf-btn').forEach(button => {
        button.addEventListener('click', function() {
            const shelfId = this.getAttribute('data-shelf-id');
            document.getElementById('selectedShelfId').value = shelfId;
        });
    });
});
</script>

<?php include '_footer.php'; ?>
