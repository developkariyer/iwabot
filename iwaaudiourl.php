<?php

require_once('_login.php');
require_once('_init.php');

if (!canViewPage('app_cm')) {
    addMessage('Bu sayfayı görüntülemek için yetkiniz yok.', 'danger');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && is_numeric($_POST['id'])) {
    $stmt = $GLOBALS['pdo']->prepare("DELETE FROM audio WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    addMessage('Audio URL silindi.');
    header('Location: iwaaudiourl.php');
    exit;
}

$audio = $GLOBALS['pdo']->query('SELECT * FROM audio ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);

$hashTags = [];
foreach ($audio as $key=>$a) {
    $audio[$key]['hash_tags'] = '';
    $hashtagjson = json_decode($a['hashtags'], true) ?? [];
    foreach ($hashtagjson as $tag) {
        if (!isset($hashTags[$tag])) {
            $hashTags[$tag] = 0;
        }
        $hashTags[$tag]++;
        $audio[$key]['hash_tags'] .= "$tag ";
    }
}

include '_header.php';
?>

<div class="container mt-5">
    <a href="./">Ana Sayfa</a>
    <div class="jumbotron m-5 p-5">
        <center>
            <h1>IWA Audio URL Kütüphanesi</h1>
            <p>Tıkladığınızda yeni pencerede açılır.</p>
        </center>
    </div>
    <div class="row">
        <input type="text" id="searchInput" class="form-control mb-3" placeholder="Tüm sütunlarda ara...">
        <div>
            <?php ksort($hashTags); ?>
            <?php foreach ($hashTags as $tag => $count): ?>
                <input type="checkbox" class="hashtag-toggle" data-tag="<?= $tag ?>" data-toggle="toggle" data-onlabel="<?= $tag ?>" data-offlabel="<?= $tag ?>" data-onstyle="success" data-offstyle="danger">
            <?php endforeach; ?>
        </div>
        <table class="table table-striped" id="dataTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">Ekleyen</th>
                    <th onclick="sortTable(2)" style="max-width: 300px;">URL</th>
                    <th onclick="sortTable(1)">Açıklama</th>
                    <th>Hash Tag</th>
                    <th>Sil</th>
                </tr>
            </thead>
            <tbody id="dataBody">
                <?php foreach ($audio as $a): ?>
                <tr>
                    <td><?= htmlspecialchars(username($a['user'])) ?><br><small><i><?= $a['created_at'] ?></i></small></td>
                    <td style="max-width: 300px;overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><a href="<?= $a['url'] ?>" target="_blank"><?= htmlspecialchars($a['url']) ?></a></td>
                    <td><?= htmlspecialchars($a['description']) ?></td>
                    <td><?= $a['hash_tags'] ?></td>
                    <td>
                        <form id="delete-form-<?= $a['id'] ?>" action="iwaaudiourl.php" method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $a['id'] ?>)">Sil</button>
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
    var toggles = document.querySelectorAll('.hashtag-toggle');

    function filterRows() {
        var activeTags = Array.from(toggles).filter(toggle => toggle.checked).map(toggle => toggle.getAttribute('data-tag').toLowerCase());
        var searchQuery = searchInput.value.toLowerCase();

        document.querySelectorAll('#dataBody tr').forEach(function(row) {
            var description = row.cells[3].textContent.toLowerCase();
            var textMatch = searchQuery === '' || Array.from(row.getElementsByTagName('td')).some(cell => cell.textContent.toLowerCase().includes(searchQuery));
            var tagMatch = activeTags.length === 0 || activeTags.every(tag => description.includes(tag));

            row.style.display = (textMatch && tagMatch) ? '' : 'none';
        });
    }
    searchInput.addEventListener('input', filterRows);
    toggles.forEach(toggle => {
        toggle.addEventListener('change', filterRows);
    });
    $('.hashtag-toggle').change(filterRows);
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
