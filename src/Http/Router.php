<?php

namespace Lithe\Http;

use BadMethodCallException;

/**
 * Router class for managing routes and middlewares in a PHP application.
 */
class Router
{
    /**
     * @var array Stores the routes defined in the application.
     */
    protected array $routes = [];

    /**
     * @var array Stores the global middlewares defined in the application.
     */
    protected array $middlewares = [];
    /**
     * @var array List of HTTP methods supported by the router.
     */
    private const METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];

    /**
     * Adds a route for handling GET requests.
     *
     * @param string $path The route path.
     * @param callable ...$handler The handlers (callbacks) for the route.
     */
    public function get(string $path, callable|array ...$handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Adds a route for handling POST requests.
     *
     * @param string $path The route path.
     * @param callable ...$handler The handlers (callbacks) for the route.
     */
    public function post(string $path, callable|array ...$handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Adds a route for handling PUT requests.
     *
     * @param string $path The route path.
     * @param callable ...$handler The handlers (callbacks) for the route.
     */
    public function put(string $path, callable|array ...$handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Adds a route for handling DELETE requests.
     *
     * @param string $path The route path.
     * @param callable ...$handler The handlers (callbacks) for the route.
     */
    public function delete(string $path, callable|array ...$handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Adds a route for handling PATCH requests.
     *
     * @param string $path The route path.
     * @param callable ...$handler The handlers (callbacks) for the route.
     */
    public function patch(string $path, callable|array ...$handler): void
    {
        $this->addRoute('PATCH', $path, $handler);
    }

    /**
     * Adds a route for handling OPTIONS requests.
     *
     * @param string $path The route path.
     * @param callable ...$handler The handlers (callbacks) for the route.
     */
    public function options(string $path, callable|array ...$handler): void
    {
        $this->addRoute('OPTIONS', $path, $handler);
    }

    /**
     * Adds a route for handling HEAD requests.
     *
     * @param string $path The route path.
     * @param callable ...$handler The handlers (callbacks) for the route.
     */
    public function head(string $path, callable|array ...$handler): void
    {
        $this->addRoute('HEAD', $path, $handler);
    }

    /**
     * Adds a route to the routes array, checking if it already exists.
     *
     * @param string $method The HTTP method for the route.
     * @param string $path The route path.
     * @param callable|array $handler The handlers (callbacks) for the route.
     */
    protected function addRoute(string $method, string $path, callable|array $handler): void
    {
        // Check if the route already exists
        foreach ($this->routes as &$route) {
            if ($route['method'] === $method && $route['route'] === $path) {
                // Route already exists, just add the handler
                $route['handler'] = array_merge($route['handler'], $handler);
                return;
            }
        }

        // If the route does not exist, add it to the routes array
        $this->routes[] = [
            'method' => $method,
            'route' => $path,
            'handler' => $handler,
        ];
    }

    /**
     * Adds a middleware or a router to the application.
     *
     * @param string|callable|Router|array ...$middleware The middlewares or routers to be added.
     */
    public function use(string|callable|Router|array ...$middleware): void
    {
        // Check if the first parameter is a string (route prefix)
        $prefix = null;
        if (isset($middleware[0]) && is_string($middleware[0]) && strpos($middleware[0], '/') === 0) {
            $prefix = array_shift($middleware); // Remove the route prefix from the middleware array
        }

        foreach ($middleware as $mid) {
            if ($mid instanceof Router) {
                $this->addRouter($mid, $prefix ?: '');
            } elseif (is_callable($mid) || is_array($mid)) {
                // Add the middleware to the application's middleware list
                $this->middlewares[] = $mid;
            }
        }
    }

    /**
     * Adds a Router as middleware for a group of routes.
     *
     * @param Router $router The Router instance to be added.
     * @param string $routePattern The route pattern for the route group.
     */
    protected function addRouter(Router $router, string $routePattern): void
    {
        foreach ($router->routes as $route) {
            $path = $route['route'] === '/' ? $routePattern : "$routePattern" . $route['route'];
            $this->routes[] = [
                'method' => $route['method'],
                'route' => $path,
                'handler' => array_merge($router->middlewares, $route['handler']),
            ];
        }
    }

    /**
     * Creates an object to define routes with a specific prefix.
     *
     * @param string $path The route prefix.
     * @return object An anonymous object to define routes with the provided prefix.
     */
    public function route(string $path): object
    {
        $router = $this;

        $methods = self::METHODS;

        return new class($path, $router, $methods)
        {
            private string $path;
            private Router $router;
            private array $methods;

            /**
             * Constructor for the anonymous class.
             *
             * @param string $path
             * @param Router $router
             * @param array $methods
             */
            public function __construct(string $path, Router $router, array $methods)
            {
                $this->path = $path;
                $this->router = $router;
                $this->methods = $methods;
            }

            /**
             * Handles dynamic method calls.
             *
             * @param string $method
             * @param array $args
             * @return self
             * @throws BadMethodCallException
             */
            public function __call(string $method, array $args): self
            {
                // Check if the method is a valid HTTP method
                if (in_array(strtoupper($method), $this->methods)) {
                    $this->router->$method($this->path, ...$args);
                } else {
                    // Throw an exception if the method does not exist
                    throw new BadMethodCallException("Method $method does not exist");
                }

                return $this;
            }
        };
    }

    /**
     * Adds a route to handle all HTTP methods.
     *
     * @param string $path The route path.
     * @param callable ...$handler The handlers (callbacks) for the route.
     */
    public function any(string $path, callable|array ...$handler): void
    {
        $methods = self::METHODS;
        foreach ($methods as $method) {
            $this->addRoute($method, $path, $handler);
        }
    }

    /**
     * Adds a route to handle multiple specified HTTP methods.
     *
     * @param array $methods HTTP methods that the route should handle.
     * @param string $path The route path.
     * @param callable ...$handler The handlers (callbacks) for the route.
     */
    public function match(array $methods, string $path, callable|array ...$handler): void
    {
        foreach ($methods as $method) {
            $this->addRoute($method, $path, $handler);
        }
    }
}
