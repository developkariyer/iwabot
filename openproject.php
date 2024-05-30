<?php

$apiKey ='a27fb9d4540f3bd402b6263e494668a5925f1f31615a4e434bb3232c25971bb7'; 
$endpointUrl = 'https://op.iwaconcept.com/api/v3/work_packages';

function performCurlRequest($url, $apiKey) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "apikey:$apiKey");
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        return false;
    }
    
    curl_close($ch);
    return $response;
}

function performCurlPost($url, $apiKey, $payload) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "apikey:$apiKey");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        return false;
    }
    
    curl_close($ch);
    return $response;
}

$currentPage = 1;
$pageSize = 1;
$morePages = true;

while ($morePages) {
    $paginatedUrl = "$endpointUrl?offset=$currentPage&pageSize=$pageSize";

    $response = performCurlRequest($paginatedUrl, $apiKey);
    echo $response;exit;

    if ($response) {
        $data = json_decode($response, true);
        
        echo 'Page ' . $currentPage . ': ' . json_encode($data) . PHP_EOL;
        exit;
        
        if (isset($data['_links']['next'])) {
            $currentPage++;
        } else {
            $morePages = false;
        }
    } else {
        $morePages = false;
    }
}


