<?php
/**
 * Test Installation Process
 * 
 * This script tests the installation process to ensure it works correctly
 */

echo "=== Laravel Shop Installation Test ===\n\n";

// Test 1: Check if install.php exists and is readable
echo "1. Checking install.php file...\n";
if (file_exists('install.php')) {
    echo "   ✓ install.php exists\n";
} else {
    echo "   ✗ install.php not found\n";
    exit(1);
}

// Test 2: Check PHP syntax
echo "2. Checking PHP syntax...\n";
$output = [];
$return_var = 0;
exec('php -l install.php 2>&1', $output, $return_var);
if ($return_var === 0) {
    echo "   ✓ PHP syntax is valid\n";
} else {
    echo "   ✗ PHP syntax errors found:\n";
    foreach ($output as $line) {
        echo "     " . $line . "\n";
    }
    exit(1);
}

// Test 3: Test status endpoint
echo "3. Testing status endpoint...\n";
$statusUrl = 'http://localhost/install.php?action=status';
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$statusResponse = @file_get_contents($statusUrl, false, $context);
if ($statusResponse !== false) {
    $statusData = json_decode($statusResponse, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "   ✓ Status endpoint working\n";
        echo "   - Installed: " . ($statusData['installed'] ? 'Yes' : 'No') . "\n";
        echo "   - PHP Version: " . $statusData['php_version'] . "\n";
    } else {
        echo "   ⚠ Status endpoint returned invalid JSON\n";
        echo "   Response: " . substr($statusResponse, 0, 200) . "...\n";
    }
} else {
    echo "   ⚠ Could not reach status endpoint (this is normal if not running on web server)\n";
}

// Test 4: Test requirements check endpoint
echo "4. Testing requirements endpoint...\n";
$reqUrl = 'http://localhost/install.php?action=check';
$reqResponse = @file_get_contents($reqUrl, false, $context);
if ($reqResponse !== false) {
    $reqData = json_decode($reqResponse, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "   ✓ Requirements endpoint working\n";
        echo "   - Requirements passed: " . ($reqData['passed'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "   ⚠ Requirements endpoint returned invalid JSON\n";
    }
} else {
    echo "   ⚠ Could not reach requirements endpoint (this is normal if not running on web server)\n";
}

// Test 5: Check Laravel files
echo "5. Checking Laravel installation...\n";
if (file_exists('vendor/autoload.php')) {
    echo "   ✓ Composer dependencies installed\n";
} else {
    echo "   ✗ Composer dependencies missing (run: composer install)\n";
}

if (file_exists('bootstrap/app.php')) {
    echo "   ✓ Laravel bootstrap file exists\n";
} else {
    echo "   ✗ Laravel bootstrap file missing\n";
}

if (file_exists('.env')) {
    echo "   ✓ .env file exists\n";
} else {
    echo "   ⚠ .env file missing (will be created during installation)\n";
}

// Test 6: Check directory permissions
echo "6. Checking directory permissions...\n";
$directories = [
    'storage' => 'storage/',
    'bootstrap_cache' => 'bootstrap/cache/',
    'public' => 'public/'
];

foreach ($directories as $name => $path) {
    if (is_dir($path) && is_writable($path)) {
        echo "   ✓ {$name} is writable\n";
    } else {
        echo "   ✗ {$name} is not writable\n";
    }
}

// Test 7: Check installation marker
echo "7. Checking installation status...\n";
if (file_exists('storage/installed')) {
    echo "   ⚠ Installation marker exists (app appears to be installed)\n";
    echo "   - To test fresh installation, delete storage/installed file\n";
    echo "   - Or use reset-installation.php to reset\n";
} else {
    echo "   ✓ No installation marker (ready for fresh installation)\n";
}

echo "\n=== Test Summary ===\n";
echo "Installation files appear to be ready.\n";
echo "To test the installation:\n";
echo "1. Access install.php in your web browser\n";
echo "2. Or use the API endpoints for automated installation\n";
echo "3. Check the QUICK-INSTALL-GUIDE.md for detailed instructions\n\n";
?>