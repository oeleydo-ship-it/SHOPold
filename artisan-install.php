<?php
/**
 * Laravel Shop CLI Installer
 * 
 * A command-line installer for Laravel Shop that can be used
 * for automated deployments and server setups.
 * 
 * Usage: php artisan-install.php [options]
 */

require __DIR__ . '/vendor/autoload.php';

class LaravelShopCLIInstaller
{
    private $options = [];
    private $interactive = true;

    public function __construct($argv = [])
    {
        $this->parseArguments($argv);
        $this->interactive = !isset($this->options['non-interactive']);
    }

    public function run()
    {
        $this->printHeader();

        try {
            // Check if already installed
            if (file_exists(storage_path('installed'))) {
                $this->error('Laravel Shop is already installed!');
                return 1;
            }

            // Check requirements
            $this->info('Checking system requirements...');
            if (!$this->checkRequirements()) {
                return 1;
            }

            // Get configuration
            $config = $this->getConfiguration();

            // Confirm installation
            if ($this->interactive && !$this->confirm('Proceed with installation?')) {
                $this->info('Installation cancelled.');
                return 0;
            }

            // Run installation
            $this->info('Starting installation...');
            $this->install($config);

            $this->success('Installation completed successfully!');
            $this->printCredentials($config);

            return 0;

        } catch (Exception $e) {
            $this->error('Installation failed: ' . $e->getMessage());
            return 1;
        }
    }

    private function parseArguments($argv)
    {
        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            
            if (strpos($arg, '--') === 0) {
                $key = substr($arg, 2);
                $value = true;
                
                if (strpos($key, '=') !== false) {
                    list($key, $value) = explode('=', $key, 2);
                }
                
                $this->options[$key] = $value;
            }
        }
    }

    private function checkRequirements(): bool
    {
        $requirements = [
            'PHP 8.2+' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'PDO Extension' => extension_loaded('pdo'),
            'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
            'Mbstring Extension' => extension_loaded('mbstring'),
            'OpenSSL Extension' => extension_loaded('openssl'),
            'Tokenizer Extension' => extension_loaded('tokenizer'),
            'JSON Extension' => extension_loaded('json'),
            'cURL Extension' => extension_loaded('curl'),
            'Fileinfo Extension' => extension_loaded('fileinfo'),
            'GD Extension' => extension_loaded('gd'),
        ];

        $permissions = [
            'storage/ writable' => is_writable(__DIR__ . '/storage'),
            'bootstrap/cache/ writable' => is_writable(__DIR__ . '/bootstrap/cache'),
            '.env writable' => is_writable(__DIR__) || is_writable(__DIR__ . '/.env'),
        ];

        $allPassed = true;

        foreach (array_merge($requirements, $permissions) as $check => $passed) {
            $status = $passed ? '✓' : '✗';
            $color = $passed ? 'green' : 'red';
            
            $this->output("  {$status} {$check}", $color);
            
            if (!$passed) {
                $allPassed = false;
            }
        }

        if (!$allPassed) {
            $this->error('Please fix the above requirements before continuing.');
        }

        return $allPassed;
    }

    private function getConfiguration(): array
    {
        $config = [];

        // Load from config file if provided
        if (isset($this->options['config'])) {
            $configFile = $this->options['config'];
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                $this->info("Loaded configuration from: {$configFile}");
            }
        }

        // Database configuration
        $config['db_host'] = $this->getOption('db-host', $config['database']['host'] ?? null, 'Database host', 'localhost');
        $config['db_port'] = $this->getOption('db-port', $config['database']['port'] ?? null, 'Database port', '3306');
        $config['db_database'] = $this->getOption('db-database', $config['database']['database'] ?? null, 'Database name');
        $config['db_username'] = $this->getOption('db-username', $config['database']['username'] ?? null, 'Database username');
        $config['db_password'] = $this->getOption('db-password', $config['database']['password'] ?? null, 'Database password', '', true);

        // Admin configuration
        $config['admin_name'] = $this->getOption('admin-name', $config['admin']['name'] ?? null, 'Admin name', 'Admin User');
        $config['admin_email'] = $this->getOption('admin-email', $config['admin']['email'] ?? null, 'Admin email');
        $config['admin_password'] = $this->getOption('admin-password', $config['admin']['password'] ?? null, 'Admin password', $this->generatePassword(), true);

        // Store configuration
        $config['store_name'] = $this->getOption('store-name', $config['store']['name'] ?? null, 'Store name', 'Laravel Shop');
        $config['store_email'] = $this->getOption('store-email', $config['store']['email'] ?? null, 'Store email', $config['admin_email']);
        $config['store_currency'] = $this->getOption('store-currency', $config['store']['currency'] ?? null, 'Store currency', 'USD');

        // App configuration
        $config['app_url'] = $this->getOption('app-url', $config['application']['url'] ?? null, 'Application URL', 'http://localhost');

        return $config;
    }

    private function getOption($key, $configValue, $prompt, $default = null, $hidden = false)
    {
        // Check command line option first
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }

        // Use config file value if available
        if ($configValue !== null) {
            return $configValue;
        }

        // Interactive prompt
        if ($this->interactive) {
            return $this->prompt($prompt, $default, $hidden);
        }

        // Use default or throw error
        if ($default !== null) {
            return $default;
        }

        throw new Exception("Required option --{$key} not provided");
    }

    private function install($config)
    {
        // Bootstrap Laravel
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        // Update .env file
        $this->info('Configuring environment...');
        $this->updateEnv($config);

        // Clear config cache
        $this->info('Clearing configuration cache...');
        Artisan::call('config:clear');

        // Generate app key
        $this->info('Generating application key...');
        Artisan::call('key:generate', ['--force' => true]);

        // Test database connection
        $this->info('Testing database connection...');
        $this->testDatabaseConnection($config);

        // Run migrations
        $this->info('Running database migrations...');
        Artisan::call('migrate', ['--force' => true]);

        // Run seeders
        $this->info('Seeding database...');
        Artisan::call('db:seed', ['--force' => true]);

        // Create admin user
        $this->info('Creating admin user...');
        $this->createAdminUser($config);

        // Create storage link
        $this->info('Creating storage link...');
        if (!file_exists(public_path('storage'))) {
            Artisan::call('storage:link');
        }

        // Create installation marker
        $this->info('Finalizing installation...');
        file_put_contents(storage_path('installed'), json_encode([
            'installed_at' => date('Y-m-d H:i:s'),
            'version' => '1.0.0',
            'installer' => 'cli',
        ]));

        // Clear caches
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
    }

    private function updateEnv($config)
    {
        $envPath = __DIR__ . '/.env';
        
        if (!file_exists($envPath)) {
            if (file_exists(__DIR__ . '/.env.example')) {
                copy(__DIR__ . '/.env.example', $envPath);
            } else {
                file_put_contents($envPath, '');
            }
        }

        $envContent = file_get_contents($envPath);

        $envVars = [
            'APP_NAME' => '"' . $config['store_name'] . '"',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => $config['app_url'],
            'DB_HOST' => $config['db_host'],
            'DB_PORT' => $config['db_port'],
            'DB_DATABASE' => $config['db_database'],
            'DB_USERNAME' => $config['db_username'],
            'DB_PASSWORD' => $config['db_password'],
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

    private function testDatabaseConnection($config)
    {
        try {
            $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_database']}";
            $pdo = new PDO($dsn, $config['db_username'], $config['db_password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    private function createAdminUser($config)
    {
        \App\Models\User::updateOrCreate(
            ['email' => $config['admin_email']],
            [
                'name' => $config['admin_name'],
                'password' => Hash::make($config['admin_password']),
                'is_admin' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }

    private function generatePassword($length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
    }

    private function prompt($message, $default = null, $hidden = false): string
    {
        $prompt = $message;
        if ($default) {
            $prompt .= " [{$default}]";
        }
        $prompt .= ': ';

        echo $prompt;

        if ($hidden) {
            system('stty -echo');
            $input = trim(fgets(STDIN));
            system('stty echo');
            echo "\n";
        } else {
            $input = trim(fgets(STDIN));
        }

        return $input ?: $default;
    }

    private function confirm($message): bool
    {
        $response = $this->prompt($message . ' (y/N)', 'n');
        return strtolower($response) === 'y';
    }

    private function printHeader()
    {
        $this->output('');
        $this->output('╔══════════════════════════════════════╗', 'cyan');
        $this->output('║        Laravel Shop Installer       ║', 'cyan');
        $this->output('║              CLI Version             ║', 'cyan');
        $this->output('╚══════════════════════════════════════╝', 'cyan');
        $this->output('');
    }

    private function printCredentials($config)
    {
        $this->output('');
        $this->output('╔══════════════════════════════════════╗', 'green');
        $this->output('║           Installation Complete      ║', 'green');
        $this->output('╚══════════════════════════════════════╝', 'green');
        $this->output('');
        $this->output('Admin Credentials:', 'yellow');
        $this->output('  Email: ' . $config['admin_email']);
        $this->output('  Password: ' . $config['admin_password']);
        $this->output('');
        $this->output('URLs:', 'yellow');
        $this->output('  Store: ' . $config['app_url']);
        $this->output('  Admin: ' . $config['app_url'] . '/admin');
        $this->output('');
    }

    private function output($message, $color = null)
    {
        $colors = [
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'blue' => "\033[34m",
            'cyan' => "\033[36m",
            'reset' => "\033[0m",
        ];

        if ($color && isset($colors[$color])) {
            echo $colors[$color] . $message . $colors['reset'] . "\n";
        } else {
            echo $message . "\n";
        }
    }

    private function info($message)
    {
        $this->output('[INFO] ' . $message, 'blue');
    }

    private function success($message)
    {
        $this->output('[SUCCESS] ' . $message, 'green');
    }

    private function error($message)
    {
        $this->output('[ERROR] ' . $message, 'red');
    }
}

// Run the installer
$installer = new LaravelShopCLIInstaller($argv);
exit($installer->run());