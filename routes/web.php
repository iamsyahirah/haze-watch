<?php

declare(strict_types=1);

use HazeWatch\Http\Response;
use HazeWatch\Http\Router;

return static function (Router $router): void {
    $router->get('/', static function (): Response {
        $appName = htmlspecialchars((string) (getenv('APP_NAME') ?: 'HazeWatch'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return Response::html(<<<HTML
            <!doctype html>
            <html lang="en">
            <head>
              <meta charset="utf-8">
              <meta name="viewport" content="width=device-width, initial-scale=1">
              <title>{$appName} | Air quality monitoring</title>
            </head>
            <body>
              <main>
                <h1>{$appName}</h1>
                <p>Malaysian air quality monitoring and alerts.</p>
                <p>Project foundation is ready. Air quality data will appear after provider integration.</p>
              </main>
            </body>
            </html>
            HTML);
    });
};
