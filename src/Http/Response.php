<?php

/**
 * Class Response to handle HTTP responses.
 */

return new class($Settings, $Options) implements \Lithe\Http\Response
{
    private array $Settings;
    private array $Options;
    protected $statusCode = null;

    public function __construct(array $Settings, array $Options)
    {
        $this->Settings = $Settings;
        $this->Options = $Options;
    }

    /**
     * Renders a view.
     *
     * @param string $file Name of the view file.
     * @param array|null $data Data to be passed to the view.
     * @throws \InvalidArgumentException If the view engine is not configured correctly.
     */
    public function render(string $file, ?array $data = []): void
    {
        $viewEngine = $this->Options['view engine'] ?? null;
        $views = $this->Options['views'] ?? null;

        // Check if view engine and views are configured
        if ($viewEngine === null || $views === null) {
            throw new \InvalidArgumentException("View configurations are not properly defined.");
        }

        // Check if the view engine is configured and callable
        if (isset($this->Settings['view engine'][$viewEngine]) && is_callable($this->Settings['view engine'][$viewEngine])) {
            $this->Settings['view engine'][$viewEngine]($file, $data, $views);
        } else {
            throw new \InvalidArgumentException("The view engine '$viewEngine' is not configured correctly.");
        }
        $this->end();
    }

    /**
     * Returns the current HTTP status code of the response.
     *
     * @return int|null Current HTTP status code.
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode; // Returns the current HTTP status code of the response
    }

    /**
     * Renders a view.
     *
     * @param string $file Name of the view file.
     * @param array|null $data Data to be passed to the view.
     */
    public function view(string $file, ?array $data = []): void
    {
        $file = str_replace('.', '/', $file);
        $this->render($file, $data);
    }

    /**
     * Sends a response, which can be serialized JSON data.
     *
     * @param mixed $data Data to be sent as response.
     */
    public function send(mixed $data): void
    {
        if (is_array($data) || is_object($data)) {
            $this->json($data);
        } else {
            echo $data;
        }
        $this->end();
    }

    /**
     * Redirects to a location using an HTTP redirect.
     *
     * @param string $url URL to redirect to.
     * @param bool $permanent Is this a permanent redirect? (default is false).
     * @return void
     */
    public function redirect(string $url, bool $permanent = false): void
    {
        $code = $permanent ? 301 : 302;
        $this->status($code);
        $this->setHeader("Location", $url);
        $this->end();
    }

    /**
     * Sends a response in JSON format.
     *
     * @param mixed $data Data to be sent as JSON response.
     */
    public function json(mixed $data): void
    {
        $this->type("application/json; charset=utf-8");
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->end();
    }

    /**
     * Sets the HTTP status code for the response.
     *
     * @param int $statusCode HTTP status code.
     * @return \Lithe\Http\Response Current Response object for chaining.
     */
    public function status(int $statusCode): self
    {
        http_response_code($statusCode); // Sets the new status code
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Sets an HTTP header in the response.
     *
     * @param string $name Name of the header.
     * @param string|null $value Value of the header.
     * @return \Lithe\Http\Response Current Response object for chaining.
     */
    public function setHeader(string $name, ?string $value = null): self
    {
        if ($value === null) {
            header($name);
        } else {
            header("$name: $value");
        }
        return $this;
    }

    /**
     * Ends the response by sending headers and status code, then exiting the script.
     *
     * @param string|null $message Optional message to send before ending.
     */
    public function end(?string $message = null): void
    {
        if ($message) {
            $this->send($message);
        }
        exit;
    }

    /**
     * Sends a file for download.
     *
     * @param string $file Path to the file.
     * @param string|null $name Name of the file for download.
     * @param array $headers Additional headers.
     * @throws \Lithe\Exceptions\Http\HttpException If the file is not found (404 Not Found).
     * @return void
     */
    public function download(string $file, ?string $name = null, array $headers = []): void
    {
        // Check if the file exists
        if (!file_exists($file)) {
            throw new \Lithe\Exceptions\Http\HttpException(404, 'File not found');
        }

        // Set the name for the download (if not provided, use the base name of the file)
        $name = $name ?: basename($file);

        // Set HTTP headers for file download
        $this->setHeaders(array_merge([
            'Content-Description' => 'File Transfer',
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'Expires' => '0',
            'Cache-Control' => 'must-revalidate',
            'Pragma' => 'public',
            'Content-Length' => filesize($file),
        ], $headers));

        // Send the file content to the client
        readfile($file);

        // End the response
        $this->end();
    }

    /**
     * Sets multiple headers at once.
     *
     * @param array $headers Associative array of headers.
     * @return self Current Response object for chaining.
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    /**
     * Displays a file in the browser.
     *
     * @param string $file Path to the file.
     * @param array $headers Additional headers.
     * @throws \Lithe\Exceptions\Http\HttpException If the file is not found (404 Not Found).
     * @return void
     */
    public function file(string $file, array $headers = []): void
    {
        // Check if the file exists
        if (!file_exists($file)) {
            throw new \Lithe\Exceptions\Http\HttpException(404, 'File not found');
        }

        // Set HTTP headers for displaying the file
        $this->setHeaders(array_merge([
            'Content-Type' => mime_content_type($file),
            'Content-Length' => filesize($file),
        ], $headers));

        // Send the file content to the client
        readfile($file);

        // End the response
        $this->end();
    }

    /**
     * Sets a new cookie.
     *
     * @param string $name The name of the cookie.
     * @param mixed $value The value of the cookie.
     * @param array $options Options to configure the cookie (default: []).
     *   - 'expire' (int): Expiration time of the cookie in seconds from the current time (default: 0).
     *   - 'path' (string): Path on the server where the cookie will be available (default: '/').
     *   - 'domain' (string): The domain for which the cookie is available (default: null).
     *   - 'secure' (bool): Indicates if the cookie should be transmitted only over a secure HTTPS connection (default: false).
     *   - 'httponly' (bool): When true, the cookie can only be accessed through HTTP protocol (default: true).
     * @return \Lithe\Http\Response Returns the Response object for method chaining.
     * @throws \RuntimeException If headers have already been sent.
     */
    public function cookie(string $name, $value, array $options = []): \Lithe\Http\Response
    {
        // Validate parameters
        if (empty($name) || !is_string($name)) {
            throw new \InvalidArgumentException('Cookie name must be a non-empty string.');
        }

        // Ensure value is a string or can be converted to a string
        if (!is_scalar($value) && !is_null($value)) {
            throw new \InvalidArgumentException('Cookie value must be a scalar or null.');
        }

        // Default options for the cookie
        $defaults = [
            'expire' => 0,
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'httponly' => true,
        ];

        // Merge the provided options with the default options
        $options = array_merge($defaults, $options);

        // Check if headers have been sent
        if (headers_sent()) {
            throw new \RuntimeException('Cannot set cookie. Headers have already been sent.');
        }

        // Set the cookie
        setcookie($name, (string)$value, $options);

        return $this;
    }

    /**
     * Remove a cookie.
     *
     * @param string $name The name of the cookie to be removed.
     * @return \Lithe\Http\Response
     */
    public function clearCookie(string $name): \Lithe\Http\Response
    {
        // If the cookie does not exist, there's no need to remove it
        if (!isset($_COOKIE[$name])) {
            return $this;
        }

        // Set the cookie with an expiration time in the past to remove it
        $this->cookie($name, '', ['expire' => time() - 3600]);

        // Unset the cookie from the $_COOKIE superglobal to ensure it is removed immediately
        unset($_COOKIE[$name]);

        return $this;
    }

    /**
     * Sets the MIME type for the response.
     *
     * @param string $mimeType The MIME type to set for the response.
     * @return self The current Response object for method chaining.
     */
    public function type(string $mimeType): self
    {
        // Set the 'Content-Type' header with the specified MIME type
        $this->setHeader('Content-Type', $mimeType);

        // Return the current instance to allow method chaining
        return $this;
    }
};
