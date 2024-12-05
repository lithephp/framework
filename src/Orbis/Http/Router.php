<?php

namespace Lithe\Orbis\Http\Router;

use Exception;
use Lithe\Http\Router;
use Lithe\Orbis\Orbis;

/**
 * Adds a GET route to the routes array.
 *
 * @param string $path The route path.
 * @param callable|array $handler The handlers for the route.
 */
function get(string $path, callable|array ...$handler): void
{
    // Get the file where the function was called
    $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]; // Ignore the arguments and get only the first level
    $callingFile = $caller['file']; // Get the full path of the calling file

    // Use the full path as the key
    $key = strtolower($callingFile); // The full path of the file

    $router = Orbis::instance($key);

    if (!$router instanceof Router) {
        throw new Exception("Invalid router instance: Router not found");
    }

    $router->get($path, ...$handler); // Add the GET route to the router
}

/**
 * Adds a POST route to the routes array.
 *
 * @param string $path The route path.
 * @param callable|array $handler The handlers for the route.
 */
function post(string $path, callable|array ...$handler): void
{
    // Get the file where the function was called
    $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $callingFile = $caller['file'];

    $key = strtolower($callingFile);

    $router = Orbis::instance($key);

    if (!$router instanceof Router) {
        throw new Exception("Invalid router instance: Router not found");
    }

    $router->post($path, ...$handler); // Add the POST route to the router
}

/**
 * Adds a PUT route to the routes array.
 *
 * @param string $path The route path.
 * @param callable|array $handler The handlers for the route.
 */
function put(string $path, callable|array ...$handler): void
{
    // Get the file where the function was called
    $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $callingFile = $caller['file'];

    $key = strtolower($callingFile);

    $router = Orbis::instance($key);

    if (!$router instanceof Router) {
        throw new Exception("Invalid router instance: Router not found");
    }

    $router->put($path, ...$handler); // Add the PUT route to the router
}

/**
 * Adds a DELETE route to the routes array.
 *
 * @param string $path The route path.
 * @param callable|array $handler The handlers for the route.
 */
function delete(string $path, callable|array ...$handler): void
{
    // Get the file where the function was called
    $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $callingFile = $caller['file'];

    $key = strtolower($callingFile);

    $router = Orbis::instance($key);

    if (!$router instanceof Router) {
        throw new Exception("Invalid router instance: Router not found");
    }

    $router->delete($path, ...$handler); // Add the DELETE route to the router
}

/**
 * Adds a PATCH route to the routes array.
 *
 * @param string $path The route path.
 * @param callable|array $handler The handlers for the route.
 */
function patch(string $path, callable|array ...$handler): void
{
    // Get the file where the function was called
    $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $callingFile = $caller['file'];

    $key = strtolower($callingFile);

    $router = Orbis::instance($key);

    if (!$router instanceof Router) {
        throw new Exception("Invalid router instance: Router not found");
    }

    $router->patch($path, ...$handler); // Add the PATCH route to the router
}

/**
 * Adds an OPTIONS route to the routes array.
 *
 * @param string $path The route path.
 * @param callable|array $handler The handlers for the route.
 */
function options(string $path, callable|array ...$handler): void
{
    // Get the file where the function was called
    $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $callingFile = $caller['file'];

    $key = strtolower($callingFile);

    $router = Orbis::instance($key);

    if (!$router instanceof Router) {
        throw new Exception("Invalid router instance: Router not found");
    }

    $router->options($path, ...$handler); // Add the OPTIONS route to the router
}

/**
 * Adds a HEAD route to the routes array.
 *
 * @param string $path The route path.
 * @param callable|array $handler The handlers for the route.
 */
function head(string $path, callable|array ...$handler): void
{
    // Get the file where the function was called
    $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $callingFile = $caller['file'];

    $key = strtolower($callingFile);

    $router = Orbis::instance($key);

    if (!$router instanceof Router) {
        throw new Exception("Invalid router instance: Router not found");
    }

    $router->head($path, ...$handler); // Add the HEAD route to the router
}

/**
 * Adds a route to handle all HTTP methods.
 *
 * @param string $path The route path.
 * @param callable ...$handler The handlers (callbacks) for the route.
 */
function any(string $path, callable|array ...$handler): void
{
    // Get the file where the function was called
    $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $callingFile = $caller['file'];

    $key = strtolower($callingFile);

    $router = Orbis::instance($key);

    if (!$router instanceof Router) {
        throw new Exception("Router not found");
    }
    
    $router->any($path, ...$handler); // Add the HEAD route to the router
}

/**
 * Configures the router by including a file and returns the Router instance.
 *
 * @param string $path The path to the router configuration file.
 * @return \Lithe\Http\Router The configured Router instance.
 * @throws \Exception If the router configuration file cannot be included or if the Router class is not instantiated.
 */
function router(string $path): \Lithe\Http\Router
{
    // Replace '/' with the correct directory separator and add '.php' at the end
    $normalizedPath = str_replace('/', DIRECTORY_SEPARATOR, $path) . '.php';
    
    // Convert the path to lowercase for the registration key
    $key = strtolower($normalizedPath);

    // Check if the file exists
    if (!file_exists($normalizedPath)) {
        throw new \Exception("Router configuration file not found: {$normalizedPath}");
    }

    // Register the Router instance in Orbis
    Orbis::register(\Lithe\Http\Router::class, $key);

    // Include the router configuration file
    include_once $normalizedPath;

    return Orbis::instance($key, true); // Return the Router instance
}

/**
 * Adds middleware or a router to the application.
 *
 * @param string|callable|Router|array ...$middleware The middlewares or routers to be added.
 */
function apply(string|callable|Router|array ...$middleware): void
{
    // Get the file where the function was called
    $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $callingFile = $caller['file'];

    $key = strtolower($callingFile);

    $router = Orbis::instance($key);

    if (!$router instanceof Router) {
        throw new Exception("Invalid router instance: Router not found");
    }

    $router->use(...$middleware);
}

/**
 * Creates an object to define routes with a specific prefix.
 *
 * @param string $path The route prefix.
 * @return object An anonymous object to define routes with the provided prefix.
 */
function route(string $path): object
{
    // Get the file where the function was called
    $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $callingFile = $caller['file'];

    $key = strtolower($callingFile);

    $router = Orbis::instance($key);

    if (!$router instanceof Router) {
        throw new Exception("Invalid router instance: Router not found");
    }

    return $router->route($path);
}