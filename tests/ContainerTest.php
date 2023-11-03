<?php

declare(strict_types=1);

test('container should resolve recursive dependencies', function () {
    $app = new TinyPHP\Application();

    $app->set(TestApp::class, TestApp::class);
    $app->set(TestConfig::class, TestConfig::class);
    $app->set(TestDatabase::class, TestDatabase::class);

    expect($app->get(TestApp::class)->database->config->foo)->toBe('bar');
});

final class TestConfig
{
    public function __construct(public string $foo = 'bar')
    {
    }
}

final class TestDatabase
{
    public function __construct(public TestConfig $config)
    {
    }
}

final class TestApp
{
    public function __construct(public TestDatabase $database)
    {
    }
}
