<?php
$verify_token = "AYOUR_VERIFY_TOKEN";  // You set this token on the Facebook developer page
$access_token = "YOUR_ACCESS_TOKEN";  // Your page access token

// Verification of the webhook
if (isset($_GET['hub_mode']) && $_GET['hub_mode'] == 'subscribe' &&
    isset($_GET['hub_verify_token']) && $_GET['hub_verify_token'] === $verify_token) {
    echo $_GET['hub_challenge'];
    exit;
}

// Handle incoming messages
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['entry'][0]['messaging'][0]['sender']['id'])) {
    $senderId = $input['entry'][0]['messaging'][0]['sender']['id'];
    $messageText = $input['entry'][0]['messaging'][0]['message']['text'];

    // Log or process the incoming message
    // For example, send a message to Slack (you'll need to implement this part based on your Slack setup)
    sendMessageToSlack($messageText);

    // Optionally, you could send a response back to the user on Facebook Messenger
//    sendFacebookMessage($senderId, "Received your message: $messageText");
}

// Function to send a message to Slack
function sendMessageToSlack($text) {
    $filename = 'postlog.txt';
    $handle = fopen($filename, 'a');
    fwrite($handle, date('Y-m-d H:i:s')."$text\n");
    fclose($handle);
}

function sendFacebookMessage($recipientId, $messageText) {
    $url = "https://graph.facebook.com/v2.6/me/messages?access_token=$access_token";
    $jsonData = '{
        "recipient":{"id":"' . $recipientId . '"},
        "message":{"text":"' . $messageText . '"}
    }';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_exec($ch);
    curl_close($ch);
}

?>
