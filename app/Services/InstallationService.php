<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;
use Exception;

class InstallationService
{
    /**
     * Comprehensive requirements check
     */
    public function checkSystemRequirements(): array
    {
        $requirements = [
            'php' => $this->checkPhpRequirements(),
            'extensions' => $this->checkPhpExtensions(),
            'permissions' => $this->checkFilePermissions(),
            'database' => $this->checkDatabaseSupport(),
            'server' => $this->checkServerRequirements(),
        ];

        $requirements['overall'] = $this->calculateOverallStatus($requirements);

        return $requirements;
    }

    /**
     * Check PHP version and configuration
     */
    private function checkPhpRequirements(): array
    {
        return [
            'version' => [
                'required' => '8.2.0',
                'current' => PHP_VERSION,
                'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'critical' => true,
            ],
            'memory_limit' => [
                'required' => '128M',
                'current' => ini_get('memory_limit'),
                'passed' => $this->parseMemoryLimit(ini_get('memory_limit')) >= $this->parseMemoryLimit('128M'),
                'critical' => false,
            ],
            'max_execution_time' => [
                'required' => '60',
                'current' => ini_get('max_execution_time'),
                'passed' => (int)ini_get('max_execution_time') >= 60 || ini_get('max_execution_time') == 0,
                'critical' => false,
            ],
            'upload_max_filesize' => [
                'required' => '10M',
                'current' => ini_get('upload_max_filesize'),
                'passed' => $this->parseMemoryLimit(ini_get('upload_max_filesize')) >= $this->parseMemoryLimit('10M'),
                'critical' => false,
            ],
        ];
    }

    /**
     * Check required PHP extensions
     */
    private function checkPhpExtensions(): array
    {
        $extensions = [
            'pdo' => ['required' => true, 'critical' => true],
            'pdo_mysql' => ['required' => true, 'critical' => true],
            'mbstring' => ['required' => true, 'critical' => true],
            'openssl' => ['required' => true, 'critical' => true],
            'tokenizer' => ['required' => true, 'critical' => true],
            'json' => ['required' => true, 'critical' => true],
            'curl' => ['required' => true, 'critical' => true],
            'fileinfo' => ['required' => true, 'critical' => true],
            'gd' => ['required' => true, 'critical' => false],
            'zip' => ['required' => true, 'critical' => false],
            'xml' => ['required' => true, 'critical' => true],
            'bcmath' => ['required' => true, 'critical' => false],
            'ctype' => ['required' => true, 'critical' => true],
            'filter' => ['required' => true, 'critical' => true],
            'hash' => ['required' => true, 'critical' => true],
            'session' => ['required' => true, 'critical' => true],
        ];

        $results = [];
        foreach ($extensions as $ext => $config) {
            $loaded = extension_loaded($ext);
            $results[$ext] = [
                'loaded' => $loaded,
                'passed' => $loaded,
                'critical' => $config['critical'],
                'required' => $config['required'],
            ];
        }

        return $results;
    }

    /**
     * Check file and directory permissions
     */
    private function checkFilePermissions(): array
    {
        $paths = [
            'storage' => [
                'path' => storage_path(),
                'required' => 'writable',
                'critical' => true,
            ],
            'bootstrap/cache' => [
                'path' => base_path('bootstrap/cache'),
                'required' => 'writable',
                'critical' => true,
            ],
            '.env' => [
                'path' => base_path('.env'),
                'required' => 'writable',
                'critical' => true,
            ],
            'public' => [
                'path' => public_path(),
                'required' => 'writable',
                'critical' => false,
            ],
            'config' => [
                'path' => config_path(),
                'required' => 'readable',
                'critical' => true,
            ],
        ];

        $results = [];
        foreach ($paths as $name => $config) {
            $path = $config['path'];
            $exists = File::exists($path);
            $readable = $exists && is_readable($path);
            $writable = $exists && is_writable($path);

            $passed = match($config['required']) {
                'writable' => $writable,
                'readable' => $readable,
                default => $exists,
            };

            $results[$name] = [
                'path' => $path,
                'exists' => $exists,
                'readable' => $readable,
                'writable' => $writable,
                'passed' => $passed,
                'required' => $config['required'],
                'critical' => $config['critical'],
            ];
        }

        return $results;
    }

    /**
     * Check database support
     */
    private function checkDatabaseSupport(): array
    {
        return [
            'mysql_support' => [
                'passed' => extension_loaded('pdo_mysql'),
                'critical' => true,
            ],
            'sqlite_support' => [
                'passed' => extension_loaded('pdo_sqlite'),
                'critical' => false,
            ],
            'postgresql_support' => [
                'passed' => extension_loaded('pdo_pgsql'),
                'critical' => false,
            ],
        ];
    }

    /**
     * Check server requirements
     */
    private function checkServerRequirements(): array
    {
        return [
            'https_support' => [
                'passed' => isset($_SERVER['HTTPS']) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'),
                'critical' => false,
                'note' => 'HTTPS is recommended for production',
            ],
            'mod_rewrite' => [
                'passed' => $this->checkModRewrite(),
                'critical' => true,
                'note' => 'Required for clean URLs',
            ],
            'composer' => [
                'passed' => File::exists(base_path('vendor/autoload.php')),
                'critical' => true,
                'note' => 'Composer dependencies must be installed',
            ],
        ];
    }

    /**
     * Test database connection with provided credentials
     */
    public function testDatabaseConnection(array $config): array
    {
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
            $pdo = new PDO($dsn, $config['username'], $config['password'] ?? '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Test basic operations
            $tests = [
                'connection' => true,
                'create_table' => $this->testCreateTable($pdo),
                'insert_data' => $this->testInsertData($pdo),
                'select_data' => $this->testSelectData($pdo),
                'drop_table' => $this->testDropTable($pdo),
            ];

            return [
                'success' => true,
                'tests' => $tests,
                'server_info' => $pdo->getAttribute(PDO::ATTR_SERVER_INFO),
                'server_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ];
        }
    }

    /**
     * Create essential directories
     */
    public function createDirectories(): array
    {
        $directories = [
            storage_path('app/public/products'),
            storage_path('app/public/categories'),
            storage_path('app/public/brands'),
            storage_path('app/public/users'),
            storage_path('app/public/media'),
            storage_path('app/public/uploads'),
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            public_path('uploads'),
            public_path('storage'),
        ];

        $results = [];
        foreach ($directories as $dir) {
            try {
                if (!File::exists($dir)) {
                    File::makeDirectory($dir, 0755, true);
                }
                $results[$dir] = [
                    'created' => true,
                    'writable' => is_writable($dir),
                ];
            } catch (Exception $e) {
                $results[$dir] = [
                    'created' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Set proper file permissions
     */
    public function setPermissions(): array
    {
        $paths = [
            storage_path() => 0755,
            base_path('bootstrap/cache') => 0755,
            base_path('.env') => 0644,
        ];

        $results = [];
        foreach ($paths as $path => $permission) {
            try {
                if (File::exists($path)) {
                    chmod($path, $permission);
                    $results[$path] = [
                        'success' => true,
                        'permission' => decoct(fileperms($path) & 0777),
                    ];
                } else {
                    $results[$path] = [
                        'success' => false,
                        'error' => 'Path does not exist',
                    ];
                }
            } catch (Exception $e) {
                $results[$path] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Create essential database tables
     */
    public function createEssentialTables(): array
    {
        $tables = [
            'cache' => function() {
                if (!Schema::hasTable('cache')) {
                    Schema::create('cache', function ($table) {
                        $table->string('key')->primary();
                        $table->mediumText('value');
                        $table->integer('expiration');
                    });
                    return true;
                }
                return false;
            },
            'cache_locks' => function() {
                if (!Schema::hasTable('cache_locks')) {
                    Schema::create('cache_locks', function ($table) {
                        $table->string('key')->primary();
                        $table->string('owner');
                        $table->integer('expiration');
                    });
                    return true;
                }
                return false;
            },
            'sessions' => function() {
                if (!Schema::hasTable('sessions')) {
                    Schema::create('sessions', function ($table) {
                        $table->string('id')->primary();
                        $table->foreignId('user_id')->nullable()->index();
                        $table->string('ip_address', 45)->nullable();
                        $table->text('user_agent')->nullable();
                        $table->longText('payload');
                        $table->integer('last_activity')->index();
                    });
                    return true;
                }
                return false;
            },
        ];

        $results = [];
        foreach ($tables as $tableName => $createFunction) {
            try {
                $created = $createFunction();
                $results[$tableName] = [
                    'created' => $created,
                    'exists' => Schema::hasTable($tableName),
                ];
            } catch (Exception $e) {
                $results[$tableName] = [
                    'created' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Run post-installation optimizations
     */
    public function optimize(): array
    {
        $commands = [
            'config:cache' => 'Cache configuration',
            'route:cache' => 'Cache routes',
            'view:cache' => 'Cache views',
        ];

        $results = [];
        foreach ($commands as $command => $description) {
            try {
                Artisan::call($command);
                $results[$command] = [
                    'success' => true,
                    'description' => $description,
                ];
            } catch (Exception $e) {
                $results[$command] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'description' => $description,
                ];
            }
        }

        return $results;
    }

    /**
     * Helper methods
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        return match($last) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    private function checkModRewrite(): bool
    {
        if (function_exists('apache_get_modules')) {
            return in_array('mod_rewrite', apache_get_modules());
        }
        
        // Alternative check for nginx or other servers
        return File::exists(public_path('.htaccess'));
    }

    private function testCreateTable(PDO $pdo): bool
    {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS installer_test (id INT PRIMARY KEY, test_data VARCHAR(255))");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function testInsertData(PDO $pdo): bool
    {
        try {
            $pdo->exec("INSERT INTO installer_test (id, test_data) VALUES (1, 'test') ON DUPLICATE KEY UPDATE test_data = 'test'");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function testSelectData(PDO $pdo): bool
    {
        try {
            $stmt = $pdo->query("SELECT * FROM installer_test WHERE id = 1");
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function testDropTable(PDO $pdo): bool
    {
        try {
            $pdo->exec("DROP TABLE IF EXISTS installer_test");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function calculateOverallStatus(array $requirements): array
    {
        $critical_failed = 0;
        $warnings = 0;
        $total_checks = 0;

        foreach ($requirements as $category => $checks) {
            if ($category === 'overall') continue;

            foreach ($checks as $check) {
                $total_checks++;
                if (!$check['passed']) {
                    if ($check['critical'] ?? false) {
                        $critical_failed++;
                    } else {
                        $warnings++;
                    }
                }
            }
        }

        return [
            'ready_to_install' => $critical_failed === 0,
            'critical_failures' => $critical_failed,
            'warnings' => $warnings,
            'total_checks' => $total_checks,
            'status' => $critical_failed === 0 ? ($warnings === 0 ? 'excellent' : 'good') : 'failed',
        ];
    }
}