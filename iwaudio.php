<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once('_slack.php');
    require_once('_db.php');
    
    $user = $_POST['user_id'] ?? '';
    $text = $_POST['text'] ?? '';
    
    $url = substr($text, 0, strpos($text, ' '));
    $description = substr($text, strpos($text, ' ') + 1, 255);
    
    if (empty($user) || empty($url) || empty($description) || !filter_var($url, FILTER_VALIDATE_URL)) {
        die('Please provide a valid URL and description: /iwaudio <URL> <description>');
    }
    
    $stmt = $GLOBALS['pdo']->prepare('INSERT INTO audio (user, url, description) VALUES (?, ?, ?)');
    $stmt->execute([$user, $url, $description]);
    
    echo 'Added to Audio library. You can access it at https://iwarden.iwaconcept.com/iwabot/';
    exit;
}

require_once('_login.php');
require_once('_db.php');
require_once('_slack.php');

$sql = 'SELECT * FROM audio ORDER BY id ASC';
$stmt = $GLOBALS['pdo']->prepare($sql);
$stmt->execute();
$audio = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '_header.php';
?>

<div class="container mt-5">
    <a href="./">Home Page</a>
    <div class="jumbotron m-5 p-5">
        <center>
            <h1>Audio Library</h1>
            <p>Click the links to open in new window.</p>
        </center>
    </div>
    <div class="row">
        <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search in all columns...">

        <table class="table" id="dataTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">User</th>
                    <th onclick="sortTable(1)">Description</th>
                    <th onclick="sortTable(2)">URL</th>
                </tr>
            </thead>
            <tbody id="dataBody">
                <?php foreach ($audio as $a): ?>
                <tr>
                    <td><?= htmlspecialchars(username($a['user'])) ?><br><small><i><?= $a['created_at'] ?></i></small></td>
                    <td><?= htmlspecialchars($a['description']) ?></td>
                    <td><a href="<?= $a['url'] ?>" target="_blank"><?= htmlspecialchars($a['url']) ?></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('input', function() {
    var searchQuery = this.value.toLowerCase();
    var rows = document.querySelectorAll('#dataBody tr');

    rows.forEach(function(row) {
        row.style.display = 'none';
        var cells = row.getElementsByTagName('td');
        for (var i = 0; i < cells.length; i++) {
            if (cells[i].textContent.toLowerCase().indexOf(searchQuery) > -1) {
                row.style.display = '';
                break;
            }
        }
    });
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
