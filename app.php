#!/usr/bin/env php
<?php

declare(strict_types=1);

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

use App\Consumer;

/**
 * @var \Psr\Container\ContainerInterface $container
 */

$container = require 'config/container.php';

/**
 * @var \Psr\Container\ContainerInterface $container
 */
$connection = $container->get(Amqp::class);
$logger = $container->get(Monolog::class);
$sender = $container->get(Sender::class);

$consumer = new Consumer($connection, $logger, $sender);

$consumer->start();
