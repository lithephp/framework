<?php

use Lithe\Orbs\Orb;
use Lithe\Support\Log;

return new class($parameters, $url) implements \Lithe\Http\Request
{
    /**
     * @var object Request parameters, such as form data or URL parameters.
     */
    protected $parameters;

    protected $path;
    /**
     * @var object Request parameters, such as form data or URL parameters.
     */
    public $params;

    /**
     * @var string HTTP method of the request (e.g., 'GET', 'POST').
     */
    public $method;

    /**
     * @var array Request headers.
     */
    public $headers;

    /**
     * @var string IP address of the client that made the request.
     */
    public $ip;

    /**
     * @var object Query string data from the URL.
     */
    public $query;

    /**
     * @var string Request URL.
     */
    public $url;

    /**
     * @var object|array|mixed Body data of the request.
     */
    public $body;

    /**
     * @var object|array|array[] Uploaded files in the request (if applicable).
     */
    public $files;

    /**
     * @var object Cookies sent with the request.
     */
    public $cookies;

    private $properties = [];

    /**
     * Constructor of the class. Initializes important objects.
     */
    public function __construct($parameters, $url)
    {
        /**
         * Get the HTTP method of the request (GET, POST, etc.).
         */
        $this->method = $this->method();

        /**
         * Extract request headers.
         */
        $this->headers = $this->extractHeaders();

        /**
         * Extract cookies from the request.
         */
        $this->cookies = $this->extractCookies();

        $this->parameters = $parameters;

        $this->params = $parameters;

        // Get the client's IP address
        $ip = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        $this->ip = $ip;

        /**
         * Extract query string data from the URL.
         */
        $this->query = $this->extractQuery();

        /**
         * Extract body data from the request.
         */
        $this->body = $this->extractBody();

        /**
         * Extract uploaded files from the request.
         */
        $this->files = $this->extractFiles();

        /**
         * Get the clean URL path.
         */
        $this->url = $url;

        $this->path = $url;
    }

    /**
     * Get a property value.
     *
     * This magic method allows dynamic access to properties that do not exist in the object.
     *
     * @param string $name The name of the property.
     * @return mixed|null The value of the property if it exists, or null if it does not.
     */
    public function __get(string $name)
    {
        return $this->properties[$name] ?? null;
    }

    /**
     * Set a property value.
     *
     * This magic method allows dynamic assignment of properties that do not exist in the object.
     *
     * @param string $name The name of the property.
     * @param mixed $value The value to set for the property.
     */
    public function __set(string $name, $value)
    {
        $this->properties[$name] = $value;
    }


    /**
     * Extracts headers from the request.
     *
     * This function attempts to retrieve all HTTP headers from the request. If the `getallheaders` function is available,
     * it uses it to get the headers. Otherwise, it falls back to manually parsing `$_SERVER` to construct the headers array.
     *
     * @return array An associative array of headers, where the keys are header names and the values are header values.
     */
    private function extractHeaders(): array
    {
        if (function_exists('getallheaders')) {
            // Retrieve headers using the getallheaders function if it exists
            $headers = getallheaders();
        } else {
            // Alternative method to retrieve headers in environments where getallheaders() is not available
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                // Check if the server variable represents an HTTP header
                if (substr($name, 0, 5) == 'HTTP_') {
                    // Format the header name to be in a more readable format (e.g., 'CONTENT_TYPE' to 'Content-Type')
                    $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$headerName] = $value;
                }
            }
        }

        return $headers;
    }

    /**
     * Retrieves the host of the server.
     *
     * Constructs the host URL considering the server's protocol and host.
     *
     * @return string The host URL.
     */
    function getHost(): string
    {
        $scheme = $this->protocol() . '://';
        $host = $_SERVER['HTTP_HOST'];

        return $scheme . $host;
    }

    /**
     * Extracts cookies from the request and returns them as an object.
     *
     * @return object An object containing cookie values.
     */
    private function extractCookies()
    {
        $cookies = new class
        {
            private $values = [];

            /**
             * Get the value of a cookie.
             *
             * @param string $name The name of the cookie.
             * @return mixed|null The value of the cookie if exists, otherwise null.
             */
            public function __get(string $name)
            {
                return $this->values[$name] ?? null;
            }

            /**
             * Set the value of a cookie.
             *
             * @param string $name The name of the cookie.
             * @param mixed $value The value of the cookie.
             */
            public function __set(string $name, $value)
            {
                $this->values[$name] = $value;
            }

            /**
             * Check if a cookie exists.
             *
             * @param string $name The name of the cookie.
             * @return bool Returns true if the cookie exists, false otherwise.
             */
            public function exists(string $name): bool
            {
                return isset($this->values[$name]);
            }
        };

        // Populate the $cookies object with existing $_COOKIE values
        foreach ($_COOKIE as $name => $value) {
            if (is_string($name)) {
                $cookies->$name = $value;
            }
        }

        return $cookies;
    }

    /**
     * Get the value of a specific cookie.
     *
     * @param string $name The name of the cookie.
     * @param mixed $default Default value to return if the cookie does not exist.
     * @return mixed The value of the cookie if it exists, otherwise the default value.
     */
    public function cookie(string $name, $default = null)
    {
        $cookies = $this->extractCookies();
        return $cookies->$name ?? $default;
    }

    /**
     * Extracts uploaded files information from the $_FILES superglobal.
     *
     * This method processes the $_FILES superglobal to create an object that contains
     * information about uploaded files. It maps each file input name to its corresponding
     * file information (e.g., name, type, size, tmp_name, error).
     *
     * @return object|null Returns an object containing uploaded files information,
     *                     or null if no files are found or an error occurs.
     */
    private function extractFiles()
    {
        // Anonymous class to store files information dynamically
        $files = new class
        {
            /**
             * Magic method to retrieve file information by name.
             *
             * @param string $name The name of the file input.
             * @return mixed|null Returns the file information if available, or null if not found.
             */
            public function __get($name)
            {
                return $this->$name ?? null;
            }
        };

        try {
            // Check if $_FILES contains any data
            if (empty($_FILES)) {
                return null;
            }

            // Iterate through each file in $_FILES
            foreach ($_FILES as $key => $fileInfo) {
                if (is_array($fileInfo['name'])) {
                    // Initialize array to store multiple files
                    $filesArray = [];
                    foreach ($fileInfo['name'] as $index => $fileName) {
                        $fileData = [
                            'name' => $fileName,
                            'type' => $fileInfo['type'][$index],
                            'tmp_name' => $fileInfo['tmp_name'][$index],
                            'error' => $fileInfo['error'][$index],
                            'size' => $fileInfo['size'][$index],
                        ];
                        $filesArray[] = new \Lithe\Base\Upload($fileData);
                    }
                    $files->$key = $filesArray; // Assign array of files
                } else {
                    // Handle single file
                    $files->$key = new \Lithe\Base\Upload($fileInfo);
                }
            }

            // Return the object containing uploaded files information
            return $files;
        } catch (\Exception $e) {
            // Error handling: log the error
            \error_log("Error extracting uploaded files: " . $e->getMessage());

            Log::error($e);

            // Return null on error
            return $files;
        }
    }

    /**
     * Retrieves the value of a specific header from the request.
     *
     * @param string $name The name of the desired header.
     * @param mixed $default The default value to return if the header does not exist.
     * @return mixed The value of the header if it exists, or the default value if it does not.
     */
    public function header(string $name, mixed $default = null): mixed
    {
        $headers = $this->extractHeaders();
        return $headers[$name] ?? $default;
    }

    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Extracts query parameters from the current request URI.
     *
     * @return object|null Returns an object containing query parameters if available, or null on failure.
     */
    private function extractQuery()
    {
        try {
            // Anonymous class to store query parameters dynamically
            $query = new class()
            {
                private $data = [];

                /**
                 * Magic method to retrieve query parameter values.
                 *
                 * @param string $name The name of the query parameter.
                 * @return mixed|null Returns the value of the query parameter if set, or null if not found.
                 */
                public function __get($name)
                {
                    return $this->data[$name] ?? null;
                }

                /**
                 * Magic method to set query parameter values.
                 *
                 * @param string $name The name of the query parameter.
                 * @param mixed $value The value to set for the query parameter.
                 */
                public function __set($name, $value)
                {
                    $this->data[$name] = $value;
                }
            };

            // Check if 'REQUEST_URI' is defined in $_SERVER
            if (isset($_SERVER['REQUEST_URI'])) {
                $urlParts = parse_url($_SERVER['REQUEST_URI']);

                // Check if 'query' key is present in URL parts
                if (isset($urlParts['query'])) {
                    // Parse query string into an array of parameters
                    parse_str($urlParts['query'], $queryData);

                    // Iterate over query parameters and add them to the $query object
                    foreach ($queryData as $key => $value) {
                        if (is_string($key)) {
                            $query->$key = $value;
                        }
                    }
                }
            }

            return $query;
        } catch (\Exception $e) {
            // Error handling: log the error
            \error_log("Error extracting query parameters: " . $e->getMessage());
            Log::error($e);

            // Return null on error
            return null;
        }
    }

    /**
     * Gets a query parameter from the URL.
     *
     * @param string $key The name of the query parameter.
     * @param mixed $default The default value to return if the query parameter does not exist.
     * @return mixed The value of the query parameter if it exists, or the default value if it doesn't.
     */
    public function query(string $key = null, $default = null)
    {
        // Use the extractQuery method to get query parameters
        $query = $this->extractQuery();

        // Return the specific query parameter if a key is provided, or the entire query object if no key is provided
        return $key === null ? $query : ($query->$key ?? $default);
    }

    /* Get information about an uploaded file.
    *
    * @param string $name The name of the file input.
    * @return \Lithe\Base\Upload|null|array Returns the file information if available, or null if not found.
    */
    public function file(string $name)
    {
        // Use the extractFiles method to get uploaded file information
        $files = $this->extractFiles();

        // Return the specific file information if a name is provided, or the entire files object if no name is provided
        return $files->$name;
    }

    /**
     * Extracts the body of the request, decoding JSON data if present.
     *
     * @return object|mixed|null An object containing the request data, or the raw value if it's a primitive type, or null if an error occurs.
     */
    private function extractBody()
    {
        try {
            // Attempt to fetch JSON data from the request body
            $json = file_get_contents("php://input");
            $bodyData = $json !== false ? json_decode($json, true) : null;

            // Check if $bodyData is an array; otherwise, set it as an empty array
            if (is_array($bodyData)) {
                // If $bodyData is an array, merge it with $_GET and $_POST
                $requestData = array_merge($_GET, $_POST, $bodyData);
            } elseif ($bodyData !== null) {
                // If $bodyData is a non-null primitive value, merge it into an array
                $requestData = $bodyData;
            } else {
                // If $bodyData is null, use an empty array
                $requestData = array_merge($_GET, $_POST);
            }

            // Convert $requestData to an object if it's an array
            if (is_array($requestData)) {
                $body = new class
                {
                    public function __get($name)
                    {
                        return $this->$name ?? null;
                    }
                };
                foreach ($requestData as $key => $value) {
                    $body->$key = $value;
                }
                return $body;
            } else {
                // If $requestData is a primitive type, return it directly
                return $requestData;
            }
        } catch (\Exception $e) {
            // Error handling: log the error
            \error_log("Error extracting request body: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Filters a value based on the specified type.
     *
     * @param string $key The key that holds the value to be filtered.
     * @param string $filterType The type of filter to be applied.
     * @param mixed $default The default value to return if the filtering fails or the value is not set.
     * @return mixed The filtered value, or the default value if the filter is not supported or the value is invalid.
     */
    public function filter(string $key, string $filterType, $default = null)
    {
        // Retrieve the input value by the given key
        $value = $this->input($key);

        if ($value === null) {
            return $default;
        }

        $filteredValue = false;

        // Apply the appropriate filter based on the filter type
        switch ($filterType) {
            case 'string':
                // Sanitize string using FILTER_SANITIZE_FULL_SPECIAL_CHARS
                $filteredValue = filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                break;
            case 'email':
                // Validate email
                $filteredValue = filter_var($value, FILTER_VALIDATE_EMAIL);
                break;
            case 'int':
                // Validate integer
                $filteredValue = filter_var($value, FILTER_VALIDATE_INT);
                break;
            case 'float':
                // Validate float
                $filteredValue = filter_var($value, FILTER_VALIDATE_FLOAT);
                break;
            case 'url':
                // Validate URL
                $filteredValue = filter_var($value, FILTER_VALIDATE_URL);
                break;
            case 'ip':
                // Validate IP address
                $filteredValue = filter_var($value, FILTER_VALIDATE_IP);
                break;
            case 'bool':
                // Validate boolean
                $filteredValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                break;
            case 'alnum':
                // Validate alphanumeric
                $filteredValue = ctype_alnum($value) ? $value : false;
                break;
            case 'html':
                // Sanitize HTML special characters
                $filteredValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                break;
            case 'name':
                // Validate name (only letters, spaces, and apostrophes)
                $filteredValue = preg_match("#^[a-zA-ZÀ-ú\s']+$#", $value) ? $value : false;
                break;
            case 'date':
                // Validate date
                $filteredValue = (bool)strtotime($value) ? $value : false;
                break;
            case 'datetime':
                // Validate datetime
                $filteredValue = DateTime::createFromFormat('Y-m-d H:i:s', $value) !== false ? $value : false;
                break;
            case 'regex':
                // Validate with custom regex pattern
                $pattern = func_get_arg(3); // Pass the regex pattern as the fourth argument
                $filteredValue = preg_match($pattern, $value) ? $value : false;
                break;
            case 'username':
                // Validate username
                $filteredValue = preg_match('/^[a-zA-Z0-9_]+$/', $value) ? $value : false;
                break;
            case 'password':
                // Example password validation (at least 8 characters, including at least one number and one special character)
                $filteredValue = preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $value) ? $value : false;
                break;
            case 'phone':
                // Validate phone number
                $filteredValue = preg_match('/^\+?[0-9]{10,15}$/', $value) ? $value : false;
                break;
            case 'creditcard':
                // Validate credit card number
                $filteredValue = preg_match('/^\d{16}$/', $value) && $this->luhnCheck($value) ? $value : false;
                break;
            case 'json':
                // Validate JSON
                json_decode($value);
                $filteredValue = (json_last_error() == JSON_ERROR_NONE) ? $value : false;
                break;
            case 'uuid':
                // Validate UUID
                $filteredValue = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) ? $value : false;
                break;
            default:
                // Apply default filter
                $filteredValue = filter_var($value, FILTER_DEFAULT);
                break;
        }

        return $filteredValue !== false ? $filteredValue : $default;
    }

    /**
     * Luhn algorithm to validate credit card numbers.
     *
     * @param string $number The credit card number.
     * @return bool Whether the number is valid according to the Luhn algorithm.
     */
    private function luhnCheck($number)
    {
        $digits = str_split($number);
        $sum = 0;
        $alternate = false;

        for ($i = count($digits) - 1; $i >= 0; $i--) {
            $n = $digits[$i];
            if ($alternate) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alternate = !$alternate;
        }

        return ($sum % 10 == 0);
    }

    /**
     * Checks if the current URL matches the specified pattern.
     *
     * @param string $url The URL pattern to compare against the current URL.
     * @return bool Returns true if the current URL matches the pattern, otherwise false.
     */
    public function is(string $url): bool
    {
        // Remove leading and trailing slashes for comparison
        $url = trim($url, '/');
        $currentUrl = trim($this->path, '/');

        // Check for exact match
        if ($url === $currentUrl) {
            return true;
        }

        // Convert wildcard pattern (*) to regex (.*) without escaping it
        $regexPattern = '/^' . str_replace('\*', '.*', preg_quote($url, '/')) . '$/i';

        // Check regex match
        return preg_match($regexPattern, $currentUrl) === 1;
    }

    /**
     * Validates input data against the provided rules.
     *
     * This method collects relevant input data using the specified rules
     * and returns a validator instance with the provided data and rules for further processing.
     *
     * @param array $rules An associative array where the key is the field name and the value is the validation rule.
     * @return \Lithe\Base\Validator Returns a validator instance configured with the provided data and rules.
     */
    public function validate(array $rules)
    {
        // Retrieve input data based on the provided rules
        foreach ($rules as $key => $rule) {
            $value = $this->input($key);
            $data[$key] = $value;
        }

        return new \Lithe\Base\Validator($data, $rules);
    }

    /**
     * Retrieves the entire request body or specific parts of it.
     *
     * @param array|null $keys An array of keys to retrieve specific parts of the body. If null, returns the entire body.
     * @param array|null $exclude An array of keys to exclude from the returned body.
     * @return mixed An associative array or an object containing the filtered request body data.
     */
    public function body(array $keys = null, array $exclude = null): mixed
    {
        // Extract the body data
        $bodyData = $this->extractBody();

        // Convert bodyData to an array if it's an object
        if (is_object($bodyData)) {
            $bodyData = get_object_vars($bodyData);
        }

        // Filter the body data based on $keys and $exclude
        if (is_array($bodyData)) {
            if ($keys !== null) {
                // Return only the specified keys
                $bodyData = array_intersect_key($bodyData, array_flip($keys));
            }
            if ($exclude !== null) {
                // Exclude the specified keys
                $bodyData = array_diff_key($bodyData, array_flip($exclude));
            }
        }

        // Return the filtered body data as an object if needed
        if (is_array($bodyData)) {
            $body = new \stdClass();
            foreach ($bodyData as $key => $value) {
                $body->$key = $value;
            }
            return $body;
        }

        return $bodyData;
    }

    /**
     * Retrieves a specific input value from the request body.
     *
     * @param string $key The key of the input value to retrieve.
     * @param mixed $default The default value to return if the key does not exist.
     * @return mixed The value of the input if it exists, or the default value if it doesn't.
     */
    public function input(string $key, $default = null)
    {
        // Extract the body data
        $body = $this->extractBody();

        // Check if $body is an array and the key exists
        if (is_array($body) && array_key_exists($key, $body)) {
            return $body[$key];
        }

        // Check if $body is an object and the property exists
        if (is_object($body) && property_exists($body, $key)) {
            return $body->$key;
        }

        // Return the default value if the key does not exist
        return $default;
    }


    /**
     * Checks if a specific input field or fields are present in the request data.
     *
     * @param string|array $key The key or an array of keys to check in the request data.
     * @return bool True if the input field(s) are present, otherwise false.
     */
    public function has(string|array $key): bool
    {
        if (is_array($key) && !empty($key)) {
            foreach ($key as $k) {
                if (!$this->input($k)) {
                    return false;
                }
            }
            return true;
        }

        if (is_string($key)) {
            return !!$this->input($key);
        }

        return false;
    }


    /**
     * Checks if the HTTP request method matches the given method.
     *
     * @param string $method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return strtolower($this->method()) === strtolower($method);
    }

    /**
     * Checks if the request expects a JSON response.
     *
     * @return bool
     */
    public function wantsJson(): bool
    {
        return strpos($this->header('Accept'), 'application/json') !== false;
    }

    /**
     * Check if the request is secure (HTTPS).
     *
     * @return bool
     */
    public function secure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            $_SERVER['SERVER_PORT'] == 443 ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');;
    }

    /**
     * Get the protocol of the request.
     *
     * @return string
     */
    public function protocol(): string
    {
        return $this->secure() ? 'https' : 'http';
    }

    /**
     * Retrieves the value of a specified parameter by its name.
     *
     * This method checks if the requested parameter exists and returns its value.
     * If the parameter is not found, it returns the provided default value.
     *
     * @param string $name The name of the parameter to retrieve.
     * @param mixed $default The default value to return if the parameter is not found (default: null).
     * @return mixed The value of the parameter, or the default value if the parameter is not found.
     */
    public function param(string $name, mixed $default = null): mixed
    {
        // Check if the parameter exists and return its value; otherwise, return the default.
        return $this->parameters->{$name} ?? $default;
    }


    /**
     * Retrieves the HTTP request method (GET, POST, PUT, DELETE, etc.).
     *
     * This method returns the request method as a string. If the request method cannot be determined,
     * it defaults to 'GET'.
     *
     * @return string The HTTP request method, or 'GET' if not determined.
     */
    public function method(): string
    {
        // Return the request method from the server variables, or 'GET' if not set
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }
};
