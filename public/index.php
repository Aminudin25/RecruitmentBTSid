<?php

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the auto-loader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap the application and handle the request...
$app = require_once __DIR__.'/../bootstrap/app.php';

// Get the HTTP kernel
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Handle the request
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

// Send the response
$response->send();

// Terminate the application
$kernel->terminate($request, $response);
