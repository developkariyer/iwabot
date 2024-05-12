<?php

require_once '_db.php';

function readDbEmoji() {
    $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM emojis");
    $stmt->execute();
    $emojis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $GLOBALS['emoji']=[];
    foreach($emojis as $emoji){
        $GLOBALS['emoji'][$emoji['emoji']]=$emoji['url'];
    }
}

function retrieveEmojis() {
    $response = curlGet('https://slack.com/api/emoji.list',[]);
    if (isset($response['emoji']) && is_array($response['emoji'])) {
        $emojis = $response['emoji'];
        $stmt = $GLOBALS['pdo']->prepare("INSERT IGNORE INTO emojis (emoji, url) VALUES (?, ?)");
        foreach ($emojis as $emoji => $url) {
            $stmt->execute([$emoji, $url]);
        }
        return count($emojis);
    }
    return 0;
}

function getEmoji($emoji) {
    if (!isset($GLOBALS['emoji'])) {
        readDbEmoji();
    }
    return isset($GLOBALS['emoji'][$emoji]) ? '<img src="'.$GLOBALS['emoji'][$emoji].'" style="height: 22px;">' : $emoji;
}

function getDefaultEmojis() {
    $file = fopen('slackemoji.txt', 'r');

    if ($file) {
        while (($line = fgets($file)) !== false) {
            if (preg_match('/":(.*?):", "(.*?)"/', $line, $matches)) {
                $emoji = $matches[1];
                $url = $matches[2];
            
                $sql = "INSERT IGNORE INTO emojis (emoji, url) VALUES (:emoji, :url)";
                $stmt = $GLOBALS['pdo']->prepare($sql);
                $stmt->execute([':emoji' => $emoji, ':url' => $url]);
                echo $url, $emoji, "<br>\n";
            }
        }
        fclose($file);
        echo "Data imported successfully.";
    } else {
        echo "Error opening the file.";
    }

}
