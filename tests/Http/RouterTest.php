<?php

use Lithe\Http\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    protected Router $router;

    protected function setUp(): void
    {
        // Initialize the Router instance before each test
        $this->router = new Router;
    }

    public function testGetRouteIsAdded()
    {
        // Define a GET route with the path '/test'
        $this->router->get('/test', function () {
            return 'Test GET';
        });

        // Retrieve the registered routes
        $routes = $this->getRoutes();
        
        // Assert that exactly one route is registered
        $this->assertCount(1, $routes);
        // Assert that the HTTP method of the route is GET
        $this->assertEquals('GET', $routes[0]['method']);
        // Assert that the route path is '/test'
        $this->assertEquals('/test', $routes[0]['route']);
    }

    public function testPostRouteIsAdded()
    {
        // Define a POST route with the path '/test'
        $this->router->post('/test', function () {
            return 'Test POST';
        });

        // Retrieve the registered routes
        $routes = $this->getRoutes();
        
        // Assert that exactly one route is registered
        $this->assertCount(1, $routes);
        // Assert that the HTTP method of the route is POST
        $this->assertEquals('POST', $routes[0]['method']);
        // Assert that the route path is '/test'
        $this->assertEquals('/test', $routes[0]['route']);
    }

    public function testRouteWithPrefix()
    {
        // Define a route with a prefix '/api' and a GET method
        $this->router->route('/api')->get(function () {
            return 'User';
        });

        // Retrieve the registered routes
        $routes = $this->getRoutes();
        
        // Assert that exactly one route is registered
        $this->assertCount(1, $routes);
        // Assert that the HTTP method of the route is GET
        $this->assertEquals('GET', $routes[0]['method']);
        // Assert that the route path is '/api'
        $this->assertEquals('/api', $routes[0]['route']);
    }

    public function testAnyMethodRoute()
    {
        $router = new Router;

        // Define a route that accepts any HTTP method
        $router->any('/test', function () {
            return 'Any Method';
        });

        // Retrieve the registered routes
        $routes = $this->getRoutes($router);

        // Define the expected routes for different HTTP methods
        $expectedRoutes = [
            ['method' => 'GET', 'route' => '/test'],
            ['method' => 'POST', 'route' => '/test'],
            ['method' => 'PUT', 'route' => '/test'],
            ['method' => 'DELETE', 'route' => '/test'],
            ['method' => 'PATCH', 'route' => '/test'],
            ['method' => 'OPTIONS', 'route' => '/test'],
            ['method' => 'HEAD', 'route' => '/test']
        ];

        // Check if each expected route exists in the registered routes
        foreach ($expectedRoutes as $expectedRoute) {
            $this->assertTrue(
                $this->routeExists($expectedRoute, $routes),
                "Failed asserting that the route array contains: " . print_r($expectedRoute, true)
            );
        }
    }

    /**
     * Helper function to check if a route exists in the routes array.
     *
     * @param array $expectedRoute The expected route.
     * @param array $routes The routes to check against.
     * @return bool True if the route exists, false otherwise.
     */
    private function routeExists(array $expectedRoute, array $routes): bool
    {
        foreach ($routes as $route) {
            if ($route['method'] === $expectedRoute['method'] && $route['route'] === $expectedRoute['route']) {
                return true;
            }
        }
        return false;
    }

    public function testMatchMethodRoute()
    {
        $router = new Router;

        // Define a route that matches GET and POST methods
        $router->match(['GET', 'POST'], '/test', function () {
            return 'Match Method';
        });

        // Retrieve the registered routes
        $routes = $this->getRoutes($router);

        // Assert that there are exactly 2 routes
        $this->assertCount(2, $routes);

        // Check if each expected method is present in the routes
        foreach (['GET', 'POST'] as $method) {
            $this->assertTrue(
                $this->routeExists(['method' => $method, 'route' => '/test'], $routes),
                "Failed asserting that the route array contains method $method and route /test."
            );
        }
    }
    
    private function getRoutes(?Router $router = null): array
    {
        // Retrieve the 'routes' property from the Router instance using reflection
        $router = $router ?? $this->router;
        $reflector = new ReflectionClass($router);
        $property = $reflector->getProperty('routes');
        $property->setAccessible(true);
        return $property->getValue($router);
    }

    private function getMiddlewares(?Router $router = null): array
    {
        // Retrieve the 'middlewares' property from the Router instance using reflection
        $router = $router ?? $this->router;
        $reflector = new ReflectionClass($router);
        $property = $reflector->getProperty('middlewares');
        $property->setAccessible(true);
        return $property->getValue($router);
    }
}
