<?php

if (!$_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('Invalid request');
    die('Invalid request');
}

require_once('_slack.php');

$eventData = json_decode($_POST['payload'], true);

if ($eventData['token'] !== $GLOBALS['slack']['verificationToken']) {
    error_log('Unauthorized request');
    exit;
}

require_once ('_init.php');

$stmt = $GLOBALS['pdo']->prepare("INSERT INTO interactive (json) VALUES (?)");

try {
    $stmt->execute([json_encode($eventData, true)]);
} catch (Exception $e) {
    error_log($e->getMessage());
}

if ($eventData['type'] === 'block_actions') {
    if ($eventData['actions'][0]['type'] === 'button') {
        if ($eventData['actions'][0]['value'] === 'influencer_add') {
            $response = curlPost('https://slack.com/api/views.open', [
                'trigger_id' => $eventData['trigger_id'],
                'view' => addInfluencerBlock(),
            ]);
        }
        if ($eventData['actions'][0]['value'] === 'url_add') {
            $response = curlPost('https://slack.com/api/views.open', [
                'trigger_id' => $eventData['trigger_id'],
                'view' => addAudioUrlBlock(),
            ]);
        }
    }
    exit;
}

function validateInfluencerInput($name, $url, $websites) {
    $errors = [];
    if (empty($name)) {
        $errors['influencer_name'] = 'İsim boş bırakılamaz';
    } else {
        if ($name[0] !== '@') {
            $errors['influencer_name'] = 'İsim @ ile başlamalıdır';
        }
    }

    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        $errors['influencer_url'] = 'Geçerli bir URL girin';
    }
    if (empty($websites)) {
        $errors['influencer_websites'] = 'En az bir websitesi secin';
    }
    return $errors;
}

function validateAudioUrlInput($url, $description, $hashtags) {
    $errors = [];
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        $errors['url_add'] = 'Geçerli bir URL girin';
    }
    foreach ($hashtags as $hashtag) {
        $hashtag = trim($hashtag);
        if (empty($hashtag) || $hashtag[0] !== '#') {
            $errors['url_hashtags'] = 'Hashtagler araladında boşlukla yazılmalı ve # ile başlamalı';
            break;
        }
    }
    return $errors;
}

if ($eventData['type'] === 'view_submission') {

    if ($eventData['view']['callback_id'] === 'influencer_add') {
        $name = $eventData['view']['state']['values']['influencer_name']['influencer_name']['value'];
        $url = $eventData['view']['state']['values']['influencer_url']['influencer_url']['value'];
        $description = $eventData['view']['state']['values']['influencer_description']['influencer_description']['value'] ?? null;
        $follower = $eventData['view']['state']['values']['influencer_follower_count']['influencer_follower_count']['value'] ?? null;
        $websites = [];
        foreach ($eventData['view']['state']['values']['influencer_websites']['influencer_websites']['selected_options'] as $website) {
            $websites[] = $website['value'];
        }
        $user = $eventData['user']['id'];

        $errors = validateInfluencerInput($name, $url, $websites);
        if (!empty($errors)) {
            header('Content-Type: application/json');
            echo json_encode([
                'response_action' => 'errors',
                'errors' => $errors,
            ]);
            error_log(json_encode($errors, true));
            exit;
        }

        $stmt = $GLOBALS['pdo']->prepare('INSERT INTO influencers (name, url, description, follower, websites, user) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$name, $url, $description, $follower, json_encode($websites), $user]);

        header('Content-Type: application/json');
        echo json_encode([
            'response_action' => 'update',
            'view' => addInfluencerSuccessBlock($name),
        ]);
        exit;
    }

    if ($eventData['view']['callback_id'] === 'url_add') {
        $url = $eventData['view']['state']['values']['url_url']['url_url']['value'];
        $description = $eventData['view']['state']['values']['url_description']['url_description']['value'];
        $hashtags = explode(" ", $eventData['view']['state']['values']['url_hashtags']['url_hashtags']['value']);
        $user = $eventData['user']['id'];

        $errors = validateAudioUrlInput($url, $description, $hashtags);
        if (!empty($errors)) {
            header('Content-Type: application/json');
            echo json_encode([
                'response_action' => 'errors',
                'errors' => $errors,
            ]);
            error_log(json_encode($errors, true));
            exit;
        }

        $stmt = $GLOBALS['pdo']->prepare('INSERT INTO audio (url, description, hashtags, user) VALUES (?,?,?,?)');
        $stmt->execute([$url, $description, json_encode($hashtags), $user]);

        header('Content-Type: application/json');
        echo json_encode([
            'response_action' => 'update',
            'view' => addAudioUrlSuccessBlock($url),
        ]);
        exit;
    }

}