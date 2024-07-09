<?php

require_once('warehouse.php');

include '../_header.php';

loadPermissions();

$channelList = slackChannels();
$userList = slackUsers();

$permissionList = [
    'manage' => 'IWA Depo Yönetme',
    'order' => 'Sipariş Oluşturma',
    'process' => 'Depo İşletme',
];

?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1>Kullanıcı Yetkilendirme</h1>
        <p>Yetkilendirme detaylarını görüntüleyin. Depo Ana Menü için <a href="./">buraya basınız.</a></p>
    </div>
    <div class="accordion mb-3" id="mainAccordion">

        <!-- First Main Accordion Item -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMain1">
                <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#userAccordion1" aria-expanded="false" aria-controls="userAccordion1">
                    <span><strong>Envanter Görmeye Yetkili Slack Kanalları</strong></span>
                </button>
            </h2>
            <div id="userAccordion1" class="accordion-collapse collapse" aria-labelledby="headingMain1" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-5">
                    <form action="controller.php" method="post">
                        <input type="hidden" name="action" value="permission_view_add">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-3">
                            <label for="inventoryChannels" class="form-label">Yetkilendirilecek Slack Kanalları</label>
                            <select id="inventoryChannels" name="target_id[]" multiple="multiple" class="select2-select form-select w-100" style="width: 100%;" required>
                                <?php foreach ($channelList as $key => $channel): ?>
                                    <?php if (in_array($key, $GLOBALS['permissions']['view_channels'])) continue; ?>
                                    <option value="<?= trim(htmlspecialchars($key)) ?>"><?= trim(htmlspecialchars($channel)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3 mt-2">Envanter Görme Yetkisi Ver</button>
                    </form>
                    <hr>
                    <h3>Envanter Görmeye Yetkili Kişiler</h3>
                    <div id="inventoryPersonnelList">
                        <ul>
                        <?php foreach ($GLOBALS['permissions']['view'] as $user_id): ?>
                            <li><?= htmlspecialchars($userList[$user_id]) ?></li>                            
                        <?php endforeach; ?>
                        <?php if (empty($GLOBALS['permissions']['view'])): ?>
                            <li><i>Envanter görmeye yetkili personel bulunmamaktadır.</i></li>
                        <?php endif; ?>
                        </ul>
                    </div>
                    <h3>Envanter Görmeye Yetkili Kanallar</h3>
                    <div id="inventoryChannelList">
                        <?php foreach ($GLOBALS['permissions']['view_channels'] as $channel_id): ?>
                            <span class="badge bg-secondary me-2">
                                <?= htmlspecialchars($channelList[$channel_id]) ?>
                                <button type="button" class="btn-close btn-close-white ms-2" aria-label="Close" onclick="removePermission('view', <?= htmlspecialchars($channel_id) ?>)"></button>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <?php if (empty($GLOBALS['permissions']['view_channels'])): ?>
                        <p>Envanter görmeye yetkili kanal bulunmamaktadır.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php foreach (array_keys($permissionList) as $permType): ?>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingMain<?= $permType ?>">
                    <button class="accordion-button bg-success text-white collapsed w-100 py-3" data-bs-toggle="collapse" data-bs-target="#userAccordion<?= $permType ?>" aria-expanded="false" aria-controls="userAccordion<?= $permType ?>">
                        <span><strong><?= $permissionList[$permType] ?> Yetkili Personel</strong></span>
                    </button>
                </h2>
                <div id="userAccordion<?= $permType ?>" class="accordion-collapse collapse" aria-labelledby="headingMain<?= $permType ?>" data-bs-parent="#mainAccordion">
                    <div class="accordion-body p-5">
                        <form action="controller.php" method="post">
                            <input type="hidden" name="action" value="permission_<?= $permType ?>_add">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <div class="mb-3">
                                <label for="<?= $permType ?>Personnel" class="form-label">Personel Seçin</label>
                                <select id="<?= $permType ?>Personnel" name="target_id[]" class="select2-select form-select w-100" required style="width: 100%;"  multiple>
                                    <?php foreach ($userList as $key => $user): ?>
                                        <?php if (in_array($key, $GLOBALS['permissions'][$permType])) continue; ?>
                                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($user) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-3 mt-2"><?= $permissionList[$permType] ?> Yetkisi Ver</button>
                        </form>
                        <hr>
                        <h3><?= $permissionList[$permType] ?> Yetkili Kişiler</h3>
                        <div id="<?= $permType ?>PersonnelList">
                            <?php foreach ($GLOBALS['permissions'][$permType] as $user_id): ?>
                                <span id="<?= $permType ?>UserSpan" class="badge bg-secondary me-2">
                                    <?= htmlspecialchars($userList[$user_id]) ?>
                                    <button type="button" class="btn-close btn-close-white ms-2" aria-label="Close" onclick="removePermission('<?= $permType ?>', <?= htmlspecialchars($user_id) ?>)"></button>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <?php if (empty($GLOBALS['permissions'][$permType])): ?>
                            <p><?= $permissionList[$permType] ?> yetkili personel bulunmamaktadır.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>


    </div>

    <hr>

    <?= wh_menu() ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<script>
    $(document).ready(function() {
        $('#inventoryChannels').select2();
        $('#managePersonnel').select2();
        $('#orderPersonnel').select2();
        $('#processPersonnel').select2();
    });

    function removePermission(type, id) {
        let action;
        switch (type) {
            case 'view':
                action = 'permission_view_remove';
                break;
            case 'manage':
                action = 'permission_manage_remove';
                break;
            case 'order':
                action = 'permission_order_remove';
                break;
            case 'process':
                action = 'permission_process_remove';
                break;
            default:
                return;
        }

        $.post('controller.php', { action: action, target_id: id }, function(response) {
            if (response.ok) {
                $(`button[onclick="removePermission('${type}', ${id})"]`).parent().remove();
            } else {
                alert('Error removing permission');
            }
        }, 'json');
    }
</script>

<?php

include '../_footer.php';

?>
