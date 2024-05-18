<?php

if (php_sapi_name() !== 'cli') {
    die('Hello World!');
}

require_once ('_init.php');

$iblck = addInfluencerSuccessBlock('Umut');

$json = json_encode($iblck, JSON_PRETTY_PRINT);

print_r($json);