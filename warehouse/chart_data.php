<?php

require_once '/var/www/html/iwabot/_init.php';

$stmt = $GLOBALS['pdo']->prepare("
WITH RECURSIVE DateSeries AS (
    SELECT CURDATE() - INTERVAL 30 DAY AS date
    UNION ALL
    SELECT date + INTERVAL 1 DAY
    FROM DateSeries
    WHERE date + INTERVAL 1 DAY <= CURDATE()
)
SELECT
    ds.date,
    ws.item_id,
    COUNT(*) AS daily_sales
FROM
    DateSeries ds
LEFT JOIN
    warehouse_sold ws ON DATE(ws.created_at) = ds.date
WHERE
    ws.item_type = 'WarehouseProduct'
    AND ws.deleted_at IS NULL
GROUP BY
    ds.date, ws.item_id
ORDER BY
    ds.date, ws.item_id;
");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dates = [];
$items = [];
$data = [];

// Process the results
foreach ($results as $row) {
    $date = $row['date'];
    $item_id = $row['item_id'];
    $daily_sales = $row['daily_sales'];

    // Collect unique dates
    if (!in_array($date, $dates)) {
        $dates[] = $date;
    }

    // Collect unique item IDs
    if (!in_array($item_id, $items)) {
        $items[] = $item_id;
    }

    // Organize data by item and date
    if (!isset($data[$item_id])) {
        $data[$item_id] = array_fill(0, count($dates), 0);
    }
    $data[$item_id][array_search($date, $dates)] = $daily_sales;
}

// Prepare datasets for Chart.js
$datasets = [];
foreach ($items as $item_id) {
    $datasets[] = [
        'label' => "Item $item_id",
        'data' => $data[$item_id],
        'borderColor' => 'rgba(' . rand(0, 255) . ',' . rand(0, 255) . ',' . rand(0, 255) . ',1)',
        'fill' => false
    ];
}

// Prepare the final output
$chartData = [
    'labels' => $dates,
    'datasets' => $datasets
];

// Output the data as JSON
header('Content-Type: application/json');
echo json_encode($chartData);