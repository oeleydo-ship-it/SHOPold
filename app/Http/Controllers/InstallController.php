<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Services\InstallationDetectionService;
use PDO;
use PDOException;
use Exception;

class InstallController extends Controller
{
    /**
     * Check if app is already installed
     */
    private function isInstalled(): bool
    {
        // Check for force parameter
        if (request()->has('force') && request()->get('force') === '1') {
            return false;
        }
        
        $detector = new InstallationDetectionService();
        return $detector->isInstalled();
    }

    /**
     * Auto-install endpoint for control panel integration
     */
    public function autoInstall(Request $request)
    {
        if ($this->isInstalled()) {
            return response()->json(['error' => 'Application is already installed'], 400);
        }

        try {
            // Validate input
            $validated = $request->validate([
                'db_host' => 'required|string',
                'db_port' => 'required|numeric',
                'db_database' => 'required|string',
                'db_username' => 'required|string',
                'db_password' => 'nullable|string',
                'admin_name' => 'required|string|max:255',
                'admin_email' => 'required|email|max:255',
                'admin_password' => 'required|string|min:8',
                'store_name' => 'required|string|max:255',
                'store_email' => 'nullable|email|max:255',
                'store_currency' => 'required|string|max:10',
                'app_url' => 'nullable|url',
            ]);

            // Check requirements
            $requirementsCheck = $this->checkRequirements();
            if (!$requirementsCheck['passed']) {
                return response()->json([
                    'error' => 'Server requirements not met',
                    'requirements' => $requirementsCheck
                ], 400);
            }

            // Test database connection
            $dbTest = $this->testDatabaseConnection($validated);
            if (!$dbTest['success']) {
                return response()->json([
                    'error' => 'Database connection failed',
                    'message' => $dbTest['message']
                ], 400);
            }

            // Update environment configuration
            $this->updateEnvironment($validated);

            // Run installation steps
            $installResult = $this->runInstallation($validated);
            
            if (!$installResult['success']) {
                return response()->json([
                    'error' => 'Installation failed',
                    'message' => $installResult['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Installation completed successfully',
                'admin_url' => url('/admin'),
                'login_credentials' => [
                    'email' => $validated['admin_email'],
                    'note' => 'Use the password you provided'
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Auto-install failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Installation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check server requirements
     */
    private function checkRequirements(): array
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
            'storage' => is_writable(storage_path()),
            'bootstrap_cache' => is_writable(base_path('bootstrap/cache')),
            'env' => is_writable(base_path('.env')) || is_writable(base_path()),
        ];

        $allPassed = collect($requirements)->every(fn($r) => $r) 
                  && collect($permissions)->every(fn($p) => $p);

        return [
            'passed' => $allPassed,
            'requirements' => $requirements,
            'permissions' => $permissions
        ];
    }

    /**
     * Test database connection
     */
    private function testDatabaseConnection(array $config): array
    {
        try {
            $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_database']}";
            $pdo = new PDO($dsn, $config['db_username'], $config['db_password'] ?? '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Test if we can create tables
            $pdo->exec("CREATE TABLE IF NOT EXISTS test_install (id INT PRIMARY KEY)");
            $pdo->exec("DROP TABLE test_install");
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update environment configuration
     */
    private function updateEnvironment(array $config): void
    {
        $envUpdates = [
            'APP_NAME' => '"' . $config['store_name'] . '"',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => $config['app_url'] ?? url('/'),
            'DB_HOST' => $config['db_host'],
            'DB_PORT' => $config['db_port'],
            'DB_DATABASE' => $config['db_database'],
            'DB_USERNAME' => $config['db_username'],
            'DB_PASSWORD' => $config['db_password'] ?? '',
        ];

        $this->updateEnv($envUpdates);
    }

    /**
     * Run the complete installation process
     */
    private function runInstallation(array $config): array
    {
        try {
            // Clear config cache
            Artisan::call('config:clear');
            
            // Generate app key if not set
            if (empty(config('app.key'))) {
                Artisan::call('key:generate', ['--force' => true]);
            }

            // Run migrations with error handling
            $migrationResult = Artisan::call('migrate', ['--force' => true]);
            if ($migrationResult !== 0) {
                throw new Exception('Migration failed');
            }

            // Create essential tables if they don't exist
            $this->createEssentialTables();

            // Run seeders
            $seederResult = Artisan::call('db:seed', ['--force' => true]);
            if ($seederResult !== 0) {
                throw new Exception('Database seeding failed');
            }

            // Create admin user
            $this->createAdminUser($config);

            // Create initial settings
            $this->createInitialSettings($config);

            // Create storage link with fallback options
            if (!File::exists(public_path('storage'))) {
                try {
                    Artisan::call('storage:link');
                } catch (Exception $e) {
                    // Fallback: Create symlink manually
                    $target = storage_path('app/public');
                    $link = public_path('storage');
                    
                    if (!File::exists($target)) {
                        File::makeDirectory($target, 0755, true);
                    }
                    
                    if (function_exists('symlink')) {
                        @symlink($target, $link);
                    } else {
                        // Create directory structure as fallback
                        File::makeDirectory($link, 0755, true);
                        $subdirs = ['products', 'categories', 'brands', 'users', 'media'];
                        foreach ($subdirs as $subdir) {
                            File::makeDirectory($link . '/' . $subdir, 0755, true);
                        }
                    }
                }
            }

            // Create necessary directories
            $this->createDirectories();

            // Set proper permissions
            $this->setPermissions();

            // Mark as installed
            File::put(storage_path('installed'), json_encode([
                'installed_at' => now()->toISOString(),
                'version' => config('app.version', '1.0.0'),
                'admin_email' => $config['admin_email']
            ]));

            // Clear all caches
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            return ['success' => true];

        } catch (Exception $e) {
            Log::error('Installation step failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create essential database tables
     */
    private function createEssentialTables(): void
    {
        // Cache table
        if (!Schema::hasTable('cache')) {
            Schema::create('cache', function ($table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        // Cache locks table
        if (!Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function ($table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }

        // Sessions table
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function ($table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    /**
     * Create admin user
     */
    private function createAdminUser(array $config): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => $config['admin_email']],
            [
                'name' => $config['admin_name'],
                'password' => Hash::make($config['admin_password']),
                'is_admin' => true,
                'admin_role' => 'super_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }

    /**
     * Create necessary directories
     */
    private function createDirectories(): void
    {
        $directories = [
            storage_path('app/public/products'),
            storage_path('app/public/categories'),
            storage_path('app/public/brands'),
            storage_path('app/public/users'),
            storage_path('app/public/media'),
            storage_path('logs'),
            public_path('uploads'),
        ];

        foreach ($directories as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        }
    }

    /**
     * Set proper file permissions
     */
    private function setPermissions(): void
    {
        $paths = [
            storage_path(),
            base_path('bootstrap/cache'),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                chmod($path, 0755);
            }
        }
    }

    /**
     * Show welcome page
     */
    public function welcome()
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        return view('install.welcome');
    }

    /**
     * Get installation status for control panel
     */
    public function status()
    {
        $installed = $this->isInstalled();
        $requirements = $this->checkRequirements();
        
        $status = [
            'installed' => $installed,
            'requirements_met' => $requirements['passed'],
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        if ($installed) {
            $installData = json_decode(File::get(storage_path('installed')), true);
            $status['installed_at'] = $installData['installed_at'] ?? null;
            $status['version'] = $installData['version'] ?? null;
        }

        return response()->json($status);
    }

    /**
     * Check server requirements
     */
    public function requirements()
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        $requirements = [
            'php_version' => [
                'name' => 'PHP Version >= 8.2',
                'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'current' => PHP_VERSION,
            ],
            'pdo' => [
                'name' => 'PDO Extension',
                'passed' => extension_loaded('pdo'),
            ],
            'pdo_mysql' => [
                'name' => 'PDO MySQL Extension',
                'passed' => extension_loaded('pdo_mysql'),
            ],
            'mbstring' => [
                'name' => 'Mbstring Extension',
                'passed' => extension_loaded('mbstring'),
            ],
            'openssl' => [
                'name' => 'OpenSSL Extension',
                'passed' => extension_loaded('openssl'),
            ],
            'tokenizer' => [
                'name' => 'Tokenizer Extension',
                'passed' => extension_loaded('tokenizer'),
            ],
            'json' => [
                'name' => 'JSON Extension',
                'passed' => extension_loaded('json'),
            ],
            'curl' => [
                'name' => 'cURL Extension',
                'passed' => extension_loaded('curl'),
            ],
            'fileinfo' => [
                'name' => 'Fileinfo Extension',
                'passed' => extension_loaded('fileinfo'),
            ],
            'gd' => [
                'name' => 'GD Extension',
                'passed' => extension_loaded('gd'),
            ],
        ];

        $permissions = [
            'storage' => [
                'name' => 'storage/',
                'path' => storage_path(),
                'writable' => is_writable(storage_path()),
            ],
            'bootstrap_cache' => [
                'name' => 'bootstrap/cache/',
                'path' => base_path('bootstrap/cache'),
                'writable' => is_writable(base_path('bootstrap/cache')),
            ],
            'env' => [
                'name' => '.env',
                'path' => base_path('.env'),
                'writable' => is_writable(base_path('.env')) || is_writable(base_path()),
            ],
        ];

        $allPassed = collect($requirements)->every(fn($r) => $r['passed']) 
                  && collect($permissions)->every(fn($p) => $p['writable']);

        return view('install.requirements', compact('requirements', 'permissions', 'allPassed'));
    }

    /**
     * Show database configuration form
     */
    public function database()
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        return view('install.database');
    }

    /**
     * Test and save database configuration
     */
    public function saveDatabase(Request $request)
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        // Test database connection
        try {
            $dsn = "mysql:host={$request->db_host};port={$request->db_port};dbname={$request->db_database}";
            $pdo = new PDO($dsn, $request->db_username, $request->db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            return back()->withInput()->withErrors(['database' => 'Database connection failed: ' . $e->getMessage()]);
        }

        // Update .env file
        $this->updateEnv([
            'DB_HOST' => $request->db_host,
            'DB_PORT' => $request->db_port,
            'DB_DATABASE' => $request->db_database,
            'DB_USERNAME' => $request->db_username,
            'DB_PASSWORD' => $request->db_password ?? '',
        ]);

        // Store in session for next step
        session(['db_configured' => true]);

        return redirect()->route('install.admin');
    }

    /**
     * Show admin account form
     */
    public function admin()
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        return view('install.admin');
    }

    /**
     * Show store settings form
     */
    public function store()
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        return view('install.store');
    }

    /**
     * Run installation
     */
    public function install(Request $request)
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        $request->validate([
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8|confirmed',
            'store_name' => 'required|string|max:255',
            'store_email' => 'nullable|email|max:255',
            'store_currency' => 'required|string|max:10',
        ]);

        try {
            // Clear config cache
            Artisan::call('config:clear');
            
            // Run migrations
            Artisan::call('migrate', ['--force' => true]);

            // Run seeders
            Artisan::call('db:seed', ['--force' => true]);

            // Create admin user
            $admin = \App\Models\User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'is_admin' => true,
                'admin_role' => 'super_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Create initial settings
            $this->createInitialSettings($request);

            // Generate app key if not set
            if (empty(config('app.key'))) {
                Artisan::call('key:generate', ['--force' => true]);
            }

            // Create storage link with fallback options
            if (!File::exists(public_path('storage'))) {
                try {
                    Artisan::call('storage:link');
                } catch (Exception $e) {
                    // Fallback: Create symlink manually
                    $target = storage_path('app/public');
                    $link = public_path('storage');
                    
                    if (!File::exists($target)) {
                        File::makeDirectory($target, 0755, true);
                    }
                    
                    if (function_exists('symlink')) {
                        @symlink($target, $link);
                    } else {
                        // Create directory structure as fallback
                        File::makeDirectory($link, 0755, true);
                        $subdirs = ['products', 'categories', 'brands', 'users', 'media'];
                        foreach ($subdirs as $subdir) {
                            File::makeDirectory($link . '/' . $subdir, 0755, true);
                        }
                    }
                }
            }

            // Update .env with app settings
            $this->updateEnv([
                'APP_NAME' => '"' . $request->store_name . '"',
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_URL' => url('/'),
            ]);

            // Mark as installed
            File::put(storage_path('installed'), date('Y-m-d H:i:s'));

            // Clear all caches
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            return redirect()->route('install.complete');

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['install' => 'Installation failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Show completion page
     */
    public function complete()
    {
        if (!$this->isInstalled()) {
            return redirect()->route('install.welcome');
        }

        return view('install.complete');
    }

    /**
     * Create initial settings
     */
    private function createInitialSettings($request)
    {
        $settings = [
            ['key' => 'store.name', 'value' => is_array($request) ? $request['store_name'] : $request->store_name],
            ['key' => 'store.email', 'value' => is_array($request) ? ($request['store_email'] ?? $request['admin_email']) : ($request->store_email ?? $request->admin_email)],
            ['key' => 'store.currency', 'value' => is_array($request) ? $request['store_currency'] : $request->store_currency],
            ['key' => 'store.currency_symbol', 'value' => $this->getCurrencySymbol(is_array($request) ? $request['store_currency'] : $request->store_currency)],
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
     * Get currency symbol
     */
    private function getCurrencySymbol(string $currency): string
    {
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
     * Update .env file
     */
    private function updateEnv(array $data)
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            if (File::exists(base_path('.env.example'))) {
                File::copy(base_path('.env.example'), $envPath);
            } else {
                File::put($envPath, '');
            }
        }
        
        $envContent = File::get($envPath);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        File::put($envPath, $envContent);
    }
}