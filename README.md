# Lithe

<p align="center">
  <img src="https://github.com/lithephp/.github/blob/main/lithecore.png" alt="Lithe Logo" width="200"/>
</p>

<p align="center">
  <a href="https://packagist.org/packages/lithephp/framework"><img src="https://img.shields.io/packagist/dt/lithephp/framework" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/lithephp/framework"><img src="https://img.shields.io/packagist/v/lithephp/framework" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/lithephp/framework"><img src="https://img.shields.io/packagist/l/lithephp/framework" alt="License"></a>
</p>

## What is Lithe?

Lithe is a PHP framework known for its simplicity, flexibility, and efficiency. Inspired by Express.js, Lithe is designed to help developers build web applications quickly and effectively. The name "Lithe" reflects the core characteristics of the framework: flexible and agile.

## Simple and Flexible Routing

In Lithe, defining routes is very simple. You can use methods like `get()`, `post()`, and others to create routes that respond to different types of HTTP requests:

```php
get('/hello/:name', function ($req, $res) {
    $res->send('Hello, ' . $req->param('name'));
});
```

Discover how [routing in Lithe](https://lithephp.vercel.app/docs/the-basics/routing) can simplify your development and offer complete control over your application's routes.

## Powerful Middleware

In Lithe, middleware is your line of defense, allowing you to inspect, filter, and manipulate HTTP requests before they reach the final routes. Imagine adding functionalities like authentication and logging in a modular and reusable way!

Here’s how easy it is to define and use middleware:

```php
// Middleware to check if the token is valid
$EnsureTokenIsValid = function ($req, $res, $next) {
    $token = $req->param('token');

    if ($token !== 'my-secret-token') {
        $res->send('Invalid token.');
    }

    $next();
};

// Protected route using the middleware
get('/protected/:token', $EnsureTokenIsValid, function ($req, $res) {
    $res->send('Protected content accessed successfully!');
});
```

Learn more about [middlewares in Lithe](https://lithephp.vercel.app/docs/the-basics/middleware) and see how they can transform the way you develop and maintain your applications.

## Database Integration

Connecting to databases is straightforward with Lithe. The framework supports popular ORMs like Eloquent and native PHP drivers such as MySQLi and PDO. Configure your connections in the `.env` file and manage schema migrations easily.

```
DB_CONNECTION_METHOD=eloquent
DB_CONNECTION=mysql
DB_HOST=localhost
DB_NAME=lithe
DB_USERNAME=root
DB_PASSWORD=
DB_SHOULD_INITIATE=true
```

Learn more about [database integration in Lithe](https://lithephp.vercel.app/docs/database/integration) and see how easy it is to manage your data.

## Database Migrations

Maintain consistency and integrity of data in your applications with automated migrations. With Lithe, you can create and apply migrations quickly and easily using any ORM interface or database driver.

```bash
php line make:migration CreateUsersTable --template=eloquent
php line migrate
```

Learn more about [migrations in Lithe](https://lithephp.vercel.app/docs/database/migrations) and make the most of this feature to build robust and scalable applications.

## Contributing

Contributions are welcome! If you find an issue or have a suggestion, feel free to open an [issue](https://github.com/lithephp/framework/issues) or submit a [pull request](https://github.com/lithephp/framework/pulls).

## License

Lithe is licensed under the [MIT License](https://opensource.org/licenses/MIT). See the [LICENSE](LICENSE) file for more details.

## Contact

If you have any questions or need support, get in touch:

- **Instagram**: [@lithephp](https://instagram.com/lithephp)
