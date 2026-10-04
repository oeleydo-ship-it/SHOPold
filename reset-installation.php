<?php
/**
 * Laravel Shop Installation Reset Tool
 * 
 * This script allows you to reset the installation status for fresh installations
 * on new servers or when you need to reinstall the application.
 * 
 * SECURITY WARNING: Remove this file after use in production!
 */

// Security check - only allow in development or with special key
$allowReset = (
    (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') ||
    (isset($_POST['reset_key']) && $_POST['reset_key'] === 'reset-laravel-shop-installation')
);

if (!$allowReset && !isset($_GET['show_form'])) {
    die('Access denied. Add ?show_form=1 to see the reset form.');
}

/**
 * Reset installation status
 */
function resetInstallation() {
    $results = [];
    
    // Remove installation marker
    $markerFile = __DIR__ . '/storage/installed';
    if (file_exists($markerFile)) {
        if (unlink($markerFile)) {
            $results[] = '✓ Removed installation marker file';
        } else {
            $results[] = '✗ Failed to remove installation marker file';
        }
    } else {
        $results[] = '- Installation marker file not found';
    }
    
    // Clear Laravel caches if possible
    try {
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
            
            if (file_exists(__DIR__ . '/bootstrap/app.php')) {
                $app = require_once __DIR__ . '/bootstrap/app.php';
                $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
                $kernel->bootstrap();
                
                // Clear caches
                Artisan::call('config:clear');
                Artisan::call('cache:clear');
                Artisan::call('view:clear');
                Artisan::call('route:clear');
                
                $results[] = '✓ Cleared Laravel caches';
            }
        }
    } catch (Exception $e) {
        $results[] = '- Could not clear Laravel caches: ' . $e->getMessage();
    }
    
    return $results;
}

// Handle reset request
if ($allowReset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $results = resetInstallation();
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Installation Reset Complete</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #f0f2f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .results { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .results ul { margin: 0; padding-left: 20px; }
            .btn { display: inline-block; padding: 12px 24px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
            .btn:hover { background: #005a87; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔄 Installation Reset Complete</h1>
            
            <div class="success">
                <strong>Success!</strong> The installation has been reset. You can now run a fresh installation.
            </div>
            
            <div class="results">
                <h3>Reset Results:</h3>
                <ul>
                    <?php foreach ($results as $result): ?>
                        <li><?= htmlspecialchars($result) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="/install.php" class="btn">Start Fresh Installation</a>
                <a href="/install" class="btn">Laravel Install Route</a>
                <a href="/" class="btn" style="background: #6c757d;">Check Main Site</a>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 5px; color: #856404;">
                <strong>Security Notice:</strong> Please delete this reset file (reset-installation.php) after use for security reasons.
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Show reset form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Laravel Shop Installation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #f0f2f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .warning { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px; margin: 20px 0; border: 1px solid #f5c6cb; }
        .form-group { margin: 20px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        .btn { display: inline-block; padding: 12px 24px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .status { background: #e2e3e5; padding: 20px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Reset Laravel Shop Installation</h1>
        
        <div class="warning">
            <strong>⚠️ Warning!</strong> This will reset the installation status and allow you to run a fresh installation. 
            This action will remove the installation marker but will NOT delete your database data or files.
        </div>
        
        <div class="status">
            <h3>Current Status:</h3>
            <ul>
                <li>Installation marker: <?= file_exists(__DIR__ . '/storage/installed') ? '✓ Present' : '✗ Not found' ?></li>
                <li>Laravel files: <?= file_exists(__DIR__ . '/vendor/autoload.php') ? '✓ Present' : '✗ Not found' ?></li>
                <li>Environment file: <?= file_exists(__DIR__ . '/.env') ? '✓ Present' : '✗ Not found' ?></li>
            </ul>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="reset_key">Enter Reset Key:</label>
                <input type="text" id="reset_key" name="reset_key" placeholder="reset-laravel-shop-installation" required>
                <small style="color: #6c757d;">Enter: reset-laravel-shop-installation</small>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" required> 
                    I understand this will reset the installation status
                </label>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" required> 
                    I have backed up any important data
                </label>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn">Reset Installation</button>
                <a href="/" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        
        <div style="margin-top: 30px; padding: 20px; background: #d1ecf1; border-radius: 5px; color: #0c5460;">
            <h4>Alternative Options:</h4>
            <ul>
                <li><a href="?confirm=yes">Quick Reset (bypass form)</a></li>
                <li><a href="/install.php?force=1">Force Installation</a></li>
                <li><a href="/debug-install.php">System Debug</a></li>
            </ul>
        </div>
    </div>
</body>
</html>