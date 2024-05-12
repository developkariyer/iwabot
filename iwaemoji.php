<?php

require_once('_login.php');
require_once('_slack.php');
require_once('_db.php');
require_once('_utils.php');
require_once('_emoji.php');

$emojicount = retrieveEmojis();

addMessage("$emojicount emojis reloaded.");

header ('Location: ./');
