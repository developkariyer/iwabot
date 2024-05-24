<?php

function homeBlockSocial() {
    return [
        [
            'type' => 'divider',
        ],
        [
            'type' => 'header',
            'text' => [
                'type' => 'plain_text',
                'text' => 'İçerik Yönetimi',
                'emoji' => true,
            ],
        ],
        [
            'type' => 'divider',
        ],
        [
            'type' => 'header',
            'text' => [
                'type' => 'plain_text',
                'text' => 'Sosyal Medya',
                'emoji' => true,
            ],
        ],
        [
            'type' => 'actions',
            'elements' => [
                [
                    'type' => 'button',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => ':instagram: Influencer Ekle',
                        'emoji' => true,
                    ],
                    'style' => 'primary',
                    'value' => 'influencer_add',
                    'action_id' => 'influencer_add',
                ],
                [
                    'type' => 'button',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => ':instagram: Influencer Listesi',
                        'emoji' => true,
                    ],
                    'value' => 'influencer_list',
                    'url' => 'https://iwarden.iwaconcept.com/iwabot/iwainfluencers.php',
                    'action_id' => 'influencer_list',
                ],
                [
                    'type' => 'button',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => ':musical_note: Audio URL Ekle',
                        'emoji' => true,
                    ],
                    'style' => 'primary',
                    'value' => 'url_add',
                    'action_id' => 'url_add',
                ],
                [
                    'type' => 'button',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => ':musical_note: Audio URL Listesi',
                        'emoji' => true,
                    ],
                    'value' => 'url_list',
                    'url' => 'https://iwarden.iwaconcept.com/iwabot/iwaaudiourl.php',
                    'action_id' => 'url_list',
                ],
            ],
        ],
    ];
}

function addInfluencerBlock() {
    return [
        'type' => 'modal',
        'callback_id' => 'influencer_add',
        'title' => [
            'type' => 'plain_text',
            'text' => 'Yeni Influencer Ekle',
        ],
        'submit' => [
            'type' => 'plain_text',
            'text' => 'Ekle',
        ],
        'close' => [
            'type' => 'plain_text',
            'text' => 'Vazgeç',
        ],
        'blocks' => [
            [
                'type' => 'input',
                'block_id' => 'influencer_name',
                'element' => [
                    'type' => 'plain_text_input',
                    'action_id' => 'influencer_name',
                    'placeholder' => [
                        'type' => 'plain_text',
                        'text' => '@influencer',
                    ],
                ],
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'Influencer Kısa İsmi - @ işareti ile başlamalıdır',
                ],
            ],
            [
                'type' => 'input',
                'block_id' => 'influencer_url',
                'element' => [
                    "type" => "url_text_input",
                    'action_id' => 'influencer_url',
                    'placeholder' => [
                        'type' => 'plain_text',
                        'text' => 'URL',
                    ],
                ],
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'URL',
                ],
            ],
            [
                'type' => 'section',
                'block_id' => 'influencer_websites',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => 'Web Siteleri'
                ],
                'accessory' => [
                    'type' => 'multi_static_select',
                    'action_id' => 'influencer_websites',
                    'placeholder' => [
                        'type' => 'plain_text',
                        'text' => 'Web Sitesi/Siteleri Seçin',
                        'emoji' => true,
                    ],
                    'options' => [
                        [
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'IWA (Yurt içi)',
                                'emoji' => true,
                            ],
                            'value' => 'iwa_tr',
                        ],
                        [
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'IWA (Yurt dışı)',
                                'emoji' => true,
                            ],
                            'value' => 'iwa_intl',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'input',
                'block_id' => 'influencer_description',
                'optional' => true,
                'element' => [
                    'type' => 'plain_text_input',
                    'action_id' => 'influencer_description',
                    'placeholder' => [
                        'type' => 'plain_text',
                        'text' => 'Açıklama',
                    ],
                ],
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'Açıklama',
                ],
            ],
            [
                'type' => 'input',
                'block_id' => 'influencer_follower_count',
                'optional' => true,
                'element' => [
                    'type' => 'plain_text_input',
                    'is_decimal_allowed' => false,
                    'action_id' => 'influencer_follower_count',
                ],
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'Takipçi Sayısı',
                ],
            ],
        ],
    ];
}

function addInfluencerSuccessBlock($name) {
    return [
        'type' => 'modal',
        'title' => [
            'type' => 'plain_text',
            'text' => 'Yeni Influencer Ekle',
        ],
        'close' => [
            'type' => 'plain_text',
            'text' => 'Kapat',
        ],
        'blocks' => [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "Influencer *$name* eklendi.",
                ],
            ],
        ],
        'clear_on_close' => true,
        'notify_on_close' => false,
    ];
}

function addAudioUrlBlock() {
    return [
        'type' => 'modal',
        'callback_id' => 'url_add',
        'title' => [
            'type' => 'plain_text',
            'text' => 'Yeni Audio URL Ekle',
        ],
        'submit' => [
            'type' => 'plain_text',
            'text' => 'Ekle',
        ],
        'close' => [
            'type' => 'plain_text',
            'text' => 'Vazgeç',
        ],
        'blocks' => [
            [
                'type' => 'input',
                'block_id' => 'url_url',
                'element' => [
                    "type" => "url_text_input",
                    'action_id' => 'url_url',
                    'placeholder' => [
                        'type' => 'plain_text',
                        'text' => 'URL',
                    ],
                ],
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'URL',
                ],
            ],
            [
                'type' => 'input',
                'block_id' => 'url_description',
                'element' => [
                    'type' => 'plain_text_input',
                    'action_id' => 'url_description',
                    'placeholder' => [
                        'type' => 'plain_text',
                        'text' => 'Açıklama',
                    ],
                ],
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'Açıklama',
                ],
            ],
            [
                'type' => 'input',
                'block_id' => 'url_hashtags',
                'optional' => true,
                'element' => [
                    'type' => 'plain_text_input',
                    'action_id' => 'url_hashtags',
                    'placeholder' => [
                        'type' => 'plain_text',
                        'text' => 'Hashtags',
                    ],
                ],
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'Hashtags',
                ],
            ],
        ],
    ];
}

function addAudioUrlSuccessBlock($url) {
    return [
        'type' => 'modal',
        'title' => [
            'type' => 'plain_text',
            'text' => 'Yeni Audio URL Ekle',
        ],
        'close' => [
            'type' => 'plain_text',
            'text' => 'Kapat',
        ],
        'blocks' => [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*$url* eklendi.",
                ],
            ],
        ],
        'clear_on_close' => true,
        'notify_on_close' => false,
    ];
}

