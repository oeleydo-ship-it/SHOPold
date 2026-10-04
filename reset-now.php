<?php
/**
 * Quick Installation Reset - Laravel Shop
 * 
 * Run this file directly to reset the installation status.
 * Usage: php reset-now.php
 */

echo "🔄 Laravel Shop Installation Reset\n";
echo "=================================\n\n";

// Check if running from command line
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    // If accessed via web, show simple interface
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Quick Reset - Laravel Shop</title>
        <style>
            body { font-family: monospace; background: #1e1e1e; color: #fff; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; }
            .success { color: #4CAF50; }
            .error { color: #f44336; }
            .warning { color: #ff9800; }
            .info { color: #2196F3; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔄 Laravel Shop Installation Reset</h1>
            <pre>
    <?php
}

// Function to reset installation
function resetInstallation() {
    $results = [];
    
    // Remove installation marker
    $markerFile = __DIR__ . '/storage/installed';
    if (file_exists($markerFile)) {
        if (unlink($markerFile)) {
            $results[] = ['✅ Removed installation marker file', 'success'];
        } else {
            $results[] = ['❌ Failed to remove installation marker file', 'error'];
        }
    } else {
        $results[] = ['ℹ️  Installation marker file not found', 'info'];
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
                
                $results[] = ['✅ Cleared Laravel caches', 'success'];
            }
        }
    } catch (Exception $e) {
        $results[] = ['⚠️  Could not clear Laravel caches: ' . $e->getMessage(), 'warning'];
    }
    
    return $results;
}

// Check current status
$markerExists = file_exists(__DIR__ . '/storage/installed');

echo "📊 Current Status:\n";
echo "   Installation marker: " . ($markerExists ? "✅ Present" : "❌ Not found") . "\n";
echo "   Laravel files: " . (file_exists(__DIR__ . '/vendor/autoload.php') ? "✅ Present" : "❌ Not found") . "\n";
echo "   Environment file: " . (file_exists(__DIR__ . '/.env') ? "✅ Present" : "❌ Not found") . "\n\n";

if ($markerExists) {
    echo "🔄 Resetting installation...\n\n";
    
    $results = resetInstallation();
    
    foreach ($results as $result) {
        echo $result[0] . "\n";
    }
    
    echo "\n🎉 Reset complete!\n\n";
    echo "🔗 Next steps:\n";
    echo "   • Visit /install.php to start fresh installation\n";
    echo "   • Or visit /install for Laravel install routes\n";
    echo "   • Use /install.php?force=1 for force installation\n\n";
} else {
    echo "ℹ️  App appears to be uninstalled already.\n";
    echo "You can proceed with normal installation.\n\n";
}

echo "🛡️  Security reminder: Remove reset tools after use!\n";

if (!$isCli) {
    ?>
            </pre>
            <div style="margin-top: 20px;">
                <a href="/install.php" style="color: #4CAF50; text-decoration: none;">→ Start Installation</a> |
                <a href="/debug-install.php" style="color: #2196F3; text-decoration: none;">→ Debug Info</a> |
                <a href="/" style="color: #ff9800; text-decoration: none;">→ Main Site</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>