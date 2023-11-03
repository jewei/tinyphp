<?php

declare(strict_types=1);

namespace TinyPHP;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class Container implements ContainerInterface
{
    private array $definitions = [];

    private array $instances = [];

    public function get(string $id)
    {
        if (! $this->has($id)) {
            throw new class () extends \Exception implements NotFoundExceptionInterface {};
        }

        if (! isset($this->instances[$id])) {
            $this->instances[$id] = $this->resolve($id);
        }

        return $this->instances[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id]);
    }

    /**
     *
     * @param mixed $id The FQCN of the abstract class.
     * @param mixed $value The interface object.
     * @return void
     */
    public function set($id, $value): void
    {
        $this->definitions[$id] = $value;
    }

    private function resolve(string $id)
    {
        $definition = $this->definitions[$id];

        if ($definition instanceof \Closure) {
            return $definition($this);
        }

        // Simplified auto-wiring.
        $reflectionClass = new \ReflectionClass($definition);
        $constructor = $reflectionClass->getConstructor();
        $parameters = $constructor !== null ? $constructor->getParameters() : [];
        $dependencies = array_map(fn ($parameter) => $this->get($parameter->getName()), $parameters);

        return $reflectionClass->newInstanceArgs($dependencies);
    }
}
