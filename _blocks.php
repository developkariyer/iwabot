<?php

require_once('_blocks_social.php');

function homeBlock($userId) {
    $blocks = [
        [
            'type' => 'section',
            'text' => [
                'type' => 'plain_text',
                'text' => 'Merhaba, ben IWA Bot. Beni kullanarak aşağıdaki işlemleri yapabilirsiniz.',
                'emoji' => true,
            ],
        ],
    ];

    if (userInChannel($userId, 'C072LD7FQ12')) {
        $blocks = array_merge($blocks, homeBlockSocial());
    }

    return [
        'type' => 'home',
        'blocks' => $blocks,
    ];
}
