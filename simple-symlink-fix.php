<?php
/**
 * Simple & Reliable Symlink Fix
 * 
 * This script provides a foolproof way to fix Laravel storage symlink issues.
 * It handles all common scenarios and provides clear feedback.
 */

// Set content type for web access
if (isset($_SERVER['HTTP_HOST'])) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Simple Symlink Fix</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        .step { margin: 15px 0; padding: 10px; border-left: 4px solid #007bff; background: #f8f9fa; }
        .btn { display: inline-block; padding: 10px 20px; margin: 10px 5px; text-decoration: none; 
               border-radius: 5px; background: #007bff; color: white; }
    </style>
</head>
<body>
    <h1>🔧 Simple Symlink Fix</h1>';
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

// Configuration
$storagePath = __DIR__ . '/storage/app/public';
$publicPath = __DIR__ . '/public/storage';
$action = $_GET['action'] ?? 'check';

msg("Laravel Storage Symlink Fix Tool", 'info');
msg("Storage: $storagePath", 'info');
msg("Public: $publicPath", 'info');

if ($action === 'fix') {
    msg("Starting symlink fix process...", 'info');
    
    // Step 1: Create storage directory if needed
    if (!file_exists($storagePath)) {
        if (mkdir($storagePath, 0755, true)) {
            msg("Created storage directory", 'success');
        } else {
            msg("Failed to create storage directory", 'error');
            exit;
        }
    } else {
        msg("Storage directory exists", 'success');
    }
    
    // Step 2: Create subdirectories
    $subdirs = ['products', 'categories', 'brands', 'users', 'media'];
    foreach ($subdirs as $subdir) {
        $dir = $storagePath . '/' . $subdir;
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    msg("Storage subdirectories ready", 'success');
    
    // Step 3: Handle existing public/storage
    if (file_exists($publicPath)) {
        if (is_link($publicPath)) {
            $currentTarget = readlink($publicPath);
            if (realpath($currentTarget) === realpath($storagePath)) {
                msg("Symlink already correct!", 'success');
                if (isset($_SERVER['HTTP_HOST'])) {
                    echo "<a href='?action=test' class='btn'>Test Symlink</a>";
                }
                exit;
            } else {
                unlink($publicPath);
                msg("Removed incorrect symlink", 'warning');
            }
        } else {
            msg("File/directory exists at symlink location. Please remove it manually: $publicPath", 'error');
            exit;
        }
    }
    
    // Step 4: Create symlink
    if (function_exists('symlink')) {
        if (symlink($storagePath, $publicPath)) {
            msg("Symlink created successfully!", 'success');
            
            // Verify
            if (is_link($publicPath) && file_exists($publicPath)) {
                msg("Symlink verification: PASSED", 'success');
                
                if (isset($_SERVER['HTTP_HOST'])) {
                    echo "<a href='?action=test' class='btn'>Test Symlink</a>";
                    echo "<p class='success'>✅ Symlink fix completed! Your images should now display correctly.</p>";
                }
            } else {
                msg("Symlink verification: FAILED", 'error');
            }
        } else {
            msg("Failed to create symlink. Creating fallback structure...", 'warning');
            createFallback($publicPath, $subdirs);
        }
    } else {
        msg("Symlink function not available. Creating fallback structure...", 'warning');
        createFallback($publicPath, $subdirs);
    }
    
} elseif ($action === 'test') {
    msg("Testing symlink functionality...", 'info');
    
    if (!file_exists($publicPath)) {
        msg("Symlink doesn't exist. Run fix first.", 'error');
    } elseif (!is_link($publicPath)) {
        msg("Public storage exists but is not a symlink", 'warning');
    } else {
        $target = readlink($publicPath);
        msg("Symlink points to: $target", 'info');
        
        if (file_exists($target)) {
            msg("Target directory accessible", 'success');
            
            // Create test file
            $testFile = $storagePath . '/test-' . time() . '.txt';
            $testContent = 'Test file created at ' . date('Y-m-d H:i:s');
            
            if (file_put_contents($testFile, $testContent)) {
                $publicTestFile = $publicPath . '/' . basename($testFile);
                
                if (file_exists($publicTestFile) && file_get_contents($publicTestFile) === $testContent) {
                    msg("File access test: PASSED", 'success');
                    
                    if (isset($_SERVER['HTTP_HOST'])) {
                        $testUrl = '/storage/' . basename($testFile);
                        echo "<p class='info'>Test URL: <a href='$testUrl' target='_blank'>$testUrl</a></p>";
                    }
                    
                    // Clean up after 3 seconds
                    if (isset($_SERVER['HTTP_HOST'])) {
                        echo "<script>setTimeout(() => { 
                            fetch('?action=cleanup&file=" . basename($testFile) . "'); 
                        }, 3000);</script>";
                    } else {
                        unlink($testFile);
                    }
                } else {
                    msg("File access test: FAILED", 'error');
                    unlink($testFile);
                }
            } else {
                msg("Could not create test file", 'error');
            }
        } else {
            msg("Target directory not accessible (broken symlink)", 'error');
        }
    }
    
} elseif ($action === 'cleanup') {
    $file = $_GET['file'] ?? '';
    if ($file && file_exists($storagePath . '/' . $file)) {
        unlink($storagePath . '/' . $file);
        msg("Test file cleaned up", 'info');
    }
    
} else {
    // Status check
    msg("Current Status:", 'info');
    
    if (file_exists($storagePath)) {
        msg("Storage directory: EXISTS", 'success');
    } else {
        msg("Storage directory: MISSING", 'error');
    }
    
    if (file_exists($publicPath)) {
        if (is_link($publicPath)) {
            $target = readlink($publicPath);
            if (file_exists($target)) {
                msg("Symlink: VALID (points to $target)", 'success');
            } else {
                msg("Symlink: BROKEN (points to $target)", 'error');
            }
        } else {
            msg("Public storage: EXISTS but not a symlink", 'warning');
        }
    } else {
        msg("Symlink: MISSING", 'warning');
    }
    
    if (function_exists('symlink')) {
        msg("Symlink support: AVAILABLE", 'success');
    } else {
        msg("Symlink support: NOT AVAILABLE", 'warning');
    }
    
    if (isset($_SERVER['HTTP_HOST'])) {
        echo "<div style='margin: 20px 0;'>";
        echo "<a href='?action=fix' class='btn'>Fix Symlink Issues</a>";
        if (file_exists($publicPath)) {
            echo "<a href='?action=test' class='btn'>Test Current Setup</a>";
        }
        echo "</div>";
    }
}

function createFallback($publicPath, $subdirs) {
    if (!file_exists($publicPath)) {
        mkdir($publicPath, 0755, true);
    }
    
    foreach ($subdirs as $subdir) {
        $dir = $publicPath . '/' . $subdir;
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    // Create .htaccess
    $htaccess = $publicPath . '/.htaccess';
    file_put_contents($htaccess, "Options -Indexes\n<Files \"*.php\">\n    Deny from all\n</Files>\n");
    
    msg("Fallback directory structure created", 'success');
    msg("Note: You'll need to manually copy files from storage/app/public to public/storage", 'warning');
}

if (isset($_SERVER['HTTP_HOST'])) {
    echo "<div style='margin-top: 30px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;'>";
    echo "<strong>⚠️ Security Notice:</strong> Delete this file after use!";
    echo "</div>";
    echo "</body></html>";
}
?>