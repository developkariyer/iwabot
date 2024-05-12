<?php

function slackUsername($text) {
    return preg_replace_callback('/<@(U[0-9A-Z]+)>/', function ($matches) {
        return '<span class="slack-user">@'.username($matches[1]).'</span>';
    }, $text);
}

function slackUrl($text) {
    return preg_replace_callback(
        '/<(http[^|]*)\|([^>]+)>/',
        function ($matches) {
            // $matches[1] is the URL, $matches[2] is the link text
            return "<a href='{$matches[1]}' target='_blank'>{$matches[2]}</a>";
        },
        $text
    );
}

function slackTags($text) {
    return preg_replace_callback(
        '/<!([\w+-]*)>/',
        function ($matches) {
            return "<b>{$matches[1]}</b>";
        },
        $text
    );
}

function slacknl2br($text) {
    return preg_replace(['/(\n\n)/', '/\n/'], ['<br>', '<br>'], $text);
}

function slackEmoji($text) {
    return preg_replace_callback(
        '/:([\w+-]*):/',
        function ($matches) {
            return getEmoji($matches[1]);
        },
        $text
    );
}

function slackBold($text) {
    return preg_replace_callback(
        '/\*([^\*]*)\*/',
        function ($matches) {
            return "<b>{$matches[1]}</b>";
        },
        $text
    );

}

function slackize($text) {
    $text = slackUsername($text);
    $text = slackUrl($text);
    $text = slackTags($text);
    $text = slacknl2br($text);
    $text = slackBold($text);
    return slackEmoji($text);
}

function addMessage($message) {
    if (!isset($_SESSION['messages'])) {
        $_SESSION['messages'] = [];
    }
    $_SESSION['messages'][] = $message;
}