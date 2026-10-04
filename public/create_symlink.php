<?php
/**
 * Enhanced Storage Symlink Creator
 * 
 * This script creates the necessary symlink for Laravel storage to work properly.
 * It handles various scenarios and provides multiple fallback options.
 * 
 * Usage:
 * 1. Run via browser: https://yourdomain.com/create_symlink.php
 * 2. Or run via command line: php public/create_symlink.php
 * 3. DELETE this file after use for security!
 */

// Configuration
$targetPath = __DIR__ . '/../storage/app/public';
$target = file_exists($targetPath) ? realpath($targetPath) : $targetPath;
$link = __DIR__ . '/storage';
$action = $_GET['action'] ?? 'check';

// Set content type for web access
if (isset($_SERVER['HTTP_HOST'])) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Laravel Storage Symlink Creator</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #28a745; }
        .warning { color: #ffc107; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; }
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 5px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <h1>Laravel Storage Symlink Creator</h1>';
}

/**
 * Log message with appropriate formatting
 */
function logMessage($message, $type = 'info') {
    $icons = [
        'success' => '✅',
        'warning' => '⚠️',
        'error' => '❌',
        'info' => 'ℹ️'
    ];
    
    $icon = $icons[$type] ?? 'ℹ️';
    
    if (isset($_SERVER['HTTP_HOST'])) {
        echo "<p class='$type'>$icon $message</p>";
    } else {
        echo "[$type] $message\n";
    }
}

/**
 * Check system capabilities
 */
function checkSystemCapabilities() {
    $capabilities = [
        'symlink_function' => function_exists('symlink'),
        'readlink_function' => function_exists('readlink'),
        'is_link_function' => function_exists('is_link'),
        'realpath_function' => function_exists('realpath'),
    ];
    
    return $capabilities;
}

/**
 * Create necessary directories
 */
function createDirectories($target) {
    $directories = [
        $target,
        $target . '/products',
        $target . '/categories',
        $target . '/brands',
        $target . '/users',
        $target . '/media',
        $target . '/temp'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (mkdir($dir, 0755, true)) {
                logMessage("Created directory: $dir", 'success');
            } else {
                logMessage("Failed to create directory: $dir", 'error');
                return false;
            }
        }
    }
    
    return true;
}

/**
 * Test symlink functionality
 */
function testSymlink() {
    $testTarget = sys_get_temp_dir() . '/laravel_symlink_test_' . uniqid();
    $testLink = sys_get_temp_dir() . '/laravel_symlink_link_' . uniqid();
    
    // Create test target
    if (!mkdir($testTarget)) {
        return false;
    }
    
    // Try to create symlink
    $result = @symlink($testTarget, $testLink);
    
    // Clean up
    if ($result && is_link($testLink)) {
        unlink($testLink);
    }
    if (is_dir($testTarget)) {
        rmdir($testTarget);
    }
    
    return $result;
}

/**
 * Create alternative directory structure (fallback)
 */
function createAlternativeStructure($target, $link) {
    logMessage("Symlinks not supported. Creating alternative structure...", 'warning');
    
    // Create public/storage directory
    if (!file_exists($link)) {
        if (mkdir($link, 0755, true)) {
            logMessage("Created public/storage directory", 'success');
        } else {
            logMessage("Failed to create public/storage directory", 'error');
            return false;
        }
    }
    
    // Create subdirectories
    $subdirs = ['products', 'categories', 'brands', 'users', 'media', 'temp'];
    foreach ($subdirs as $subdir) {
        $dir = $link . '/' . $subdir;
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    // Create .htaccess for direct access
    $htaccess = $link . '/.htaccess';
    $htaccessContent = "# Laravel Storage Access\n";
    $htaccessContent .= "Options -Indexes\n";
    $htaccessContent .= "<Files \"*.php\">\n";
    $htaccessContent .= "    Deny from all\n";
    $htaccessContent .= "</Files>\n";
    
    file_put_contents($htaccess, $htaccessContent);
    
    logMessage("Alternative structure created. You'll need to copy files manually.", 'warning');
    return true;
}

// Main execution
switch ($action) {
    case 'create':
        logMessage("Starting symlink creation process...", 'info');
        
        // Check system capabilities
        $capabilities = checkSystemCapabilities();
        if (!$capabilities['symlink_function']) {
            logMessage("Symlink function not available on this system", 'error');
            createAlternativeStructure($target, $link);
            break;
        }
        
        // Test symlink capability
        if (!testSymlink()) {
            logMessage("Symlink test failed. System may not support symlinks.", 'error');
            createAlternativeStructure($target, $link);
            break;
        }
        
        // Check if target exists and create if needed
        if (!$target || !file_exists($target)) {
            $targetToCreate = $target ?: $targetPath;
            logMessage("Target directory does not exist: $targetToCreate", 'warning');
            if (!createDirectories($targetToCreate)) {
                logMessage("Failed to create target directories", 'error');
                break;
            }
            // Update target path after creation
            $target = realpath($targetToCreate);
        }
        
        // Remove existing link if it's broken
        if (file_exists($link)) {
            if (is_link($link)) {
                $currentTarget = readlink($link);
                if (!file_exists($currentTarget)) {
                    logMessage("Removing broken symlink", 'warning');
                    unlink($link);
                } else {
                    logMessage("Valid symlink already exists", 'success');
                    logMessage("Link: $link → " . readlink($link), 'info');
                    break;
                }
            } else {
                logMessage("File/directory exists at link location but is not a symlink", 'error');
                logMessage("Please remove: $link", 'error');
                break;
            }
        }
        
        // Create the symlink
        if (symlink($target, $link)) {
            logMessage("Symlink created successfully!", 'success');
            logMessage("Link: $link", 'info');
            logMessage("Target: $target", 'info');
            
            // Verify the symlink
            if (is_link($link) && file_exists($link)) {
                logMessage("Symlink verification: PASSED", 'success');
            } else {
                logMessage("Symlink verification: FAILED", 'error');
            }
        } else {
            logMessage("Failed to create symlink", 'error');
            createAlternativeStructure($target, $link);
        }
        break;
        
    case 'test':
        logMessage("Testing image access...", 'info');
        
        if (!$target || !file_exists($target)) {
            logMessage("Cannot test - target directory does not exist", 'error');
            break;
        }
        
        // Create a test image
        $testImage = $target . '/test-image.txt';
        if (file_put_contents($testImage, 'Test image content - ' . date('Y-m-d H:i:s'))) {
            logMessage("Test file created: $testImage", 'success');
            
            // Check if accessible via web
            $testUrl = '/storage/test-image.txt';
            logMessage("Test URL: $testUrl", 'info');
            
            if (isset($_SERVER['HTTP_HOST'])) {
                echo "<p class='info'>Test the link: <a href='$testUrl' target='_blank'>$testUrl</a></p>";
            }
            
            // Clean up test file after 5 seconds
            if (isset($_SERVER['HTTP_HOST'])) {
                echo "<script>setTimeout(() => { 
                    fetch('?action=cleanup'); 
                }, 5000);</script>";
            }
        } else {
            logMessage("Failed to create test file", 'error');
        }
        break;
        
    case 'cleanup':
        if ($target && file_exists($target)) {
            $testImage = $target . '/test-image.txt';
            if (file_exists($testImage)) {
                unlink($testImage);
                logMessage("Test file cleaned up", 'info');
            }
        }
        break;
        
    case 'artisan':
        logMessage("Attempting Laravel Artisan storage:link command...", 'info');
        
        $artisanPath = __DIR__ . '/../artisan';
        if (file_exists($artisanPath)) {
            $output = [];
            $returnVar = 0;
            exec("php $artisanPath storage:link 2>&1", $output, $returnVar);
            
            if ($returnVar === 0) {
                logMessage("Artisan command executed successfully", 'success');
                foreach ($output as $line) {
                    logMessage($line, 'info');
                }
            } else {
                logMessage("Artisan command failed", 'error');
                foreach ($output as $line) {
                    logMessage($line, 'error');
                }
            }
        } else {
            logMessage("Artisan file not found", 'error');
        }
        break;
        
    default:
        // Check current status
        logMessage("Laravel Storage Symlink Status Check", 'info');
        
        $capabilities = checkSystemCapabilities();
        logMessage("System Capabilities:", 'info');
        foreach ($capabilities as $cap => $available) {
            $status = $available ? 'Available' : 'Not Available';
            $type = $available ? 'success' : 'error';
            logMessage("  $cap: $status", $type);
        }
        
        logMessage("Paths:", 'info');
        logMessage("  Target: $target", 'info');
        logMessage("  Link: $link", 'info');
        
        if (file_exists($link)) {
            if (is_link($link)) {
                $linkTarget = readlink($link);
                logMessage("Symlink exists and points to: $linkTarget", 'success');
                
                if (file_exists($linkTarget)) {
                    logMessage("Target directory exists and is accessible", 'success');
                } else {
                    logMessage("Target directory does not exist (broken symlink)", 'error');
                }
            } else {
                logMessage("File/directory exists but is not a symlink", 'warning');
            }
        } else {
            logMessage("Symlink does not exist", 'warning');
        }
        
        if (!$target || !file_exists($target)) {
            logMessage("Target directory does not exist", 'error');
        } else {
            logMessage("Target directory exists", 'success');
        }
        
        // Show action buttons for web interface
        if (isset($_SERVER['HTTP_HOST'])) {
            echo '<div style="margin: 20px 0;">';
            echo '<a href="?action=create" class="btn btn-primary">Create Symlink</a>';
            echo '<a href="?action=artisan" class="btn btn-success">Use Artisan Command</a>';
            echo '<a href="?action=test" class="btn btn-warning">Test Image Access</a>';
            echo '</div>';
            
            echo '<div class="code">';
            echo '<strong>Manual Commands (if needed):</strong><br>';
            echo 'Via Artisan: <code>php artisan storage:link</code><br>';
            echo 'Via Command Line: <code>ln -s ' . $target . ' ' . $link . '</code><br>';
            echo '</div>';
            
            echo '<p class="error"><strong>⚠️ SECURITY WARNING:</strong> Delete this file after use!</p>';
        }
        break;
}

if (isset($_SERVER['HTTP_HOST'])) {
    echo '</body></html>';
}
