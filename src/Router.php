<?php

declare(strict_types=1);

namespace TinyPHP;

use Closure;

class Router
{
    /**
     * @var array<string, array<string, Closure>>
     */
    private array $routes = [];

    public function add(string $method, string $pattern, Closure $callback): self
    {
        $this->routes[strtoupper($method)][$pattern] = $callback;

        return $this;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = $this->resolveHttpMethod($method);
        $uri = $this->resolveUri($uri);

        // Attempt to match a simple route first.
        if (isset($this->routes[$method], $this->routes[$method][$uri])) {
            $this->sendResponse($this->routes[$method][$uri]());
            return;
        }

        // Attempt to match a regex route.
        foreach ($this->routes[$method] as $pattern => $callback) {
            if(preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                $this->sendResponse($callback(...array_slice($matches, 1)));
                return;
            }
        }

        // Handle not found.
        $this->sendNotFound();
    }

    /**
     * Support HTTP verb overriding with `_method` only when the real HTTP method
     * is POST. This "intended" HTTP method should be use with CSRF tokens.
     */
    private function resolveHttpMethod(string $method): string
    {
        return $_POST['_method'] ?? strtoupper($method);
    }

    /**
     * Strip query string (?foo=bar) and decode URI.
     */
    private function resolveUri(string $uri): string
    {
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        return '/'.trim(rawurldecode($uri), '/');
    }

    private function sendResponse(mixed $response): void
    {
        if (is_string($response)) {
            echo $response;
        } else {
            // Handle non-string responses, could throw an exception or log an error.
        }
    }

    private function sendNotFound(): void
    {
        http_response_code(404);
        echo "404 Not Found";
    }
}
