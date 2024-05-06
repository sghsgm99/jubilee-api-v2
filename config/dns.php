<?php

return [
    'namecheap' => [
        'sandbox' => [
            'api_user' => 'disruptsocial',
            'api_key' => '5067170cabd3421083c37f265f6744e1',
            'client_ip' => env('CLIENT_IP', null),
        ],
        'production' => [
            'api_user' => 'whiteline',
            'api_key' => 'cc03d2b036ce472a81648f9db6838285',
            'client_ip' => env('CLIENT_IP', null),
        ]
    ],
    'git' => [
        'url' => 'https://api.github.com/repos/Disrupt-Social-Team/dns-vhost/actions/workflows/create-vhost.yml/dispatches',
        'ref' => 'main',
        'token' => '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'
    ],
    'ssh' => [
        'password' => 'jubilee'
    ]
];