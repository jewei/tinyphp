<?php

declare(strict_types=1);

it('registers and retrieves a service', function () {
    $app = new TinyPHP\Application();

    $app->set(TestConfig::class, TestConfig::class);

    $config = $app->get(TestConfig::class);

    expect($config)->toBeInstanceOf(TestConfig::class);
});

it('auto-wires dependencies', function () {
    $app = new TinyPHP\Application();

    $app->set(TestConfig::class, TestConfig::class);
    $app->set(TestDatabase::class, TestDatabase::class);

    $db = $app->get(TestDatabase::class);

    expect($db)->toBeInstanceOf(TestDatabase::class);
});

it('resolves primitive dependencies with default values', function () {
    $app = new TinyPHP\Application();

    $app->set(TestVariadicPrimitive::class, TestVariadicPrimitive::class);

    $primitive = $app->get(TestVariadicPrimitive::class);

    expect($primitive->params)->toBe([]);
});

it('resolves recursive dependencies', function () {
    $app = new TinyPHP\Application();

    $app->set(TestApp::class, TestApp::class);
    $app->set(TestConfig::class, TestConfig::class);
    $app->set(TestDatabase::class, TestDatabase::class);

    $foo = $app->get(TestApp::class)->database->config->foo;

    expect($foo)->toBe('bar');
});

it('throws an exception for unresolvable dependencies', function () {
    $app = new TinyPHP\Application();

    $app->set(Unresolvable::class, Unresolvable::class);

    $app->get(Unresolvable::class);
})->throws(TinyPHP\InvalidEntryException::class);

it('throws an exception for unknown services', function () {
    $app = new TinyPHP\Application();

    $app->get('UnknownService');
})->throws(TinyPHP\EntryNotFoundException::class);

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

final class TestVariadicPrimitive
{
    public function __construct(public array $params = [])
    {
    }
}
