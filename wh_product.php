<?php

require_once('_login.php');
require_once('_init.php');
require_once('wh_include.php');

$productId = $_GET['product'] ?? '';

if ($productId) {
    $product = StockProduct::getById($productId, $GLOBALS['pdo']);
} else {
    $product = null;
}

if (empty($product)) {
    addMessage("Ürün bilgisi bulunamadı. ($productId)", 'danger');
    header('Location: wh_product_search.php');
    exit;
}

$shelfList = StockShelf::allShelves($GLOBALS['pdo']);

function createShelfOptions($product) {
    $shelves = $product->getShelves();
    
    // Separate and sort shelves
    $openShelves = [];
    $boxedShelves = [];
    
    foreach ($shelves as $shelf) {
        if ($shelf->type === 'Raf') {
            $openShelves[] = $shelf;
        } else {
            $boxedShelves[] = $shelf;
        }
    }

    // Sort shelves
    usort($openShelves, function($a, $b) use ($product) {
        return $product->shelfCount($a) - $product->shelfCount($b);
    });

    usort($boxedShelves, function($a, $b) use ($product) {
        if ($a->type === $b->type) {
            return $product->shelfCount($a) - $product->shelfCount($b);
        }
        return $a->type === 'Koli (Açılmış)' ? -1 : 1;
    });

    $shelvesGrouped = [];

    foreach ($openShelves as $shelf) {
        $shelvesGrouped[$shelf->name][] = $shelf;
    }

    foreach ($boxedShelves as $shelf) {
        $parentName = $shelf->parent->name;
        $shelvesGrouped[$parentName][] = $shelf;
    }

    return $shelvesGrouped;
}

$shelvesGrouped = createShelfOptions($product);

include '_header.php';

?>

<div class="container mt-5">
    <div class="mt-5">
        <h2><?= htmlspecialchars($product->name) ?></h2>
        <h5>Ürün Bilgileri</h5>
        <p><?= nl2br(htmlspecialchars($product->productInfo())) ?></p>
        <h5>Ürünün Bulunduğu Yerler</h5>
        İşlem yapmak için lütfen aşağıdaki raf ve kolilerden birini seçin.
        <div class="mb-3 mt-3">
            <label for="existingShelvesSelect" class="form-label">Raf/Koli Seçin</label>
            <select class="form-select" id="existingShelvesSelect">
                <option value="">Raf/Koli seçin...</option>
                <?php foreach ($shelvesGrouped as $groupName => $shelves): ?>
                    <optgroup label="<?= htmlspecialchars($groupName) ?>">
                        <?php foreach ($shelves as $shelf): ?>
                            <?php
                            if ($shelf->type === 'Raf') {
                                $optionText = "Rafta açık {$product->shelfCount($shelf)} adet ürün";
                            } else {
                                $optionText = "{$shelf->name} kutusunda " . ($shelf->type === 'Koli (Açılmış)' ? 'açık' : 'kapalı') . " {$product->shelfCount($shelf)} adet ürün";
                            }
                            ?>
                            <option value="<?= htmlspecialchars($shelf->id) ?>">
                                <?= htmlspecialchars($optionText) ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row g-3 m-3 mt-5">
        <div class="col-md-3">
        </div>
        <div class="col-md-6">
            <button type="button" id="put_to_shelf" class="btn btn-outline-primary btn-lg rounded-pill w-100 py-3" data-bs-toggle="modal" data-bs-target="#putToShelfModal">Ürünü Rafa Yerleştir</button>
        </div>
    </div>

    <?= wh_menu() ?>
</div>

<!-- Modal for moving products -->
<div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalLabel">Ürün İşlemleri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="actionForm" action="wh_product_action.php" method="post">
                    <input type="hidden" name="product_id" id="modalProductId">
                    <input type="hidden" name="shelf_id" id="modalShelfId">
                    <div class="mb-3">
                        <button type="submit" name="action" value="send_to_sale" class="btn btn-success w-100 mb-2">Satışa Gönder</button>
                    </div>
                    <div class="mb-3">
                        <label for="newShelfSelect" class="form-label">Yeni Raf/Koli Seçin</label>
                        <select class="form-select" id="newShelfSelect" name="new_shelf_id">
                            <option value="">Raf seçin...</option>
                            <?php foreach ($shelfList as $shelf): ?>
                                <optgroup label="<?= htmlspecialchars($shelf->name) ?>">
                                    <option value="<?= htmlspecialchars($shelf->id) ?>">Rafa açık olarak koy</option>
                                    <?php foreach ($shelf->getChildren() as $child): ?>
                                        <option value="<?= htmlspecialchars($child->id) ?>"><?= htmlspecialchars($child->name) ?> / <?= htmlspecialchars($child->type) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="action" value="move_to_shelf" class="btn btn-primary w-100" id="moveToShelfButton" disabled>Başka Rafa Taşı</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal for placing products on shelves -->
<div class="modal fade" id="putToShelfModal" tabindex="-1" aria-labelledby="putToShelfModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="putToShelfModalLabel">Ürünü Rafa Yerleştir</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="wh_product_action.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($product->id) ?>">
                    <input type="hidden" name="action" value="add_to_shelf">
                    <div class="mb-3">
                        <label for="shelfSelect" class="form-label">Raf/Koli Seçin</label>
                        <select class="form-select" id="shelfSelect" name="shelf_id" required>
                            <option value="">Raf seçin...</option>
                            <?php foreach ($shelfList as $shelf): ?>
                                <optgroup label="<?= htmlspecialchars($shelf->name) ?>">
                                    <option value="<?= htmlspecialchars($shelf->id) ?>">Rafa açık olarak koy</option>
                                    <?php foreach ($shelf->getChildren() as $child): ?>
                                        <option value="<?= htmlspecialchars($child->id) ?>"><?= htmlspecialchars($child->name) ?> / <?= htmlspecialchars($child->type) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantityInput" class="form-label">Miktar</label>
                        <div class="input-group">
                            <button type="button" class="btn btn-outline-secondary" id="decrementBtn">-</button>
                            <input type="number" class="form-control text-center" id="quantityInput" name="quantity" min="0" value="0" required>
                            <button type="button" class="btn btn-outline-secondary" id="incrementBtn">+</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" id="saveButton" class="btn btn-primary" disabled>Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const decrementBtn = document.getElementById('decrementBtn');
        const incrementBtn = document.getElementById('incrementBtn');
        const quantityInput = document.getElementById('quantityInput');
        const shelfSelect = document.getElementById('shelfSelect');
        const saveButton = document.getElementById('saveButton');
        const existingShelvesSelect = document.getElementById('existingShelvesSelect');
        const actionModal = new bootstrap.Modal(document.getElementById('actionModal'));
        const modalProductId = document.getElementById('modalProductId');
        const modalShelfId = document.getElementById('modalShelfId');
        const newShelfSelect = document.getElementById('newShelfSelect');
        const moveToShelfButton = document.getElementById('moveToShelfButton');

        const updateButtons = () => {
            decrementBtn.disabled = quantityInput.value <= 0;
            saveButton.disabled = shelfSelect.value === "" || quantityInput.value <= 0;
            moveToShelfButton.disabled = newShelfSelect.value === "";
        };

        decrementBtn.addEventListener('click', () => {
            if (quantityInput.value > 0) {
                quantityInput.value = parseInt(quantityInput.value) - 1;
                updateButtons();
            }
        });

        incrementBtn.addEventListener('click', () => {
            quantityInput.value = parseInt(quantityInput.value) + 1;
            updateButtons();
        });

        quantityInput.addEventListener('input', () => {
            updateButtons();
        });

        shelfSelect.addEventListener('change', () => {
            updateButtons();
        });

        newShelfSelect.addEventListener('change', () => {
            updateButtons();
        });

        // Handle selection of existing shelves
        existingShelvesSelect.addEventListener('change', () => {
            const selectedShelf = existingShelvesSelect.value;
            if (selectedShelf) {
                modalProductId.value = "<?= htmlspecialchars($product->id) ?>";
                modalShelfId.value = selectedShelf;
                actionModal.show();
            }
        });

        // Initial button state
        updateButtons();
    });
</script>

<?php

include '_footer.php';
?>
