<?php

require_once('_login.php');
require_once('_init.php');

if (!userInChannel($_SESSION['user_id'], 'C072LD7FQ12')) {
    addMessage('Bu sayfayı görüntülemek için yetkiniz yok.', 'danger');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && is_numeric($_POST['id'])) {
    $stmt = $GLOBALS['pdo']->prepare("DELETE FROM influencers WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    addMessage('Influencer silindi.');
    header('Location: iwainfluencers.php');
    exit;
}

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
<div class="container mt-5">
<a href="./">Ana Sayfa</a>
    <div class="jumbotron m-5 p-5">
        <center>
            <h1>IWA Influencer Listesi</h1>
            <p>Tıkladığınızda yeni pencerede açılır.</p>
        </center>
    </div>
    <div class="row">
        <input type="text" id="searchInput" class="form-control mb-3" placeholder="Tüm sütunlarda ara...">
        <table class="table table-striped" id="dataTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">Ekleyen</th>
                    <th onclick="sortTable(1)">Influencer</th>
                    <th onclick="sortTable(2)">URL</th>
                    <th onclick="sortTable(3)">Takipçi</th>
                    <th>İlgili Siteler</th>
                    <th onclick="sortTable(4)">Açıklama</th>
                    <th>Sil</th>
                </tr>
            </thead>
            <tbody id="dataBody">
                <?php foreach ($influencers as $influencer): ?>
                    <tr>
                        <td><?= htmlspecialchars(username($influencer['user'])) ?><br><small><i><?= $influencer['created_at'] ?></i></small></td>
                        <td><?= htmlspecialchars($influencer['name']) ?></td>
                        <td style="max-width: 300px;overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><a href="<?= htmlspecialchars($influencer['url']) ?>" target="_blank"><?= htmlspecialchars($influencer['url']) ?></a></td>
                        <td><?= htmlspecialchars($influencer['follower']) ?></td>
                        <td><?= sitesExplode($influencer['websites']) ?></td>
                        <td><?= htmlspecialchars($influencer['description']) ?></td>
                        <td>
                            <form id="delete-form-<?= $influencer['id'] ?>" action="iwainfluencers.php" method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $influencer['id'] ?>">
                                <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $influencer['id'] ?>)">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Emin misiniz?')) {
        document.getElementById('delete-form-' + id).submit();
    }
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('searchInput');

    function filterRows() {
        var searchQuery = searchInput.value.toLowerCase();

        document.querySelectorAll('#dataBody tr').forEach(function(row) {
            var textMatch = Array.from(row.getElementsByTagName('td')).some(cell => cell.textContent.toLowerCase().includes(searchQuery));
            row.style.display = textMatch ? '' : 'none';
        });
    }
    searchInput.addEventListener('input', filterRows);
});

function sortTable(columnIndex) {
    var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    table = document.getElementById("dataTable");
    switching = true;
    dir = "asc"; 
    while (switching) {
        switching = false;
        rows = table.rows;
        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("TD")[columnIndex];
            y = rows[i + 1].getElementsByTagName("TD")[columnIndex];
            if (dir == "asc") {
                if (x.textContent.toLowerCase() > y.textContent.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            } else if (dir == "desc") {
                if (x.textContent.toLowerCase() < y.textContent.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            }
        }
        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchcount++;
        } else {
            if (switchcount == 0 && dir == "asc") {
                dir = "desc";
                switching = true;
            }
        }
    }
}
</script>


<?php

include '_footer.php';

