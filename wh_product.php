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

include '_header.php';

?>

<div class="container mt-5">
    <div class="mt-5">
        <h2><?= htmlspecialchars($product->name) ?></h2>
        <h5>Ürün Bilgileri</h5>
        <p><?= nl2br(htmlspecialchars($product->productInfo())) ?></p>
        <h5>Ürünün Bulunduğu Yerler</h5>
        İşlem yapmak için lütfen aşağıdaki raf ve kolilerden birini seçin.
        <div class="g-3 m-3 mt-5">
            <?php foreach ($product->getShelves() as $shelf): ?>
                <a href="wh_product_action.php?product=<?= urlencode($product->id) ?>&shelf=<?= urlencode($shelf->id) ?>" class="btn btn-outline-primary rounded-pill w-100 btn-lg py-3 m-1">
                    <?php
                        if ($shelf->type === 'Raf') {
                            echo "{$shelf->name} rafında {$product->shelfCount($shelf)} açık ürün";
                        } else {
                            echo "{$shelf->parent->name} rafında {$shelf->name} kolisinde {$product->shelfCount($shelf)} adet ürün. {$shelf->type}";
                        }
                    ?>
                </a>
            <?php endforeach; ?>
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

<!-- Modal -->
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
                    <div class="mb-3">
                        <label for="shelfSelect" class="form-label">Raf/Koli Seçin</label>
                        <select class="form-select" id="shelfSelect" name="shelf_id" required>
                            <?php foreach ($shelfList as $shelf): ?>
                                <option value="<?= htmlspecialchars($shelf->id) ?>"><?= htmlspecialchars($shelf->name) ?> (<?= htmlspecialchars($shelf->type) ?>)</option>
                                <?php foreach ($shelf->getChildren() as $child): ?>
                                    <option value="<?= htmlspecialchars($child->id) ?>">-- <?= htmlspecialchars($child->name) ?> (<?= htmlspecialchars($child->type) ?>)</option>
                                <?php endforeach; ?>
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
                    <button type="submit" class="btn btn-primary">Kaydet</button>
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

        const updateButtons = () => {
            decrementBtn.disabled = quantityInput.value <= 0;
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

        // Initial button state
        updateButtons();
    });
</script>

<?php

include '_footer.php';

