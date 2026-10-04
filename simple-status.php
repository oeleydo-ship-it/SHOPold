<?php
/**
 * Simple status checker that doesn't depend on Laravel
 */

header('Content-Type: application/json');

try {
    $status = [
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'installed' => file_exists(__DIR__ . '/storage/installed'),
        'vendor_exists' => file_exists(__DIR__ . '/vendor/autoload.php'),
        'env_exists' => file_exists(__DIR__ . '/.env'),
        'storage_writable' => is_writable(__DIR__ . '/storage'),
        'cache_writable' => is_writable(__DIR__ . '/bootstrap/cache'),
    ];

    // Check basic requirements
    $requirements = [
        'php_version_ok' => version_compare(PHP_VERSION, '8.2.0', '>='),
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'mbstring' => extension_loaded('mbstring'),
        'openssl' => extension_loaded('openssl'),
        'curl' => extension_loaded('curl'),
        'json' => extension_loaded('json'),
    ];

    $status['requirements'] = $requirements;
    $status['requirements_met'] = !in_array(false, $requirements, true);

    if ($status['installed'] && is_readable(__DIR__ . '/storage/installed')) {
        $installData = json_decode(file_get_contents(__DIR__ . '/storage/installed'), true);
        if ($installData) {
            $status['install_data'] = $installData;
        }
    }

    echo json_encode($status, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'error' => 'Status check failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>