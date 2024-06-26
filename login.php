<?php

$guestFree = true;

require_once('_login.php');

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: '.$redirectUri);
    exit;
}

require_once('_slack.php');

if (isset($_SESSION['logged_in'])) {
    header('Location: '.$loggedInUri);
    exit;
}

$content = '';

if (isset($_GET['code']) && isset($_GET['state']) && isset($_SESSION['state']) && $_GET['state'] === $_SESSION['state']) {
    $code = $_GET['code'];
    $state = $_GET['state'];
    $url = 'https://slack.com/api/openid.connect.token';
    $data = [
        'grant_type' => 'authorization_code',
        'client_id' => $GLOBALS['slack']['clientId'],
        'client_secret' => $GLOBALS['slack']['clientSecret'],
        'code' => $code,
        'redirect_uri' => $redirectUri,
    ];
    $options = [
        'http' => [
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'method' => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $response = json_decode($response, true);
    if (isset($response['ok']) && $response['ok'] && isset($response['id_token'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['id_token'] = $response['id_token'];
        $_SESSION['response'] = $response;
        $_SESSION['access_token'] = $response['access_token'];
        $tokenParts = explode('.', $response['id_token']);
        $_SESSION['user_info'] = json_decode(base64_decode($tokenParts[1]), true);
        $_SESSION['user_id'] = $_SESSION['user_info']['sub'];
        if (isset($_SESSION['prev_url'])) {
            $loggedInUri = $_SESSION['prev_url'];
            unset($_SESSION['prev_url']);
        }
        header('Location: '.$loggedInUri);
        exit;
    } else {
        $content = '<div class="alert alert-danger" role="alert">Error: '.$response['error'].'</div>';
    }
} 

$_SESSION['state'] = bin2hex(random_bytes(16));
$_SESSION['nonce'] = bin2hex(random_bytes(16));
$content .= '<a href="https://slack.com/openid/connect/authorize?response_type=code&scope=openid%20profile&client_id='.$GLOBALS['slack']['clientId'].'&state='.$_SESSION['state'].'&team='.$GLOBALS['slack']['teamId'].'&nonce='.$_SESSION['nonce'].'&redirect_uri='.urlencode($redirectUri).'" style="color:#fff;background-color:#4A154B;" class="btn btn-primary btn-lg"><svg xmlns="http://www.w3.org/2000/svg" style="height:24px;width:24px;margin-right:12px" viewBox="0 0 122.8 122.8"><path d="M25.8 77.6c0 7.1-5.8 12.9-12.9 12.9S0 84.7 0 77.6s5.8-12.9 12.9-12.9h12.9v12.9zm6.5 0c0-7.1 5.8-12.9 12.9-12.9s12.9 5.8 12.9 12.9v32.3c0 7.1-5.8 12.9-12.9 12.9s-12.9-5.8-12.9-12.9V77.6z" fill="#e01e5a"></path><path d="M45.2 25.8c-7.1 0-12.9-5.8-12.9-12.9S38.1 0 45.2 0s12.9 5.8 12.9 12.9v12.9H45.2zm0 6.5c7.1 0 12.9 5.8 12.9 12.9s-5.8 12.9-12.9 12.9H12.9C5.8 58.1 0 52.3 0 45.2s5.8-12.9 12.9-12.9h32.3z" fill="#36c5f0"></path><path d="M97 45.2c0-7.1 5.8-12.9 12.9-12.9s12.9 5.8 12.9 12.9-5.8 12.9-12.9 12.9H97V45.2zm-6.5 0c0 7.1-5.8 12.9-12.9 12.9s-12.9-5.8-12.9-12.9V12.9C64.7 5.8 70.5 0 77.6 0s12.9 5.8 12.9 12.9v32.3z" fill="#2eb67d"></path><path d="M77.6 97c7.1 0 12.9 5.8 12.9 12.9s-5.8 12.9-12.9 12.9-12.9-5.8-12.9-12.9V97h12.9zm0-6.5c-7.1 0-12.9-5.8-12.9-12.9s5.8-12.9 12.9-12.9h32.3c7.1 0 12.9 5.8 12.9 12.9s-5.8 12.9-12.9 12.9H77.6z" fill="#ecb22e"></path></svg>Sign in with Slack</a>';   


include('_header.php');
?>
<div class="container">
    <div class="jumbotron m-5 p-5 text-center">
        <h1>Welcome to IWA Bot, <span class="username"><?= $_SESSION['user_info']['name'] ?? '' ?></span></h1>
        <p>Click the button below to login with your Slack account.</p>
        <?php echo $content; ?>
    </div>
</div>
<?php
include('_footer.php');
