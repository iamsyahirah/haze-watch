<?php

declare(strict_types=1);

use HazeWatch\Http\Response;
use HazeWatch\Http\Router;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
require $root . '/bootstrap/env.php';

HazeWatch\Bootstrap\loadEnvironment($root . '/.env');

$timezone = getenv('APP_TIMEZONE') ?: 'Asia/Kuala_Lumpur';
date_default_timezone_set($timezone);

$router = new Router();
$registerRoutes = require $root . '/routes/web.php';
$registerRoutes($router);

try {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path)->send();
} catch (Throwable $exception) {
    error_log($exception->__toString());
    $debug = filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL);
    $message = $debug ? 'Application error: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'An unexpected error occurred.';
    Response::html('<h1>500</h1><p>' . $message . '</p>', 500)->send();
}
