<?php
/**
 * Laravel Shop Installation Detection Script
 * 
 * This script automatically detects if Laravel Shop is installed
 * and redirects users to the appropriate page.
 * 
 * Place this file in your web root for automatic detection.
 */

// Configuration
$config = [
    'app_name' => 'Laravel Shop',
    'install_url' => '/install.php',
    'debug_url' => '/debug-install.php',
    'main_app_url' => '/',
];

/**
 * Check if Laravel Shop is installed
 */
function isLaravelShopInstalled() {
    // Check for installation marker
    $markers = [
        __DIR__ . '/storage/installed',
        __DIR__ . '/../storage/installed',
    ];
    
    foreach ($markers as $marker) {
        if (file_exists($marker)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if Laravel files exist
 */
function hasLaravelFiles() {
    $requiredFiles = [
        __DIR__ . '/vendor/autoload.php',
        __DIR__ . '/bootstrap/app.php',
        __DIR__ . '/.env',
    ];
    
    // Also check parent directory (in case we're in public/)
    $parentFiles = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../bootstrap/app.php',
        __DIR__ . '/../.env',
    ];
    
    $hasFiles = true;
    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            $hasFiles = false;
            break;
        }
    }
    
    if (!$hasFiles) {
        $hasFiles = true;
        foreach ($parentFiles as $file) {
            if (!file_exists($file)) {
                $hasFiles = false;
                break;
            }
        }
    }
    
    return $hasFiles;
}

/**
 * Get installation status
 */
function getInstallationStatus() {
    return [
        'installed' => isLaravelShopInstalled(),
        'has_laravel_files' => hasLaravelFiles(),
        'has_installer' => file_exists(__DIR__ . '/install.php') || file_exists(__DIR__ . '/../install.php'),
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
    ];
}

// Handle different actions
$action = $_GET['action'] ?? 'detect';

switch ($action) {
    case 'json':
        header('Content-Type: application/json');
        echo json_encode(getInstallationStatus(), JSON_PRETTY_PRINT);
        exit;
        
    case 'detect':
    default:
        $status = getInstallationStatus();
        
        // If installed, redirect to main app
        if ($status['installed']) {
            header('Location: ' . $config['main_app_url']);
            exit;
        }
        
        // If not installed but has Laravel files, redirect to installer
        if ($status['has_laravel_files'] && $status['has_installer']) {
            header('Location: ' . $config['install_url']);
            exit;
        }
        
        // Show setup instructions
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Setup Required - <?= htmlspecialchars($config['app_name']) ?></title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
                .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
                .header { background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 40px; text-align: center; }
                .header h1 { margin: 0; font-size: 2.5em; font-weight: 300; }
                .header p { margin: 10px 0 0; opacity: 0.9; font-size: 1.1em; }
                .content { padding: 40px; }
                .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }
                .status-item { padding: 20px; border-radius: 8px; text-align: center; }
                .status-ok { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
                .status-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
                .status-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
                .icon { font-size: 2em; margin-bottom: 10px; }
                .actions { text-align: center; margin: 30px 0; }
                .btn { display: inline-block; padding: 15px 30px; margin: 0 10px; text-decoration: none; border-radius: 6px; font-weight: 500; transition: all 0.3s; }
                .btn-primary { background: #3498db; color: white; }
                .btn-primary:hover { background: #2980b9; transform: translateY(-2px); }
                .btn-secondary { background: #95a5a6; color: white; }
                .btn-secondary:hover { background: #7f8c8d; transform: translateY(-2px); }
                .instructions { background: #f8f9fa; padding: 30px; border-radius: 8px; margin: 30px 0; }
                .instructions h3 { margin-top: 0; color: #2c3e50; }
                .instructions ol { padding-left: 20px; }
                .instructions li { margin: 10px 0; line-height: 1.6; }
                .code { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; font-family: 'Courier New', monospace; margin: 10px 0; overflow-x: auto; }
                .footer { text-align: center; padding: 20px; background: #f8f9fa; color: #6c757d; font-size: 0.9em; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?= htmlspecialchars($config['app_name']) ?></h1>
                    <p>Setup & Installation Detection</p>
                </div>
                
                <div class="content">
                    <h2>System Status</h2>
                    
                    <div class="status-grid">
                        <div class="status-item <?= $status['installed'] ? 'status-ok' : 'status-error' ?>">
                            <div class="icon"><?= $status['installed'] ? '✅' : '❌' ?></div>
                            <strong>Installation</strong><br>
                            <?= $status['installed'] ? 'Completed' : 'Required' ?>
                        </div>
                        
                        <div class="status-item <?= $status['has_laravel_files'] ? 'status-ok' : 'status-error' ?>">
                            <div class="icon"><?= $status['has_laravel_files'] ? '✅' : '❌' ?></div>
                            <strong>Laravel Files</strong><br>
                            <?= $status['has_laravel_files'] ? 'Present' : 'Missing' ?>
                        </div>
                        
                        <div class="status-item <?= $status['has_installer'] ? 'status-ok' : 'status-warning' ?>">
                            <div class="icon"><?= $status['has_installer'] ? '✅' : '⚠️' ?></div>
                            <strong>Installer</strong><br>
                            <?= $status['has_installer'] ? 'Available' : 'Not Found' ?>
                        </div>
                        
                        <div class="status-item status-ok">
                            <div class="icon">🐘</div>
                            <strong>PHP Version</strong><br>
                            <?= $status['php_version'] ?>
                        </div>
                    </div>
                    
                    <?php if (!$status['has_laravel_files']): ?>
                    <div class="instructions">
                        <h3>📦 Laravel Files Missing</h3>
                        <p>It looks like Laravel Shop files are not properly uploaded. Please follow these steps:</p>
                        <ol>
                            <li>Download the complete Laravel Shop package</li>
                            <li>Upload all files to your web server</li>
                            <li>Make sure the following files exist:
                                <div class="code">
vendor/autoload.php<br>
bootstrap/app.php<br>
.env (or .env.example)
                                </div>
                            </li>
                            <li>Set proper file permissions (755 for directories, 644 for files)</li>
                            <li>Run composer install if needed:
                                <div class="code">composer install --optimize-autoloader --no-dev</div>
                            </li>
                        </ol>
                    </div>
                    <?php elseif (!$status['has_installer']): ?>
                    <div class="instructions">
                        <h3>🔧 Installer Missing</h3>
                        <p>Laravel files are present but the installer is missing. You can:</p>
                        <ol>
                            <li>Upload the <code>install.php</code> file to your web root</li>
                            <li>Or run manual installation via command line:
                                <div class="code">php artisan migrate --force<br>php artisan db:seed --force</div>
                            </li>
                            <li>Or use the Laravel installation routes: <code>/install</code></li>
                        </ol>
                    </div>
                    <?php else: ?>
                    <div class="instructions">
                        <h3>🚀 Ready for Installation</h3>
                        <p>All files are in place! You can now proceed with the installation.</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="actions">
                        <?php if ($status['has_installer']): ?>
                            <a href="<?= $config['install_url'] ?>" class="btn btn-primary">Start Installation</a>
                        <?php endif; ?>
                        
                        <?php if (file_exists(__DIR__ . '/debug-install.php')): ?>
                            <a href="<?= $config['debug_url'] ?>" class="btn btn-secondary">System Debug</a>
                        <?php endif; ?>
                        
                        <a href="?action=json" class="btn btn-secondary">JSON Status</a>
                    </div>
                </div>
                
                <div class="footer">
                    <p>Laravel Shop Detection Script | <?= $status['timestamp'] ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        break;
}
?>