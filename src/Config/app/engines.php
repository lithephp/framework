<?php

use Lithe\Support\Log;

return [
    'blade' => function (string $file, ?array $data = [], string $views) {
        // Check if Blade is available
        if (class_exists('Jenssegers\Blade\Blade')) {
            try {
                // Define cache and instantiate Blade
                $cache = dirname(__DIR__, 6) . '/storage/framework/views';
                $blade = new \Jenssegers\Blade\Blade($views, $cache);

                // Render and output the Blade view
                echo $blade->make($file, $data)->render();
            } catch (Exception $e) {
                // Handle rendering errors
                $errorMessage = 'Error rendering view with Blade: ' . $e->getMessage();
                error_log($errorMessage);
                Log::error($errorMessage);
            }
        } else {
            // Blade not installed, throw exception
            $errorMessage = 'Blade is not installed. Install it to use the template feature ( composer require jenssegers/blade ).';
            Log::error($errorMessage);
            throw new RuntimeException($errorMessage);
        }
    },
    'twig' => function (string $file, ?array $data = [], string $views) {
        // Check if Twig is available
        if (class_exists('Twig\Environment')) {
            try {
                // Set up Twig loader and environment
                $loader = new \Twig\Loader\FilesystemLoader($views);
                $twig = new \Twig\Environment($loader);

                // Render and output the Twig template
                echo $twig->render("$file.twig", $data);
            } catch (\Twig\Error\LoaderError | \Twig\Error\RuntimeError | \Twig\Error\SyntaxError $e) {
                // Handle Twig rendering errors
                $errorMessage = "Error rendering Twig template: " . $e->getMessage();
                error_log($errorMessage);
                Log::error($errorMessage);
            }
        } else {
            // Twig not installed, throw exception
            $errorMessage = 'Twig is not installed. Install it to use the Twig template feature.';
            Log::error($errorMessage);
            throw new RuntimeException($errorMessage);
        }
    },
    'default' => function (string $file, ?array $data = [], string $views) {
        // Render using default PHP include
        $viewPath = $views . "/$file.php";

        if (file_exists($viewPath)) {
            extract($data); // Extract data for use in view
            include $viewPath; // Include PHP view file
        } else {
            // View file not found, throw exception
            $errorMessage = "File not found: $viewPath";
            Log::error($errorMessage);
            throw new RuntimeException($errorMessage);
        }
    },

    // Add more options as needed
];
