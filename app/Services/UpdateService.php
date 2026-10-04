<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class UpdateService
{
    // Current app version - update this with each release
    const VERSION = '1.7.0';
    
    // Author information
    const AUTHOR = 'Jhay Eleydo';
    const AUTHOR_URL = 'https://jrwebpro.cm';
    
    // Update server URL - change this to your actual update server
    // Can be overridden via .env: UPDATE_SERVER_URL
    const UPDATE_SERVER = 'https://updates.codearena.dev/updateserver/index.php';
    
    // Cache duration for update check (in minutes)
    const CACHE_DURATION = 60;
    
    /**
     * Get the update server URL
     */
    protected function getUpdateServerUrl(): string
    {
        return env('UPDATE_SERVER_URL', self::UPDATE_SERVER);
    }
    
    /**
     * Get the license key
     */
    protected function getLicenseKey(): ?string
    {
        return env('UPDATE_LICENSE_KEY') ?: setting('system.license_key');
    }

    /**
     * Get current app version
     */
    public function getCurrentVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Get author name
     */
    public function getAuthor(): string
    {
        return self::AUTHOR;
    }

    /**
     * Get author URL
     */
    public function getAuthorUrl(): string
    {
        return self::AUTHOR_URL;
    }

    /**
     * Check for available updates
     */
    public function checkForUpdates(bool $force = false): array
    {
        $cacheKey = 'app_update_check';
        
        if (!$force && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $updateServerUrl = $this->getUpdateServerUrl();
            
            // Handle file:// URLs for local testing
            if (str_starts_with($updateServerUrl, 'file://')) {
                $mockResponse = $this->getMockUpdateResponse();
                $response = new class($mockResponse) {
                    private $data;
                    public function __construct($data) { $this->data = $data; }
                    public function successful() { return true; }
                    public function json() { return $this->data; }
                    public function status() { return 200; }
                };
            } else {
                $response = Http::connectTimeout(5)->timeout(15)
                    ->withHeaders([
                        'X-License-Key' => $this->getLicenseKey() ?? '',
                    ])
                    ->get($updateServerUrl, [
                        'action' => 'check',
                        'version' => self::VERSION,
                        'php_version' => PHP_VERSION,
                        'laravel_version' => app()->version(),
                        'domain' => request()->getHost(),
                    ]);
            }

            if ($response->successful()) {
                $data = $response->json();
                
                // Check if the response contains an error
                if (isset($data['error'])) {
                    $result = $this->getOfflineResult('Update server error: ' . $data['error']);
                } else {
                    $result = [
                        'available' => $data['update_available'] ?? false,
                        'latest_version' => $data['latest_version'] ?? self::VERSION,
                        'current_version' => self::VERSION,
                        'changelog' => $data['changelog'] ?? [],
                        'breaking_changes' => $data['breaking_changes'] ?? [],
                        'upgrade_notes' => $data['upgrade_notes'] ?? '',
                        'download_url' => $data['download_url'] ?? null,
                        'release_date' => $data['release_date'] ?? null,
                        'min_php_version' => $data['min_php_version'] ?? '8.2',
                        'min_laravel_version' => $data['min_laravel_version'] ?? '11.0',
                        'file_hash' => $data['file_hash'] ?? null,
                        'file_size' => $data['file_size'] ?? null,
                        'requires_license' => $data['requires_license'] ?? false,
                        'checked_at' => now()->toDateTimeString(),
                        'error' => null,
                    ];
                }
            } else {
                $statusCode = $response->status();
                $errorMessage = "Update server returned HTTP $statusCode";
                
                if ($statusCode === 404) {
                    $errorMessage = 'Update server endpoint not found (404). Please check your UPDATE_SERVER_URL configuration.';
                } elseif ($statusCode === 403) {
                    $errorMessage = 'Access denied to update server (403). Please check your license key.';
                } elseif ($statusCode >= 500) {
                    $errorMessage = 'Update server is experiencing issues (HTTP ' . $statusCode . '). Please try again later.';
                }
                
                $result = $this->getOfflineResult($errorMessage);
            }
        } catch (\Exception $e) {
            Log::warning('Update check failed: ' . $e->getMessage());
            
            // Provide a more user-friendly error message
            $errorMessage = 'Unable to connect to update server';
            if (str_contains($e->getMessage(), 'Connection refused') || str_contains($e->getMessage(), 'Could not resolve host')) {
                $errorMessage = 'Update server is currently unavailable. Please try again later.';
            } elseif (str_contains($e->getMessage(), '404')) {
                $errorMessage = 'Update server endpoint not found. Please check your configuration.';
            } elseif (str_contains($e->getMessage(), 'timeout')) {
                $errorMessage = 'Update server request timed out. Please try again later.';
            }
            
            $result = $this->getOfflineResult($errorMessage);
        }

        Cache::put($cacheKey, $result, now()->addMinutes(self::CACHE_DURATION));
        
        return $result;
    }

    /**
     * Get mock update response for testing
     */
    private function getMockUpdateResponse(): array
    {
        return [
            'success' => true,
            'update_available' => false, // Always false for testing
            'latest_version' => self::VERSION,
            'current_version' => self::VERSION,
            'min_php_version' => '8.2',
            'min_laravel_version' => '11.0',
            'release_date' => date('Y-m-d'),
            'changelog' => [
                'System is up to date',
                'Using local mock update server for testing',
            ],
            'breaking_changes' => [],
            'upgrade_notes' => '',
            'download_url' => null,
            'requires_license' => false,
            'file_hash' => null,
            'file_size' => null,
        ];
    }

    /**
     * Get offline/error result
     */
    private function getOfflineResult(string $error): array
    {
        return [
            'available' => false,
            'latest_version' => self::VERSION,
            'current_version' => self::VERSION,
            'changelog' => [],
            'breaking_changes' => [],
            'upgrade_notes' => '',
            'download_url' => null,
            'release_date' => null,
            'min_php_version' => '8.2',
            'min_laravel_version' => '11.0',
            'file_hash' => null,
            'file_size' => null,
            'requires_license' => false,
            'checked_at' => now()->toDateTimeString(),
            'error' => $error,
        ];
    }
    
    /**
     * Get available versions from update server
     */
    public function getAvailableVersions(): array
    {
        try {
            $response = Http::connectTimeout(5)->timeout(15)
                ->withHeaders([
                    'X-License-Key' => $this->getLicenseKey() ?? '',
                ])
                ->get($this->getUpdateServerUrl(), [
                    'action' => 'versions',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['versions'] ?? [];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get versions: ' . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * Verify file integrity using hash
     */
    public function verifyFileIntegrity(string $filePath, string $expectedHash): bool
    {
        if (!File::exists($filePath) || empty($expectedHash)) {
            return false;
        }
        
        $actualHash = hash_file('sha256', $filePath);
        return hash_equals($expectedHash, $actualHash);
    }

    /**
     * Download and install update
     */
    public function installUpdate(string $downloadUrl, ?string $expectedHash = null): array
    {
        $backupPath = storage_path('backups/pre-update-' . date('Y-m-d-His'));
        $tempPath = storage_path('app/updates');
        $zipPath = $tempPath . '/update.zip';

        try {
            // Create directories
            File::ensureDirectoryExists($backupPath);
            File::ensureDirectoryExists($tempPath);

            // Step 1: Create backup
            Log::info('Update: Creating backup...');
            $this->createBackup($backupPath);

            // Step 2: Download update
            Log::info('Update: Downloading update file...');
            $this->downloadUpdate($downloadUrl, $zipPath, $expectedHash);

            // Step 3: Extract update
            Log::info('Update: Extracting update file...');
            $extractPath = $tempPath . '/extracted';
            $this->extractUpdate($zipPath, $extractPath);

            // Step 4: Apply update
            Log::info('Update: Applying update files...');
            $this->applyUpdate($extractPath);

            // Step 5: Run migrations
            Log::info('Update: Running database migrations...');
            Artisan::call('migrate', ['--force' => true]);
            $migrationOutput = Artisan::output();

            // Step 6: Clear caches
            Log::info('Update: Clearing caches...');
            $this->clearCaches();

            // Step 7: Fix permissions (important for shared hosting)
            Log::info('Update: Fixing directory permissions...');
            $this->fixPermissions();

            // Step 8: Cleanup
            Log::info('Update: Cleaning up temporary files...');
            File::deleteDirectory($tempPath);

            // Clear update cache
            Cache::forget('app_update_check');
            
            Log::info('Update: Installation completed successfully!');

            return [
                'success' => true,
                'message' => 'Update installed successfully!',
                'backup_path' => $backupPath,
                'migration_output' => $migrationOutput ?? '',
            ];

        } catch (\Exception $e) {
            Log::error('Update failed: ' . $e->getMessage());
            
            // Attempt to restore backup
            if (File::exists($backupPath)) {
                $this->restoreBackup($backupPath);
            }

            return [
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
                'backup_path' => $backupPath,
            ];
        }
    }

    /**
     * Create backup of critical files
     */
    private function createBackup(string $backupPath): void
    {
        // Backup .env
        if (File::exists(base_path('.env'))) {
            File::copy(base_path('.env'), $backupPath . '/.env');
        }

        // Backup app folder
        File::copyDirectory(app_path(), $backupPath . '/app');

        // Backup config folder
        File::copyDirectory(config_path(), $backupPath . '/config');

        // Backup routes folder
        File::copyDirectory(base_path('routes'), $backupPath . '/routes');

        // Backup database migrations
        File::copyDirectory(database_path('migrations'), $backupPath . '/migrations');
    }

    /**
     * Download update file
     */
    private function downloadUpdate(string $url, string $destination, ?string $expectedHash = null): void
    {
        // Handle file:// URLs for manual uploads
        if (str_starts_with($url, 'file://')) {
            $localPath = substr($url, 7);
            if (!File::exists($localPath)) {
                throw new \Exception('Local update file not found');
            }
            File::copy($localPath, $destination);
        } else {
            // Download from remote server
            $response = Http::timeout(300)
                ->withHeaders([
                    'X-License-Key' => $this->getLicenseKey() ?? '',
                ])
                ->get($url);

            if (!$response->successful()) {
                throw new \Exception('Failed to download update file. Server returned: ' . $response->status());
            }

            File::put($destination, $response->body());
        }

        if (!File::exists($destination) || File::size($destination) < 1000) {
            throw new \Exception('Downloaded file is invalid or corrupted');
        }
        
        // Verify file integrity if hash provided
        if ($expectedHash && !$this->verifyFileIntegrity($destination, $expectedHash)) {
            File::delete($destination);
            throw new \Exception('Update file integrity check failed. The file may be corrupted.');
        }
    }

    /**
     * Extract update zip file
     */
    private function extractUpdate(string $zipPath, string $extractPath): void
    {
        File::ensureDirectoryExists($extractPath);

        $zip = new ZipArchive();
        $result = $zip->open($zipPath);

        if ($result !== true) {
            throw new \Exception('Failed to open update archive');
        }

        $zip->extractTo($extractPath);
        $zip->close();
    }

    /**
     * Apply update files
     */
    private function applyUpdate(string $extractPath): void
    {
        // Find the actual content directory (might be nested)
        $contentPath = $extractPath;
        $dirs = File::directories($extractPath);
        
        if (count($dirs) === 1) {
            $contentPath = $dirs[0];
        }

        // Copy files, excluding certain directories
        $excludeDirs = ['vendor', 'node_modules', 'storage', '.git', '.env'];
        
        $this->copyDirectory($contentPath, base_path(), $excludeDirs);
    }

    /**
     * Copy directory recursively with exclusions
     */
    private function copyDirectory(string $source, string $destination, array $exclude = []): void
    {
        if (!File::isDirectory($source)) {
            return;
        }

        File::ensureDirectoryExists($destination);

        foreach (File::allFiles($source, true) as $file) {
            $relativePath = $file->getRelativePath();
            
            // Check if path should be excluded
            $shouldExclude = false;
            foreach ($exclude as $excludeDir) {
                if (str_starts_with($relativePath, $excludeDir) || $file->getFilename() === $excludeDir) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude) {
                continue;
            }

            $destPath = $destination . '/' . $file->getRelativePathname();
            File::ensureDirectoryExists(dirname($destPath));
            File::copy($file->getPathname(), $destPath);
        }
    }

    /**
     * Restore from backup
     */
    public function restoreBackup(string $backupPath): bool
    {
        try {
            if (File::exists($backupPath . '/.env')) {
                File::copy($backupPath . '/.env', base_path('.env'));
            }

            if (File::isDirectory($backupPath . '/app')) {
                File::copyDirectory($backupPath . '/app', app_path());
            }

            if (File::isDirectory($backupPath . '/config')) {
                File::copyDirectory($backupPath . '/config', config_path());
            }

            if (File::isDirectory($backupPath . '/routes')) {
                File::copyDirectory($backupPath . '/routes', base_path('routes'));
            }

            $this->clearCaches();

            return true;
        } catch (\Exception $e) {
            Log::error('Backup restore failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear all caches
     */
    private function clearCaches(): void
    {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        
        // Fix permissions after clearing caches
        $this->fixPermissions();
    }

    /**
     * Fix storage and cache directory permissions
     * This prevents permission errors after updates
     */
    public function fixPermissions(): bool
    {
        $directories = [
            storage_path(),
            storage_path('app'),
            storage_path('app/public'),
            storage_path('app/updates'),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            storage_path('backups'),
            base_path('bootstrap/cache'),
        ];

        $fixed = 0;
        foreach ($directories as $dir) {
            try {
                // Create directory if it doesn't exist
                if (!File::isDirectory($dir)) {
                    File::makeDirectory($dir, 0775, true);
                    Log::info("Created directory: {$dir}");
                }
                
                // Try to set permissions (may fail on some hosts)
                if (function_exists('chmod')) {
                    @chmod($dir, 0775);
                    $fixed++;
                }
            } catch (\Exception $e) {
                Log::warning("Could not fix permissions for {$dir}: " . $e->getMessage());
            }
        }

        // Create .gitignore files to prevent git from tracking cache files
        $gitignoreContent = "*\n!.gitignore\n";
        $gitignoreDirs = [
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];

        foreach ($gitignoreDirs as $dir) {
            $gitignorePath = $dir . '/.gitignore';
            if (File::isDirectory($dir) && !File::exists($gitignorePath)) {
                try {
                    File::put($gitignorePath, $gitignoreContent);
                } catch (\Exception $e) {
                    // Ignore
                }
            }
        }

        Log::info("Fixed permissions for {$fixed} directories");
        return $fixed > 0;
    }

    /**
     * Get list of available backups
     */
    public function getBackups(): array
    {
        $backupDir = storage_path('backups');
        
        if (!File::isDirectory($backupDir)) {
            return [];
        }

        $backups = [];
        foreach (File::directories($backupDir) as $dir) {
            $name = basename($dir);
            if (str_starts_with($name, 'pre-update-')) {
                $backups[] = [
                    'name' => $name,
                    'path' => $dir,
                    'date' => File::lastModified($dir),
                    'size' => $this->getDirectorySize($dir),
                ];
            }
        }

        // Sort by date descending
        usort($backups, fn($a, $b) => $b['date'] - $a['date']);

        return $backups;
    }

    /**
     * Delete a backup
     */
    public function deleteBackup(string $backupName): bool
    {
        $backupPath = storage_path('backups/' . $backupName);
        
        if (File::isDirectory($backupPath) && str_starts_with($backupName, 'pre-update-')) {
            File::deleteDirectory($backupPath);
            return true;
        }

        return false;
    }

    /**
     * Get directory size
     */
    private function getDirectorySize(string $path): int
    {
        $size = 0;
        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }
}
