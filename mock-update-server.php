<?php
/**
 * Mock Update Server for Testing
 * This simulates an update server response without requiring a web server
 */

// Get the action from command line or default to 'check'
$action = $_GET['action'] ?? $argv[1] ?? 'check';
$version = $_GET['version'] ?? $argv[2] ?? '1.3.4';

$config = [
    'latest_version' => '1.3.4',
    'min_php_version' => '8.2',
    'min_laravel_version' => '11.0',
    'release_date' => '2025-12-11',
    'require_license' => false,
];

switch ($action) {
    case 'check':
        $updateAvailable = version_compare($version, $config['latest_version'], '<');
        
        $response = [
            'success' => true,
            'update_available' => false, // Always false for testing since we don't have actual update files
            'latest_version' => $config['latest_version'],
            'current_version' => $version,
            'min_php_version' => $config['min_php_version'],
            'min_laravel_version' => $config['min_laravel_version'],
            'release_date' => $config['release_date'],
            'changelog' => [
                'System is up to date',
                'No updates available at this time',
            ],
            'breaking_changes' => [],
            'upgrade_notes' => '',
            'download_url' => null,
            'requires_license' => $config['require_license'],
            'file_hash' => null,
            'file_size' => null,
        ];
        break;
        
    case 'health':
        $response = [
            'success' => true,
            'status' => 'healthy',
            'server_time' => date('Y-m-d H:i:s'),
            'latest_version' => $config['latest_version'],
        ];
        break;
        
    case 'versions':
        $response = [
            'success' => true,
            'versions' => [
                [
                    'version' => $config['latest_version'],
                    'release_date' => $config['release_date'],
                    'changes' => ['Current version'],
                    'is_latest' => true,
                ]
            ],
            'latest_version' => $config['latest_version'],
        ];
        break;
        
    default:
        $response = [
            'error' => 'Invalid action. Valid actions: check, health, versions'
        ];
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);