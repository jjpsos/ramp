<?php
declare(strict_types=1);

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Application;

try {
    define('APP_PATH', realpath('..') . '/');

    $di = new FactoryDefault();

    $providers = APP_PATH . '/app/config/providers.php';
    if (!file_exists($providers) || !is_readable($providers)) {
        throw new Exception('File providers.php does not exist or is not readable.');
    }

    $di->loadFromPhp($providers);

    (new Application($di))
        ->handle($_SERVER['REQUEST_URI'])
        ->send();
} catch (Exception $e) {
    echo $e->getMessage() . '<br>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}
