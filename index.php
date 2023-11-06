<?php

declare(strict_types=1);

use TinyPHP\Router;

$app = require __DIR__.'/bootstrap.php';

$app->get(Router::class)->add('GET', '/', fn () => 'Homepage')
    ->add('GET', '/about', fn () => 'About Us')
    ->add('GET', '/articles/(\d+)', fn ($id) => 'Article: '.$id)
    ->add('GET', '/articles/(\w+)/(\d+)', fn ($author, $id) => 'Article by: '.$author. ' with ID:'.$id);

$app->get(Router::class)->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
