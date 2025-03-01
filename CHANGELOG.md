# Changelog

## [1.4.0] - 2025-1-09

### Features
- Automatic modular routing system implemented.
- Support for hierarchical route mounting based on folder structure.
- Middleware inheritance applied to child routes.
- Enhanced scalability and code organization for routing.

### Improvements
- Simplified route management with automatic detection and setup.
- Clear separation of parent and child routers for better modularity.

Enjoy the new streamlined routing experience!

## [1.3.4] - 2024-12-05

### Fixed
- **Fixed router system compatibility issue on Linux/Unix systems**:
    - Resolved an issue where route handling was failing on Linux/Unix systems due to incorrect file formatting and path visibility issues.
    - Adjusted file and directory permissions to ensure compatibility with Unix-based systems.
    - Improved the handling of system-specific configurations, ensuring the router functions properly across all platforms.

## [1.3.2] - 2024-11-14

### Fixed
- **Corrected router instance check in the `any` function**:
    - Updated the `any` function to properly verify if `$router` is an instance of `Router`.
    - Adjusted the condition to throw an exception only when `$router` is not a valid `Router` instance.
    - Improved error handling when the router instance is missing, increasing function reliability.

### Refactored
- **Enhanced route registration and creation with Orbis in the `Lithe\App` class**:
    - Replaced `Orbis::instance` with `Orbis::unregister` when registering routes, ensuring the instance is discarded after use.
    - Improved the `createRouterFromFile` method in the `Lithe\App` class to verify that the object returned from Orbis is an instance of `Router`.
    - Added extra validations and error messages for missing or invalid router configuration files.
    - Enhanced error handling and logging for cases where route registration fails.

## [1.3.1] - 2024-11-09

### Fixed
- **Adjusted mapping for 'index.php' files in subdirectories**:
    - Updated the logic to map 'index.php' in the root of the application to `/`:
        - 'index.php' at the root is now mapped to `/`.
        - 'subdir/index.php' is now mapped to `/subdir/index`, maintaining the subdirectory structure.

## [1.3.0] - 2024-11-08

### Modified
- **Add support for defining route directories using the set method**:
    - Implemented the `set` method to dynamically configure route directories, providing more flexibility in defining the base directory for routes.
    - Refactored route loading logic to support configurable directories, simplifying customization of route sources.
    - Improved route file inclusion to avoid redundancy, ensuring cleaner and more efficient route handling.
    
## [1.2.3] - 2024-11-07

### Modified
- **Adds template validation for make: migration, model, and seeders commands**: If the template configured in the environment variable does not exist, a default template is created automatically.

- **Refactor of param and extractCookies methods**:
    - **param**: Updated to handle URL-decoded values directly.
    - **extractCookies**: Replaced the anonymous object with a class featuring dynamic properties, allowing direct access to cookie values through `__get`, `__set`, and an `exists` method for existence checks.
    These improvements enhance the handling of parameters and cookies in the system.
    
## [1.2.2] - 2024-11-03

### Modified
- **Refactor query parameter handling**: Changed to a direct property access method using `__get` to simplify access to query parameters.

## [1.2.1] - 2024-10-31

### Modified
- **make:seeder command update**: Modified the `make:seeder` command to default to the environment connection method (`DB_CONNECTION_METHOD`) when no template option is specified.
- **Template validation enhancement**: Improved validation to ensure that the specified template is valid before proceeding with file creation, providing a clearer error message if invalid.
- **Enhanced feedback messages**: Updated the feedback messages for better user experience, confirming success or reporting errors when creating seeder files.

## [1.2.0] - 2024-10-28

### Added
- **Create .env file for test configuration**: Added a `.env` file to facilitate test configuration.
- **Add support for Seeder commands**: Implemented the structure to support seeder commands.
- **Add make:seeder and db:seed commands**: New commands introduced for creating and executing seeders.

### Modified
- **Remove .env from .gitignore**: The `.env` file was removed from `.gitignore`, allowing its inclusion in the repository.
- **Update Model generation templates**: Updates to model generation templates for better compatibility and organization.
- **Refactor middleware template to use class-based definition**: Refactored middleware templates to utilize class-based definitions, improving code readability and structure.

## [1.1.5] - Input Method Enhancements and Query Parameter Handling

- **Description**: This version introduces enhancements to the input handling in the Lithe , allowing for more flexible retrieval of request data from both the body and query parameters.
- **Changes**:
  - **Enhance input method to include query parameters**:
    - Updated the `input` method to retrieve values from both the request body and query parameters, improving usability.
  - **Improve migration file naming convention**:
    - Updated migration file naming format to include the day for better organization: `YYYY_MM_DD_HHMMSS_name.php`.
  - **Refactor getHost method to use protocol method**:
    - Modified the `getHost` function to utilize the protocol method for determining the scheme, enhancing code readability.

## [1.1.4] - 2024-10-25

### Changes
- **Updated the getHost() and secure() functions**:
  - Enhanced the `secure()` function to properly check for secure requests, including support for proxies.
  - Modified the `getHost()` function to construct the host URL based on the secure request check.

## [1.1.3] - 2024-10-25

### Fixes
- **cookie function**: 
  - Fixed the validation for the `expire` option to ensure it is an integer.
  - Enhanced handling of the `expire` parameter to convert string values to Unix timestamps when needed.
  - Resolved an issue where setting cookies would throw a "option 'expire' is invalid" error.

### Improvements
- Improved error handling in the `cookie` function for better robustness.


## [1.1.2] - Bug Fixes and Parameter Adjustments

- **Description**: This version addresses minor bugs and makes adjustments to method parameter orders, improving the overall stability of the Lithe framework.
- **Changes**:
  - **Fix case sensitivity issue for config path in App.php**:
    - Resolved issues related to case sensitivity for configuration paths in the App.php file.
  - **Fixing the render method call in Response**:
    - Adjusted the parameter order in the render method to resolve compatibility issues.
  - **Adjusting the order of parameters and method calls in engines.php**:
    - Rearranged parameters in function calls to avoid deprecation warnings.

## [1.1.0] - Improvements to Routing System and Performance

- **Description**: This version focuses on enhancing the routing system and the overall performance of the Lithe framework, making it even lighter and more efficient.
- **Changes**:
  - **Routing System Enhancements**:
    - Implemented improvements in route management to optimize performance and reduce complexity.
    - Removed event handling methods from the `App` class, simplifying the code structure.
  - **Database Connection Access**:
    - Removed direct access to the `DB_CONNECTION` constant. Now, the connection can be accessed through the new `connection()` method of the `Manager` class.
  - **Force Database Initialization**:
    - Modifications to ensure that the database initializes even if the environment variable `DB_SHOULD_INITIATE` is set to `false`.

## [1.0.2] - Refactor Router File Handling to be Case-Insensitive

- **Description**: This version refactors the router's file handling to be case-insensitive, improving consistency and reliability when dealing with file paths.
- **Changes**:
  - Modified the method for obtaining the file path in the router to ensure case-insensitive handling.
  - Updated the router registration in Orbis to ensure key comparison is case-insensitive.

## [1.0.1] - Updated Blade Cache Directory Path

- **Description**: This version updates the Blade view rendering function to change the cache directory path initialization from using `PROJECT_ROOT` to `dirname(__DIR__, 6)`. This adjustment enhances the flexibility and maintainability of the cache directory structure while ensuring compatibility with the project’s directory layout.

## [1.0.0] - Initial Release

- **Description**: Lithe is a PHP framework inspired by Express.js, renowned for its lightweight and flexible nature. It offers a minimalist approach to web development, integrating various components, ORMs, and databases while maintaining agile and efficient performance.

- **Key Features**:
  - **Routing**: Simple and expressive route management with methods like `get()`, `post()`, among others.
  - **Middleware**: Robust support for middleware to handle requests, responses, and add functionalities such as authentication and logging.
  - **Templates**: Support for multiple template engines, including PHP Pure, Blade, and Twig, with easy configuration.
  - **Database Integration**: Integrated support for various ORMs and database drivers, including Eloquent, MySQLi, and PDO. Simple configuration through `.env` file and support for automated migrations.
  - **Migration Flexibility**: Ability to perform migrations with any database approach, including custom SQL queries or ORM-based migrations.
  - **Package Manager**: Lithe includes an integrated package manager to simplify the addition and management of modules and packages within your application.

- **Achievements**:
  - **Agile Development**: Implementation of a lightweight framework that promotes rapid and intuitive development.
  - **Flexibility**: Seamless integration of various components and libraries, offering high flexibility for developers.
  - **Documentation**: Provision of clear and comprehensive documentation to facilitate effective use of the framework.
  - **Testing**: Support for testing with PHPUnit and Mockery to ensure code quality and reliability.
  - **Ease of Use**: User-friendly interfaces and abstractions designed to simplify the creation and maintenance of web applications.
  - **Database Integration**: Ease of configuration and management of connections to different databases, making integration with data management systems more efficient and flexible.
  - **Migration Capabilities**: Support for a variety of migration approaches, allowing developers to manage schema changes flexibly with either ORM tools or custom SQL scripts.