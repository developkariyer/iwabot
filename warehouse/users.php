<?php

require_once 'warehouse.php';

if (!userCan(['manage'])) {
    addMessage('Bu sayfaya erişim izniniz yok!', 'alert-danger');
    header('Location: ./');
    exit;
}

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
                            <form action="controller.php" method="post" style="display:inline;">
                                <input type="hidden" name="action" value="permission_view_remove">
                                <input type="hidden" name="target_id" value="<?= htmlspecialchars($channel_id) ?>">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <span class="badge bg-secondary me-2">
                                    <?= htmlspecialchars($channelList[$channel_id]) ?>
                                    <button type="submit" class="btn-close btn-close-white ms-2" aria-label="Close"></button>
                                </span>
                            </form>
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
                                <form action="controller.php" method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="permission_<?= $permType ?>_remove">
                                    <input type="hidden" name="target_id" value="<?= htmlspecialchars($user_id) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <span class="badge bg-secondary me-2 mb-1">
                                        <?= htmlspecialchars($userList[$user_id]) ?>
                                        <button type="submit" class="btn-close btn-close-white ms-2" aria-label="Close"></button>
                                    </span>
                                </form>
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

<script>
    $(document).ready(function() {
        $('#inventoryChannels').select2();
        $('#managePersonnel').select2();
        $('#orderPersonnel').select2();
        $('#processPersonnel').select2();
    });
</script>

<?php

include '../_footer.php';

?>
