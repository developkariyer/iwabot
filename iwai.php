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
    
    //print_r($response->toArray());   
    print_r($response->choices[0]->message->content);
}

require_once('_init.php');

$sql = "SELECT
            json->>'$.event.text' AS text,
            json->>'$.event.user' AS user,
            json->>'$.event.channel' AS channel,
            json->>'$.event_time' AS event_time,
            json->>'$.event.files' AS files
        FROM rawlog
        WHERE 
            json->>'$.event.type' = 'message'
            AND json->>'$.event_time' > (UNIX_TIMESTAMP() - 86400)
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

$prompt = "Aşağıda şirketimizin son 24 saatlik Slack arşivi var.
Şirkette ne olup bittiği hakkında yönetici özeti hazırla.
Her konunun sonunda parantez içinde o konu ile ilgili kullanıcı isimlerini belirt.
Herhangi bir ürün ismi geçiyorsa özette detaylarıyla birlikte açıkça belirt.
Anlamlı bir içerik olmayan kanalları dikkate alma.
İş ile ilgili olmayan hususları dikkate alma.
Numerik verilerle ilgili tablo oluştur.
Tespit edilen ciddi kriz alanları varsa ayrıca tablo

Yöneticilerimiz: @Hüseyin Başaran, @Aytaç Yüksel, @Mehmet, @Umit Maden, @Serkan Gürkan

24 saatlik log = ".json_encode($jsonlog);

echo "<pre>";
//print_r($jsonlog);
aiResponse($prompt);
