<?php
/**
 * Live Server Image Fix
 * 
 * This script fixes common image display issues when deploying Laravel to live servers.
 * It handles APP_URL configuration, symlinks, and file permissions.
 */

// Set content type for web access
if (isset($_SERVER['HTTP_HOST'])) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Live Server Image Fix</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        .step { margin: 15px 0; padding: 15px; border-left: 4px solid #007bff; background: #f8f9fa; }
        .btn { display: inline-block; padding: 10px 20px; margin: 10px 5px; text-decoration: none; 
               border-radius: 5px; background: #007bff; color: white; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; }
        .current-url { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🚀 Live Server Image Fix</h1>';
}

function msg($text, $type = 'info') {
    $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
    $icon = $icons[$type] ?? 'ℹ️';
    
    if (isset($_SERVER['HTTP_HOST'])) {
        echo "<div class='step $type'>$icon $text</div>";
    } else {
        echo "[$type] $text\n";
    }
}

// Get current URL info
$currentDomain = $_SERVER['HTTP_HOST'] ?? 'localhost';
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$currentUrl = $protocol . '://' . $currentDomain;

// Configuration
$envPath = __DIR__ . '/.env';
$storagePath = __DIR__ . '/storage/app/public';
$publicPath = __DIR__ . '/public/storage';
$action = $_GET['action'] ?? 'check';

msg("Live Server Image Fix Tool", 'info');

if (isset($_SERVER['HTTP_HOST'])) {
    echo "<div class='current-url'>";
    echo "<strong>Current Server URL:</strong> $currentUrl<br>";
    echo "<strong>Domain:</strong> $currentDomain";
    echo "</div>";
}

if ($action === 'fix') {
    msg("Starting comprehensive live server fix...", 'info');
    
    // Step 1: Fix APP_URL in .env
    if (file_exists($envPath)) {
        $envContent = file_get_contents($envPath);
        $originalContent = $envContent;
        
        // Update APP_URL
        if (preg_match('/^APP_URL=(.*)$/m', $envContent, $matches)) {
            $oldUrl = trim($matches[1]);
            $envContent = preg_replace('/^APP_URL=.*$/m', "APP_URL=$currentUrl", $envContent);
            
            if (file_put_contents($envPath, $envContent)) {
                msg("Updated APP_URL from '$oldUrl' to '$currentUrl'", 'success');
            } else {
                msg("Failed to update .env file", 'error');
                exit;
            }
        } else {
            // Add APP_URL if not exists
            $envContent .= "\nAPP_URL=$currentUrl\n";
            if (file_put_contents($envPath, $envContent)) {
                msg("Added APP_URL=$currentUrl to .env", 'success');
            } else {
                msg("Failed to add APP_URL to .env", 'error');
                exit;
            }
        }
    } else {
        msg(".env file not found", 'error');
        exit;
    }
    
    // Step 2: Clear Laravel caches
    $cacheCommands = [
        'config:clear' => 'Configuration cache',
        'route:clear' => 'Route cache',
        'view:clear' => 'View cache',
        'cache:clear' => 'Application cache'
    ];
    
    foreach ($cacheCommands as $command => $description) {
        $output = [];
        $returnCode = 0;
        
        // Try different PHP executables
        $phpPaths = ['php', '/usr/bin/php', '/usr/local/bin/php', 'php.exe'];
        $success = false;
        
        foreach ($phpPaths as $phpPath) {
            exec("$phpPath artisan $command 2>&1", $output, $returnCode);
            if ($returnCode === 0) {
                $success = true;
                break;
            }
            $output = []; // Reset for next attempt
        }
        
        if ($success) {
            msg("Cleared $description", 'success');
        } else {
            msg("Could not clear $description (run 'php artisan $command' manually)", 'warning');
        }
    }
    
    // Step 3: Fix storage symlink
    msg("Fixing storage symlink...", 'info');
    
    // Create storage directory if needed
    if (!file_exists($storagePath)) {
        if (mkdir($storagePath, 0755, true)) {
            msg("Created storage directory", 'success');
        } else {
            msg("Failed to create storage directory", 'error');
            exit;
        }
    }
    
    // Create subdirectories
    $subdirs = ['products', 'categories', 'brands', 'users', 'media'];
    foreach ($subdirs as $subdir) {
        $dir = $storagePath . '/' . $subdir;
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    msg("Storage subdirectories ready", 'success');
    
    // Advanced symlink handling for live servers
    $symlinkCreated = handleSymlinkCreation($publicPath, $storagePath);
    
    if (!$symlinkCreated) {
        msg("Symlink creation failed, using fallback method", 'warning');
        createFallbackStructure($publicPath, $storagePath);
    }
    
    // Step 4: Set proper permissions
    msg("Setting file permissions...", 'info');
    
    $permissionPaths = [
        $storagePath => 0755,
        __DIR__ . '/storage' => 0755,
        __DIR__ . '/storage/logs' => 0755,
        __DIR__ . '/storage/framework' => 0755,
        __DIR__ . '/storage/framework/cache' => 0755,
        __DIR__ . '/storage/framework/sessions' => 0755,
        __DIR__ . '/storage/framework/views' => 0755,
        __DIR__ . '/bootstrap/cache' => 0755,
    ];
    
    foreach ($permissionPaths as $path => $permission) {
        if (file_exists($path)) {
            if (chmod($path, $permission)) {
                msg("Set permissions for " . basename($path), 'success');
            } else {
                msg("Could not set permissions for " . basename($path), 'warning');
            }
        }
    }
    
    // Step 5: Test image access
    msg("Testing image access...", 'info');
    testImageAccess($currentUrl);
    
    if (isset($_SERVER['HTTP_HOST'])) {
        echo "<div class='step success'>";
        echo "✅ <strong>Live server fix completed!</strong><br>";
        echo "Your images should now display correctly at: <strong>$currentUrl</strong><br>";
        echo "<a href='?action=test' class='btn'>Test Image Access</a>";
        echo "<a href='/admin/media' class='btn'>Check Media Library</a>";
        echo "</div>";
        
        echo "<div class='step info'>";
        echo "<strong>Next Steps:</strong><br>";
        echo "1. Clear your browser cache<br>";
        echo "2. Check that images display correctly on your site<br>";
        echo "3. If issues persist, check server error logs<br>";
        echo "4. Delete this file after confirming everything works";
        echo "</div>";
    }
    
} elseif ($action === 'symlink-only') {
    msg("Creating symlink only (manual mode)...", 'info');
    
    $symlinkCreated = handleSymlinkCreation($publicPath, $storagePath);
    
    if ($symlinkCreated) {
        msg("Symlink created successfully!", 'success');
        if (isset($_SERVER['HTTP_HOST'])) {
            echo "<a href='?action=test' class='btn'>Test Symlink</a>";
        }
    } else {
        msg("All symlink methods failed", 'error');
        if (isset($_SERVER['HTTP_HOST'])) {
            echo "<div class='step warning'>";
            echo "Manual steps you can try:<br>";
            echo "1. SSH into your server<br>";
            echo "2. Run: <code>ln -sf " . realpath($storagePath) . " " . $publicPath . "</code><br>";
            echo "3. Or run: <code>php artisan storage:link</code><br>";
            echo "4. Or use cPanel File Manager to create a symbolic link<br>";
            echo "<a href='?action=fix' class='btn'>Use Fallback Method</a>";
            echo "</div>";
        }
    }
    
} elseif ($action === 'test') {
    msg("Testing current configuration...", 'info');
    
    // Test .env APP_URL
    if (file_exists($envPath)) {
        $envContent = file_get_contents($envPath);
        if (preg_match('/^APP_URL=(.*)$/m', $envContent, $matches)) {
            $appUrl = trim($matches[1]);
            if ($appUrl === $currentUrl) {
                msg("APP_URL correctly set to: $appUrl", 'success');
            } else {
                msg("APP_URL mismatch: set to '$appUrl', should be '$currentUrl'", 'warning');
            }
        } else {
            msg("APP_URL not found in .env", 'error');
        }
    }
    
    // Test symlink
    if (file_exists($publicPath)) {
        if (is_link($publicPath)) {
            $target = readlink($publicPath);
            if (file_exists($target)) {
                msg("Storage symlink is working", 'success');
            } else {
                msg("Storage symlink is broken", 'error');
            }
        } else {
            msg("Public storage exists but is not a symlink", 'warning');
        }
    } else {
        msg("Storage symlink missing", 'error');
    }
    
    // Test image access
    testImageAccess($currentUrl);
    
} else {
    // Status check
    msg("Current Status Check:", 'info');
    
    // Check APP_URL
    if (file_exists($envPath)) {
        $envContent = file_get_contents($envPath);
        if (preg_match('/^APP_URL=(.*)$/m', $envContent, $matches)) {
            $appUrl = trim($matches[1]);
            if (isset($_SERVER['HTTP_HOST'])) {
                echo "<div class='step " . ($appUrl === $currentUrl ? 'success' : 'warning') . "'>";
                echo "APP_URL: <code>$appUrl</code><br>";
                if ($appUrl !== $currentUrl) {
                    echo "Should be: <code>$currentUrl</code>";
                }
                echo "</div>";
            }
        } else {
            msg("APP_URL not configured", 'error');
        }
    } else {
        msg(".env file not found", 'error');
    }
    
    // Check storage
    if (file_exists($storagePath)) {
        msg("Storage directory: EXISTS", 'success');
    } else {
        msg("Storage directory: MISSING", 'error');
    }
    
    // Check symlink
    if (file_exists($publicPath)) {
        if (is_link($publicPath)) {
            $target = readlink($publicPath);
            if (file_exists($target)) {
                msg("Storage symlink: WORKING", 'success');
            } else {
                msg("Storage symlink: BROKEN", 'error');
            }
        } else {
            msg("Public storage: EXISTS (not symlink)", 'warning');
        }
    } else {
        msg("Storage symlink: MISSING", 'error');
    }
    
    if (isset($_SERVER['HTTP_HOST'])) {
        echo "<div style='margin: 20px 0;'>";
        echo "<a href='?action=fix' class='btn'>Fix All Issues</a>";
        echo "<a href='?action=symlink-only' class='btn'>Create Symlink Only</a>";
        echo "<a href='?action=test' class='btn'>Run Tests</a>";
        echo "</div>";
        
        echo "<div class='step info'>";
        echo "<strong>What each option does:</strong><br>";
        echo "• <strong>Fix All Issues:</strong> Updates APP_URL, clears caches, creates symlink<br>";
        echo "• <strong>Create Symlink Only:</strong> Only attempts to create the storage symlink<br>";
        echo "• <strong>Run Tests:</strong> Checks current configuration without making changes";
        echo "</div>";
    }
}

function handleSymlinkCreation($publicPath, $storagePath) {
    msg("Attempting symlink creation with multiple methods...", 'info');
    
    // Method 1: Check and handle existing public/storage
    if (file_exists($publicPath)) {
        if (is_link($publicPath)) {
            $currentTarget = readlink($publicPath);
            $realCurrentTarget = realpath($currentTarget);
            $realStoragePath = realpath($storagePath);
            
            if ($realCurrentTarget === $realStoragePath) {
                msg("Symlink already exists and is correct", 'success');
                return true;
            } else {
                msg("Removing incorrect symlink (points to: $currentTarget)", 'warning');
                if (!unlink($publicPath)) {
                    msg("Failed to remove existing symlink", 'error');
                    return false;
                }
            }
        } else {
            // It's a real directory, backup it
            $backupPath = $publicPath . '_backup_' . date('Y-m-d_H-i-s');
            msg("Backing up existing directory to " . basename($backupPath), 'warning');
            if (!rename($publicPath, $backupPath)) {
                msg("Failed to backup existing directory", 'error');
                return false;
            }
        }
    }
    
    // Method 2: Try native PHP symlink function
    if (function_exists('symlink')) {
        msg("Trying PHP symlink function...", 'info');
        if (@symlink($storagePath, $publicPath)) {
            // Verify the symlink works
            if (is_link($publicPath) && file_exists($publicPath)) {
                msg("PHP symlink created successfully", 'success');
                return true;
            } else {
                msg("PHP symlink created but verification failed", 'warning');
                @unlink($publicPath);
            }
        } else {
            msg("PHP symlink function failed", 'warning');
        }
    }
    
    // Method 3: Try system commands (Linux/Unix)
    if (PHP_OS_FAMILY !== 'Windows') {
        msg("Trying system ln command...", 'info');
        $command = "ln -sf " . escapeshellarg($storagePath) . " " . escapeshellarg($publicPath);
        $output = [];
        $returnCode = 0;
        exec($command . " 2>&1", $output, $returnCode);
        
        if ($returnCode === 0 && is_link($publicPath) && file_exists($publicPath)) {
            msg("System ln command successful", 'success');
            return true;
        } else {
            msg("System ln command failed: " . implode(', ', $output), 'warning');
        }
    }
    
    // Method 4: Try Windows mklink (if on Windows)
    if (PHP_OS_FAMILY === 'Windows') {
        msg("Trying Windows mklink command...", 'info');
        $command = 'mklink /D ' . escapeshellarg($publicPath) . ' ' . escapeshellarg($storagePath);
        $output = [];
        $returnCode = 0;
        exec($command . " 2>&1", $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($publicPath)) {
            msg("Windows mklink successful", 'success');
            return true;
        } else {
            msg("Windows mklink failed: " . implode(', ', $output), 'warning');
        }
    }
    
    // Method 5: Try Laravel artisan command
    msg("Trying Laravel artisan storage:link...", 'info');
    $phpPaths = ['php', '/usr/bin/php', '/usr/local/bin/php', 'php.exe'];
    
    foreach ($phpPaths as $phpPath) {
        $command = "$phpPath artisan storage:link";
        $output = [];
        $returnCode = 0;
        exec($command . " 2>&1", $output, $returnCode);
        
        if ($returnCode === 0) {
            if (is_link($publicPath) && file_exists($publicPath)) {
                msg("Laravel artisan storage:link successful", 'success');
                return true;
            }
        }
    }
    msg("Laravel artisan storage:link failed", 'warning');
    
    return false;
}

function createFallbackStructure($publicPath, $storagePath) {
    msg("Creating fallback directory structure...", 'info');
    
    if (!file_exists($publicPath)) {
        mkdir($publicPath, 0755, true);
    }
    
    // Copy files from storage to public
    if (file_exists($storagePath)) {
        copyDirectory($storagePath, $publicPath);
        msg("Copied all files from storage to public directory", 'success');
    }
    
    // Create .htaccess for security
    $htaccess = $publicPath . '/.htaccess';
    $htaccessContent = "Options -Indexes\n<Files \"*.php\">\n    Deny from all\n</Files>\n";
    file_put_contents($htaccess, $htaccessContent);
    
    // Create sync script for future updates
    createSyncScript($publicPath, $storagePath);
    
    msg("Fallback structure created successfully", 'success');
    msg("Note: Files are copied, not linked. Use the sync script to update when needed.", 'warning');
}

function copyDirectory($src, $dst) {
    $dir = opendir($src);
    if (!file_exists($dst)) {
        mkdir($dst, 0755, true);
    }
    
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            $srcFile = $src . '/' . $file;
            $dstFile = $dst . '/' . $file;
            
            if (is_dir($srcFile)) {
                copyDirectory($srcFile, $dstFile);
            } else {
                copy($srcFile, $dstFile);
            }
        }
    }
    closedir($dir);
}

function createSyncScript($publicPath, $storagePath) {
    $syncScript = __DIR__ . '/sync-storage.php';
    $syncContent = '<?php
/**
 * Storage Sync Script
 * Run this script to sync files from storage/app/public to public/storage
 * when symlinks are not available
 */

$storagePath = __DIR__ . "/storage/app/public";
$publicPath = __DIR__ . "/public/storage";

function syncDirectory($src, $dst) {
    if (!file_exists($dst)) {
        mkdir($dst, 0755, true);
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    $synced = 0;
    foreach ($iterator as $item) {
        $target = $dst . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
        
        if ($item->isDir()) {
            if (!file_exists($target)) {
                mkdir($target, 0755, true);
            }
        } else {
            $srcTime = $item->getMTime();
            $dstTime = file_exists($target) ? filemtime($target) : 0;
            
            if ($srcTime > $dstTime) {
                copy($item, $target);
                $synced++;
            }
        }
    }
    
    return $synced;
}

if (isset($_SERVER["HTTP_HOST"])) {
    header("Content-Type: text/html");
    echo "<h2>Storage Sync</h2>";
}

$synced = syncDirectory($storagePath, $publicPath);

if (isset($_SERVER["HTTP_HOST"])) {
    echo "<p>Synced $synced files from storage to public directory.</p>";
    echo "<p><a href=\"javascript:history.back()\">Go Back</a></p>";
} else {
    echo "Synced $synced files.\n";
}
?>';
    
    file_put_contents($syncScript, $syncContent);
    msg("Created sync script: sync-storage.php", 'info');
}

function testImageAccess($baseUrl) {
    // Look for sample images
    $storagePath = __DIR__ . '/storage/app/public';
    $sampleImages = [];
    
    // Find some sample images
    $dirs = ['products', 'media', 'categories'];
    foreach ($dirs as $dir) {
        $dirPath = $storagePath . '/' . $dir;
        if (file_exists($dirPath)) {
            $files = glob($dirPath . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
            if (!empty($files)) {
                $sampleImages[] = '/storage/' . $dir . '/' . basename($files[0]);
                break;
            }
        }
    }
    
    if (!empty($sampleImages)) {
        $testUrl = $baseUrl . $sampleImages[0];
        msg("Sample image URL: $testUrl", 'info');
        
        if (isset($_SERVER['HTTP_HOST'])) {
            echo "<div class='step info'>";
            echo "Test this image URL: <a href='$testUrl' target='_blank'>$testUrl</a>";
            echo "</div>";
        }
    } else {
        msg("No sample images found for testing", 'warning');
    }
}

if (isset($_SERVER['HTTP_HOST'])) {
    echo "<div style='margin-top: 30px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;'>";
    echo "<strong>⚠️ Security Notice:</strong> Delete this file after use!";
    echo "</div>";
    echo "</body></html>";
}
?>