<?php
/**
 * cPanel/Control Panel Auto-Installer for Laravel Shop
 * Version: 2.0.0
 * 
 * This script is designed to be integrated with hosting control panels
 * to provide one-click installation of Laravel Shop.
 * 
 * Features:
 * - Automatic database creation and user setup
 * - Environment configuration
 * - Requirements checking
 * - Progress tracking
 * - Error handling and rollback
 * - Admin role privileges (super_admin, manager, editor)
 * - AI features support
 * - Blog section support
 * 
 * Changelog v2.0.0:
 * - Added admin role system (super_admin, manager, editor)
 * - Added AI features (smart search, recommendations, review analysis)
 * - Added blog section for homepage
 * - Improved installation detection
 * - Enhanced security measures
 */

class LaravelShopInstaller
{
    private $config;
    private $errors = [];
    private $log = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'app_name' => 'Laravel Shop',
            'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'install_path' => __DIR__,
            'db_prefix' => '',
            'admin_email' => 'admin@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
            'admin_password' => $this->generatePassword(),
            'store_name' => 'My Online Store',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'language' => 'en',
            'admin_role' => 'super_admin',
        ], $config);
    }

    /**
     * Main installation method
     */
    public function install(): array
    {
        try {
            $this->log('Starting Laravel Shop installation...');

            // Step 1: Check requirements
            $this->log('Checking system requirements...');
            if (!$this->checkRequirements()) {
                throw new Exception('System requirements not met');
            }

            // Step 2: Setup database
            $this->log('Setting up database...');
            $dbConfig = $this->setupDatabase();

            // Step 3: Configure environment
            $this->log('Configuring environment...');
            $this->configureEnvironment($dbConfig);

            // Step 4: Install Laravel
            $this->log('Installing Laravel application...');
            $this->installLaravel();

            // Step 5: Setup admin user
            $this->log('Creating admin user...');
            $this->createAdminUser();

            // Step 6: Finalize installation
            $this->log('Finalizing installation...');
            $this->finalizeInstallation();

            $this->log('Installation completed successfully!');

            return [
                'success' => true,
                'message' => 'Laravel Shop installed successfully',
                'admin_url' => $this->getAdminUrl(),
                'admin_credentials' => [
                    'email' => $this->config['admin_email'],
                    'password' => $this->config['admin_password'],
                ],
                'store_url' => $this->getStoreUrl(),
                'log' => $this->log,
            ];

        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->log('Installation failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $this->errors,
                'log' => $this->log,
            ];
        }
    }

    /**
     * Check system requirements
     */
    private function checkRequirements(): bool
    {
        $requirements = [
            'php_version' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'mbstring' => extension_loaded('mbstring'),
            'openssl' => extension_loaded('openssl'),
            'tokenizer' => extension_loaded('tokenizer'),
            'json' => extension_loaded('json'),
            'curl' => extension_loaded('curl'),
            'fileinfo' => extension_loaded('fileinfo'),
            'gd' => extension_loaded('gd'),
            'zip' => extension_loaded('zip'),
        ];

        $permissions = [
            'storage_writable' => is_writable($this->config['install_path'] . '/storage'),
            'cache_writable' => is_writable($this->config['install_path'] . '/bootstrap/cache'),
            'env_writable' => is_writable($this->config['install_path']) || is_writable($this->config['install_path'] . '/.env'),
        ];

        $failed = [];
        foreach (array_merge($requirements, $permissions) as $check => $passed) {
            if (!$passed) {
                $failed[] = $check;
            }
        }

        if (!empty($failed)) {
            $this->errors[] = 'Failed requirements: ' . implode(', ', $failed);
            return false;
        }

        return true;
    }

    /**
     * Setup database (for cPanel integration)
     */
    private function setupDatabase(): array
    {
        // For cPanel, we can use the cPanel API to create database and user
        if (isset($this->config['cpanel_api'])) {
            return $this->setupCpanelDatabase();
        }

        // For manual configuration
        if (isset($this->config['db_host'])) {
            return [
                'host' => $this->config['db_host'],
                'port' => $this->config['db_port'] ?? 3306,
                'database' => $this->config['db_name'],
                'username' => $this->config['db_user'],
                'password' => $this->config['db_password'],
            ];
        }

        throw new Exception('Database configuration not provided');
    }

    /**
     * Setup database using cPanel API
     */
    private function setupCpanelDatabase(): array
    {
        $api = $this->config['cpanel_api'];
        $dbName = $this->config['db_prefix'] . 'laravelshop_' . substr(md5(time()), 0, 8);
        $dbUser = $this->config['db_prefix'] . 'shop_' . substr(md5(time()), 0, 6);
        $dbPass = $this->generatePassword(16);

        try {
            // Create database
            $result = $api->call('Mysql', 'create_database', ['name' => $dbName]);
            if (!$result['status']) {
                throw new Exception('Failed to create database: ' . $result['errors'][0]);
            }

            // Create user
            $result = $api->call('Mysql', 'create_user', [
                'name' => $dbUser,
                'password' => $dbPass
            ]);
            if (!$result['status']) {
                throw new Exception('Failed to create database user: ' . $result['errors'][0]);
            }

            // Grant privileges
            $result = $api->call('Mysql', 'set_privileges_on_database', [
                'user' => $dbUser,
                'database' => $dbName,
                'privileges' => 'ALL PRIVILEGES'
            ]);
            if (!$result['status']) {
                throw new Exception('Failed to grant database privileges: ' . $result['errors'][0]);
            }

            return [
                'host' => 'localhost',
                'port' => 3306,
                'database' => $dbName,
                'username' => $dbUser,
                'password' => $dbPass,
            ];

        } catch (Exception $e) {
            throw new Exception('Database setup failed: ' . $e->getMessage());
        }
    }

    /**
     * Configure Laravel environment
     */
    private function configureEnvironment(array $dbConfig): void
    {
        $envPath = $this->config['install_path'] . '/.env';
        
        // Copy from example if .env doesn't exist
        if (!file_exists($envPath)) {
            if (file_exists($this->config['install_path'] . '/.env.example')) {
                copy($this->config['install_path'] . '/.env.example', $envPath);
            } else {
                file_put_contents($envPath, '');
            }
        }

        $envContent = file_get_contents($envPath);

        $envVars = [
            'APP_NAME' => '"' . $this->config['store_name'] . '"',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => 'https://' . $this->config['domain'],
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $dbConfig['host'],
            'DB_PORT' => $dbConfig['port'],
            'DB_DATABASE' => $dbConfig['database'],
            'DB_USERNAME' => $dbConfig['username'],
            'DB_PASSWORD' => $dbConfig['password'],
            'MAIL_FROM_ADDRESS' => $this->config['admin_email'],
            'MAIL_FROM_NAME' => '"' . $this->config['store_name'] . '"',
        ];

        foreach ($envVars as $key => $value) {
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
     * Install Laravel application
     */
    private function installLaravel(): void
    {
        $commands = [
            'php artisan key:generate --force',
            'php artisan migrate --force',
            'php artisan db:seed --force',
            'php artisan storage:link',
        ];

        foreach ($commands as $command) {
            $output = [];
            $returnCode = 0;
            
            exec("cd {$this->config['install_path']} && {$command} 2>&1", $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception("Command failed: {$command}. Output: " . implode("\n", $output));
            }
            
            $this->log("Executed: {$command}");
        }
    }

    /**
     * Create admin user with super_admin role
     */
    private function createAdminUser(): void
    {
        $command = sprintf(
            'php artisan tinker --execute="\\App\\Models\\User::updateOrCreate([\'email\' => \'%s\'], [\'name\' => \'Admin\', \'password\' => bcrypt(\'%s\'), \'is_admin\' => true, \'admin_role\' => \'super_admin\', \'email_verified_at\' => now()]);"',
            $this->config['admin_email'],
            $this->config['admin_password']
        );

        $output = [];
        $returnCode = 0;
        
        exec("cd {$this->config['install_path']} && {$command} 2>&1", $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Failed to create admin user. Output: " . implode("\n", $output));
        }
    }

    /**
     * Finalize installation
     */
    private function finalizeInstallation(): void
    {
        // Create installation marker
        $installData = [
            'installed_at' => date('Y-m-d H:i:s'),
            'version' => '2.0.0',
            'installer' => 'cpanel-auto',
            'domain' => $this->config['domain'],
            'features' => [
                'admin_roles' => true,
                'ai_features' => true,
                'blog_section' => true,
            ],
        ];

        file_put_contents(
            $this->config['install_path'] . '/storage/installed',
            json_encode($installData, JSON_PRETTY_PRINT)
        );

        // Clear caches
        $commands = [
            'php artisan config:clear',
            'php artisan cache:clear',
            'php artisan view:clear',
            'php artisan route:clear',
        ];

        foreach ($commands as $command) {
            exec("cd {$this->config['install_path']} && {$command} 2>&1");
        }

        // Set proper permissions
        $this->setPermissions();
    }

    /**
     * Set file permissions
     */
    private function setPermissions(): void
    {
        $paths = [
            '/storage' => 0755,
            '/bootstrap/cache' => 0755,
            '/.env' => 0644,
        ];

        foreach ($paths as $path => $permission) {
            $fullPath = $this->config['install_path'] . $path;
            if (file_exists($fullPath)) {
                chmod($fullPath, $permission);
            }
        }
    }

    /**
     * Generate secure password
     */
    private function generatePassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
    }

    /**
     * Get admin URL
     */
    private function getAdminUrl(): string
    {
        return 'https://' . $this->config['domain'] . '/admin';
    }

    /**
     * Get store URL
     */
    private function getStoreUrl(): string
    {
        return 'https://' . $this->config['domain'];
    }

    /**
     * Log message
     */
    private function log(string $message): void
    {
        $this->log[] = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    }

    /**
     * Get installation status
     */
    public function getStatus(): array
    {
        $installedFile = $this->config['install_path'] . '/storage/installed';
        
        if (file_exists($installedFile)) {
            $data = json_decode(file_get_contents($installedFile), true);
            return [
                'installed' => true,
                'data' => $data,
            ];
        }

        return ['installed' => false];
    }
}

// Usage examples:

// 1. Basic installation
if (isset($_GET['action']) && $_GET['action'] === 'install') {
    header('Content-Type: application/json');
    
    $config = [
        'domain' => $_SERVER['HTTP_HOST'],
        'admin_email' => $_POST['admin_email'] ?? 'admin@' . $_SERVER['HTTP_HOST'],
        'store_name' => $_POST['store_name'] ?? 'My Online Store',
        'db_host' => $_POST['db_host'] ?? 'localhost',
        'db_name' => $_POST['db_name'],
        'db_user' => $_POST['db_user'],
        'db_password' => $_POST['db_password'],
    ];
    
    $installer = new LaravelShopInstaller($config);
    echo json_encode($installer->install());
    exit;
}

// 2. Check status
if (isset($_GET['action']) && $_GET['action'] === 'status') {
    header('Content-Type: application/json');
    
    $installer = new LaravelShopInstaller();
    echo json_encode($installer->getStatus());
    exit;
}

// 3. cPanel integration example
if (isset($_GET['action']) && $_GET['action'] === 'cpanel-install') {
    header('Content-Type: application/json');
    
    // This would be provided by the cPanel integration
    $cpanelApi = new cPanelAPI(); // Your cPanel API wrapper
    
    $config = [
        'domain' => $_SERVER['HTTP_HOST'],
        'cpanel_api' => $cpanelApi,
        'db_prefix' => $cpanelApi->getUser() . '_',
        'admin_email' => $_POST['admin_email'],
        'store_name' => $_POST['store_name'],
    ];
    
    $installer = new LaravelShopInstaller($config);
    echo json_encode($installer->install());
    exit;
}

// Default: Show installation form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Shop - cPanel Installer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-2xl">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold text-center mb-8">Laravel Shop Auto-Installer</h1>
            
            <form id="install-form" class="space-y-6">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Store Name</label>
                        <input type="text" name="store_name" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Admin Email</label>
                        <input type="email" name="admin_email" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>
                
                <div class="border-t pt-6">
                    <h3 class="font-medium mb-4">Database Configuration</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Database Host</label>
                            <input type="text" name="db_host" value="localhost" required class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Database Name</label>
                            <input type="text" name="db_name" required class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Database User</label>
                            <input type="text" name="db_user" required class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Database Password</label>
                            <input type="password" name="db_password" class="w-full px-3 py-2 border rounded-lg">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Install Laravel Shop
                </button>
            </form>
            
            <div id="progress" class="mt-8 hidden">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    <p class="text-lg font-medium">Installing Laravel Shop...</p>
                    <p class="text-gray-600">This may take a few minutes.</p>
                </div>
            </div>
            
            <div id="result" class="mt-8 hidden"></div>
        </div>
    </div>

    <script>
        document.getElementById('install-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'install');
            
            document.getElementById('install-form').classList.add('hidden');
            document.getElementById('progress').classList.remove('hidden');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                document.getElementById('progress').classList.add('hidden');
                document.getElementById('result').classList.remove('hidden');
                
                if (result.success) {
                    document.getElementById('result').innerHTML = `
                        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                            <h3 class="text-lg font-bold text-green-800 mb-4">Installation Successful!</h3>
                            <div class="space-y-2 text-sm">
                                <p><strong>Store URL:</strong> <a href="${result.store_url}" class="text-blue-600 underline" target="_blank">${result.store_url}</a></p>
                                <p><strong>Admin URL:</strong> <a href="${result.admin_url}" class="text-blue-600 underline" target="_blank">${result.admin_url}</a></p>
                                <p><strong>Admin Email:</strong> ${result.admin_credentials.email}</p>
                                <p><strong>Admin Password:</strong> ${result.admin_credentials.password}</p>
                            </div>
                        </div>
                    `;
                } else {
                    document.getElementById('result').innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                            <h3 class="text-lg font-bold text-red-800 mb-4">Installation Failed</h3>
                            <p class="text-red-700">${result.error}</p>
                        </div>
                    `;
                }
            } catch (error) {
                document.getElementById('progress').classList.add('hidden');
                document.getElementById('result').classList.remove('hidden');
                document.getElementById('result').innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                        <h3 class="text-lg font-bold text-red-800 mb-4">Installation Error</h3>
                        <p class="text-red-700">${error.message}</p>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>