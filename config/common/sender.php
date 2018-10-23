<?php

declare(strict_types=1);

use GuzzleHttp\Client;

return [
    'dependencies' => [
        'factories' => [
            Sender::class => function () {
                return new Client();
            }
        ],
    ]
];
