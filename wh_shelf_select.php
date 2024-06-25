<?php

require_once('_login.php');
require_once('_init.php');

$shelfs = $GLOBALS['pdo']->query('SELECT * FROM wh_shelf ORDER BY type, name ASC')->fetchAll(PDO::FETCH_ASSOC);
$shelf = [];
foreach ($shelfs as $s) {
    $shelf[$s['id']] = $s;
}

include '_header.php';

?>
<div class="container mt-5">
    <div class="mt-4 m-3">
        <h2>Raf / Koli Seçin</h2>
        <form action="wh_shelf_product.php" method="POST">
            <div class="mb-3">
                <label for="shelf" class="form-label">Raf / Koli Seçin</label>
                <select class="form-select" id="shelf" name="shelf" required>
                    <?php foreach ($shelf as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= $s['name'] ?> (<?= $s['type'] ?><?= $s['parent_id'] ? ' / '.$shelf[$s['parent_id']]['name'] : '' ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Devam</button>
        </form>
    </div>
    <div class="d-grid gap-2 mt-4 m-3">
        <a href="./wh.php" class="btn btn-secondary btn-lg w-100">Depo Yönetim Ana Sayfa</a>
        <a href="./" class="btn btn-secondary btn-lg w-100">Ana Sayfa</a>
        <a href="./?logout=1" class="btn btn-danger btn-lg w-100">Logout</a>
    </div>
</div>


<?php

include '_footer.php';
