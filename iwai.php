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


$basePrompt = "Using the information below, please prepare a manager summary according to [Format].
If you can't find information about a title in the [json] section, delete that part.
Don't include anything in the report that is not in the [json] content.
Mention the name of the person responsible for each topic in parentheses.
Keep your comments short and use short sentences, and write in bullet points whenever possible.
Do not invent information that is not in the [json] content.
[Json] is the main source of information.
[Önceki Rapor] is for giving you a context, do not include [Önceki Rapor] if there is no information on that subject in [json].

Following content is in Turkish. Make your final report in Turkish please.


[Format]:
*Öne çıkanlar*
*Etkileşimler*
*Proje Güncellemeleri*
*Satış ve Pazarlama*
*Operasyonel Sorunlar*
*Devam eden sorunlar*

";

$prompt = "$basePrompt\n[Önceki Rapor]: $previousReport\n\n\n[json]: $json";

//messageChannel('C072ZHN5YUV', $prompt); exit; // this is test channel 

$report = aiResponse($prompt);

$stmt = $GLOBALS['pdo']->prepare('INSERT INTO slack_summary (prompt, json, report) VALUES (?,?,?)');
$stmt->execute([$basePrompt, $json, $report]);

messageChannel('C072R4E46BG', "*BU RAPOR OTOMATİK OLARAK HAZIRLANMIŞTIR!*\n\n$report"); // this is core-plus channel

