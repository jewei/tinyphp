<?php

declare(strict_types=1);

use TinyPHP\Application;
use TinyPHP\Router;

/**
 * Ensure app is running the minimum PHP version.
 */
if (version_compare(PHP_VERSION, '8.2', '<')) {
    throw new Exception('PHP version 8.2 or higher is required.');
}

/**
 * Autoloading from Composer generated PSR-4 autoloader instance.
 */
require __DIR__.'/vendor/autoload.php';

return (new Application())
    ->set(Router::class, Router::class);
