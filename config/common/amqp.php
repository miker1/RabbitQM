<?php

declare(strict_types=1);

use PhpAmqpLib\Connection\AMQPStreamConnection;

return [
    'dependencies' => [
        'abstract_factories' => [
            Zend\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
        ],
        'factories' => [
            Amqp::class => function () {
                return new AMQPStreamConnection(
                    '172.20.0.1',
                    5672,
                    'guest',
                    'guest',
                    '/'
                );
            },
        ],
    ]
];
