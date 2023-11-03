<?php

declare(strict_types=1);

namespace TinyPHP;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

abstract class Container implements ContainerInterface
{
    /**
     * @var array<string, class-string<object>|object>
     */
    private array $definitions = [];

    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Register an entry in the container.
     *
     * @param  class-string<object>|object  $value
     */
    public function set(string $id, string|object $value): void
    {
        $this->definitions[$id] = $value;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     */
    public function get(string $id): mixed
    {
        // First check if the entry is registered.
        if (! $this->has($id)) {
            throw new EntryNotFoundException();
        }

        // Instantiate an instance of the entry.
        if (! isset($this->instances[$id])) {
            $this->instances[$id] = $this->resolve($id);
        }

        return $this->instances[$id];
    }

    /**
     * Determine if the given entry has been registered or instantiated.
     */
    public function has(string $id): bool
    {
        return isset($this->definitions[$id]) || isset($this->instances[$id]);
    }

    /**
     * Resolve the entry's nested dependencies recursively.
     */
    private function resolve(string $id): mixed
    {
        $definition = $this->definitions[$id];

        // If the entry type is a Closure, execute it and return the result.
        if ($definition instanceof \Closure) {
            return $definition($this);
        }

        try {
            // Probably a PHPStan bug.
            // See https://phpstan.org/blog/bring-your-exceptions-under-control.
            /** @throws ReflectionException */
            $reflector = new ReflectionClass($definition);
        } catch (ReflectionException $e) {
            throw new InvalidEntryException("Entry class '$id' does not exist.", 0, $e);
        }

        if (! $reflector->isInstantiable()) {
            throw new InvalidEntryException("Entry class '$id' is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        // Class has no dependencies, instantiate and return the instance.
        if (is_null($constructor)) {
            return new $id;
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters());

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param  array<int, ReflectionParameter>  $dependencies
     * @return array<int, mixed>
     */
    private function resolveDependencies(array $dependencies): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {

            $className = $this->getParameterClassName($dependency);

            $result = is_null($className)
                ? $this->resolvePrimitive($dependency)
                : $this->get($className);

            if ($dependency->isVariadic()) {
                $results = array_merge($results, $result); /** @phpstan-ignore-line */
            } else {
                $results[] = $result;
            }
        }

        return $results;
    }

    private function resolvePrimitive(ReflectionParameter $parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->isVariadic()) {
            return [];
        }

        throw new InvalidEntryException('Unresolvable dependency in '.$parameter->getDeclaringClass()?->getName());
    }

    /**
     * Get the class name of the given ReflectionParameter
     */
    private function getParameterClassName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (! $type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        if (! is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }
}
