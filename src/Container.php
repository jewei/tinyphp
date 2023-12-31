<?php

declare(strict_types=1);

namespace TinyPHP;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
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
    public function set(string $id, string|object $value): self
    {
        $this->definitions[$id] = $value;

        return $this;
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
        if ($definition instanceof Closure) {
            return $definition($this);
        }

        try {
            // Probably a PHPStan bug.
            // See https://phpstan.org/blog/bring-your-exceptions-under-control.
            /** @throws ReflectionException */
            $reflector = new ReflectionClass($definition);
        } catch (ReflectionException $reflectionException) {
            throw new InvalidEntryException(sprintf('Entry class \'%s\' does not exist.', $id), 0, $reflectionException);
        }

        if (! $reflector->isInstantiable()) {
            throw new InvalidEntryException(sprintf('Entry class \'%s\' is not instantiable', $id));
        }

        $constructor = $reflector->getConstructor();

        // Class has no dependencies, instantiate and return the instance.
        if (is_null($constructor)) {
            return new $id();
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

            // If $className is null, it means the dependency is a primitive type or variadic.
            $result = is_null($className)
                ? $this->resolvePrimitive($dependency)
                : $this->get($className);

            // Check if the dependency is variadic. Variadic parameters are captured as an array.
            if ($dependency->isVariadic()) {
                $results = array_merge($results, $result); /** @phpstan-ignore-line */
            } else {
                $results[] = $result; // If it's not variadic, add the resolved dependency to the array.
            }
        }

        return $results; // Return the array of resolved dependencies.
    }

    /**
     * Resolve a non-class dependency, either a string or other primitive type.
     */
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
     * Get the class name of the given ReflectionParameter.
     */
    private function getParameterClassName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        // Checks for built-in types.
        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        // Handles self and parent references.
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
