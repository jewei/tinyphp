<?php

declare(strict_types=1);

namespace TinyPHP;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class Container implements ContainerInterface
{
    protected $definitions = [];
    protected $instances = [];

    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new class () extends \Exception implements NotFoundExceptionInterface {};
        }

        if (!isset($this->instances[$id])) {
            $this->instances[$id] = $this->resolve($id);
        }

        return $this->instances[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id]);
    }

    public function set($id, $value)
    {
        $this->definitions[$id] = $value;
    }

    protected function resolve($id)
    {
        $definition = $this->definitions[$id];

        if ($definition instanceof \Closure) {
            return $definition($this);
        }

        // Simplified auto-wiring.
        $reflectionClass = new \ReflectionClass($definition);
        $constructor = $reflectionClass->getConstructor();
        $parameters = $constructor ? $constructor->getParameters() : [];
        $dependencies = array_map(fn ($parameter) => $this->get($parameter->getName()), $parameters);

        return $reflectionClass->newInstanceArgs($dependencies);
    }
}
