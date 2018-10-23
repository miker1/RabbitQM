<?php

declare(strict_types=1);

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Zend\ServiceManager\ServiceManager;

return [
    'dependencies' => [
        'factories' => [
            Monolog::class => function (ServiceManager $container) {
                $log = new Logger('myApp');
                return $log->pushHandler($container->get(StreamHandler::class));
            },

            StreamHandler::class => function(){
                return new StreamHandler('/tmp/logs/myApp.log', Logger::INFO);
            }
        ],
    ]
];

