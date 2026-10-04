<?php
/**
 * Laravel Shop Auto-Detection Bootstrap
 * 
 * This file automatically detects the installation status and redirects accordingly.
 * Perfect for new live servers or when you're unsure of the installation status.
 * 
 * Usage: Place this file in your web root and visit it directly.
 */

// Simple installation detection
function quickInstallCheck() {
    $possibleMarkers = [
        __DIR__ . '/storage/installed',
        __DIR__ . '/../storage/installed',
        './storage/installed',
        '../storage/installed',
    ];
    
    foreach ($possibleMarkers as $marker) {
        if (file_exists($marker)) {
            return true;
        }
    }
    
    return false;
}

// Check if this is a fresh server
$isInstalled = quickInstallCheck();

if ($isInstalled) {
    // Already installed - redirect to main app
    header('Location: /');
    exit;
} else {
    // Not installed - check what's available and redirect accordingly
    
    // Check for installer files
    $installerFiles = [
        '/install.php',
        '/detect-installation.php',
        '/debug-install.php',
    ];
    
    foreach ($installerFiles as $file) {
        if (file_exists(__DIR__ . $file)) {
            header('Location: ' . $file);
            exit;
        }
    }
    
    // Check for Laravel install routes
    if (file_exists(__DIR__ . '/vendor/autoload.php') || file_exists(__DIR__ . '/../vendor/autoload.php')) {
        header('Location: /install');
        exit;
    }
    
    // Show basic setup message
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Laravel Shop - Setup Required</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #f0f2f5; text-align: center; }
            .container { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .icon { font-size: 4em; margin-bottom: 20px; }
            h1 { color: #333; margin-bottom: 20px; }
            p { color: #666; line-height: 1.6; margin-bottom: 30px; }
            .btn { display: inline-block; padding: 12px 24px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
            .btn:hover { background: #005a87; }
            .steps { text-align: left; background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .steps h3 { margin-top: 0; }
            .steps ol { padding-left: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">🚀</div>
            <h1>Laravel Shop Setup</h1>
            <p>Welcome! Laravel Shop needs to be set up on this server. Please follow the steps below to get started.</p>
            
            <div class="steps">
                <h3>Quick Setup Steps:</h3>
                <ol>
                    <li>Upload all Laravel Shop files to this directory</li>
                    <li>Make sure <code>vendor/</code>, <code>bootstrap/</code>, and <code>storage/</code> folders exist</li>
                    <li>Upload <code>install.php</code> to this directory</li>
                    <li>Visit <code>/install.php</code> to start installation</li>
                </ol>
            </div>
            
            <a href="/install" class="btn">Try Laravel Install Route</a>
            <a href="/" class="btn">Check Main Site</a>
        </div>
    </body>
    </html>
    <?php
}
?>