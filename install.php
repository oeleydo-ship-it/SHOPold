<?php
/**
 * Laravel Shop Auto-Installer for Control Panels
 * 
 * This script provides a simple interface for control panels (cPanel, Plesk, etc.)
 * to automatically install Laravel Shop with database injection and requirements checking.
 * 
 * Usage:
 * - Place this file in your web root
 * - Access via: https://yourdomain.com/install.php
 * - Or integrate with your control panel's auto-installer
 */

// Configuration
$config = [
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

// Note: Laravel bootstrap is done only when needed to avoid errors

/**
 * Check server requirements
 */
function checkRequirements($config) {
    $results = [
        'passed' => true,
        'php_version' => [
            'required' => $config['min_php_version'],
            'current' => PHP_VERSION,
            'passed' => version_compare(PHP_VERSION, $config['min_php_version'], '>=')
        ],
        'extensions' => [],
        'permissions' => []
    ];

    // Check PHP extensions
    foreach ($config['required_extensions'] as $ext) {
        $loaded = extension_loaded($ext);
        $results['extensions'][$ext] = [
            'loaded' => $loaded,
            'passed' => $loaded
        ];
        if (!$loaded) $results['passed'] = false;
    }

    // Check directory permissions
    foreach ($config['required_directories'] as $dir) {
        $path = __DIR__ . '/' . $dir;
        $writable = is_dir($path) && is_writable($path);
        $results['permissions'][$dir] = [
            'path' => $path,
            'writable' => $writable,
            'passed' => $writable
        ];
        if (!$writable) $results['passed'] = false;
    }

    // Check .env file
    $envWritable = is_writable(__DIR__ . '/.env') || is_writable(__DIR__);
    $results['permissions']['.env'] = [
        'path' => __DIR__ . '/.env',
        'writable' => $envWritable,
        'passed' => $envWritable
    ];
    if (!$envWritable) $results['passed'] = false;

    return $results;
}

/**
 * Test database connection
 */
function testDatabase($host, $port, $database, $username, $password) {
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$database}";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Test table creation permissions
        $pdo->exec("CREATE TABLE IF NOT EXISTS installer_test (id INT PRIMARY KEY)");
        $pdo->exec("DROP TABLE installer_test");
        
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Handle different request methods
 */
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'form';

// Set JSON header for API responses
if (in_array($action, ['check', 'install', 'status'])) {
    header('Content-Type: application/json');
}

switch ($action) {
    case 'debug':
        // Debug endpoint to check basic system info
        $debug = [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
            'current_dir' => __DIR__,
            'vendor_exists' => file_exists(__DIR__ . '/vendor/autoload.php'),
            'bootstrap_exists' => file_exists(__DIR__ . '/bootstrap/app.php'),
            'env_exists' => file_exists(__DIR__ . '/.env'),
            'storage_exists' => is_dir(__DIR__ . '/storage'),
            'storage_writable' => is_writable(__DIR__ . '/storage'),
            'cache_writable' => is_writable(__DIR__ . '/bootstrap/cache'),
            'installed_marker' => file_exists(__DIR__ . '/storage/installed'),
        ];
        echo json_encode($debug, JSON_PRETTY_PRINT);
        break;

    case 'check':
        // Check requirements endpoint
        try {
            echo json_encode(checkRequirements($config));
        } catch (Exception $e) {
            echo json_encode(['error' => 'Requirements check failed: ' . $e->getMessage()]);
        }
        break;

    case 'test-db':
        // Test database connection endpoint
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
            break;
        }
        
        $required = ['host', 'port', 'database', 'username'];
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                echo json_encode(['success' => false, 'error' => "Missing field: {$field}"]);
                exit;
            }
        }
        
        echo json_encode(testDatabase(
            $input['host'],
            $input['port'],
            $input['database'],
            $input['username'],
            $input['password'] ?? ''
        ));
        break;

    case 'install':
        // Auto-install endpoint
        if ($method !== 'POST') {
            echo json_encode(['error' => 'POST method required']);
            break;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            echo json_encode(['error' => 'Invalid JSON input']);
            break;
        }

        // Validate required fields
        $required = [
            'db_host', 'db_port', 'db_database', 'db_username',
            'admin_name', 'admin_email', 'admin_password',
            'store_name', 'store_currency'
        ];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                echo json_encode(['error' => "Missing required field: {$field}"]);
                exit;
            }
        }

        // Run installation directly
        try {
            if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
                echo json_encode(['error' => 'Laravel dependencies not found. Please run: composer install --optimize-autoloader --no-dev']);
                break;
            }

            // Test database connection first
            $dbTest = testDatabase(
                $input['db_host'],
                $input['db_port'],
                $input['db_database'],
                $input['db_username'],
                $input['db_password'] ?? ''
            );
            
            if (!$dbTest['success']) {
                echo json_encode(['error' => 'Database connection failed: ' . $dbTest['error']]);
                break;
            }

            // Bootstrap Laravel
            require_once __DIR__ . '/vendor/autoload.php';
            $app = require_once __DIR__ . '/bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();

            // Update .env file
            updateEnvFile([
                'APP_NAME' => '"' . $input['store_name'] . '"',
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_URL' => $input['app_url'] ?? ((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']),
                'DB_HOST' => $input['db_host'],
                'DB_PORT' => $input['db_port'],
                'DB_DATABASE' => $input['db_database'],
                'DB_USERNAME' => $input['db_username'],
                'DB_PASSWORD' => $input['db_password'] ?? '',
            ]);

            // Clear config cache
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            
            // Generate app key if not set
            if (empty(config('app.key'))) {
                \Illuminate\Support\Facades\Artisan::call('key:generate', ['--force' => true]);
            }

            // Run migrations
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

            // Run seeders
            \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);

            // Create admin user
            $admin = \App\Models\User::updateOrCreate(
                ['email' => $input['admin_email']],
                [
                    'name' => $input['admin_name'],
                    'password' => \Illuminate\Support\Facades\Hash::make($input['admin_password']),
                    'is_admin' => true,
                    'admin_role' => 'super_admin',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Create initial settings
            createInitialSettings($input);

            // Create storage link with fallback options
            if (!file_exists(__DIR__ . '/public/storage')) {
                try {
                    \Illuminate\Support\Facades\Artisan::call('storage:link');
                } catch (Exception $e) {
                    // Fallback: Create symlink manually
                    $target = __DIR__ . '/storage/app/public';
                    $link = __DIR__ . '/public/storage';
                    
                    if (!file_exists($target)) {
                        mkdir($target, 0755, true);
                    }
                    
                    if (function_exists('symlink')) {
                        @symlink($target, $link);
                    } else {
                        // Create directory structure as fallback
                        mkdir($link, 0755, true);
                        $subdirs = ['products', 'categories', 'brands', 'users', 'media'];
                        foreach ($subdirs as $subdir) {
                            mkdir($link . '/' . $subdir, 0755, true);
                        }
                    }
                }
            }

            // Create necessary directories
            createDirectories();

            // Mark as installed
            file_put_contents(__DIR__ . '/storage/installed', json_encode([
                'installed_at' => date('Y-m-d H:i:s'),
                'version' => '1.0.0',
                'admin_email' => $input['admin_email'],
                'installer' => 'auto-install'
            ], JSON_PRETTY_PRINT));

            // Clear all caches
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');

            echo json_encode([
                'success' => true,
                'message' => 'Installation completed successfully',
                'admin_url' => ((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/admin'),
                'login_credentials' => [
                    'email' => $input['admin_email'],
                    'note' => 'Use the password you provided'
                ]
            ]);

        } catch (Exception $e) {
            echo json_encode(['error' => 'Installation failed: ' . $e->getMessage()]);
        }
        break;

    case 'status':
        // Installation status endpoint
        try {
            // Check for force parameter
            $forceInstall = isset($_GET['force']) && $_GET['force'] === '1';
            
            // Simple status check without Laravel bootstrap
            $installed = file_exists(__DIR__ . '/storage/installed') && !$forceInstall;
            $vendorExists = file_exists(__DIR__ . '/vendor/autoload.php');
            $envExists = file_exists(__DIR__ . '/.env');
            
            $status = [
                'installed' => $installed,
                'force_mode' => $forceInstall,
                'php_version' => PHP_VERSION,
                'vendor_exists' => $vendorExists,
                'env_exists' => $envExists,
            ];

            if ($installed) {
                $installFile = __DIR__ . '/storage/installed';
                if (is_readable($installFile)) {
                    $installData = json_decode(file_get_contents($installFile), true);
                    $status['installed_at'] = $installData['installed_at'] ?? null;
                    $status['version'] = $installData['version'] ?? null;
                }
            }

            // Try to get Laravel version if possible
            if ($vendorExists) {
                try {
                    require_once __DIR__ . '/vendor/autoload.php';
                    if (class_exists('Illuminate\Foundation\Application')) {
                        $status['laravel_available'] = true;
                    }
                } catch (Exception $e) {
                    $status['laravel_error'] = $e->getMessage();
                }
            }

            echo json_encode($status);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Status check failed: ' . $e->getMessage()]);
        }
        break;

    default:
        // Show installation form
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['app_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-blue-600 text-white p-6">
                <h1 class="text-2xl font-bold flex items-center">
                    <i class="fas fa-rocket mr-3"></i>
                    <?= htmlspecialchars($config['app_name']) ?>
                </h1>
                <p class="mt-2 opacity-90">Automated installation for control panels and web hosting</p>
            </div>

            <!-- Status Display -->
            <div id="status-section" class="p-6 border-b">
                <h2 class="text-lg font-semibold mb-4">Installation Status</h2>
                <div id="status-content" class="text-gray-600">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Checking status...
                </div>
            </div>

            <!-- Requirements Check -->
            <div id="requirements-section" class="p-6 border-b">
                <h2 class="text-lg font-semibold mb-4">Server Requirements</h2>
                <div id="requirements-content" class="text-gray-600">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Checking requirements...
                </div>
            </div>

            <!-- Installation Form -->
            <div id="install-form-section" class="p-6" style="display: none;">
                <h2 class="text-lg font-semibold mb-4">Installation Configuration</h2>
                
                <form id="install-form" class="space-y-6">
                    <!-- Database Configuration -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-medium mb-3">Database Configuration</h3>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Database Host</label>
                                <input type="text" name="db_host" value="localhost" required
                                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Database Port</label>
                                <input type="number" name="db_port" value="3306" required
                                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Database Name</label>
                                <input type="text" name="db_database" required
                                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Database Username</label>
                                <input type="text" name="db_username" required
                                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1">Database Password</label>
                                <input type="password" name="db_password"
                                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        <button type="button" id="test-db-btn" class="mt-3 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                            <i class="fas fa-database mr-2"></i> Test Database Connection
                        </button>
                        <div id="db-test-result" class="mt-2"></div>
                    </div>

                    <!-- Admin Account -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-medium mb-3">Admin Account</h3>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Admin Name</label>
                                <input type="text" name="admin_name" required
                                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Admin Email</label>
                                <input type="email" name="admin_email" required
                                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1">Admin Password</label>
                                <input type="password" name="admin_password" required minlength="8"
                                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Store Configuration -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-medium mb-3">Store Configuration</h3>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Store Name</label>
                                <input type="text" name="store_name" required
                                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Store Email</label>
                                <input type="email" name="store_email"
                                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Currency</label>
                                <select name="store_currency" required
                                        class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                                    <option value="USD">USD - US Dollar</option>
                                    <option value="EUR">EUR - Euro</option>
                                    <option value="GBP">GBP - British Pound</option>
                                    <option value="INR">INR - Indian Rupee</option>
                                    <option value="CAD">CAD - Canadian Dollar</option>
                                    <option value="AUD">AUD - Australian Dollar</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">App URL (optional)</label>
                                <input type="url" name="app_url" placeholder="https://yourdomain.com"
                                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <button type="submit" id="install-btn" class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                        <i class="fas fa-play mr-2"></i> Start Installation
                    </button>
                </form>
            </div>

            <!-- Installation Progress -->
            <div id="progress-section" class="p-6" style="display: none;">
                <h2 class="text-lg font-semibold mb-4">Installation Progress</h2>
                <div id="progress-content"></div>
            </div>
        </div>
    </div>

    <script>
        // Check installation status and requirements on page load
        async function checkStatus() {
            try {
                let response = await fetch('?action=status');
                
                // If main status fails, try fallback
                if (!response.ok) {
                    console.warn('Main status endpoint failed, trying fallback...');
                    response = await fetch('simple-status.php');
                }
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const text = await response.text();
                let status;
                
                try {
                    status = JSON.parse(text);
                } catch (parseError) {
                    throw new Error(`Invalid JSON response: ${text.substring(0, 100)}...`);
                }
                
                const statusContent = document.getElementById('status-content');
                if (status.error) {
                    statusContent.innerHTML = `
                        <div class="text-red-600">
                            <i class="fas fa-times-circle mr-2"></i>
                            Error: ${status.error}
                        </div>
                        <div class="mt-2">
                            <a href="debug-install.php" class="text-blue-600 underline text-sm">
                                <i class="fas fa-bug mr-1"></i> View Debug Info
                            </a>
                        </div>
                    `;
                    // Still show install form for manual installation
                    document.getElementById('install-form-section').style.display = 'block';
                    return;
                }
                
                if (status.installed && !status.force_mode) {
                    statusContent.innerHTML = `
                        <div class="flex items-center text-green-600">
                            <i class="fas fa-check-circle mr-2"></i>
                            Laravel Shop is already installed
                        </div>
                        <p class="text-sm text-gray-600 mt-1">Installed: ${status.installed_at || status.install_data?.installed_at || 'Unknown'}</p>
                        <div class="mt-3">
                            <a href="?force=1" class="text-orange-600 underline text-sm">
                                <i class="fas fa-redo mr-1"></i> Force Reinstallation
                            </a> | 
                            <a href="reset-installation.php?show_form=1" class="text-red-600 underline text-sm">
                                <i class="fas fa-trash mr-1"></i> Reset Installation
                            </a>
                        </div>
                    `;
                } else {
                    const statusMessage = status.force_mode ? 
                        'Force installation mode - Reinstalling Laravel Shop' : 
                        'Not installed - Ready for installation';
                    const statusIcon = status.force_mode ? 'fas fa-redo' : 'fas fa-exclamation-triangle';
                    const statusColor = status.force_mode ? 'text-blue-600' : 'text-orange-600';
                    
                    statusContent.innerHTML = `
                        <div class="flex items-center ${statusColor}">
                            <i class="${statusIcon} mr-2"></i>
                            ${statusMessage}
                        </div>
                        <p class="text-sm text-gray-600 mt-1">PHP ${status.php_version} | Requirements: ${status.requirements_met ? '✓ Met' : '✗ Issues found'}</p>
                        ${status.force_mode ? '<p class="text-sm text-blue-600 mt-1"><i class="fas fa-info-circle mr-1"></i> This will overwrite existing installation</p>' : ''}
                    `;
                    document.getElementById('install-form-section').style.display = 'block';
                }
            } catch (error) {
                console.error('Status check error:', error);
                document.getElementById('status-content').innerHTML = `
                    <div class="text-red-600">
                        <i class="fas fa-times-circle mr-2"></i>
                        Error checking status: ${error.message}
                    </div>
                    <div class="mt-2">
                        <a href="debug-install.php" class="text-blue-600 underline text-sm">
                            <i class="fas fa-bug mr-1"></i> View Debug Info
                        </a> | 
                        <a href="simple-status.php" class="text-blue-600 underline text-sm">
                            <i class="fas fa-info-circle mr-1"></i> Simple Status
                        </a>
                    </div>
                `;
                // Show install form anyway if status check fails
                document.getElementById('install-form-section').style.display = 'block';
            }
        }

        async function checkRequirements() {
            try {
                const response = await fetch('?action=check');
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const text = await response.text();
                let requirements;
                
                try {
                    requirements = JSON.parse(text);
                } catch (parseError) {
                    throw new Error(`Invalid JSON response: ${text.substring(0, 100)}...`);
                }
                
                if (requirements.error) {
                    throw new Error(requirements.error);
                }
                
                let html = '';
                
                // PHP Version
                const phpPassed = requirements.php_version.passed;
                html += `
                    <div class="flex items-center justify-between p-2 rounded ${phpPassed ? 'bg-green-50' : 'bg-red-50'}">
                        <span>PHP ${requirements.php_version.required}+</span>
                        <span class="${phpPassed ? 'text-green-600' : 'text-red-600'}">
                            <i class="fas fa-${phpPassed ? 'check' : 'times'}-circle mr-1"></i>
                            ${requirements.php_version.current}
                        </span>
                    </div>
                `;

                // Extensions
                for (const [ext, info] of Object.entries(requirements.extensions)) {
                    html += `
                        <div class="flex items-center justify-between p-2 rounded ${info.passed ? 'bg-green-50' : 'bg-red-50'}">
                            <span>${ext} extension</span>
                            <span class="${info.passed ? 'text-green-600' : 'text-red-600'}">
                                <i class="fas fa-${info.passed ? 'check' : 'times'}-circle"></i>
                            </span>
                        </div>
                    `;
                }

                // Permissions
                for (const [dir, info] of Object.entries(requirements.permissions)) {
                    html += `
                        <div class="flex items-center justify-between p-2 rounded ${info.passed ? 'bg-green-50' : 'bg-red-50'}">
                            <span>${dir} writable</span>
                            <span class="${info.passed ? 'text-green-600' : 'text-red-600'}">
                                <i class="fas fa-${info.passed ? 'check' : 'times'}-circle"></i>
                            </span>
                        </div>
                    `;
                }

                if (!requirements.passed) {
                    html += `
                        <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded">
                            <p class="text-red-700 text-sm">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Please fix the issues above before proceeding with installation.
                            </p>
                        </div>
                    `;
                }

                document.getElementById('requirements-content').innerHTML = html;
                
                // Hide install form if requirements not met
                if (!requirements.passed) {
                    document.getElementById('install-form-section').style.display = 'none';
                }
                
            } catch (error) {
                console.error('Requirements check error:', error);
                document.getElementById('requirements-content').innerHTML = `
                    <div class="text-red-600">
                        <i class="fas fa-times-circle mr-2"></i>
                        Error checking requirements: ${error.message}
                    </div>
                `;
            }
        }

        // Test database connection
        document.getElementById('test-db-btn').addEventListener('click', async function() {
            const form = document.getElementById('install-form');
            const formData = new FormData(form);
            const data = {
                host: formData.get('db_host'),
                port: formData.get('db_port'),
                database: formData.get('db_database'),
                username: formData.get('db_username'),
                password: formData.get('db_password')
            };

            const resultDiv = document.getElementById('db-test-result');
            resultDiv.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Testing connection...';

            try {
                const response = await fetch('?action=test-db', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="text-green-600">
                            <i class="fas fa-check-circle mr-2"></i>
                            Database connection successful!
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="text-red-600">
                            <i class="fas fa-times-circle mr-2"></i>
                            Connection failed: ${result.error}
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="text-red-600">
                        <i class="fas fa-times-circle mr-2"></i>
                        Error: ${error.message}
                    </div>
                `;
            }
        });

        // Handle installation form submission
        document.getElementById('install-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            // Show progress section
            document.getElementById('install-form-section').style.display = 'none';
            document.getElementById('progress-section').style.display = 'block';
            
            const progressContent = document.getElementById('progress-content');
            progressContent.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-3xl text-blue-600 mb-4"></i>
                    <p class="text-lg font-medium">Installing Laravel Shop...</p>
                    <p class="text-gray-600">This may take a few minutes. Please do not close this page.</p>
                </div>
            `;

            try {
                const response = await fetch('?action=install', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    progressContent.innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-check-circle text-4xl text-green-600 mb-4"></i>
                            <h3 class="text-xl font-bold text-green-800 mb-2">Installation Completed Successfully!</h3>
                            <p class="text-gray-600 mb-4">${result.message}</p>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                <h4 class="font-medium text-blue-800 mb-2">Admin Access</h4>
                                <p class="text-sm text-blue-700">
                                    <strong>URL:</strong> <a href="${result.admin_url}" class="underline" target="_blank">${result.admin_url}</a><br>
                                    <strong>Email:</strong> ${result.login_credentials.email}<br>
                                    <strong>Password:</strong> ${result.login_credentials.note}
                                </p>
                            </div>
                            <a href="${result.admin_url}" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700" target="_blank">
                                <i class="fas fa-external-link-alt mr-2"></i>
                                Access Admin Panel
                            </a>
                        </div>
                    `;
                } else {
                    progressContent.innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-times-circle text-4xl text-red-600 mb-4"></i>
                            <h3 class="text-xl font-bold text-red-800 mb-2">Installation Failed</h3>
                            <p class="text-red-600 mb-4">${result.error}</p>
                            <p class="text-gray-600 text-sm">${result.message || ''}</p>
                            <button onclick="location.reload()" class="mt-4 px-6 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                                <i class="fas fa-redo mr-2"></i>
                                Try Again
                            </button>
                        </div>
                    `;
                }
            } catch (error) {
                progressContent.innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-times-circle text-4xl text-red-600 mb-4"></i>
                        <h3 class="text-xl font-bold text-red-800 mb-2">Installation Error</h3>
                        <p class="text-red-600 mb-4">An unexpected error occurred: ${error.message}</p>
                        <button onclick="location.reload()" class="mt-4 px-6 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                            <i class="fas fa-redo mr-2"></i>
                            Try Again
                        </button>
                    </div>
                `;
            }
        });

        // Initialize checks
        checkStatus();
        checkRequirements();
    </script>
</body>
</html>
        <?php
        break;
}

/**
 * Helper function to update .env file
 */
function updateEnvFile(array $data) {
    $envPath = __DIR__ . '/.env';
    
    if (!file_exists($envPath)) {
        if (file_exists(__DIR__ . '/.env.example')) {
            copy(__DIR__ . '/.env.example', $envPath);
        } else {
            file_put_contents($envPath, '');
        }
    }
    
    $envContent = file_get_contents($envPath);

    foreach ($data as $key => $value) {
        $pattern = "/^{$key}=.*/m";
        $replacement = "{$key}={$value}";

        if (preg_match($pattern, $envContent)) {
            $envContent = preg_replace($pattern, $replacement, $envContent);
        } else {
            $envContent .= "\n{$replacement}";
        }
    }

    file_put_contents($envPath, $envContent);
}

/**
 * Helper function to create initial settings
 */
function createInitialSettings($input) {
    $settings = [
        ['key' => 'store.name', 'value' => $input['store_name']],
        ['key' => 'store.email', 'value' => $input['store_email'] ?? $input['admin_email']],
        ['key' => 'store.currency', 'value' => $input['store_currency']],
        ['key' => 'store.currency_symbol', 'value' => getCurrencySymbol($input['store_currency'])],
        ['key' => 'store.tax_rate', 'value' => '10'],
        ['key' => 'features.multi_vendor', 'value' => '0'],
        ['key' => 'features.driver_portal', 'value' => '1'],
        ['key' => 'features.reviews', 'value' => '1'],
        ['key' => 'features.wishlist', 'value' => '1'],
        ['key' => 'features.coupons', 'value' => '1'],
    ];

    foreach ($settings as $setting) {
        \App\Models\Setting::updateOrCreate(
            ['key' => $setting['key']],
            ['value' => $setting['value']]
        );
    }
}

/**
 * Helper function to get currency symbol
 */
function getCurrencySymbol($currency) {
    $symbols = [
        'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥',
        'AUD' => 'A$', 'CAD' => 'C$', 'CHF' => 'CHF', 'CNY' => '¥',
        'INR' => '₹', 'BRL' => 'R$', 'MXN' => '$', 'SGD' => 'S$',
        'HKD' => 'HK$', 'NOK' => 'kr', 'SEK' => 'kr', 'DKK' => 'kr',
        'NZD' => 'NZ$', 'ZAR' => 'R', 'RUB' => '₽', 'KRW' => '₩',
        'PHP' => '₱', 'THB' => '฿', 'MYR' => 'RM', 'IDR' => 'Rp',
        'AED' => 'د.إ', 'SAR' => '﷼', 'EGP' => 'E£', 'NGN' => '₦',
    ];

    return $symbols[$currency] ?? $currency;
}

/**
 * Helper function to create necessary directories
 */
function createDirectories() {
    $directories = [
        __DIR__ . '/storage/app/public/products',
        __DIR__ . '/storage/app/public/categories',
        __DIR__ . '/storage/app/public/brands',
        __DIR__ . '/storage/app/public/users',
        __DIR__ . '/storage/app/public/media',
        __DIR__ . '/storage/logs',
        __DIR__ . '/public/uploads',
    ];

    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
?>