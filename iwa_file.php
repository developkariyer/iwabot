<?php

if (!isset($_GET['id'])) {    
    echo "No file selected...";
    exit;
}

$guestFree = true;
require_once('_login.php');

require_once '_init.php';

$filerow = $GLOBALS['pdo']->query("SELECT * FROM files WHERE file_id = '{$_GET['id']}'")->fetch(PDO::FETCH_ASSOC);
if (!$filerow) {
    echo "File not found...";
    exit;
}

$file = json_decode($filerow['json'], true);

function simpleHash($text) {
    return substr(md5($text), 0, 2);    
}

if (!file_exists('downloads/'.simpleHash($file['id']))) {
    mkdir('downloads/'.simpleHash($file['id']));
}

if (!file_exists('downloads/'.simpleHash($file['id']).'/'.$file['id'].'_'.$file['name'])) {
    $fileData = curlFileGet($file['url_private']);
    file_put_contents('downloads/'.simpleHash($file['id']).'/'.$file['id'].'_'.$file['name'], $fileData);
    if (isset($file['thumb_360'])) {
        $thumbData = curlFileGet($file['thumb_360']);
        file_put_contents('downloads/'.simpleHash($file['id']).'/'.$file['id'].'_360_'.$file['name'].'.png', $thumbData);
    }
    if (isset($file['thumb_pdf'])) {
        $thumbData = curlFileGet($file['thumb_pdf']);
        file_put_contents('downloads/'.simpleHash($file['id']).'/'.$file['id'].'_pdf_'.$file['name'].'.png', $thumbData);
    }
}

if (isset($_GET['thumb'])) {
    if (file_exists('downloads/'.simpleHash($file['id']).'/'.$file['id'].'_pdf_'.$file['name'].'.png')) {
        header('Content-Type: image/png');
        readfile('downloads/'.simpleHash($file['id']).'/'.$file['id'].'_pdf_'.$file['name'].'.png');
        exit;
    }
    if (file_exists('downloads/'.simpleHash($file['id']).'/'.$file['id'].'_360_'.$file['name'].'.png')) {
        header('Content-Type: image/png');
        readfile('downloads/'.simpleHash($file['id']).'/'.$file['id'].'_360_'.$file['name'].'.png');
        exit;
    }
    echo "No thumbnail found...";
    exit;
}

header('Content-Type: '.$file['mimetype']);
header("Content-Disposition: inline; filename={$file['name']}");
readfile('downloads/'.simpleHash($file['id']).'/'.$file['id'].'_'.$file['name']);
exit;