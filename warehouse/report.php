<?php

require_once('warehouse.php');

$query = "
        SELECT
            p.name,
            p.category,
            p.fnsku,
            COALESCE(SUM(c.type = 'Koli' AND parent_c.type = 'Gemi' AND c.deleted_at IS NULL AND parent_c.deleted_at IS NULL), 0) AS count_in_ship,
            COALESCE(SUM((c.type = 'Raf' OR parent_c.type = 'Raf') AND c.deleted_at IS NULL AND parent_c.deleted_at IS NULL), 0) AS count_in_raf,
            COALESCE(SUM(
                (c.type = 'Koli' AND parent_c.type = 'Gemi' AND c.deleted_at IS NULL AND parent_c.deleted_at IS NULL) +
                ((c.type = 'Raf' OR parent_c.type = 'Raf') AND c.deleted_at IS NULL AND parent_c.deleted_at IS NULL)
            ), 0) AS total_count
        FROM
            warehouse_product p
        LEFT JOIN
            warehouse_container_product cp ON cp.product_id = p.id AND cp.deleted_at IS NULL
        LEFT JOIN
            warehouse_container c ON c.id = cp.container_id AND c.deleted_at IS NULL
        LEFT JOIN
            warehouse_container parent_c ON c.parent_id = parent_c.id AND parent_c.deleted_at IS NULL
        WHERE
            p.deleted_at IS NULL AND p.category <> 'Paket'
        GROUP BY
            p.id, p.name, p.category, p.fnsku
        ORDER BY
            total_count DESC";

// Check if the CSV download option is requested
if (isset($_GET['csv']) && $_GET['csv'] == 1) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=report.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Name', 'Category', 'FNSKU', 'Count in Ship', 'Count in Raf', 'Total Count']);
    
    $stmt = $GLOBALS['pdo']->query($query);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['name'], 
            $row['category'], 
            $row['fnsku'], 
            $row['count_in_ship'], 
            $row['count_in_raf'], 
            $row['total_count']
        ]);
    }
    
    fclose($output);
    exit();
}

if (!userCan(['view'])) {
    addMessage('Bu sayfaya erişim izniniz yok!', 'alert-danger');
    header('Location: ./');
    exit;
}

include '../_header.php';
?>

<div class="container mt-4">
    <h2>Product Report</h2>
    <div>
        <a href="?csv=1" class="btn btn-primary mb-3">Download CSV</a>
    </div>
    
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Category</th>
                <th>FNSKU</th>
                <th>Count in Ship</th>
                <th>Count in Raf</th>
                <th>Total Count</th>
            </tr>
        </thead>
        <tbody>
            <?php
            
            $stmt = $GLOBALS['pdo']->query($query);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                echo "<td>" . htmlspecialchars($row['fnsku']) . "</td>";
                echo "<td>" . htmlspecialchars($row['count_in_ship']) . "</td>";
                echo "<td>" . htmlspecialchars($row['count_in_raf']) . "</td>";
                echo "<td>" . htmlspecialchars($row['total_count']) . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php
include '../_footer.php';
?>
