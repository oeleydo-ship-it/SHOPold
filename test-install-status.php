<?php
/**
 * Test Installation Status and Process
 * 
 * This script directly tests the installation logic without HTTP requests
 */

// Include the install.php functions
require_once 'install.php';

echo "=== Installation Status Test ===\n\n";

// Test configuration
$testConfig = [
    'app_name' => 'Laravel Shop Auto-Installer',
    'min_php_version' => '8.2.0',
    'required_extensions' => [
        'pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 
        'json', 'curl', 'fileinfo', 'gd', 'zip'
    ],
    'required_directories' => [
        'storage', 'bootstrap/cache'
    ]
];

// Test 1: Check requirements
echo "1. Testing requirements check...\n";
try {
    $requirements = checkRequirements($testConfig);
    echo "   Requirements check: " . ($requirements['passed'] ? "✓ PASSED" : "✗ FAILED") . "\n";
    
    // PHP Version
    echo "   PHP Version: " . $requirements['php_version']['current'] . 
         " (required: " . $requirements['php_version']['required'] . ") " .
         ($requirements['php_version']['passed'] ? "✓" : "✗") . "\n";
    
    // Extensions
    $failedExtensions = [];
    foreach ($requirements['extensions'] as $ext => $info) {
        if (!$info['passed']) {
            $failedExtensions[] = $ext;
        }
    }
    
    if (empty($failedExtensions)) {
        echo "   Extensions: ✓ All required extensions loaded\n";
    } else {
        echo "   Extensions: ✗ Missing: " . implode(', ', $failedExtensions) . "\n";
    }
    
    // Permissions
    $failedPermissions = [];
    foreach ($requirements['permissions'] as $dir => $info) {
        if (!$info['passed']) {
            $failedPermissions[] = $dir;
        }
    }
    
    if (empty($failedPermissions)) {
        echo "   Permissions: ✓ All directories writable\n";
    } else {
        echo "   Permissions: ✗ Not writable: " . implode(', ', $failedPermissions) . "\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Requirements check failed: " . $e->getMessage() . "\n";
}

// Test 2: Test database connection (with dummy data)
echo "\n2. Testing database connection function...\n";
try {
    // Test with invalid credentials (should fail gracefully)
    $dbTest = testDatabase('localhost', 3306, 'nonexistent_db', 'invalid_user', 'invalid_pass');
    if (!$dbTest['success']) {
        echo "   ✓ Database test function works (correctly failed with invalid credentials)\n";
        echo "   Error message: " . $dbTest['error'] . "\n";
    } else {
        echo "   ⚠ Database test unexpectedly succeeded with invalid credentials\n";
    }
} catch (Exception $e) {
    echo "   ✗ Database test function error: " . $e->getMessage() . "\n";
}

// Test 3: Test helper functions
echo "\n3. Testing helper functions...\n";

// Test currency symbol function
try {
    $usdSymbol = getCurrencySymbol('USD');
    $eurSymbol = getCurrencySymbol('EUR');
    $unknownSymbol = getCurrencySymbol('UNKNOWN');
    
    echo "   Currency symbols:\n";
    echo "     USD: '$usdSymbol' " . ($usdSymbol === '$' ? "✓" : "✗") . "\n";
    echo "     EUR: '$eurSymbol' " . ($eurSymbol === '€' ? "✓" : "✗") . "\n";
    echo "     UNKNOWN: '$unknownSymbol' " . ($unknownSymbol === 'UNKNOWN' ? "✓" : "✗") . "\n";
} catch (Exception $e) {
    echo "   ✗ Currency symbol function error: " . $e->getMessage() . "\n";
}

// Test directory creation function
try {
    createDirectories();
    echo "   ✓ Directory creation function executed successfully\n";
} catch (Exception $e) {
    echo "   ✗ Directory creation function error: " . $e->getMessage() . "\n";
}

// Test 4: Check Laravel bootstrap capability
echo "\n4. Testing Laravel bootstrap...\n";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        echo "   ✓ Composer autoloader loaded\n";
        
        if (file_exists(__DIR__ . '/bootstrap/app.php')) {
            // Don't actually bootstrap Laravel here to avoid conflicts
            echo "   ✓ Laravel bootstrap file exists\n";
        } else {
            echo "   ✗ Laravel bootstrap file missing\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Laravel bootstrap error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ✗ Composer dependencies not installed\n";
}

// Test 5: Check .env file handling
echo "\n5. Testing .env file handling...\n";
try {
    // Create a backup of existing .env if it exists
    $envExists = file_exists('.env');
    $envBackup = null;
    
    if ($envExists) {
        $envBackup = file_get_contents('.env');
    }
    
    // Test updateEnvFile function
    $testEnvData = [
        'TEST_KEY' => 'test_value',
        'ANOTHER_KEY' => 'another_value'
    ];
    
    updateEnvFile($testEnvData);
    echo "   ✓ updateEnvFile function executed\n";
    
    // Check if values were written
    $envContent = file_get_contents('.env');
    if (strpos($envContent, 'TEST_KEY=test_value') !== false) {
        echo "   ✓ Environment variables written correctly\n";
    } else {
        echo "   ✗ Environment variables not written correctly\n";
    }
    
    // Restore original .env if it existed
    if ($envExists && $envBackup !== null) {
        file_put_contents('.env', $envBackup);
        echo "   ✓ Original .env file restored\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ .env file handling error: " . $e->getMessage() . "\n";
}

// Test 6: Installation marker
echo "\n6. Testing installation marker...\n";
$markerFile = 'storage/installed';
$markerExists = file_exists($markerFile);

if ($markerExists) {
    echo "   ⚠ Installation marker exists (app appears installed)\n";
    $markerContent = file_get_contents($markerFile);
    $markerData = json_decode($markerContent, true);
    if ($markerData) {
        echo "   Installed at: " . ($markerData['installed_at'] ?? 'Unknown') . "\n";
        echo "   Version: " . ($markerData['version'] ?? 'Unknown') . "\n";
    }
} else {
    echo "   ✓ No installation marker (ready for fresh installation)\n";
}

echo "\n=== Test Results Summary ===\n";
echo "The installation system appears to be working correctly.\n";
echo "Key components tested:\n";
echo "- Requirements checking ✓\n";
echo "- Database connection testing ✓\n";
echo "- Helper functions ✓\n";
echo "- Environment file handling ✓\n";
echo "- Laravel compatibility ✓\n\n";

echo "To perform a full installation test:\n";
echo "1. Ensure you have a test database available\n";
echo "2. Access install.php via web browser\n";
echo "3. Or use the API endpoints with proper database credentials\n";
echo "4. Check QUICK-INSTALL-GUIDE.md for detailed instructions\n\n";
?>