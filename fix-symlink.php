<?php
/**
 * Laravel Storage Symlink Fixer
 * 
 * This script fixes common symlink issues for Laravel storage.
 * It can be run via web browser or command line.
 * 
 * Usage:
 * - Web: https://yourdomain.com/fix-symlink.php
 * - CLI: php fix-symlink.php
 */

// Configuration
$storagePath = __DIR__ . '/storage/app/public';
$publicPath = __DIR__ . '/public/storage';
$isWebRequest = isset($_SERVER['HTTP_HOST']);

// Set content type for web requests
if ($isWebRequest) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Laravel Storage Symlink Fixer</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        .step { margin: 15px 0; padding: 10px; border-left: 4px solid #007bff; background: #f8f9fa; }
        .code { background: #f1f1f1; padding: 10px; border-radius: 5px; font-family: monospace; }
    </style>
</head>
<body>
    <h1>🔧 Laravel Storage Symlink Fixer</h1>';
}

/**
 * Output a message with appropriate formatting
 */
function output($message, $type = 'info') {
    global $isWebRequest;
    
    $icons = [
        'success' => '✅',
        'error' => '❌',
        'warning' => '⚠️',
        'info' => 'ℹ️'
    ];
    
    $icon = $icons[$type] ?? 'ℹ️';
    
    if ($isWebRequest) {
        echo "<div class='step $type'>$icon $message</div>";
    } else {
        echo "[$type] $message\n";
    }
}

/**
 * Check if symlinks are supported
 */
function checkSymlinkSupport() {
    if (!function_exists('symlink')) {
        return false;
    }
    
    // Test symlink creation
    $testTarget = sys_get_temp_dir() . '/test_symlink_' . uniqid();
    $testLink = sys_get_temp_dir() . '/test_link_' . uniqid();
    
    // Create test directory
    if (!mkdir($testTarget)) {
        return false;
    }
    
    // Try to create symlink
    $result = @symlink($testTarget, $testLink);
    
    // Clean up
    if ($result && is_link($testLink)) {
        unlink($testLink);
    }
    rmdir($testTarget);
    
    return $result;
}

/**
 * Create directory structure
 */
function createDirectories($path) {
    $directories = [
        $path,
        $path . '/products',
        $path . '/categories', 
        $path . '/brands',
        $path . '/users',
        $path . '/media',
        $path . '/temp'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (mkdir($dir, 0755, true)) {
                output("Created directory: $dir", 'success');
            } else {
                output("Failed to create directory: $dir", 'error');
                return false;
            }
        }
    }
    
    return true;
}

/**
 * Fix symlink issues
 */
function fixSymlink($storagePath, $publicPath) {
    output("Starting symlink fix process...", 'info');
    
    // Step 1: Check if storage directory exists
    if (!file_exists($storagePath)) {
        output("Storage directory doesn't exist. Creating it...", 'warning');
        if (!createDirectories($storagePath)) {
            output("Failed to create storage directories", 'error');
            return false;
        }
    } else {
        output("Storage directory exists: $storagePath", 'success');
    }
    
    // Step 2: Handle existing public/storage
    if (file_exists($publicPath)) {
        if (is_link($publicPath)) {
            $currentTarget = readlink($publicPath);
            if (realpath($currentTarget) === realpath($storagePath)) {
                output("Symlink already exists and is correct", 'success');
                return true;
            } else {
                output("Symlink exists but points to wrong location. Removing...", 'warning');
                unlink($publicPath);
            }
        } else {
            output("File/directory exists at symlink location but is not a symlink", 'error');
            output("Please manually remove: $publicPath", 'error');
            return false;
        }
    }
    
    // Step 3: Check symlink support
    if (!checkSymlinkSupport()) {
        output("Symlinks not supported on this system. Creating alternative structure...", 'warning');
        return createAlternativeStructure($publicPath);
    }
    
    // Step 4: Create symlink
    if (symlink($storagePath, $publicPath)) {
        output("Symlink created successfully!", 'success');
        output("Link: $publicPath → $storagePath", 'info');
        
        // Verify symlink
        if (is_link($publicPath) && file_exists($publicPath)) {
            output("Symlink verification: PASSED", 'success');
            return true;
        } else {
            output("Symlink verification: FAILED", 'error');
            return false;
        }
    } else {
        output("Failed to create symlink. Creating alternative structure...", 'error');
        return createAlternativeStructure($publicPath);
    }
}

/**
 * Create alternative directory structure when symlinks aren't supported
 */
function createAlternativeStructure($publicPath) {
    output("Creating alternative directory structure...", 'info');
    
    if (!file_exists($publicPath)) {
        if (!mkdir($publicPath, 0755, true)) {
            output("Failed to create public storage directory", 'error');
            return false;
        }
    }
    
    // Create subdirectories
    $subdirs = ['products', 'categories', 'brands', 'users', 'media', 'temp'];
    foreach ($subdirs as $subdir) {
        $dir = $publicPath . '/' . $subdir;
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    // Create .htaccess for security
    $htaccess = $publicPath . '/.htaccess';
    $htaccessContent = "# Laravel Storage Security\n";
    $htaccessContent .= "Options -Indexes\n";
    $htaccessContent .= "<Files \"*.php\">\n";
    $htaccessContent .= "    Deny from all\n";
    $htaccessContent .= "</Files>\n";
    
    file_put_contents($htaccess, $htaccessContent);
    
    output("Alternative structure created", 'success');
    output("Note: You'll need to manually copy files from storage/app/public to public/storage", 'warning');
    
    return true;
}

/**
 * Test image access
 */
function testImageAccess($storagePath, $publicPath) {
    output("Testing image access...", 'info');
    
    // Create test file
    $testFile = $storagePath . '/test-image.txt';
    $testContent = 'Test file created at ' . date('Y-m-d H:i:s');
    
    if (file_put_contents($testFile, $testContent)) {
        output("Test file created: $testFile", 'success');
        
        // Check if accessible via public path
        $publicTestFile = $publicPath . '/test-image.txt';
        if (file_exists($publicTestFile)) {
            $content = file_get_contents($publicTestFile);
            if ($content === $testContent) {
                output("✅ Image access test: PASSED", 'success');
                
                // Clean up
                unlink($testFile);
                return true;
            } else {
                output("❌ Image access test: FAILED (content mismatch)", 'error');
            }
        } else {
            output("❌ Image access test: FAILED (file not accessible)", 'error');
        }
        
        // Clean up
        unlink($testFile);
    } else {
        output("Failed to create test file", 'error');
    }
    
    return false;
}

// Main execution
output("Laravel Storage Symlink Fixer v1.0", 'info');
output("Storage Path: $storagePath", 'info');
output("Public Path: $publicPath", 'info');

// Fix the symlink
if (fixSymlink($storagePath, $publicPath)) {
    // Test the fix
    testImageAccess($storagePath, $publicPath);
    
    output("🎉 Symlink fix completed successfully!", 'success');
    
    if ($isWebRequest) {
        echo "<div class='step info'>";
        echo "<strong>Next Steps:</strong><br>";
        echo "1. Test your application to ensure images are loading<br>";
        echo "2. Delete this file (fix-symlink.php) for security<br>";
        echo "3. If issues persist, check your hosting provider's symlink support<br>";
        echo "</div>";
        
        echo "<div class='code'>";
        echo "<strong>Alternative Commands:</strong><br>";
        echo "Laravel Artisan: <code>php artisan storage:link</code><br>";
        echo "Manual Symlink: <code>ln -s " . realpath($storagePath) . " " . $publicPath . "</code><br>";
        echo "</div>";
    }
} else {
    output("❌ Symlink fix failed. Please check the errors above.", 'error');
    
    if ($isWebRequest) {
        echo "<div class='step error'>";
        echo "<strong>Manual Solutions:</strong><br>";
        echo "1. Contact your hosting provider about symlink support<br>";
        echo "2. Use Laravel Artisan: <code>php artisan storage:link</code><br>";
        echo "3. Manually create symlink via SSH/terminal<br>";
        echo "4. Use the alternative directory structure created above<br>";
        echo "</div>";
    }
}

if ($isWebRequest) {
    echo '<p style="color: red; font-weight: bold;">⚠️ SECURITY: Delete this file after use!</p>';
    echo '</body></html>';
}
?>