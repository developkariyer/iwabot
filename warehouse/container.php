<?php

require_once('warehouse.php');

$icon = [
    'Gemi' => '🚢', //\u{1F6A2}
    'Raf' => '🗄️', // \u{1F5C4}
    'Koli' => '📦', //\u{1F4E6}
];

include '../_header.php';

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
                <div class="accordion-body p-5">
                    <form action="controller.php" method="POST">
                        <input type="hidden" name="action" value="set_parent">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-3">
                            <label for="container_id" class="form-label">Koli Seçin</label>
                            <select id="container_id" name="container_id" class="form-select" required>
                                <option value="">Gemiden Koli Seçin</option>
                                <?= containersInOpt(type: 'Gemi') ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Raf Seçin</label>
                            <select id="parent_id" name="parent_id" class="form-select" required>
                                <option value="">Yerleştirileceği Rafı Seçin</option>
                                <?= parentContainersOpt() ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3 mt-2">Gemiden Rafa Taşı</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Second Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain2">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#containerAccordion2" aria-expanded="false" aria-controls="containerAccordion2">
                    <span><strong>Depodaki Koliler</strong></span>
                </button>
            </h2>
            <div id="containerAccordion2" class="accordion-collapse collapse" aria-labelledby="headingMain2" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <form action="controller.php" method="POST" id="containerForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-3">
                            <label for="container_id" class="form-label">Koli Seçin</label>
                            <select id="container_id" name="container_id" class="form-select" required>
                                <option value="">Raf Seçin</option>
                                <?= containersInOpt('Raf') ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Yerleştirileceği Rafı Seçin</label>
                            <select id="parent_id" name="parent_id" class="form-select">
                                <option value="">Yerleştirileceği Rafı Seçin</option>
                                <?= parentContainersOpt() ?>
                            </select>
                        </div>
                        <button type="submit" name="action" value="set_parent" class="btn btn-primary w-100 py-3 mt-2" id="moveButton">Raftan Rafa Taşı</button>
                        <button type="submit" name="action" value="delete_container" class="btn btn-danger w-100 py-3 mt-2" id="deleteButton">Koli Sil</button>
                    </form>
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
                    <form action="controller.php" method="POST" id="addContainerForm">
                        <input type="hidden" name="action" value="add_container">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label">Adı</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Tip</label>
                            <select id="type" name="type" class="form-select" required>
                                <option value="">Tip Seçin</option>
                                <option value="Gemi"><?= $icon['Gemi'] ?> Gemi</option>
                                <option value="Raf"><?= $icon['Raf'] ?> Raf</option>
                                <option value="Koli"><?= $icon['Koli'] ?> Koli</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Yerleştirileceği Konum</label>
                            <select id="parent_id" name="parent_id" class="form-select" disabled>
                                <option value="">Yerleştirileceği Konumu Seçin</option>
                                <optgroup label="Raf">
                                    <?= parentContainersOpt('Raf') ?>
                                </optgroup>
                                <optgroup label="Gemi">
                                    <?= parentContainersOpt('Gemi') ?>
                                </optgroup>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3 mt-2" id="addNewButton">Yeni Ekle</button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <hr>

    <?= wh_menu() ?>
</div>


<script>

document.addEventListener('DOMContentLoaded', function() {
    const containerForm = document.getElementById('containerForm');
    const parentSelect = document.getElementById('parent_id');
    const moveButton = document.getElementById('moveButton');
    const deleteButton = document.getElementById('deleteButton');

    moveButton.addEventListener('click', function(event) {
        if (!parentSelect.value) {
            alert('Yerleştirileceği Rafı Seçin');
            event.preventDefault(); // Prevent form submission
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const addContainerForm = document.getElementById('addContainerForm');
    const typeSelect = document.getElementById('type');
    const parentSelect = document.getElementById('parent_id');
    const addNewButton = document.getElementById('addNewButton');

    addNewButton.addEventListener('click', function(event) {
        if (typeSelect.value === 'Koli' && !parentSelect.value) {
            alert('Kolinin yerleştirileceği Gemi/Rafı seçin');
            event.preventDefault();
        }
    }

    typeSelect.addEventListener('change', function() {
        if (typeSelect.value === 'Koli') {
            parentSelect.disabled = false;
        } else {
            parentSelect.disabled = true;
        }
    });
});
</script>

<?php

include '../_footer.php';

?>
