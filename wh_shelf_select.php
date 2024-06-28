<?php

require_once('_login.php');
require_once('_init.php');
require_once('wh_include.php');

$shelfs = StockShelf::allShelves($GLOBALS['pdo']);

include '_header.php';

?>
<div class="container mt-5">
    <div class="mt-4 m-3">
        <h2>Raf / Koli Seçin</h2>
        <form action="wh_shelf_product.php" method="POST">
            <div class="mb-3">
                <label for="shelf" class="form-label">Raf / Koli Seçin</label>
                <select class="form-select" id="shelf" name="shelf" required>
                    <option value="">Seçiniz...</option>
                    <?php foreach ($shelves as $s): ?>
                        <option value="<?= $s->id ?>"><?= $s->name ?> (<?= $s->type ?><?= $s->parent ? ' / '.$s->parent->name : '' ?>)</option>
                    <?php endforeach; ?>
                    <option value="add_new">Yeni Raf / Koli Ekle</option>
                </select>
            </div>
            <!-- hide show fields for new raf/koli based on SELECT value -->
            <div class="mb-3 d-none" id="newShelf">
                <label for="newShelfName" class="form-label">Yeni Raf / Koli Adı</label>
                <input type="text" class="form-control" id="newShelfName" name="newShelfName">
                <label for="newShelfType" class="form-label">Tipi</label>
                <select class="form-select" id="newShelfType" name="newShelfType">
                    <option value="Raf">Raf</option>
                    <option value="Koli (Açılmış)">Koli (Açılmış)</option>
                    <option value="Koli (Kapalı)">Koli (Kapalı)</option>
                </select>
                <label for="newShelfParent" class="form-label">Koli ise Rafı</label>
                <select class="form-select" id="newShelfParent" name="newShelfParent">
                    <option value="">Yok</option>
                    <?php foreach ($shelf as $s): ?>
                        <?php if ($s->type === 'Raf'): ?>
                            <option value="<?= $s->id ?>"><?= $s->name ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Devam</button>
        </form>
    </div>
    <hr>
    <div class="d-grid gap-2 mt-4 m-3">
        <a href="./wh.php" class="btn btn-secondary btn-lg w-100">Depo Yönetim Ana Sayfa</a>
        <a href="./" class="btn btn-secondary btn-lg w-100">Ana Sayfa</a>
        <a href="./?logout=1" class="btn btn-danger btn-lg w-100">Logout</a>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const shelfSelect = document.getElementById('shelf');
        const newShelfDiv = document.getElementById('newShelf');

        shelfSelect.addEventListener('change', function() {
            if (this.value === 'add_new') {
                newShelfDiv.classList.remove('d-none');
            } else {
                newShelfDiv.classList.add('d-none');
            }
        });
    });
</script>

<?php

include '_footer.php';

