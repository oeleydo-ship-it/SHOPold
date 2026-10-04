<?php
/**
 * Laravel Storage Symlink Diagnostic Tool
 * 
 * This tool provides comprehensive diagnostics for symlink issues
 * and helps identify the root cause of image display problems.
 */

// Configuration
$storagePath = __DIR__ . '/storage/app/public';
$publicPath = __DIR__ . '/public/storage';
$isWebRequest = isset($_SERVER['HTTP_HOST']);

if ($isWebRequest) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Laravel Symlink Diagnostic Tool</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
        .diagnostic { margin: 20px 0; padding: 15px; border-radius: 8px; }
        .pass { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .fail { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warn { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 5px; color: white; }
        .btn-primary { background: #007bff; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; }
    </style>
</head>
<body>
    <h1>🔍 Laravel Storage Symlink Diagnostic Tool</h1>';
}

function diagnostic($title, $status, $message, $details = '') {
    global $isWebRequest;
    
    $statusClass = $status ? 'pass' : 'fail';
    $statusIcon = $status ? '✅' : '❌';
    
    if ($isWebRequest) {
        echo "<div class='diagnostic $statusClass'>";
        echo "<h3>$statusIcon $title</h3>";
        echo "<p>$message</p>";
        if ($details) echo "<div class='code'>$details</div>";
        echo "</div>";
    } else {
        echo "[$statusClass] $title: $message\n";
        if ($details) echo "  Details: $details\n";
    }
}

function info($title, $message, $details = '') {
    global $isWebRequest;
    
    if ($isWebRequest) {
        echo "<div class='diagnostic info'>";
        echo "<h3>ℹ️ $title</h3>";
        echo "<p>$message</p>";
        if ($details) echo "<div class='code'>$details</div>";
        echo "</div>";
    } else {
        echo "[INFO] $title: $message\n";
        if ($details) echo "  Details: $details\n";
    }
}

// Start diagnostics
if ($isWebRequest) {
    echo "<p>This tool will help diagnose symlink issues that prevent images from displaying correctly.</p>";
}

// 1. Check PHP Functions
$symlinkExists = function_exists('symlink');
$readlinkExists = function_exists('readlink');
$isLinkExists = function_exists('is_link');

diagnostic(
    'PHP Symlink Functions',
    $symlinkExists && $readlinkExists && $isLinkExists,
    $symlinkExists ? 'All required PHP functions are available' : 'Some PHP functions are missing',
    "symlink(): " . ($symlinkExists ? 'Available' : 'Missing') . "\n" .
    "readlink(): " . ($readlinkExists ? 'Available' : 'Missing') . "\n" .
    "is_link(): " . ($isLinkExists ? 'Available' : 'Missing')
);

// 2. Check Paths
$storageExists = file_exists($storagePath);
$storageWritable = is_writable($storagePath);
$publicDirExists = file_exists(dirname($publicPath));
$publicDirWritable = is_writable(dirname($publicPath));

diagnostic(
    'Storage Directory',
    $storageExists && $storageWritable,
    $storageExists ? 'Storage directory exists' : 'Storage directory missing',
    "Path: $storagePath\n" .
    "Exists: " . ($storageExists ? 'Yes' : 'No') . "\n" .
    "Writable: " . ($storageWritable ? 'Yes' : 'No')
);

diagnostic(
    'Public Directory',
    $publicDirExists && $publicDirWritable,
    $publicDirExists ? 'Public directory accessible' : 'Public directory issues',
    "Path: " . dirname($publicPath) . "\n" .
    "Exists: " . ($publicDirExists ? 'Yes' : 'No') . "\n" .
    "Writable: " . ($publicDirWritable ? 'Yes' : 'No')
);

// 3. Check Current Symlink Status
$symlinkExists = file_exists($publicPath);
$isActualLink = is_link($publicPath);
$linkTarget = $isActualLink ? readlink($publicPath) : null;
$linkValid = $isActualLink && file_exists($linkTarget);

if ($symlinkExists) {
    if ($isActualLink) {
        diagnostic(
            'Symlink Status',
            $linkValid,
            $linkValid ? 'Symlink exists and is valid' : 'Symlink exists but is broken',
            "Link: $publicPath\n" .
            "Target: $linkTarget\n" .
            "Target Exists: " . (file_exists($linkTarget) ? 'Yes' : 'No')
        );
    } else {
        diagnostic(
            'Symlink Status',
            false,
            'File/directory exists but is not a symlink',
            "Path: $publicPath\n" .
            "Type: " . (is_dir($publicPath) ? 'Directory' : 'File')
        );
    }
} else {
    diagnostic(
        'Symlink Status',
        false,
        'Symlink does not exist',
        "Expected location: $publicPath"
    );
}

// 4. Test Symlink Creation
if ($symlinkExists) {
    info('Symlink Test', 'Skipping creation test (symlink already exists)');
} else {
    $testTarget = sys_get_temp_dir() . '/laravel_test_' . uniqid();
    $testLink = sys_get_temp_dir() . '/laravel_link_' . uniqid();
    
    $testResult = false;
    if (mkdir($testTarget)) {
        $testResult = @symlink($testTarget, $testLink);
        
        // Clean up
        if ($testResult && is_link($testLink)) {
            unlink($testLink);
        }
        rmdir($testTarget);
    }
    
    diagnostic(
        'Symlink Creation Test',
        $testResult,
        $testResult ? 'System supports symlink creation' : 'System does not support symlinks',
        $testResult ? 'Test symlink created and removed successfully' : 'Failed to create test symlink'
    );
}

// 5. Check File Permissions
$storagePerms = $storageExists ? substr(sprintf('%o', fileperms($storagePath)), -4) : 'N/A';
$publicPerms = $publicDirExists ? substr(sprintf('%o', fileperms(dirname($publicPath))), -4) : 'N/A';

info(
    'File Permissions',
    'Current permission settings',
    "Storage directory: $storagePerms (recommended: 0755)\n" .
    "Public directory: $publicPerms (recommended: 0755)"
);

// 6. Check Laravel Environment
$envExists = file_exists(__DIR__ . '/.env');
$vendorExists = file_exists(__DIR__ . '/vendor/autoload.php');
$artisanExists = file_exists(__DIR__ . '/artisan');

diagnostic(
    'Laravel Environment',
    $envExists && $vendorExists && $artisanExists,
    'Laravel installation status',
    ".env file: " . ($envExists ? 'Exists' : 'Missing') . "\n" .
    "Vendor directory: " . ($vendorExists ? 'Exists' : 'Missing') . "\n" .
    "Artisan command: " . ($artisanExists ? 'Exists' : 'Missing')
);

// 7. Web Server Information
if ($isWebRequest) {
    $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown';
    $scriptPath = $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown';
    
    info(
        'Web Server Information',
        'Server environment details',
        "Server: $serverSoftware\n" .
        "Document Root: $documentRoot\n" .
        "Script Path: $scriptPath"
    );
}

// 8. Recommendations
if ($isWebRequest) {
    echo "<div class='diagnostic info'>";
    echo "<h3>🔧 Recommended Actions</h3>";
    
    if (!$symlinkExists || !$linkValid) {
        echo "<p><strong>Primary Issue:</strong> Symlink is missing or broken</p>";
        echo "<div class='code'>";
        echo "<strong>Solutions (try in order):</strong><br>";
        echo "1. <a href='create_symlink.php' class='btn btn-primary'>Use Enhanced Symlink Creator</a><br>";
        echo "2. <a href='fix-symlink.php' class='btn btn-success'>Use Automatic Symlink Fixer</a><br>";
        echo "3. Run Laravel Artisan: <code>php artisan storage:link</code><br>";
        echo "4. Manual symlink: <code>ln -s " . realpath($storagePath) . " $publicPath</code><br>";
        echo "</div>";
    }
    
    if (!$storageExists) {
        echo "<p><strong>Storage Issue:</strong> Storage directory missing</p>";
        echo "<div class='code'>";
        echo "Create storage directory: <code>mkdir -p $storagePath</code><br>";
        echo "Set permissions: <code>chmod 755 $storagePath</code>";
        echo "</div>";
    }
    
    if (!$symlinkExists && !function_exists('symlink')) {
        echo "<p><strong>Symlink Not Supported:</strong> Your hosting doesn't support symlinks</p>";
        echo "<div class='code'>";
        echo "Alternative solutions:<br>";
        echo "1. Contact hosting provider about symlink support<br>";
        echo "2. Use cloud storage (S3, etc.) for uploads<br>";
        echo "3. Configure web server to serve files directly from storage";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Test Links
    echo "<div class='diagnostic info'>";
    echo "<h3>🧪 Test Tools</h3>";
    echo "<p>Use these tools to test and fix symlink issues:</p>";
    echo "<a href='create_symlink.php' class='btn btn-primary'>Enhanced Symlink Creator</a>";
    echo "<a href='fix-symlink.php' class='btn btn-success'>Automatic Symlink Fixer</a>";
    echo "<a href='?refresh=1' class='btn btn-warning'>Refresh Diagnostics</a>";
    echo "</div>";
    
    echo "<div class='diagnostic warn'>";
    echo "<h3>⚠️ Security Notice</h3>";
    echo "<p><strong>Important:</strong> Delete this diagnostic file after use for security!</p>";
    echo "<p>Files to delete: <code>symlink-diagnostic.php</code>, <code>create_symlink.php</code>, <code>fix-symlink.php</code></p>";
    echo "</div>";
    
    echo "</body></html>";
}
?>