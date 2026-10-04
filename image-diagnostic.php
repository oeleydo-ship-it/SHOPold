<?php
/**
 * Image Diagnostic Tool
 * 
 * This script helps diagnose image display issues on live servers.
 * It checks all common causes and provides specific solutions.
 */

// Set content type for web access
if (isset($_SERVER['HTTP_HOST'])) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Image Diagnostic Tool</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        .check { margin: 15px 0; padding: 15px; border-left: 4px solid #007bff; background: #f8f9fa; }
        .result { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .sample-images { display: flex; flex-wrap: wrap; gap: 10px; margin: 20px 0; }
        .sample-image { border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
        .sample-image img { max-width: 150px; max-height: 150px; }
    </style>
</head>
<body>
    <h1>🔍 Image Diagnostic Tool</h1>';
}

function checkResult($condition, $successMsg, $errorMsg, $warningMsg = null) {
    if ($condition === true) {
        echo "<div class='result success'>✅ $successMsg</div>";
        return true;
    } elseif ($condition === false) {
        echo "<div class='result error'>❌ $errorMsg</div>";
        return false;
    } else {
        echo "<div class='result warning'>⚠️ " . ($warningMsg ?: $errorMsg) . "</div>";
        return null;
    }
}

// Get current URL info
$currentDomain = $_SERVER['HTTP_HOST'] ?? 'localhost';
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$currentUrl = $protocol . '://' . $currentDomain;

// Configuration paths
$envPath = __DIR__ . '/.env';
$storagePath = __DIR__ . '/storage/app/public';
$publicPath = __DIR__ . '/public/storage';

echo "<div class='check'>";
echo "<h2>🌐 Server Information</h2>";
echo "<strong>Current URL:</strong> $currentUrl<br>";
echo "<strong>Server OS:</strong> " . PHP_OS_FAMILY . "<br>";
echo "<strong>PHP Version:</strong> " . PHP_VERSION . "<br>";
echo "<strong>Symlink Support:</strong> " . (function_exists('symlink') ? 'Available' : 'Not Available') . "<br>";
echo "</div>";

// Check 1: .env APP_URL
echo "<div class='check'>";
echo "<h3>1. APP_URL Configuration</h3>";
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    if (preg_match('/^APP_URL=(.*)$/m', $envContent, $matches)) {
        $appUrl = trim($matches[1]);
        $isCorrect = ($appUrl === $currentUrl);
        checkResult($isCorrect, 
            "APP_URL is correctly set to: $appUrl",
            "APP_URL mismatch: set to '$appUrl', should be '$currentUrl'"
        );
        
        if (!$isCorrect) {
            echo "<div class='code'>Fix: Update .env file<br>APP_URL=$currentUrl</div>";
        }
    } else {
        checkResult(false, "", "APP_URL not found in .env file");
        echo "<div class='code'>Fix: Add to .env file<br>APP_URL=$currentUrl</div>";
    }
} else {
    checkResult(false, "", ".env file not found");
}
echo "</div>";

// Check 2: Storage Directory
echo "<div class='check'>";
echo "<h3>2. Storage Directory Structure</h3>";
$storageExists = file_exists($storagePath);
checkResult($storageExists, 
    "Storage directory exists: $storagePath",
    "Storage directory missing: $storagePath"
);

if ($storageExists) {
    $subdirs = ['products', 'categories', 'brands', 'users', 'media'];
    foreach ($subdirs as $subdir) {
        $dir = $storagePath . '/' . $subdir;
        $exists = file_exists($dir);
        echo "<div class='result " . ($exists ? 'success' : 'warning') . "'>";
        echo ($exists ? '✅' : '⚠️') . " Subdirectory: $subdir " . ($exists ? 'exists' : 'missing');
        echo "</div>";
    }
}
echo "</div>";

// Check 3: Public Storage Symlink
echo "<div class='check'>";
echo "<h3>3. Public Storage Symlink</h3>";
if (file_exists($publicPath)) {
    if (is_link($publicPath)) {
        $target = readlink($publicPath);
        $realTarget = realpath($target);
        $realStorage = realpath($storagePath);
        
        if ($realTarget === $realStorage) {
            checkResult(true, "Symlink is correct and working");
        } else {
            checkResult(false, "", "Symlink points to wrong location: $target");
            echo "<div class='code'>Expected: $storagePath<br>Actual: $target</div>";
        }
    } else {
        checkResult(false, "", "public/storage exists but is not a symlink (it's a regular directory)");
        echo "<div class='code'>Fix: Remove directory and create symlink<br>rm -rf public/storage<br>ln -sf storage/app/public public/storage</div>";
    }
} else {
    checkResult(false, "", "public/storage does not exist");
    echo "<div class='code'>Fix: Create symlink<br>ln -sf storage/app/public public/storage</div>";
}
echo "</div>";

// Check 4: File Permissions
echo "<div class='check'>";
echo "<h3>4. File Permissions</h3>";
$paths = [
    $storagePath => 'Storage directory',
    $publicPath => 'Public storage',
    __DIR__ . '/storage' => 'Storage root',
    __DIR__ . '/bootstrap/cache' => 'Bootstrap cache'
];

foreach ($paths as $path => $description) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path);
        $readable = is_readable($path);
        
        echo "<div class='result " . ($writable && $readable ? 'success' : 'warning') . "'>";
        echo ($writable && $readable ? '✅' : '⚠️') . " $description: $perms " . 
             ($writable ? '(writable)' : '(not writable)') . " " .
             ($readable ? '(readable)' : '(not readable)');
        echo "</div>";
    } else {
        echo "<div class='result error'>❌ $description: does not exist</div>";
    }
}
echo "</div>";

// Check 5: Sample Images
echo "<div class='check'>";
echo "<h3>5. Sample Images Test</h3>";
$sampleImages = [];
$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Find sample images
$dirs = ['products', 'media', 'categories', 'brands'];
foreach ($dirs as $dir) {
    $dirPath = $storagePath . '/' . $dir;
    if (file_exists($dirPath)) {
        foreach ($imageExtensions as $ext) {
            $files = glob($dirPath . '/*.' . $ext);
            if (!empty($files)) {
                $sampleImages[] = [
                    'file' => basename($files[0]),
                    'dir' => $dir,
                    'path' => $files[0],
                    'url' => $currentUrl . '/storage/' . $dir . '/' . basename($files[0])
                ];
                if (count($sampleImages) >= 3) break 2; // Limit to 3 samples
            }
        }
    }
}

if (!empty($sampleImages)) {
    echo "<p>Found " . count($sampleImages) . " sample images. Testing accessibility:</p>";
    echo "<div class='sample-images'>";
    
    foreach ($sampleImages as $image) {
        echo "<div class='sample-image'>";
        echo "<strong>File:</strong> {$image['file']}<br>";
        echo "<strong>Directory:</strong> {$image['dir']}<br>";
        echo "<strong>URL:</strong> <a href='{$image['url']}' target='_blank'>{$image['url']}</a><br>";
        
        // Check if file is accessible via web
        echo "<img src='{$image['url']}' alt='Test Image' onerror=\"this.style.display='none'; this.nextSibling.style.display='block';\">";
        echo "<div style='display:none; color:red; font-weight:bold;'>❌ Image failed to load</div>";
        echo "</div>";
    }
    
    echo "</div>";
} else {
    checkResult(false, "", "No sample images found in storage directories");
    echo "<p>Upload some images through your admin panel to test image display.</p>";
}
echo "</div>";

// Check 6: Laravel Configuration
echo "<div class='check'>";
echo "<h3>6. Laravel Configuration</h3>";

// Check if we can load Laravel config
$configPath = __DIR__ . '/config/filesystems.php';
if (file_exists($configPath)) {
    checkResult(true, "Laravel filesystems config found");
    
    // Try to check the actual config (basic check)
    $configContent = file_get_contents($configPath);
    if (strpos($configContent, "'public' => [") !== false) {
        checkResult(true, "Public disk configuration found");
    } else {
        checkResult(false, "", "Public disk configuration missing");
    }
} else {
    checkResult(false, "", "Laravel filesystems config not found");
}

// Check for artisan
$artisanPath = __DIR__ . '/artisan';
if (file_exists($artisanPath)) {
    checkResult(true, "Laravel artisan file found");
    echo "<div class='code'>You can try: php artisan storage:link</div>";
} else {
    checkResult(false, "", "Laravel artisan file not found");
}
echo "</div>";

// Check 7: Web Server Configuration
echo "<div class='check'>";
echo "<h3>7. Web Server Test</h3>";

// Test direct access to storage URL
$testUrl = $currentUrl . '/storage/';
echo "<p>Testing storage URL access: <a href='$testUrl' target='_blank'>$testUrl</a></p>";

// Check .htaccess
$htaccessPath = __DIR__ . '/public/.htaccess';
if (file_exists($htaccessPath)) {
    checkResult(true, "Laravel .htaccess file found");
} else {
    checkResult(false, "", "Laravel .htaccess file missing - URL rewriting may not work");
}

echo "</div>";

// Recommendations
echo "<div class='check'>";
echo "<h2>🔧 Recommended Actions</h2>";
echo "<ol>";
echo "<li><strong>Run the fix script:</strong> <a href='live-server-fix.php'>live-server-fix.php</a></li>";
echo "<li><strong>Clear browser cache:</strong> Press Ctrl+F5 or Cmd+Shift+R</li>";
echo "<li><strong>Check server error logs</strong> for any PHP or web server errors</li>";
echo "<li><strong>Contact hosting provider</strong> if symlinks are not supported</li>";
echo "</ol>";
echo "</div>";

if (isset($_SERVER['HTTP_HOST'])) {
    echo "<div style='margin-top: 30px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;'>";
    echo "<strong>⚠️ Security Notice:</strong> Delete this diagnostic file after use!";
    echo "</div>";
    echo "</body></html>";
}
?>