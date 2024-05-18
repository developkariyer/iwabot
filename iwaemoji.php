<?php

require_once('_login.php');
require_once('_init.php');

$emojicount = retrieveEmojis();

addMessage("$emojicount emojis reloaded.");

header ('Location: ./');
