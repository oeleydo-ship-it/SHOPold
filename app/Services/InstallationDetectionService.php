<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class InstallationDetectionService
{
    /**
     * Check if the application is fully installed
     */
    public function isInstalled(): bool
    {
        return $this->hasInstallationMarker() && 
               $this->hasDatabaseConnection() && 
               $this->hasRequiredTables() &&
               $this->hasAdminUser();
    }

    /**
     * Check if installation marker exists
     */
    public function hasInstallationMarker(): bool
    {
        return File::exists(storage_path('installed'));
    }

    /**
     * Check if database connection is working
     */
    public function hasDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if required database tables exist
     */
    public function hasRequiredTables(): bool
    {
        try {
            $requiredTables = [
                'users',
                'settings',
                'categories',
                'products',
                'orders',
            ];

            foreach ($requiredTables as $table) {
                if (!Schema::hasTable($table)) {
                    return false;
                }
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if admin user exists
     */
    public function hasAdminUser(): bool
    {
        try {
            return DB::table('users')->where('is_admin', true)->exists();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get installation status details
     */
    public function getInstallationStatus(): array
    {
        $status = [
            'installed' => false,
            'checks' => [
                'marker' => $this->hasInstallationMarker(),
                'database' => $this->hasDatabaseConnection(),
                'tables' => $this->hasRequiredTables(),
                'admin_user' => $this->hasAdminUser(),
            ],
            'missing_requirements' => [],
        ];

        // Determine what's missing
        if (!$status['checks']['marker']) {
            $status['missing_requirements'][] = 'Installation marker file';
        }
        if (!$status['checks']['database']) {
            $status['missing_requirements'][] = 'Database connection';
        }
        if (!$status['checks']['tables']) {
            $status['missing_requirements'][] = 'Required database tables';
        }
        if (!$status['checks']['admin_user']) {
            $status['missing_requirements'][] = 'Admin user account';
        }

        $status['installed'] = empty($status['missing_requirements']);

        return $status;
    }

    /**
     * Get installation data if available
     */
    public function getInstallationData(): ?array
    {
        if (!$this->hasInstallationMarker()) {
            return null;
        }

        try {
            $content = File::get(storage_path('installed'));
            return json_decode($content, true);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Mark application as installed
     */
    public function markAsInstalled(array $data = []): bool
    {
        try {
            $installData = array_merge([
                'installed_at' => now()->toISOString(),
                'version' => config('app.version', '1.0.0'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ], $data);

            File::put(storage_path('installed'), json_encode($installData, JSON_PRETTY_PRINT));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Remove installation marker (for testing/reinstallation)
     */
    public function markAsUninstalled(): bool
    {
        try {
            if (File::exists(storage_path('installed'))) {
                File::delete(storage_path('installed'));
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if system meets installation requirements
     */
    public function checkSystemRequirements(): array
    {
        $requirements = [
            'php_version' => [
                'name' => 'PHP Version >= 8.2',
                'required' => '8.2.0',
                'current' => PHP_VERSION,
                'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
            ],
            'extensions' => [],
            'permissions' => [],
        ];

        // Check PHP extensions
        $requiredExtensions = [
            'pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer',
            'json', 'curl', 'fileinfo', 'gd', 'zip'
        ];

        foreach ($requiredExtensions as $ext) {
            $requirements['extensions'][$ext] = [
                'name' => $ext . ' extension',
                'passed' => extension_loaded($ext),
            ];
        }

        // Check directory permissions
        $requiredPermissions = [
            'storage' => storage_path(),
            'bootstrap_cache' => base_path('bootstrap/cache'),
            'env' => base_path('.env'),
        ];

        foreach ($requiredPermissions as $name => $path) {
            $requirements['permissions'][$name] = [
                'name' => $name,
                'path' => $path,
                'passed' => is_writable($path) || ($name === 'env' && is_writable(dirname($path))),
            ];
        }

        // Calculate overall status
        $allPassed = $requirements['php_version']['passed'];
        foreach ($requirements['extensions'] as $ext) {
            if (!$ext['passed']) $allPassed = false;
        }
        foreach ($requirements['permissions'] as $perm) {
            if (!$perm['passed']) $allPassed = false;
        }

        $requirements['overall_passed'] = $allPassed;

        return $requirements;
    }
}