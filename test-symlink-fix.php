<?php
/**
 * Simple Symlink Test & Fix
 * 
 * This script tests and fixes symlink issues quickly
 */

echo "=== Laravel Storage Symlink Test & Fix ===\n\n";

// Configuration
$storagePath = __DIR__ . '/storage/app/public';
$publicPath = __DIR__ . '/public/storage';

echo "Storage Path: $storagePath\n";
echo "Public Path: $publicPath\n\n";

// Step 1: Check if storage directory exists
if (!file_exists($storagePath)) {
    echo "Creating storage directory...\n";
    if (mkdir($storagePath, 0755, true)) {
        echo "✓ Storage directory created\n";
    } else {
        echo "✗ Failed to create storage directory\n";
        exit(1);
    }
} else {
    echo "✓ Storage directory exists\n";
}

// Step 2: Create subdirectories
$subdirs = ['products', 'categories', 'brands', 'users', 'media', 'temp'];
foreach ($subdirs as $subdir) {
    $dir = $storagePath . '/' . $subdir;
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "✓ Created subdirectory: $subdir\n";
    }
}

// Step 3: Handle existing public/storage
if (file_exists($publicPath)) {
    if (is_link($publicPath)) {
        $currentTarget = readlink($publicPath);
        if (realpath($currentTarget) === realpath($storagePath)) {
            echo "✓ Symlink already exists and is correct\n";
            exit(0);
        } else {
            echo "⚠ Removing incorrect symlink\n";
            unlink($publicPath);
        }
    } else {
        echo "✗ File/directory exists at symlink location\n";
        echo "Please manually remove: $publicPath\n";
        exit(1);
    }
}

// Step 4: Create symlink
if (function_exists('symlink')) {
    if (symlink($storagePath, $publicPath)) {
        echo "✓ Symlink created successfully\n";
        
        // Verify
        if (is_link($publicPath) && file_exists($publicPath)) {
            echo "✓ Symlink verification passed\n";
            
            // Test with a file
            $testFile = $storagePath . '/test.txt';
            file_put_contents($testFile, 'Test content');
            
            if (file_exists($publicPath . '/test.txt')) {
                echo "✓ File access test passed\n";
                unlink($testFile);
            } else {
                echo "✗ File access test failed\n";
            }
        } else {
            echo "✗ Symlink verification failed\n";
        }
    } else {
        echo "✗ Failed to create symlink\n";
        createFallbackStructure($publicPath);
    }
} else {
    echo "⚠ Symlink function not available\n";
    createFallbackStructure($publicPath);
}

function createFallbackStructure($publicPath) {
    global $subdirs;
    
    echo "Creating fallback directory structure...\n";
    
    if (!file_exists($publicPath)) {
        mkdir($publicPath, 0755, true);
    }
    
    foreach ($subdirs as $subdir) {
        $dir = $publicPath . '/' . $subdir;
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    // Create .htaccess for security
    $htaccess = $publicPath . '/.htaccess';
    $htaccessContent = "Options -Indexes\n<Files \"*.php\">\n    Deny from all\n</Files>\n";
    file_put_contents($htaccess, $htaccessContent);
    
    echo "✓ Fallback structure created\n";
    echo "⚠ Note: You'll need to manually copy files from storage/app/public to public/storage\n";
}

echo "\n=== Test Complete ===\n";
?>