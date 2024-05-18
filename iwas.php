<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once('_slack.php');
    require_once('_db.php');
    
    $user = $_POST['user_id'] ?? '';
    $text = $_POST['text'] ?? '';
    
    $url = substr($text, 0, strpos($text, ' '));
    $description = substr($text, strpos($text, ' ') + 1, 255);
    
    if (empty($user) || empty($url) || empty($description) || !filter_var($url, FILTER_VALIDATE_URL)) {
        die('Please provide a valid URL and description: /iwas <URL> <description> <#hashtags>');
    }
    
    $stmt = $GLOBALS['pdo']->prepare('INSERT INTO audio (user, url, description) VALUES (?, ?, ?)');
    $stmt->execute([$user, $url, $description]);
    
    echo 'Added to IWA URL library. You can access it at https://iwarden.iwaconcept.com/iwabot/iwas.php';
    exit;
}

require_once('_login.php');
require_once('_init.php');

$sql = 'SELECT * FROM audio ORDER BY id ASC';
$stmt = $GLOBALS['pdo']->prepare($sql);
$stmt->execute();
$audio = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hashTags = [];
foreach ($audio as $key=>$a) {
    $audio[$key]['hash_tags'] = '';
    preg_match_all('/#(\w+)/', $a['description'], $matches);
    if (empty($matches[1])) $matches[1] = ['_no_tag_'];
    foreach ($matches[1] as $tag) {
        if (!isset($hashTags[$tag])) {
            $hashTags[$tag] = 0;
        }
        $hashTags[$tag]++;
        $audio[$key]['hash_tags'] .= "#$tag ";
        $audio[$key]['description'] = trim(str_replace('#'.$tag, '', $audio[$key]['description']));
    }    
}

include '_header.php';
?>

<div class="container mt-5">
    <a href="./">Home Page</a>
    <div class="jumbotron m-5 p-5">
        <center>
            <h1>IWA URL Library</h1>
            <p>Click the links to open in new window.</p>
        </center>
    </div>
    <div class="row">
        <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search in all columns...">
        <div>
            <?php ksort($hashTags); ?>
            <?php foreach ($hashTags as $tag => $count): ?>
                <input type="checkbox" class="hashtag-toggle" data-tag="<?= $tag ?>" data-toggle="toggle" data-onlabel="#<?= $tag ?>" data-offlabel="#<?= $tag ?>" data-onstyle="success" data-offstyle="danger">
            <?php endforeach; ?>
        </div>
        <table class="table" id="dataTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">User</th>
                    <th onclick="sortTable(1)">Description</th>
                    <th onclick="sortTable(2)" style="max-width: 300px;">URL</th>
                    <th>Hash Tags</th>
                </tr>
            </thead>
            <tbody id="dataBody">
                <?php foreach ($audio as $a): ?>
                <tr>
                    <td><?= htmlspecialchars(username($a['user'])) ?><br><small><i><?= $a['created_at'] ?></i></small></td>
                    <td><?= htmlspecialchars($a['description']) ?></td>
                    <td  style="max-width: 300px;overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><a href="<?= $a['url'] ?>" target="_blank"><?= htmlspecialchars($a['url']) ?></a></td>
                    <td><?= $a['hash_tags'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('searchInput');
    var toggles = document.querySelectorAll('.hashtag-toggle');

    function filterRows() {
        console.log('filter triggered');
        var activeTags = Array.from(toggles).filter(toggle => toggle.checked).map(toggle => toggle.getAttribute('data-tag').toLowerCase());
        var searchQuery = searchInput.value.toLowerCase();

        document.querySelectorAll('#dataBody tr').forEach(function(row) {
            var description = row.cells[3].textContent.toLowerCase();
            var textMatch = searchQuery === '' || Array.from(row.getElementsByTagName('td')).some(cell => cell.textContent.toLowerCase().includes(searchQuery));
            var tagMatch = activeTags.length === 0 || activeTags.every(tag => description.includes('#' + tag));

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
