# Changelog

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