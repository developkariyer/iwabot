<?php

require_once('_init.php');

$msgToChannel = 'C074PP75YM6'; // this is live channel
//$msgToChannel = 'C072ZHN5YUV'; // this is test channel


$wpList = $GLOBALS['pdo']->query('SELECT 
        json->>\'$.work_package.subject\' AS wp_subject, 
        json->>\'$.work_package.date\' AS wp_date, 
        json->>\'$.work_package._embedded.status.name\' AS wp_status,
        user_id, 
        wp_id 
    FROM op_workpackages 
    WHERE 
        json->>\'$.work_package._embedded.status.isClosed\' = "false" 
    ORDER BY wp_date ASC')->fetchAll(PDO::FETCH_ASSOC);

$msgArray = [];
foreach ($wpList as $wp) {
    $wp['wp_date'] ??= 'Tarih yok';
    $url = "<https://op.iwaconcept.com/work_packages/{$wp['wp_id']}|{$wp['wp_date']}: {$wp['wp_subject']} ({$wp['wp_id']}/{$wp['wp_status']})>";
    $user = userEmailToId($wp['user_id']) ?? 'Atanmamış';
    if (empty($msgArray["<@$user>"])) $msgArray["<@$user>"] = [];
    $msgArray["<@$user>"][] = $url;
}

$msg = date('d.m.Y')." sabahı itibarı ile devam eden içerik paketlerinin özet durumu:\n";
foreach ($msgArray as $user => $urls) {
    $msg .= "*$user:*\n- " . implode("\n- ", $urls) . "\n";
}

messageChannel($msgToChannel, $msg);
