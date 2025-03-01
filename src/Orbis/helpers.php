<?php

use Lithe\Orbis\Orbis;

/**
 * Retrieves the Response instance from Orbis.
 *
 * @return \Lithe\Http\Response
 * @throws \Exception If the Response instance cannot be found.
 */
function response() {
    // Fetch the Response instance from Orbis
    $response = Orbis::instance('\Lithe\Http\Response');

    if (!$response instanceof \Lithe\Http\Response) {
        // Throw an exception if the Response instance is not found
        throw new \Exception('The Response instance could not be found.');
    }

    return $response;
}

/**
 * Renders a view file with optional data.
 *
 * @param string $file The path to the view file.
 * @param array|null $data An associative array of data to pass to the view.
 * @return \Lithe\Http\Response
 */
function view(string $file, ?array $data = []) {
    // Render the view using the Response instance
    return response()->view($file, $data);
}
