<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intelligent Workspace Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap5-toggle@5.0.4/css/bootstrap5-toggle.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        html, body {
            height: auto;
            margin: 0;
            padding: 0;
        }
        .slack-user {
            color: red;
            font-weight: bold;
        }
        .username {
            color: navy;
            font-weight: bold;
        }
        .row {
            flex-grow: 1; /* Allows the row to fill the available space */
            overflow: hidden; /* Prevent scrolling within the row */
        }
        .container-fluid {
            height: 100%; /* Make sure the outer container takes full height of the viewport */
            display: flex;
            flex-direction: column;
        }
        .channel-container {
            height: 100vh; /* 75% of the viewport height */
            overflow-y: auto; /* Add scrollbar if content overflows */
        }
        #message-container {
            height: 100vh; /* 75% of the viewport height */
            overflow-y: auto; /* Add scrollbar if content overflows */
        }
        #messagesDisplay {
            height: 100%;
        }
        #channelsList {
            height: 100%; /* Make the list take up the full height of its container */
        }        
        .no-wrap {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .select2-container .select2-choice, .select2-result-label {
            font-size: 2em!important;
            height: 45px!important; 
            overflow: auto!important;
        }
        .select2-arrow, .select2-chosen {
            padding-top: 26px!important;
        }
    </style>
</head>
<body>
<?php
if (isset($_SESSION['messages'])) {
    foreach ($_SESSION['messages'] as $message) { ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php }
    unset($_SESSION['messages']);
}
?>
