<?php

require_once('_login.php');
require_once('_init.php');

$influencers = $GLOBALS['pdo']->query("SELECT * FROM influencers")->fetchAll(PDO::FETCH_ASSOC);

function sitesExplode($sites) {
    $sites = json_decode($sites, true) ?? [];
    $retval = "";
    foreach ($sites as $site) {
        $retval .= "<a href='$site' target='_blank'>$site</a><br>";
    }
    return $retval;
}

include '_header.php';

?>
<div class="container">
    <div class="row">
        <div class="col-12">
            <h2 class="mt-5">Influencerlar</h2>
            <table class="table table-striped table-bordered mt-3">
                <thead>
                    <tr>
                        <th>Ekleyen</th>
                        <th>Name</th>
                        <th>URL</th>
                        <th>Takipçi</th>
                        <th>İlgili Siteler</th>
                        <th>Açıklama</th>
                        <th>Sil</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($influencers as $influencer): ?>
                        <tr>
                            <td><?= htmlspecialchars(username($influencer['user'])) ?><br><small><i><?= $influencer['created_at'] ?></i></small></td>
                            <td><?= htmlspecialchars($influencer['name']) ?></td>
                            <td><a href="<?= htmlspecialchars($influencer['url']) ?>" target="_blank"><?= htmlspecialchars($influencer['url']) ?></a></td>
                            <td><?= htmlspecialchars($influencer['follower']) ?></td>
                            <td><?= sitesExplode($influencer['websites']) ?></td>
                            <td><?= htmlspecialchars($influencer['description']) ?></td>
                            <td><a href="deleteInfluencer.php?id=<?= $influencer['id'] ?>" class="btn btn-danger">Sil</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<?

include '_footer.php';

