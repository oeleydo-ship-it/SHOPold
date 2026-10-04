<?php
/**
 * Laravel Shop Update Server
 * 
 * A production-ready update server with license verification,
 * version management, and secure download handling.
 * 
 * Host this on your update server (e.g., updates.yoursite.com)
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// CORS and headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-License-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============================================================================
// CONFIGURATION - Update these values for each release
// ============================================================================
$config = [
    // Current latest version
    'latest_version' => '1.3.5',
    
    // Minimum PHP version required
    'min_php_version' => '8.2',
    
    // Minimum Laravel version required
    'min_laravel_version' => '11.0',
    
    // Release date
    'release_date' => '2025-12-11',
    
    // Download base URL (where zip files are stored)
    'download_base_url' => 'https://updates.codearena.dev/updateserver/releases/',
    
    // Enable license verification (set to true in production)
    'require_license' => false,
    
    // Valid license keys (in production, use database)
    'valid_licenses' => [
        'DEMO-LICENSE-KEY-12345',
        'EXTENDED-LICENSE-67890',
    ],
    
    // Rate limiting (requests per minute per IP)
    'rate_limit' => 30,
    
    // Changelog for current version
    'changelog' => [
        '1.3.5' => [
            'release_date' => '2025-12-11',
            'changes' => [
                'Fixed: Update server 404 errors',
                'Improved: Update system error handling',
                'Added: Better connection failure messages',
                'Enhanced: Mock server support for testing',
                'Updated: Configuration management',
            ],
            'breaking_changes' => [],
            'upgrade_notes' => 'Run migrations after update: php artisan migrate',
        ],
        '1.3.4' => [
            'release_date' => '2025-12-10',
            'changes' => [
                'Current: Mobile display settings with custom typography',
                'Current: Desktop typography customization',
                'Current: Enhanced mobile side menu navigation',
                'Current: Driver portal with earnings tracking',
                'Current: Checkout process with Stripe integration',
                'Current: Multi-language support',
                'Current: Various bugs and performance improvements',
                'Current: Stock management system',
                'Current: Admin dashboard widgets',
            ],
            'breaking_changes' => [],
            'upgrade_notes' => 'Run migrations after update: php artisan migrate',
        ],
        '1.2.0' => [
            'release_date' => '2025-12-15',
            'changes' => [
                'New: Mobile display settings with custom typography',
                'New: Desktop typography customization',
                'New: Enhanced mobile side menu navigation',
                'New: Driver portal with earnings tracking',
                'Improved: Checkout process with Stripe integration',
                'Improved: Multi-language support',
                'Fixed: Various bugs and performance improvements',
                'Added: Stock management system',
                'Added: New admin dashboard widgets',
            ],
            'breaking_changes' => [],
            'upgrade_notes' => 'Run migrations after update: php artisan migrate',
        ],
        '1.1.0' => [
            'release_date' => '2025-11-01',
            'changes' => [
                'Added multi-vendor support',
                'Added product variations',
                'Improved admin dashboard',
                'Bug fixes',
            ],
            'breaking_changes' => [],
            'upgrade_notes' => '',
        ],
        '1.0.0' => [
            'release_date' => '2025-10-01',
            'changes' => [
                'Initial release',
            ],
            'breaking_changes' => [],
            'upgrade_notes' => '',
        ],
    ],
    
    // Version history for rollback support
    'available_versions' => ['1.3.5', '1.3.4', '1.2.0', '1.1.0', '1.0.0'],
];

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Send JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Log request for analytics
 */
function logRequest($action, $data = []) {
    $logFile = __DIR__ . '/logs/' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'action' => $action,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'data' => $data,
    ];
    
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Verify license key
 */
function verifyLicense($licenseKey, $config) {
    if (!$config['require_license']) {
        return true;
    }
    
    if (empty($licenseKey)) {
        return false;
    }
    
    // In production, verify against database
    return in_array($licenseKey, $config['valid_licenses']);
}

/**
 * Check rate limit
 */
function checkRateLimit($config) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $cacheFile = __DIR__ . '/cache/rate_' . md5($ip) . '.json';
    $cacheDir = dirname($cacheFile);
    
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $now = time();
    $windowStart = $now - 60; // 1 minute window
    
    $requests = [];
    if (file_exists($cacheFile)) {
        $requests = json_decode(file_get_contents($cacheFile), true) ?: [];
    }
    
    // Remove old requests
    $requests = array_filter($requests, fn($t) => $t > $windowStart);
    
    if (count($requests) >= $config['rate_limit']) {
        return false;
    }
    
    $requests[] = $now;
    file_put_contents($cacheFile, json_encode($requests));
    
    return true;
}

/**
 * Get file hash for integrity verification
 */
function getFileHash($filePath) {
    if (!file_exists($filePath)) {
        return null;
    }
    return hash_file('sha256', $filePath);
}

// ============================================================================
// RATE LIMITING
// ============================================================================
if (!checkRateLimit($config)) {
    jsonResponse(['error' => 'Rate limit exceeded. Please try again later.'], 429);
}

// ============================================================================
// REQUEST HANDLING
// ============================================================================
$action = $_GET['action'] ?? 'check';
$currentVersion = $_GET['version'] ?? '1.0.0';
$licenseKey = $_GET['license'] ?? $_SERVER['HTTP_X_LICENSE_KEY'] ?? '';

// Log the request
logRequest($action, [
    'version' => $currentVersion,
    'license' => substr($licenseKey, 0, 10) . '...',
]);

switch ($action) {
    // ========================================================================
    // CHECK FOR UPDATES
    // ========================================================================
    case 'check':
        $updateAvailable = version_compare($currentVersion, $config['latest_version'], '<');
        $latestChangelog = $config['changelog'][$config['latest_version']] ?? [];
        
        $response = [
            'success' => true,
            'update_available' => $updateAvailable,
            'latest_version' => $config['latest_version'],
            'current_version' => $currentVersion,
            'min_php_version' => $config['min_php_version'],
            'min_laravel_version' => $config['min_laravel_version'],
            'release_date' => $config['release_date'],
            'changelog' => $latestChangelog['changes'] ?? [],
            'breaking_changes' => $latestChangelog['breaking_changes'] ?? [],
            'upgrade_notes' => $latestChangelog['upgrade_notes'] ?? '',
            'download_url' => $updateAvailable ? $config['download_base_url'] . 'shop_' . $config['latest_version'] . '.zip' : null,
            'requires_license' => $config['require_license'],
        ];
        
        // Add file hash for integrity verification
        $zipFile = __DIR__ . '/releases/shop_' . $config['latest_version'] . '.zip';
        if (file_exists($zipFile)) {
            $response['file_hash'] = getFileHash($zipFile);
            $response['file_size'] = filesize($zipFile);
        }
        // Note: File doesn't exist locally, but it's available on the remote server
        // So we keep update_available as determined by version comparison
        
        jsonResponse($response);
        break;

    // ========================================================================
    // GET VERSION HISTORY
    // ========================================================================
    case 'versions':
        $versions = [];
        foreach ($config['available_versions'] as $version) {
            $changelog = $config['changelog'][$version] ?? [];
            $versions[] = [
                'version' => $version,
                'release_date' => $changelog['release_date'] ?? 'Unknown',
                'changes' => $changelog['changes'] ?? [],
                'is_latest' => $version === $config['latest_version'],
            ];
        }
        
        jsonResponse([
            'success' => true,
            'versions' => $versions,
            'latest_version' => $config['latest_version'],
        ]);
        break;

    // ========================================================================
    // DOWNLOAD UPDATE
    // ========================================================================
    case 'download':
        // Verify license if required
        if ($config['require_license'] && !verifyLicense($licenseKey, $config)) {
            jsonResponse(['error' => 'Invalid or missing license key'], 403);
        }
        
        $requestedVersion = $_GET['v'] ?? $config['latest_version'];
        
        // Validate version
        if (!in_array($requestedVersion, $config['available_versions'])) {
            jsonResponse(['error' => 'Invalid version requested'], 400);
        }
        
        $file = __DIR__ . '/releases/shop_' . $requestedVersion . '.zip';
        
        if (!file_exists($file)) {
            jsonResponse(['error' => 'Update file not found. Please contact support.'], 404);
        }
        
        // Log download
        logRequest('download_complete', [
            'version' => $requestedVersion,
            'file_size' => filesize($file),
        ]);
        
        // Send file
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="shop_' . $requestedVersion . '.zip"');
        header('Content-Length: ' . filesize($file));
        header('X-File-Hash: ' . getFileHash($file));
        header('Cache-Control: no-cache, must-revalidate');
        
        readfile($file);
        exit;
        break;

    // ========================================================================
    // VERIFY LICENSE
    // ========================================================================
    case 'verify-license':
        $isValid = verifyLicense($licenseKey, $config);
        
        jsonResponse([
            'success' => true,
            'valid' => $isValid,
            'message' => $isValid ? 'License is valid' : 'Invalid license key',
        ]);
        break;

    // ========================================================================
    // GET CHANGELOG
    // ========================================================================
    case 'changelog':
        $version = $_GET['v'] ?? $config['latest_version'];
        $changelog = $config['changelog'][$version] ?? null;
        
        if (!$changelog) {
            jsonResponse(['error' => 'Changelog not found for this version'], 404);
        }
        
        jsonResponse([
            'success' => true,
            'version' => $version,
            'changelog' => $changelog,
        ]);
        break;

    // ========================================================================
    // HEALTH CHECK
    // ========================================================================
    case 'health':
        jsonResponse([
            'success' => true,
            'status' => 'healthy',
            'server_time' => date('Y-m-d H:i:s'),
            'latest_version' => $config['latest_version'],
        ]);
        break;

    // ========================================================================
    // INVALID ACTION
    // ========================================================================
    default:
        jsonResponse(['error' => 'Invalid action. Valid actions: check, versions, download, verify-license, changelog, health'], 400);
}
