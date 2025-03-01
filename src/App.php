<?php

namespace Lithe;

use Lithe\Exceptions\InvalidParameterTypeException;
use Lithe\Orbis\Orbis;
use Lithe\Support\import;
use Lithe\Support\Log;

/**
 * Main function that represents the application.
 */
class App extends \Lithe\Http\Router
{
    private const ALLOWED_OPTIONS = ['view engine', 'views', 'routes'];

    private $Settings;
    private $HttpExceptions = [];

    /**
     * Handles application settings, optionally setting or retrieving specific configurations.
     *
     * @param string $type   The type of setting (optional).
     * @param string $name   The name of the setting (optional).
     * @param mixed  $value  The value to set (optional).
     *
     * @return array  Returns the application settings array.
     */
    private function getSettings(string $type = null, string $name = null, mixed $value = null): array
    {
        $settings = import::file(__DIR__ . '/Config/app/settings.php');

        // Set a specific configuration if all parameters are provided
        if ($type && $name && $value) {
            $settings[$type][$name] = $value;
        }

        $this->Settings = $settings;
        return $settings;
    }

    /**
     * Sets a template engine for the application.
     *
     * @param string   $name    The name of the template engine.
     * @param callable $config  A configuration function for the template engine.
     *
     * @return void
     */
    public function engine(string $name, callable $config): void
    {
        // Calls the static Settings method to configure the template engine.
        // This method manages general application settings.
        self::getSettings('view engine', $name, $config);
    }


    // Default application settings
    private $Options = [
        "view engine" => "default",  // Sets the default view engine
        'views' => PROJECT_ROOT . '/views',  // Specifies the default directory for views
        'routes' =>  '',
    ];

    /**
     * Sets a value for a configuration in the application.
     *
     * @param string $name  The name of the configuration.
     * @param mixed  $value The value to be assigned to the configuration.
     * @throws InvalidArgumentException if the option does not exist.
     */
    public function set(string $name, mixed $value)
    {
        // Checks if the provided option is in the allowed list
        if (!in_array($name, self::ALLOWED_OPTIONS)) {
            // Throws an exception if the option does not exist in the allowed list
            throw new \InvalidArgumentException("The option '$name' does not exist.");
        }

        // Sets the configuration with the provided value
        $this->Options[$name] = $value;
    }


    /**
     * Creates and returns an instance of the Request object.
     *
     * This method initializes a new Request object with the provided parameters and URL.
     * The Request object encapsulates details such as URL parameters, HTTP headers,
     * and request body contents, providing a unified interface to interact with the
     * HTTP request data.
     *
     * @param object $parameters An object of parameters to be included in the request.
     * @param string $url The URL associated with the request.
     * @return \Lithe\Http\Request An instance of the Request object.
     */
    private function Request(object $parameters, string $url): \Lithe\Http\Request
    {
        // Imports the Request.php file and returns its result, which is expected to be an instance of \Lithe\Http\Request.
        return import::with(compact('parameters', 'url'))
            ->file(__DIR__ . '/Http/Request.php');
    }


    /**
     * Response object for handling HTTP responses.
     *
     * Retrieves an instance of the Response object with settings and options.
     *
     * @return \Lithe\Http\Response
     */
    private function Response(): \Lithe\Http\Response
    {
        // Retrieves settings and options
        $Settings = $this->Settings;
        $Options = $this->Options;

        // Imports the Response.php file with settings and options and returns its result,
        // which should be an instance of \Lithe\Http\Response.
        return import::with(compact('Settings', 'Options'))
            ->file(__DIR__ . '/Http/Response.php');
    }

    /**
     * Defines the handler for specific HTTP exceptions.
     *
     * @param int $status The HTTP status code for which the handler will be defined.
     * @param callable $handler The handler to deal with the HTTP exception. It should accept Lithe\Http\Request and \Lithe\Http\Response as parameters.
     * @return void
     */
    public function fail(int $status, callable $handler): void
    {
        $this->HttpExceptions[$status] = $handler;
    }

    /**
     * Handles a specific HTTP error if a handler is registered for it.
     *
     * @param int $statusCode HTTP status code.
     * @param \Lithe\Http\Request $request The HTTP request object.
     * @param \Lithe\Http\Response $response The HTTP response object.
     * @param mixed $exception Optional. The exception that caused the HTTP error.
     * @return void
     */
    private function handleHttpException(int $statusCode, \Lithe\Http\Request $request, \Lithe\Http\Response $response, $exception = null): void
    {
        // Call error handler for the specific status code if defined
        $errorHandler = $this->HttpExceptions[$statusCode] ?? null;
        if ($errorHandler && is_callable($errorHandler)) {
            // Determine arguments to pass to the error handler callback
            $args = [$request, $response];
            if ($exception instanceof \Exception) {
                $args[] = $exception;
            }
            // Call the error handler callback with appropriate arguments
            call_user_func_array($errorHandler, $args);
        }
    }

    /**
     * Método que inicia o carregamento das rotas e monta a hierarquia.
     */
    public function loadRoutes()
    {
        $routesDir = $this->Options['routes'];
        if (!is_dir($routesDir)) {
            return;
        }

        // 1. Varre recursivamente o diretório e obtém todos os arquivos PHP
        $files = $this->scanDirectory($routesDir);

        // 2. Para cada arquivo, calcula a rota correspondente e obtém o objeto Router
        // A chave será a rota (ex.: '/', '/cart', '/cart/dest', etc)
        $routers = [];
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }
            $routeName = $this->computeRouteName($file, $routesDir);
            $router = $this->getRouterFromFile($file);
            if ($router) {
                $routers[$routeName] = $router;
            }
        }

        // 3. Ordena os roteadores pela profundidade (rotas mais profundas primeiro)
        uksort($routers, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        // 4. Percorre os roteadores e, se houver um pai para a rota atual, monta-o como sub-router
        foreach ($routers as $routeName => $router) {
            if ($routeName === '/' || $routeName === '') {
                continue; // Rota raiz não tem pai
            }
            $parentRoute = $this->getParentRoute($routeName);
            if ($parentRoute !== false && isset($routers[$parentRoute])) {
                // Calcula o caminho relativo que será usado ao montar no pai.
                $relative = substr($routeName, strlen($parentRoute));
                if ($relative === false || $relative === '') {
                    $relative = '/';
                }
                if ($relative[0] !== '/') {
                    $relative = '/' . $relative;
                }
                // Monta o roteador filho no pai
                $routers[$parentRoute]->use($relative, $router);
                // Remove da lista de roteadores de nível superior, pois já está montado
                unset($routers[$routeName]);
            }
        }

        // 5. Por fim, monta os roteadores restantes (normalmente a raiz e outros de nível superior) na aplicação
        foreach ($routers as $routeName => $router) {
            $this->use($routeName, $router);
        }
    }

    /**
     * Varre recursivamente um diretório em busca de arquivos PHP.
     *
     * @param string $directory Diretório a ser escaneado.
     * @return array Lista dos caminhos completos dos arquivos PHP.
     */
    private function scanDirectory(string $directory): array
    {
        $files = [];
        $items = array_diff(scandir($directory), ['.', '..']);
        foreach ($items as $item) {
            $path = "$directory/$item";
            if (is_dir($path)) {
                $files = array_merge($files, $this->scanDirectory($path));
            } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $files[] = $path;
            }
        }
        return $files;
    }

    /**
     * Calcula o nome da rota com base no caminho do arquivo.
     * Remove o diretório base e a extensão, normalizando as barras.
     *
     * @param string $file Caminho completo do arquivo.
     * @param string $baseDir Diretório base das rotas.
     * @return string Nome da rota (ex.: '/', '/cart', '/cart/dest').
     */
    private function computeRouteName(string $file, string $baseDir): string
    {
        $route = str_replace([$baseDir, '.php'], '', $file);
        $route = str_replace(DIRECTORY_SEPARATOR, '/', $route);
        $route = rtrim($route, '/');
        return $route === '' ? '/' : $route;
    }

    /**
     * Obtém o nome da rota pai com base na rota atual.
     *
     * Exemplo: para "/cart/dest", retorna "/cart".
     *
     * @param string $route Rota atual.
     * @return mixed Rota pai ou false se não houver.
     */
    private function getParentRoute(string $route)
    {
        if ($route === '/' || $route === '') {
            return false;
        }
        $parts = explode('/', ltrim($route, '/'));
        array_pop($parts);
        $parent = '/' . implode('/', $parts);
        return $parent === '' ? '/' : $parent;
    }

    /**
     * Obtém o objeto Router a partir do arquivo.
     *
     * Tenta incluir o arquivo, que deve retornar uma instância de \Lithe\Http\Router,
     * ou tenta recuperar uma instância já registrada.
     *
     * @param string $file Caminho do arquivo.
     * @return \Lithe\Http\Router|null Retorna o objeto Router ou null em caso de erro.
     */
    private function getRouterFromFile(string $file): ?\Lithe\Http\Router
    {
        try {
            $key = strtolower(str_replace('/', DIRECTORY_SEPARATOR, $file));
            Orbis::register(\Lithe\Http\Router::class, $key);
            $router = require($file);
            if ($router instanceof \Lithe\Http\Router) {
                return $router;
            } else {
                return $this->createRouterFromFile($key);
            }
        } catch (\Exception $e) {
            error_log("Erro ao registrar rota do arquivo {$file}: " . $e->getMessage());
            Log::error("Erro ao registrar rota do arquivo {$file}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cria ou recupera o Router a partir do container usando a chave informada.
     *
     * @param string $key Chave única baseada no caminho do arquivo.
     * @return \Lithe\Http\Router
     * @throws \Exception Caso não seja encontrado o Router.
     */
    private function createRouterFromFile(string $key): \Lithe\Http\Router
    {
        $router = Orbis::instance($key, true);
        if (!$router instanceof \Lithe\Http\Router) {
            throw new \Exception("Router não encontrado para a chave {$key}");
        }
        return $router;
    }

    /**
     * Listens for incoming requests, processes them, and handles errors.
     *
     * @return void
     */
    public function listen()
    {
        // Initialize settings if not already set
        $this->Settings = $this->Settings ?? $this->getSettings();
        $this->loadRoutes();
        // Match the request to a route
        $matchedRouteInfo = $this->findRouteAndParams();

        ['route' => $route, 'params' => $params] = $matchedRouteInfo;

        // Get the request and response objects
        $request = $this->Request($params, $this->url());
        $response = $this->Response();

        Orbis::register($response, '\Lithe\Http\Response');

        try {

            // Run global middlewares
            $this->runMiddlewares($this->middlewares, $request, $response, function () use ($request, $response, $route) {
                if ($route) {
                    // Handle the matched route
                    $this->handleRoute($route, $request, $response);
                } else {
                    // Route not found, throw a 404 Not Found exception
                    throw new \Lithe\Exceptions\Http\HttpException(404, 'Not found');
                }
            });
        } catch (\Lithe\Exceptions\Http\HttpException $e) {
            // Handle HTTP exceptions (e.g., 404 Not Found)
            $this->handleHttpException($e->getStatusCode(), $request, $response, $e);
            $this->sendErrorPage($response, $e->getStatusCode(), $e->getMessage());
        } catch (\Exception $e) {
            // Handle general exceptions
            \Lithe\Support\Log::error($e->getMessage());
            $this->handleHttpException(500, $request, $response, $e);
            $this->sendErrorPage($response, 500, 'Internal Server Error');
        }
    }

    /**
     * Processes the current URL to remove the project context and returns the clean path.
     *
     * This function handles URLs both at the root (localhost:8000) and in subdirectories (localhost/project).
     *
     * @return string The clean URL path, starting with a slash.
     */
    private function url(): string
    {
        // Get the full URL of the request.
        $requestUri = $_SERVER['REQUEST_URI'];

        // Get the path of the currently executing script.
        $scriptName = $_SERVER['SCRIPT_NAME'];

        // Extract the project context by removing the script name from the script path.
        // For example, if SCRIPT_NAME is "/project/index.php", projectContext will be "/project/".
        $projectContext = str_replace(basename($scriptName), '', $scriptName);

        // Parse the full URL to get just the path.
        // For example, if REQUEST_URI is "/project/home", path will be "/project/home".
        $path = parse_url($requestUri, PHP_URL_PATH);

        // If the path starts with the project context, remove that context from the path.
        // This adjusts the path to work correctly even in subdirectories.
        if (strpos($path, $projectContext) === 0) {
            // Substring removes the project context from the start of the path.
            $path = substr($path, strlen($projectContext));
        }

        // Return the clean path, ensuring it starts with a slash.
        // This adds a slash at the beginning and removes extra slashes from the start.
        return '/' . ltrim($path, '/');
    }

    /**
     * Finds the matching route for the current request and extracts parameters from the URL.
     *
     * @return array An array containing the matched route and the extracted parameters, or an empty array if no route matches.
     */
    private function findRouteAndParams(): array
    {
        // Get the HTTP method and URL from the request
        $method = $_SERVER['REQUEST_METHOD'];
        $url = $this->url();

        // Iterate through routes to find a match
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchRoute($route['route'], $url)) {
                // Extract parameters from the URL
                $params = $this->extractParams($route['route'], $url);

                // Return the matched route and extracted parameters
                return [
                    'route' => $route,
                    'params' => $params
                ];
            }
        }

        // No route matched
        return [
            'route' => null,
            'params' => new class() {
                public function __get($name)
                {
                    return $this->$name ?? null;
                }
            }
        ];
    }

    /**
     * Handles the processing of a matched route.
     *
     * @param array $route The route information.
     * @param \Lithe\Http\Request $request The HTTP request.
     * @param \Lithe\Http\Response $response The HTTP response.
     * @return void
     */
    private function handleRoute(array $route, \Lithe\Http\Request $request, \Lithe\Http\Response $response)
    {
        // Run route-specific middlewares
        $this->runMiddlewares($route['handler'], $request, $response, function () use ($response) {
            // End the response
            $response->end();
        });
    }

    /**
     * Executa a cadeia de middlewares.
     *
     * @param array $middlewares Lista de middlewares.
     * @param mixed $request Objeto ou dados da requisição.
     * @param mixed $response Objeto ou dados da resposta.
     * @param callable $next Callback a ser chamado ao final da cadeia.
     */
    protected function runMiddlewares($middlewares, $request, $response, $next)
    {
        if (empty($middlewares)) {
            $next();
            return;
        }

        $index = 0;

        // Função interna para chamar o próximo middleware
        $runNextMiddleware = function () use ($middlewares, $request, $response, &$index, $next, &$runNextMiddleware) {
            if ($index < count($middlewares)) {
                $middleware = $middlewares[$index++];

                // Se o middleware for um array no formato [controller, 'method']
                if (is_array($middleware) && count($middleware) === 2) {
                    [$controller, $method] = $middleware;

                    if (is_string($controller)) {
                        $refMethod = new \ReflectionMethod($controller, $method);
                        if ($refMethod->isStatic()) {
                            // Middleware estático
                            $middleware = function ($req, $res, $next) use ($controller, $method) {
                                $controller::$method($req, $res, $next);
                            };
                        } else {
                            // Middleware não estático: instancia o controlador
                            $instance = new $controller();
                            $middleware = function ($req, $res, $next) use ($instance, $method) {
                                $instance->$method($req, $res, $next);
                            };
                        }
                    } elseif (is_object($controller)) {
                        // Se já for uma instância, chama o método diretamente
                        $middleware = function ($req, $res, $next) use ($controller, $method) {
                            $controller->$method($req, $res, $next);
                        };
                    }
                }

                // Executa o middleware atual
                $middleware($request, $response, $runNextMiddleware);
            } else {
                // Quando todos os middlewares forem executados, chama o callback final
                $next();
            }
        };

        // Inicia a execução dos middlewares
        $runNextMiddleware();
    }

    /**
     * Sends an error page response.
     *
     * @param \Lithe\Http\Response $response The response object to send.
     * @param int $statusCode The HTTP status code for the error.
     * @param string $message The error message to display.
     * @return void
     */
    private function sendErrorPage(\Lithe\Http\Response $response, int $statusCode, string $message)
    {
        // Set the HTTP status code and send the HTML error page
        $response->status($statusCode)->send('<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . $message . '</title><meta name="robots" content="noindex, nofollow"><style>body{margin:0;padding:0;display:flex;justify-content:center;align-items:center;height:100vh;color:#6c757d;font-family:Arial,sans-serif;}.container{text-align:center;}.error-code{font-size:1.1rem;margin-bottom:0.5rem;display:flex;align-items:center;justify-content:center;text-transform:uppercase;font-family:\'Segoe UI\',Tahoma,Geneva,Verdana,sans-serif;}.icon{width:1rem;height:3rem;margin:0 0.5rem;fill:#6c757d;}</style></head><body><div class="container"><div class="error-code">' . $statusCode . '<svg class="icon" viewBox="0 0 1 10" xmlns="http://www.w3.org/2000/svg"><line x1="0" y1="0" x2="0" y2="10" stroke="#6c757d" stroke-width="0.1"/></svg>' . $message . '</div></div></body></html>');
    }

    /**
     * Checks if the URL matches the route pattern and validates parameter types.
     *
     * @param string $routePattern The route pattern.
     * @param string $url The URL to be checked.
     * @return bool True if the URL matches the route pattern and parameter types are valid, False otherwise.
     * @throws InvalidParameterTypeException If an invalid parameter type is encountered.
     */
    private function matchRoute(string $routePattern, string $url): bool
    {
        // Remove leading slash from route pattern and URL
        $routePattern = ltrim($routePattern, '/');
        $url = ltrim($url, '/');

        // Build regex pattern
        $pattern = "#^" . preg_replace_callback('/:(\w+)(?:=([^\/]+))?/', function ($matches) {
            $paramName = $matches[1];
            $paramType = $matches[2] ?? 'string'; // Default type is string if not specified
            return "(?P<" . $paramName . ">" . $this->getPatternForType($paramType) . ")";
        }, $routePattern) . "$#";

        // Execute URL matching against the route pattern
        if (!preg_match($pattern, $url, $matches)) {
            return false;
        }

        // Validate parameter types
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $expectedTypes = $this->getExpectedType($routePattern, $key);
                if (!$this->validateParameterType($value, $expectedTypes)) {
                    return false;
                }
            }
        }

        // Check for optional parameters in route that were not matched
        if (strpos($routePattern, '?') !== false && !empty($matches)) {
            return false;
        }

        return true;
    }

    /**
     * Returns the regex pattern for a parameter type specified in the route.
     *
     * @param string $types Parameter types specified in the route (e.g., 'int|uuid|regex<\d+>').
     * @return string Regex pattern for the parameter type.
     */
    private function getPatternForType(string $types): string
    {
        $patterns = [];

        // Split parameter types using '|', except within 'regex<...>'
        $parts = preg_split('/(regex<[^>]+>)/', $types, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $part) {
            if (strpos($part, 'regex<') === 0) {
                // Handle custom regex patterns inside 'regex<...>'
                $regex = substr($part, 6, -1);
                $patterns[] = "($regex)";
            } elseif (strpos($part, '|') !== false) {
                // Handle standard parameter types separated by '|'
                $subParts = explode('|', $part);
                foreach ($subParts as $subPart) {
                    if (!empty(trim($subPart))) { // Check if part is not empty or only contains spaces
                        $patterns[] = $this->getPatternForStandardType($subPart);
                    }
                }
            } elseif (!empty(trim($part))) { // Check if part is not empty or only contains spaces
                // Handle standard parameter types
                $patterns[] = $this->getPatternForStandardType($part);
            }
        }

        return implode('|', $patterns);
    }

    /**
     * Returns the regex pattern for a standard parameter type.
     *
     * @param string $type Standard parameter type.
     * @return string Regex pattern for the parameter type.
     * @throws InvalidParameterTypeException If an invalid parameter type is encountered.
     */
    private function getPatternForStandardType(string $type): string
    {
        switch ($type) {
            case 'int':
                return '[0-9]+';
            case 'string':
                return '[^/]+';
            case 'uuid':
                return '[a-f\d]{8}(-[a-f\d]{4}){3}[a-f\d]{12}';
            case 'date':
                return '\d{4}-\d{1,2}-\d{1,2}';
            case 'email':
                return '[^\s@]+@[^\s@]+\.[^\s@]+';
            case 'bool':
                return '(false|true|0|1)';
            case 'float':
                return '[-+]?[0-9]*\.?[0-9]+';
            case 'slug':
                return '[a-z0-9]+(?:-[a-z0-9]+)*';
            case 'username':
                return '[a-zA-Z0-9_]{3,20}';
            case 'tel':
                return '\+?[\d\-\(\)]+';
            case 'file':
                return '[^/]+(?:\.([^.]+))';
            case 'alphanumeric':
                return '[a-zA-Z0-9]+';
            default:
                throw new InvalidParameterTypeException("Invalid parameter type: $type");
        }
    }

    /**
     * Returns the expected types for a parameter based on the route pattern.
     *
     * @param string $routePattern Route pattern.
     * @param string $paramName Parameter name.
     * @return string Expected types for the parameter.
     */
    private function getExpectedType(string $routePattern, string $paramName): string
    {
        preg_match('/:' . $paramName . '=([^\/]+)/', $routePattern, $matches);
        return $matches[1] ?? 'string';
    }

    /**
     * Extracts parameters from a URL based on the route pattern.
     *
     * @param string $routePattern The pattern of the route.
     * @param string $url The URL from which parameters will be extracted.
     * @return object An object containing the extracted parameters.
     * @throws InvalidParameterTypeException If an invalid parameter type is encountered.
     */
    private function extractParams(string $routePattern, string $url): object
    {
        // Anonymous class to store parameters dynamically
        $params = new class()
        {
            /**
             * Magic method to retrieve parameter values.
             *
             * @param string $name The name of the parameter.
             * @return mixed|null Returns the parameter value if defined, or null if not found.
             */
            public function __get($name)
            {
                return $this->$name ?? null;
            }
        };

        // Remove leading slashes from route pattern and URL
        $routePattern = ltrim($routePattern, '/');
        $url = ltrim($url, '/');

        // Replace ':param=type' syntax with '{param:type}' for regex
        $pattern = "#^" . preg_replace_callback('/:(\w+)(?:=([^\/]+))?/', function ($matches) {
            $paramName = $matches[1];
            $paramType = $matches[2] ?? 'string'; // Default type is string if not specified
            return "(?P<" . $paramName . ">" . $this->getPatternForType($paramType) . ")";
        }, $routePattern) . "$#";

        // Match the URL against the route pattern
        if (!preg_match($pattern, $url, $matches)) {
            return $params; // Return an empty object if there's no match
        }

        // Extract and convert matched parameters
        foreach ($matches as $key => $value) {
            if (is_string($key) && !is_numeric($key)) { // Ensure $key is a string and not numeric
                $expectedTypes = $this->getExpectedType($routePattern, $key);
                $params->$key = $this->convertParameterValue($value, $expectedTypes);
            }
        }

        return $params;
    }

    /**
     * Validates the value of a parameter according to the specified types.
     *
     * @param mixed $value The parameter value.
     * @param string $types The types of the parameter.
     * @return bool True if the value matches any of the types, False otherwise.
     * @throws InvalidParameterTypeException If the parameter type is invalid.
     */
    private function validateParameterType($value, string $types): bool
    {
        // Split types by '|', handling cases inside 'regex<...>'
        $typesArray = $this->parseTypes($types);

        foreach ($typesArray as $type) {
            // Check if it's a custom regular expression 'regex<...>'
            if (strpos($type, 'regex<') === 0 && substr($type, -1) === '>') {
                $regexPattern = substr($type, 6, -1); // Get the regex inside regex<...>
                if (preg_match("/$regexPattern/", $value) === 1) return true; // Validate the value with regex
            } else {
                // Handle standard types like 'int', 'float', etc.
                switch ($type) {
                    case 'int':
                        if (preg_match("#^[0-9]+$#", $value) === 1) return true;
                        break;
                    case 'float':
                        if (is_float($value) || (is_numeric($value) && strpos($value, '.') !== false)) return true;
                        break;
                    case 'bool':
                        if (is_bool($value) || in_array(strtolower($value), ['true', 'false', '1', '0'], true)) return true;
                        break;
                    case 'uuid':
                        if (preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', $value)) return true;
                        break;
                    case 'date':
                        if (strtotime($value)) return true;
                        break;
                    case 'email':
                        if (filter_var($value, FILTER_VALIDATE_EMAIL) !== false) return true;
                        break;
                    case 'slug':
                        if (preg_match("#^[a-z0-9]+(?:-[a-z0-9]+)*$#", $value) === 1) return true;
                        break;
                    case 'username':
                        if (preg_match("#^[a-zA-Z0-9_]{3,20}$#", $value) === 1) return true;
                        break;
                    case 'tel':
                        if (preg_match("#^\+?[\d\-\(\)]+$#", $value) === 1) return true;
                        break;
                    case 'file':
                        if (preg_match("#^[^/]+(?:\.([^.]+))$#", $value) === 1) return true;
                        break;
                    case 'alphanumeric':
                        if (preg_match("#^[a-zA-Z0-9]+$#", $value) === 1) return true;
                        break;
                    case 'string':
                        return true;
                    default:
                        // If no type matches, throw an exception
                        throw new InvalidParameterTypeException("Invalid parameter type: $type");
                }
            }
        }

        return false;
    }

    /**
     * Parses the types string into an array of individual types.
     *
     * @param string $types The types string to parse (e.g., 'int|uuid|regex<\d+>').
     * @return array An array containing individual types parsed from the string.
     */
    private function parseTypes(string $types): array
    {
        $typesArray = [];
        $currentType = '';
        $depth = 0;

        // Iterate through each character in the $types string
        for ($i = 0; $i < strlen($types); $i++) {
            $char = $types[$i];

            // Check if a '|' separator is found at the outermost level
            if ($char === '|' && $depth === 0) {
                $typesArray[] = trim($currentType); // Add the current type to the array
                $currentType = ''; // Reset the current type string for the next type
            } else {
                $currentType .= $char; // Append the character to the current type string

                // Update depth to handle '<' and '>'
                if ($char === '<') {
                    $depth++;
                } elseif ($char === '>') {
                    $depth--;
                }
            }
        }

        // Add the last found type to the array, if any remains
        if (!empty($currentType)) {
            $typesArray[] = trim($currentType);
        }

        return $typesArray;
    }

    /**
     * Converts the parameter value to the first valid specified type.
     *
     * @param mixed $value The parameter value.
     * @param string $types The types of the parameter.
     * @return mixed The converted value to the first valid type.
     * @throws InvalidParameterTypeException If the parameter type is invalid.
     */
    private function convertParameterValue($value, string $types)
    {
        foreach (explode('|', $types) as $type) {
            try {
                switch ($type) {
                    case 'int':
                        if (preg_match("#^[0-9]+$#", $value)) return (int) $value;
                        break;
                    case 'float':
                        if (is_numeric($value) && strpos($value, '.') !== false) return (float) $value;
                        break;
                    case 'bool':
                        if (is_bool($value) || in_array(strtolower($value), ['true', 'false', '1', '0'], true)) return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        break;
                    case 'uuid':
                        if (preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', $value)) return $value;
                        break;
                    case 'date':
                        if (strtotime($value)) return date('Y-m-d', strtotime($value));
                        break;
                    case 'email':
                        if (filter_var($value, FILTER_VALIDATE_EMAIL) !== false) return $value;
                        break;
                    case 'slug':
                        if (preg_match("#^[a-z0-9]+(?:-[a-z0-9]+)*$#", $value)) return $value;
                        break;
                    case 'username':
                        if (preg_match("#^[a-zA-Z0-9_]{3,16}$#", $value)) return $value;
                        break;
                    case 'tel':
                        if (preg_match("#^\+?[\d\-\(\)]+$#", $value)) return $value;
                        break;
                    case 'file':
                        if (preg_match("#^[^/]+(?:\.([^.]+))$#", $value)) return $value;
                        break;
                    case 'alphanumeric':
                        if (preg_match("#^[a-zA-Z0-9]+$#", $value)) return $value;
                        break;
                    case 'string':
                        return (string) $value;
                }
            } catch (\Exception $e) {
                // Continue to the next type if conversion fails
            }
        }
        return (string) $value; // Default to string if no types match
    }
}
