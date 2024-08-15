<?php

$guestFree = true;

require_once '_login.php';

if (isset($_GET['logout'])) {
    error_log('Login.php: Logging out');
    session_destroy();
    session_start();
    setcookie('remember_me', '', time() - 3600, "/", "", true, true);
    header("Location: $redirectUri");
    exit;
}

require_once '_slack.php';

function initializeSession($response) {
    $_SESSION['logged_in'] = true;
    $_SESSION['id_token'] = $response['id_token'];
    $_SESSION['response'] = $response;
    $_SESSION['access_token'] = $response['access_token'];
    $tokenParts = explode('.', $response['id_token']);
    $_SESSION['user_info'] = json_decode(base64_decode(strtr($tokenParts[1], '-_', '+/')), true);
    $_SESSION['user_id'] = $_SESSION['user_info']['sub'];
}

// Check if the remember_me cookie is set and valid
if (isset($_COOKIE['remember_me'])) {
    $parts = explode(':', $_COOKIE['remember_me'], 2);

    if (count($parts) === 2) {
        [$sessionId, $token] = $parts;

        $stmt = $GLOBALS['pdo']->prepare("SELECT session_info FROM session_info WHERE id = :id");
        $stmt->execute([':id' => $sessionId]);
        $sessionData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sessionData) {
            $response = json_decode($sessionData['session_info'], true);

            // Verify the token
            if (isset($response['hashed_token']) && password_verify($token, $response['hashed_token'])) {
                // Token is valid, restore the session
                initializeSession($response);
            } else {
                // Invalid token, destroy the cookie
                setcookie('remember_me', '', time() - 3600, "/", "", true, true);
            }
        }
    } else {
        // Malformed cookie, destroy the cookie
        setcookie('remember_me', '', time() - 3600, "/", "", true, true);
    }
}


// Check if the user is already logged in via session
if (isset($_SESSION['logged_in'])) {
    error_log('Login.php: Already logged in');
    header("Location: $loggedInUri");
    exit;
}




$content = '';

if (isset($_GET['code']) && isset($_GET['state']) && isset($_SESSION['state']) && $_GET['state'] === $_SESSION['state']) {
    error_log('Login.php called by Slack: '.json_encode($_GET));
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
    error_log('Login.php requesting token from Slack: '.json_encode($url).' '.json_encode($options));
    $response = file_get_contents($url, false, $context);
    error_log("Login.php slack response received: $response");
    $response = json_decode($response, true);
    if (isset($response['ok']) && $response['ok'] && isset($response['id_token'])) {
        initializeSession($response);
    
        // Generate a secure token
        $rawToken = bin2hex(random_bytes(16));
        $hashedToken = password_hash($rawToken, PASSWORD_DEFAULT);
        $response['hashed_token'] = $hashedToken;
        $stmt = $GLOBALS['pdo']->prepare("INSERT INTO session_info (session_info) VALUES (:session_info)");
        $stmt->execute([':session_info' => json_encode($response)]);
        $sessionId = $GLOBALS['pdo']->lastInsertId();
        setcookie('remember_me', "$sessionId:$rawToken", time() + 2592000, "/", "", true, true);

    
        if (isset($_SESSION['prev_url'])) {
            $loggedInUri = $_SESSION['prev_url'];
            unset($_SESSION['prev_url']);
        }
        header("Location: $loggedInUri");
        exit;
    } else {
        $content = '<div class="alert alert-danger" role="alert">Error: '.$response['error'].'</div>';
        error_log('Login.php: Error: '.json_encode($response));
    }
}

error_log('Login.php: Asking for Login with Slack');

$_SESSION['state'] = bin2hex(random_bytes(16));
$_SESSION['nonce'] = bin2hex(random_bytes(16));
$content .= '<a href="https://slack.com/openid/connect/authorize?response_type=code&scope=openid%20profile&client_id='.$GLOBALS['slack']['clientId'].'&state='.$_SESSION['state'].'&team='.$GLOBALS['slack']['teamId'].'&nonce='.$_SESSION['nonce'].'&redirect_uri='.urlencode($redirectUri).'" style="color:#fff;background-color:#4A154B;" class="btn btn-primary btn-lg"><svg xmlns="http://www.w3.org/2000/svg" style="height:24px;width:24px;margin-right:12px" viewBox="0 0 122.8 122.8"><path d="M25.8 77.6c0 7.1-5.8 12.9-12.9 12.9S0 84.7 0 77.6s5.8-12.9 12.9-12.9h12.9v12.9zm6.5 0c0-7.1 5.8-12.9 12.9-12.9s12.9 5.8 12.9 12.9v32.3c0 7.1-5.8 12.9-12.9 12.9s-12.9-5.8-12.9-12.9V77.6z" fill="#e01e5a"></path><path d="M45.2 25.8c-7.1 0-12.9-5.8-12.9-12.9S38.1 0 45.2 0s12.9 5.8 12.9 12.9v12.9H45.2zm0 6.5c7.1 0 12.9 5.8 12.9 12.9s-5.8 12.9-12.9 12.9H12.9C5.8 58.1 0 52.3 0 45.2s5.8-12.9 12.9-12.9h32.3z" fill="#36c5f0"></path><path d="M97 45.2c0-7.1 5.8-12.9 12.9-12.9s12.9 5.8 12.9 12.9-5.8 12.9-12.9 12.9H97V45.2zm-6.5 0c0 7.1-5.8 12.9-12.9 12.9s-12.9-5.8-12.9-12.9V12.9C64.7 5.8 70.5 0 77.6 0s12.9 5.8 12.9 12.9v32.3z" fill="#2eb67d"></path><path d="M77.6 97c7.1 0 12.9 5.8 12.9 12.9s-5.8 12.9-12.9 12.9-12.9-5.8-12.9-12.9V97h12.9zm0-6.5c-7.1 0-12.9-5.8-12.9-12.9s5.8-12.9 12.9-12.9h32.3c7.1 0 12.9 5.8 12.9 12.9s-5.8 12.9-12.9 12.9H77.6z" fill="#ecb22e"></path></svg>Sign in with Slack</a>';   
/*
$content .= '
    <div class="form-group">
        <input type="checkbox" id="rememberMe" name="rememberMe">
        <label for="rememberMe">Remember Me</label>
    </div><br>
    <a href="#" id="slackLoginBtn" style="color:#fff;background-color:#4A154B;" class="btn btn-primary btn-lg">
        <svg xmlns="http://www.w3.org/2000/svg" style="height:24px;width:24px;margin-right:12px" viewBox="0 0 122.8 122.8">
            <path d="M25.8 77.6c0 7.1-5.8 12.9-12.9 12.9S0 84.7 0 77.6s5.8-12.9 12.9-12.9h12.9v12.9zm6.5 0c0-7.1 5.8-12.9 12.9-12.9s12.9 5.8 12.9 12.9v32.3c0 7.1-5.8 12.9-12.9 12.9s-12.9-5.8-12.9-12.9V77.6z" fill="#e01e5a"></path>
            <path d="M45.2 25.8c-7.1 0-12.9-5.8-12.9-12.9S38.1 0 45.2 0s12.9 5.8 12.9 12.9v12.9H45.2zm0 6.5c7.1 0 12.9 5.8 12.9 12.9s-5.8 12.9-12.9 12.9H12.9C5.8 58.1 0 52.3 0 45.2s5.8-12.9 12.9-12.9h32.3z" fill="#36c5f0"></path>
            <path d="M97 45.2c0-7.1 5.8-12.9 12.9-12.9s12.9 5.8 12.9 12.9-5.8 12.9-12.9 12.9H97V45.2zm-6.5 0c0 7.1-5.8 12.9-12.9 12.9s-12.9-5.8-12.9-12.9V12.9C64.7 5.8 70.5 0 77.6 0s12.9 5.8 12.9 12.9v32.3z" fill="#2eb67d"></path>
            <path d="M77.6 97c7.1 0 12.9 5.8 12.9 12.9s-5.8 12.9-12.9 12.9-12.9-5.8-12.9-12.9V97h12.9zm0-6.5c-7.1 0-12.9-5.8-12.9-12.9s5.8-12.9 12.9-12.9h32.3c7.1 0 12.9 5.8 12.9 12.9s-5.8 12.9-12.9 12.9H77.6z" fill="#ecb22e"></path>
        </svg>
        Sign in with Slack
    </a>
    <script>
        document.getElementById("slackLoginBtn").onclick = function() {
            var rememberMe = document.getElementById("rememberMe").checked;
            if (rememberMe) {
                localStorage.setItem("rememberMe", "true");
            } else {
                localStorage.removeItem("rememberMe");
            }
            window.location.href = "https://slack.com/openid/connect/authorize?response_type=code&scope=openid%20profile&client_id=' . $GLOBALS['slack']['clientId'] . '&state=' . $_SESSION['state'] . '&team=' . $GLOBALS['slack']['teamId'] . '&nonce=' . $_SESSION['nonce'] . '&redirect_uri=' . urlencode($redirectUri) . '";
        };
    </script>
';
*/
include '_header.php';
?>
<div class="container">
    <div class="jumbotron m-5 p-5 text-center">
        <h1>Welcome to IWA Bot, <span class="username"><?= $_SESSION['user_info']['name'] ?? '' ?></span></h1>
        <p>Click the button below to login with your Slack account.</p>
        <?php echo $content; ?>
    </div>
</div>
<?php
include '_footer.php';
