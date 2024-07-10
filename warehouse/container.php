<?php

require_once('warehouse.php');

if (!userCan(['process'])) {
    addMessage('Bu sayfaya erişim izniniz yok!', 'alert-danger');
    header('Location: ./');
    exit;
}

$icon = [
    'Gemi' => '🚢', //\u{1F6A2}
    'Raf' => '🗄️', // \u{1F5C4}
    'Koli' => '📦', //\u{1F4E6}
];

$unfulfilledBoxes = WarehouseSold::getSoldItems(item_type: 'WarehouseContainer');

include '../_header.php';

?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>Gemi/Roli/Raf İşlemleri</h1>
        <p>Depo Ana Menü için <a href="./">buraya basınız.</a></p>
    </div>
    <div class="accordion mb-3" id="mainAccordion">

        <!-- First Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain4">
                <button class="accordion-button bg-success text-white  w-100 py-3" data-bs-toggle="collapse" data-bs-target="#orderAccordion4" aria-expanded="true" aria-controls="orderAccordion4">
                    <span><strong>İşlem Bekleyen Koliler</strong></span>
                </button>
            </h2>
            <div id="orderAccordion4" class="accordion-collapse collapse show" aria-labelledby="headingMain4" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <?php foreach ($unfulfilledBoxes as $index => $soldItem): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingBox<?= $index ?>">
                                <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBox<?= $index ?>" aria-expanded="false" aria-controls="collapseBox<?= $index ?>">
                                    <span><strong><?= htmlspecialchars($soldItem->object->name) ?></strong></span>
                                </button>
                            </h2>
                            <div id="collapseBox<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="headingBox<?= $index ?>" data-bs-parent="#orderAccordion4">
                                <div class="accordion-body">
                                    <form action="controller.php" method="post">
                                        <input type="hidden" name="action" value="fulfil_box">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="sold_id" value="<?= htmlspecialchars($soldItem->id) ?>">
                                        <div class="mb-3">
                                            <p>
                                                <?= containerInfo($soldItem->object) ?>
                                            </p>
                                            <p>
                                                <strong>Açıklama:</strong><br>
                                                <?= nl2br(htmlspecialchars($soldItem->description)) ?>
                                            </p>
                                            <label for="container_id_<?= $index ?>" class="form-label">Koli Seçin</label>
                                            <select id="container_id_<?= $index ?>" name="container_id" class="form-select" required>
                                                <option value="">Çıkış Yapılacak Koli Seçin</option>
                                                <optgroup label="Bu Koli">
                                                    <option value="<?= htmlspecialchars($soldItem->object->id) ?>"><?= htmlspecialchars($soldItem->object->name) ?></option>
                                                </optgroup>
                                                <optgroup label="Aynı İçerikli Koliler">
                                                    <?php foreach ($soldItem->object->findSimilar() as $sameContainer): ?>
                                                        <option value="<?= htmlspecialchars($sameContainer->id) ?>"><?= htmlspecialchars($sameContainer->name) ?> (<?= htmlspecialchars($sameContainer->parent->name) ?>)</option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100 py-3 mt-2">Koli Çıkışını Tamamla</button>
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



        <!-- Second Main Accordion Item -->
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
                            <label for="accordion1container_id" class="form-label">Koli Seçin</label>
                            <select id="accordion1container_id" name="container_id[]" multiple="multiple" class="select2-select form-select w-100" style="width: 100%;">
                                <option value="">Gemiden Koli Seçin</option>
                                <?= containersInOpt(type: 'Gemi') ?>
                            </select>
                        </div><!--
                        <div class="mb-3">
                            <label for="tempTextArea" class="form-label">Toplu Koli Girişi (geçici çözüm)</label>
                            <textarea id="tempTextArea" name="tempTextArea" class="form-control" rows="10" placeholder="Koli ID'lerini virgülle veya yeni satır ile ayırarak yapıştırın"></textarea>
                        </div>-->
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Raf Seçin</label>
                            <select id="accordion1parent_id" name="parent_id" class="form-select" required>
                                <option value="">Yerleştirileceği Rafı Seçin</option>
                                <?= parentContainersOpt() ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3 mt-2">Gemiden Rafa Taşı</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Third Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain2">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#containerAccordion2" aria-expanded="false" aria-controls="containerAccordion2">
                    <span><strong>Depodaki Koliler</strong></span>
                </button>
            </h2>
            <div id="containerAccordion2" class="accordion-collapse collapse" aria-labelledby="headingMain2" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <form action="controller.php" method="POST" id="accordion2containerForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-3">
                            <label for="accordion2container_id" class="form-label">Koli Seçin</label>
                            <select id="accordion2container_id" name="container_id[]"  multiple="multiple" class="select2-select form-select w-100" required style="width: 100%;" required>
                                <option value="">Koli Seçin</option>
                                <?= containersInOpt('Raf') ?>
                            </select>
                        </div>
                        <div id="containerInfo" class="p-3"></div>
                        <div class="mb-3">
                            <label for="accordion2parent_id" class="form-label">Yerleştirileceği Rafı Seçin</label>
                            <select id="accordion2parent_id" name="parent_id" class="form-select">
                                <option value="">Yerleştirileceği Rafı Seçin</option>
                                <?= parentContainersOpt() ?>
                            </select>
                        </div>
                        <button type="submit" name="action" value="set_parent" class="btn btn-primary w-100 py-3 mt-2" id="accordion2moveButton">Raftan Rafa Taşı</button>
                        <button type="submit" name="action" value="flush_box" class="btn btn-primary w-100 py-3 mt-2" id="accordion2moveButton">Ürünlerin Hepsini Rafa Çıkar</button>
                        <button type="submit" name="action" value="delete_container" class="btn btn-danger w-100 py-3 mt-2" id="accordion2deleteButton">Koli Sil</button>
                    </form>
                </div>
            </div>
        </div>


        <!-- Fourth Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain5">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#containerAccordion5" aria-expanded="false" aria-controls="containerAccordion5">
                    <span><strong>Kendiniz Koli Seçin</strong></span>
                </button>
            </h2>
            <div id="containerAccordion5" class="accordion-collapse collapse" aria-labelledby="headingMain5" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5 w-100">
                    <select id="accordion5dynamiccontainer" class="select2-select form-select w-100" style="width: 100%;">
                        <option value="">Koli Seçin</option>
                        <optgroup label="Depodaki Koliler">
                            <?= containersInOpt('Raf') ?>
                        </optgroup>
                        <optgroup label="Gemideki Koliler">
                            <?= containersInOpt('Gemi') ?>
                        </optgroup>                            
                    </select>
                    <div id="selectedContainer" class="d-none">
                        <div class="p-3" id="container_info"></div>
                        <form id="customActionForm" action="controller.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="set_parent">
                            <input type="hidden" name="container_id" id="selectedContainerId">
                            <div class="mb-3">
                                <label for="parent_id" class="form-label">Yerleştirileceği Rafı Seçin</label>
                                <select id="accordion5parent_id" name="parent_id" class="form-select">
                                    <option value="">Yerleştirileceği Rafı Seçin</option>
                                    <?= parentContainersOpt() ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-3 mt-2">Raftan Rafa Taşı</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <!-- Fifth Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain3">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#containerAccordion3" aria-expanded="false" aria-controls="containerAccordion3">
                    <span><strong>Depoya Yeni Raf Ekleyin</strong></span>
                </button>
            </h2>
            <div id="containerAccordion3" class="accordion-collapse collapse" aria-labelledby="headingMain3" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5 w-100">
                    <form action="controller.php" method="POST" id="accordion3addContainerForm">
                        <input type="hidden" name="action" value="add_container">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-3">
                            <label for="accordion3name" class="form-label">Adı</label>
                            <input id="accordion3name" type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="accordion3type" class="form-label">Tip</label>
                            <select id="accordion3type" name="type" class="form-select" required>
                                <option value="">Tip Seçin</option>
                                <option value="Gemi"><?= $icon['Gemi'] ?> Gemi</option>
                                <option value="Raf"><?= $icon['Raf'] ?> Raf</option>
                                <option value="Koli"><?= $icon['Koli'] ?> Koli</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="accordion3parent_id" class="form-label">Yerleştirileceği Konum</label>
                            <select id="accordion3parent_id" name="parent_id" class="form-select" disabled>
                                <option value="">Yerleştirileceği Konumu Seçin</option>
                                <?= parentContainersOpt('Raf') ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3 mt-2" id="accordion3addNewButton">Yeni Ekle</button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <hr>

    <?= wh_menu() ?>
</div>

<script defer>

$(document).ready(function() {
    $('#accordion5dynamiccontainer').on('change', function() {
        var containerId = $(this).val();
        if (containerId) {
            $.ajax({
                url: 'controller.php',
                method: 'POST',
                data: { container_id: containerId , action: 'container_info', csrf_token: '<?= $_SESSION['csrf_token'] ?>'},
                success: function(response) {
                    $('#container_info').html(response.info);
                    $('#selectedContainer').removeClass('d-none');
                    $('#selectedContainerId').val(response.id);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching product information:', error);
                }
            });
        } else {
            $('#selectedContainer').addClass('d-none');
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const containerSelect = document.getElementById('accordion2container_id');
    const containerInfoDiv = document.getElementById('containerInfo');

    containerSelect.addEventListener('change', function() {
        const containerId = containerSelect.value;
        if (containerId) {
            fetch(`controller.php?action=container_info&container_id=${containerId}`)
                .then(response => response.json())
                .then(data => {
                    containerInfoDiv.innerHTML = data.info;
                })
                .catch(error => console.error('Error fetching container info:', error));
        } else {
            containerInfoDiv.innerHTML = '';
        }
    });

    const containerForm = document.getElementById('accordion2containerForm');
    const a2parentSelect = document.getElementById('accordion2parent_id');
    const moveButton = document.getElementById('accordion2moveButton');
    const deleteButton = document.getElementById('accordion2deleteButton');

    moveButton.addEventListener('click', function(event) {
        if (!a2parentSelect.value) {
            alert('Yerleştirileceği Rafı Seçin');
            event.preventDefault();
        }
    });

    const addContainerForm = document.getElementById('accordion3addContainerForm');
    const typeSelect = document.getElementById('accordion3type');
    const a3parentSelect = document.getElementById('accordion3parent_id');
    const addNewButton = document.getElementById('accordion3addNewButton');

    addNewButton.addEventListener('click', function(event) {
        if (typeSelect.value === 'Koli' && !a3parentSelect.value) {
            alert('Kolinin yerleştirileceği Gemi/Rafı seçin');
            event.preventDefault();
        }
    });

    typeSelect.addEventListener('change', function() {
        if (typeSelect.value === 'Koli') {
            a3parentSelect.disabled = false;
        } else {
            a3parentSelect.disabled = true;
        }
    });
});

$(document).ready(function(){$('#accordion1container_id').select2({theme: "classic"});});
$(document).ready(function(){$('#accordion2container_id').select2({theme: "classic"});});
$(document).ready(function(){$('#accordion5dynamiccontainer').select2({theme: "classic"});});




</script>

<?php

include '../_footer.php';

?>
