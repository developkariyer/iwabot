<?php

require_once ('_db.php');
require_once ('_slack.php');
require_once ('_utils.php');

$rawData = file_get_contents("php://input");
$basecampData = json_decode($rawData, true);

if (empty($basecampData)) {
    exit;
}

if ($basecampData['kind'] === 'todo_assignment_changed') {
    
}



$filename = 'postlog.txt';
$handle = fopen($filename, 'a');

fwrite($handle, date('Y-m-d H:i:s') .print_r($basecampData, true)."\n");

fclose($handle);
