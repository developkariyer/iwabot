<?php

require 'iwai/vendor/autoload.php';
use OpenAI;

function aiResponse($prompt) {

    $yourApiKey = 'sk-proj-dCYw6lxW1gqltMCT9YPVT3BlbkFJbbfND9MgKuPs0uWFPhPz';
    
    $client = OpenAI::client($yourApiKey);

    $response = $client->chat()->create([
        'model' => 'gpt-4o',
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ],
    ]);
    
    return print_r($response->choices[0]->message->content, true);
}

require_once('_init.php');

$interval = date('N') === '1' ? 259200 : 86400;

$sql = "SELECT
            json->>'$.event.text' AS text,
            json->>'$.event.user' AS user,
            json->>'$.event.channel' AS channel,
            json->>'$.event_time' AS event_time,
            json->>'$.event.files' AS files
        FROM rawlog
        WHERE 
            json->>'$.event.type' = 'message'
            AND json->>'$.event_time' > (UNIX_TIMESTAMP() - $interval)
        ORDER BY channel, event_time
";

$textDb = $GLOBALS['pdo']->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$jsonlog = [];
foreach ($textDb as $text) {
    $channel = '#'.channelIdToName($text['channel']);
    if ($channel === '#general') continue;

    $files = json_decode($text['files'], true) ?? [];
    $msg = slackize($text['text']);
    foreach ($files as $file) {
        $msg .= "<br>".previewFile($file);
    }

    if (!isset($jsonlog[$channel])) {
        $jsonlog[$channel] = [];
    }
    $jsonlog[$channel][date('Y.m.d H:i:s', $text['event_time'])] = [
        'user' => '@'.username($text['user']),
        'text' => $msg,
    ];
}

$json = json_encode($jsonlog);

$t = $GLOBALS['pdo']->query("SELECT created_at FROM slack_summary WHERE created_at > (NOW() - INTERVAL 23 HOUR - INTERVAL 50 MINUTE)")->fetchColumn();
if ($t) exit;

$previousReport = $GLOBALS['pdo']->query('SELECT report FROM slack_summary ORDER BY id DESC LIMIT 1')->fetchColumn();


$basePrompt = "Verilen bilgileri kullanarak aşağıdaki [Format] doğrultusunda bir yönetici özeti hazırla.
Eğer json bölümünde, formatta olan bir başlık ile ilgili bilgi bulamazsan boş bırak.
Formattaki parantezler sana yol göstermek içindir, nihai raporda onları gösterme.
Her konunun ilgilisinin ismini parantez içinde belirt.
Yorum yapma, kısa cümleler kullan ve mümkün olan her yerde maddeler halinde yaz.
[Önceki Rapor] sana context sağlaması içindir, [Önceki Rapor] içinde olup da [Json] içinde geçmeyen hususları dikkate alma.
[Json] temel bilgi kaynağındır. Json içinde yer almayan hususları rapora dahil etme. [json] içinde yeterli bilgi olmadığı zaman rapordaki ilgili bölüme 'Bir gelişme yok' yaz.

Önemli: Ürettiğin rapor ile Json içeriği karşılaştır. Json içerikte olmayan konuları rapordan çıkar.

[Format]:
*Öne çıkanlar* (maddeler halinde)
*Etkileşimler*
*Proje Güncellemeleri*
*Satış ve Pazarlama*
*Operasyonel Sorunlar*
*Devam eden sorunlar* (maddeler halinde)

";

$prompt = "$basePrompt\n[Önceki Rapor]: $previousReport\n\n\n[json]: $json";

//messageChannel('C072ZHN5YUV', $prompt); exit; // this is test channel 

$report = aiResponse($prompt);

$stmt = $GLOBALS['pdo']->prepare('INSERT INTO slack_summary (prompt, json, report) VALUES (?,?,?)');
$stmt->execute([$basePrompt, $json, $report]);

messageChannel('C072R4E46BG', "*BU RAPOR OTOMATİK OLARAK HAZIRLANMIŞTIR!*\n\n$report"); // this is core-plus channel

