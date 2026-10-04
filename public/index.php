<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check if Laravel Shop is installed
|--------------------------------------------------------------------------
|
| Before loading Laravel, check if the application is installed.
| If not, redirect to the installer.
|
*/

// Check if app is installed
$installedMarker = __DIR__ . '/../storage/installed';
$isInstalled = file_exists($installedMarker);

// Check if this is an install-related request
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$isInstallRequest = (
    strpos($requestUri, '/install') !== false ||
    strpos($requestUri, 'install.php') !== false ||
    strpos($requestUri, 'debug-install.php') !== false ||
    strpos($requestUri, 'simple-status.php') !== false ||
    strpos($requestUri, 'test-install-status.php') !== false
);

// If not installed and not an install request, redirect to installer
if (!$isInstalled && !$isInstallRequest) {
    // Check if install.php exists
    if (file_exists(__DIR__ . '/../install.php')) {
        header('Location: /install.php');
        exit;
    } elseif (file_exists(__DIR__ . '/install.php')) {
        header('Location: /install.php');
        exit;
    } else {
        // Show a basic installation required message
        http_response_code(503);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Installation Required - Laravel Shop</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { text-align: center; margin-bottom: 30px; }
                .icon { font-size: 48px; color: #e74c3c; margin-bottom: 20px; }
                h1 { color: #2c3e50; margin: 0; }
                .message { color: #7f8c8d; line-height: 1.6; margin-bottom: 30px; }
                .actions { text-align: center; }
                .btn { display: inline-block; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; margin: 0 10px; }
                .btn:hover { background: #2980b9; }
                .requirements { background: #ecf0f1; padding: 20px; border-radius: 4px; margin: 20px 0; }
                .requirements h3 { margin-top: 0; color: #2c3e50; }
                .requirements ul { margin: 0; padding-left: 20px; }
                .requirements li { margin: 5px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="icon">🚀</div>
                    <h1>Laravel Shop Installation Required</h1>
                </div>
                
                <div class="message">
                    <p>Welcome! Laravel Shop needs to be installed before you can use it. The installation process will set up your database, create an admin account, and configure your store.</p>
                </div>

                <div class="requirements">
                    <h3>Before you begin, make sure you have:</h3>
                    <ul>
                        <li>PHP 8.2 or higher</li>
                        <li>MySQL database credentials</li>
                        <li>Write permissions on storage and bootstrap/cache directories</li>
                        <li>All required PHP extensions installed</li>
                    </ul>
                </div>

                <div class="actions">
                    <a href="/install" class="btn">Start Installation</a>
                    <a href="/debug-install.php" class="btn" style="background: #95a5a6;">System Check</a>
                </div>

                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ecf0f1; text-align: center; color: #95a5a6; font-size: 14px;">
                    <p>Need help? Check the <strong>INSTALLATION.md</strong> file for detailed instructions.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);