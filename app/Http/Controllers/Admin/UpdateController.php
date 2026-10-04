<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UpdateService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UpdateController extends Controller
{
    protected UpdateService $updateService;

    public function __construct(UpdateService $updateService)
    {
        $this->updateService = $updateService;
    }

    /**
     * Show update settings page
     */
    public function index()
    {
        $currentVersion = $this->updateService->getCurrentVersion();
        $author = $this->updateService->getAuthor();
        $authorUrl = $this->updateService->getAuthorUrl();
        $updateInfo = $this->updateService->checkForUpdates();
        $backups = $this->updateService->getBackups();

        return Inertia::render('Admin/Settings/Updates', [
            'currentVersion' => $currentVersion,
            'author' => $author,
            'authorUrl' => $authorUrl,
            'updateInfo' => [
                'error' => $updateInfo['error'] ?? null,
                'available' => (bool) ($updateInfo['available'] ?? false),
                'latest_version' => $updateInfo['latest_version'] ?? null,
                'release_date' => $updateInfo['release_date'] ?? null,
                'file_size' => $updateInfo['file_size'] ?? null,
                'min_php_version' => $updateInfo['min_php_version'] ?? null,
                'changelog' => array_values((array) ($updateInfo['changelog'] ?? [])),
                'breaking_changes' => array_values((array) ($updateInfo['breaking_changes'] ?? [])),
                'upgrade_notes' => $updateInfo['upgrade_notes'] ?? null,
                'has_hash' => !empty($updateInfo['file_hash']),
                'checked_at' => $updateInfo['checked_at'] ?? null,
            ],
            'backups' => collect($backups)->map(fn ($b) => [
                'name' => $b['name'],
                'date' => date('M d, Y H:i', $b['date']),
                'size' => round($b['size'] / 1024 / 1024, 2),
            ])->values(),
            'system' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'server' => php_uname('s'),
                'database' => config('database.default'),
            ],
        ]);
    }

    /**
     * Check for updates (AJAX)
     */
    public function check()
    {
        $updateInfo = $this->updateService->checkForUpdates(true);
        
        return response()->json($updateInfo);
    }

    /**
     * Install update
     */
    public function install(Request $request)
    {
        $updateInfo = $this->updateService->checkForUpdates();

        if (!$updateInfo['available'] || !$updateInfo['download_url']) {
            return back()->with('error', 'No update available or download URL not found.');
        }

        // Check PHP version requirement
        if (isset($updateInfo['min_php_version']) && version_compare(PHP_VERSION, $updateInfo['min_php_version'], '<')) {
            return back()->with('error', "This update requires PHP {$updateInfo['min_php_version']} or higher. You have PHP " . PHP_VERSION);
        }
        
        // Check Laravel version requirement
        if (isset($updateInfo['min_laravel_version']) && version_compare(app()->version(), $updateInfo['min_laravel_version'], '<')) {
            return back()->with('error', "This update requires Laravel {$updateInfo['min_laravel_version']} or higher. You have Laravel " . app()->version());
        }
        
        // Check for breaking changes warning
        if (!empty($updateInfo['breaking_changes']) && !$request->has('confirm_breaking')) {
            return back()->with('warning', 'This update contains breaking changes. Please review the changelog and confirm the update.');
        }

        $result = $this->updateService->installUpdate(
            $updateInfo['download_url'],
            $updateInfo['file_hash'] ?? null
        );

        if ($result['success']) {
            $message = $result['message'];
            if (!empty($updateInfo['upgrade_notes'])) {
                $message .= ' Note: ' . $updateInfo['upgrade_notes'];
            }
            return redirect()->route('admin.settings.updates')
                ->with('success', $message);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Restore from backup
     */
    public function restore(Request $request)
    {
        $request->validate([
            'backup' => ['required', 'string', 'regex:/^pre-update-[A-Za-z0-9._-]+$/'],
        ]);

        $backupPath = storage_path('backups/' . $request->backup);
        $root = realpath(storage_path('backups'));
        $real = realpath($backupPath);

        // must be a real folder directly inside storage/backups
        if ($root === false || $real === false || !is_dir($real) || dirname($real) !== $root) {
            return back()->with('error', 'Backup not found.');
        }

        $success = $this->updateService->restoreBackup($backupPath);

        if ($success) {
            return back()->with('success', 'Backup restored successfully!');
        }

        return back()->with('error', 'Failed to restore backup.');
    }

    /**
     * Delete backup
     */
    public function deleteBackup(Request $request)
    {
        $request->validate([
            'backup' => ['required', 'string', 'regex:/^pre-update-[A-Za-z0-9._-]+$/'],
        ]);

        $success = $this->updateService->deleteBackup($request->backup);

        if ($success) {
            return back()->with('success', 'Backup deleted successfully!');
        }

        return back()->with('error', 'Failed to delete backup.');
    }

    /**
     * Fix storage permissions
     */
    public function fixPermissions()
    {
        $success = $this->updateService->fixPermissions();

        if ($success) {
            return back()->with('success', 'Permissions fixed successfully!');
        }

        return back()->with('error', 'Could not fix permissions. You may need to do this via SSH or your hosting control panel.');
    }

    /**
     * Manual upload update
     */
    public function uploadUpdate(Request $request)
    {
        $request->validate([
            'update_file' => 'required|file|mimes:zip|max:102400', // 100MB max
        ]);

        $tempPath = storage_path('app/updates');
        $zipPath = $tempPath . '/manual-update.zip';

        // Ensure directory exists
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        // Move uploaded file
        $request->file('update_file')->move($tempPath, 'manual-update.zip');

        // Create a fake download URL for the service
        $result = $this->updateService->installUpdate('file://' . $zipPath);

        if ($result['success']) {
            return redirect()->route('admin.settings.updates')
                ->with('success', 'Update installed successfully from uploaded file!');
        }

        return back()->with('error', $result['message']);
    }
}
