<?php

use PHPUnit\Framework\TestCase;

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', __DIR__);
}

class AppTest extends TestCase
{
    private \Lithe\App $app;
    
    protected function setUp(): void
    {
        // Initial setup for each test
        $this->app = new \Lithe\App;
    }

    /**
     * Tests the matchRoute method with a valid URL and an invalid URL.
     */
    public function testMatchRoute()
    {
        // Use reflection to access the private method matchRoute
        $reflection = new ReflectionClass($this->app);
        $method = $reflection->getMethod('matchRoute');
        $method->setAccessible(true);

        // Define some route patterns and URLs for testing
        $routePattern = '/user/:id=int';
        $validUrl = '/user/123';
        $invalidUrl = '/user/abc';

        // Test with a valid URL
        $result = $method->invoke($this->app, $routePattern, $validUrl);
        $this->assertTrue($result);

        // Test with an invalid URL
        $result = $method->invoke($this->app, $routePattern, $invalidUrl);
        $this->assertFalse($result);
    }

    /**
     * Tests the matchRoute method with optional parameters in the URL.
     */
    public function testMatchRouteWithOptionalParameters()
    {
        // Use reflection to access the private method matchRoute
        $reflection = new ReflectionClass($this->app);
        $method = $reflection->getMethod('matchRoute');
        $method->setAccessible(true);

        // Define some route patterns and URLs for testing
        $routePattern = '/user/:id';
        $validUrlWithParam = '/user/123';
        $invalidUrl = '/user/123/extra';

        // Test with a valid URL containing the parameter
        $result = $method->invoke($this->app, $routePattern, $validUrlWithParam);
        $this->assertTrue($result);

        // Test with an invalid URL
        $result = $method->invoke($this->app, $routePattern, $invalidUrl);
        $this->assertFalse($result);
    }

    /**
     * Tests the matchRoute method with multiple parameters in the URL.
     */
    public function testMatchRouteWithMultipleParameters()
    {
        // Use reflection to access the private method matchRoute
        $reflection = new ReflectionClass($this->app);
        $method = $reflection->getMethod('matchRoute');
        $method->setAccessible(true);

        // Define some route patterns and URLs for testing
        $routePattern = '/user/:id=int/post/:postId=int';
        $validUrl = '/user/123/post/456';
        $invalidUrl = '/user/123/post/abc';

        // Test with a valid URL
        $result = $method->invoke($this->app, $routePattern, $validUrl);
        $this->assertTrue($result);

        // Test with an invalid URL
        $result = $method->invoke($this->app, $routePattern, $invalidUrl);
        $this->assertFalse($result);
    }

    /**
     * Tests the matchRoute method with complex route patterns.
     */
    public function testMatchRouteWithComplexPatterns()
    {
        // Use reflection to access the private method matchRoute
        $reflection = new ReflectionClass($this->app);
        $method = $reflection->getMethod('matchRoute');
        $method->setAccessible(true);

        // Define some route patterns and URLs for testing
        $routePattern = '/user/:id=int/profile/:section';
        $validUrl = '/user/123/profile/settings';
        $invalidUrl = '/user/123/profile';

        // Test with a valid URL
        $result = $method->invoke($this->app, $routePattern, $validUrl);
        $this->assertTrue($result);

        // Test with an invalid URL
        $result = $method->invoke($this->app, $routePattern, $invalidUrl);
        $this->assertFalse($result);
    }
}
