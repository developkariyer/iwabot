<?php

// if not called by cronjob exit
if (php_sapi_name() !== 'cli') exit;

require_once('_init.php');

openProjectUpdateUsers();
openProjectUpdateGroups();
openProjectUpdateWorkPackages();

$dailyReport = [];

$workPackages = $GLOBALS['pdo']->query('SELECT * FROM op_workpackages')->fetchAll(PDO::FETCH_ASSOC);
foreach ($workPackages as $workPackage) {
    $json = json_decode($workPackage['json'], true);
    if ($json['_embedded']['status']['isClosed']) continue;
    if (empty($json['_embedded']['assignee'])) continue;
    $wpId = $json['id'];
    $wpDate = $json['date'] ?? 'Tarih yok';
    $wpSubject = $json['subject'] ?? 'Konu yok';
    $wpStatus = $json['_embedded']['status']['name'] ?? 'Durum yok';
    $wpText = "<https://op.iwaconcept.com/work_packages/{$wpId}|{$wpDate}: {$wpSubject} ({$wpId}/{$wpStatus})>";

    $wpMembers = [];
    if ($json['_embedded']['assignee']['_type'] === 'Group') {
        $members = $json['_embedded']['assignee']['_links']['members'] ?? [];
        foreach ($members as $member) {
            $wpMembers[] = explode("/", $member['href'])[4];
        }
        $wpText.= " ({$json['_embedded']['assignee']['name']} üyeliğiniz sebebiyle)";
    } else {
        $wpMembers[] = $json['_embedded']['assignee']['id'];
    }

    foreach ($wpMembers as $wpMember) {
        if (empty($dailyReport[$wpMember])) $dailyReport[$wpMember] = [];
        $dailyReport[$wpMember][] = $wpText;
    }
}

foreach ($dailyReport as $userId => $urls) {
    if (empty($urls)) continue;

    $userName = $GLOBALS['opUsers'][$userId]['name'] ?? '';
    $slackId = userEmailToId($GLOBALS['opUsers'][$userId]['email'] ?? '');
    

    $msg = "Merhaba <@$userName>,\n\n";
    $msg.= "Bugün itibarı ile devam eden içerik paketlerinizin özeti aşağıda sunulmuştur.\n\nKolay gelsin.\n\n";
    $msg.= "- " . implode("\n- ", $urls) . "\n";
    messageChannel($slackId, $msg);
}


