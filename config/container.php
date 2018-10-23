<?php

declare(strict_types=1);

use Zend\ServiceManager\ServiceManager;

$config = require __DIR__ . '/config.php';

$container = new ServiceManager($config['dependencies']);

return $container;
