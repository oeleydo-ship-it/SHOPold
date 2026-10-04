<?php
/**
 * Simple debug script to test installation issues
 */

echo "<h1>Laravel Shop Installation Debug</h1>";

echo "<h2>Basic System Info</h2>";
echo "<ul>";
echo "<li>PHP Version: " . PHP_VERSION . "</li>";
echo "<li>Current Directory: " . __DIR__ . "</li>";
echo "<li>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</li>";
echo "</ul>";

echo "<h2>File System Checks</h2>";
echo "<ul>";
echo "<li>vendor/autoload.php: " . (file_exists(__DIR__ . '/vendor/autoload.php') ? '✓ EXISTS' : '✗ MISSING') . "</li>";
echo "<li>bootstrap/app.php: " . (file_exists(__DIR__ . '/bootstrap/app.php') ? '✓ EXISTS' : '✗ MISSING') . "</li>";
echo "<li>.env file: " . (file_exists(__DIR__ . '/.env') ? '✓ EXISTS' : '✗ MISSING') . "</li>";
echo "<li>storage/ directory: " . (is_dir(__DIR__ . '/storage') ? '✓ EXISTS' : '✗ MISSING') . "</li>";
echo "<li>storage/ writable: " . (is_writable(__DIR__ . '/storage') ? '✓ WRITABLE' : '✗ NOT WRITABLE') . "</li>";
echo "<li>bootstrap/cache/ writable: " . (is_writable(__DIR__ . '/bootstrap/cache') ? '✓ WRITABLE' : '✗ NOT WRITABLE') . "</li>";
echo "<li>Installation marker: " . (file_exists(__DIR__ . '/storage/installed') ? '✓ INSTALLED' : '✗ NOT INSTALLED') . "</li>";
echo "</ul>";

echo "<h2>PHP Extensions</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'json', 'curl', 'fileinfo', 'gd', 'zip'];
echo "<ul>";
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "<li>{$ext}: " . ($loaded ? '✓ LOADED' : '✗ MISSING') . "</li>";
}
echo "</ul>";

echo "<h2>Laravel Bootstrap Test</h2>";
try {
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
        echo "<p>✓ Autoloader loaded successfully</p>";
        
        if (file_exists(__DIR__ . '/bootstrap/app.php')) {
            $app = require_once __DIR__ . '/bootstrap/app.php';
            echo "<p>✓ Laravel app bootstrapped successfully</p>";
            
            if (method_exists($app, 'version')) {
                echo "<p>Laravel Version: " . $app->version() . "</p>";
            }
        } else {
            echo "<p>✗ bootstrap/app.php not found</p>";
        }
    } else {
        echo "<p>✗ vendor/autoload.php not found</p>";
    }
} catch (Exception $e) {
    echo "<p>✗ Laravel bootstrap failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h2>Test Install.php Endpoints</h2>";
echo "<ul>";
echo "<li><a href='install.php?action=debug'>Debug Endpoint</a></li>";
echo "<li><a href='install.php?action=status'>Status Endpoint</a></li>";
echo "<li><a href='install.php?action=check'>Requirements Check</a></li>";
echo "<li><a href='install.php'>Main Install Page</a></li>";
echo "</ul>";
?>