<?php

require_once('_blocks_influencers.php');

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
        $blocks = array_merge($blocks, homeBlockInfluencer());
    }

    return [
        'type' => 'home',
        'blocks' => $blocks,
    ];
}
