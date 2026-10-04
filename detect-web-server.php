<?php
/**
 * Detect the correct web server URL for AMPPS
 */

echo "Detecting web server configuration...\n\n";

// Common AMPPS ports
$ports = [80, 8080, 8000, 3000];
$paths = [
    '/shop/update-server-example/index.php',
    '/update-server-example/index.php'
];

foreach ($ports as $port) {
    foreach ($paths as $path) {
        $url = "http://localhost:$port$path?action=health";
        echo "Testing: $url\n";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if ($response !== false) {
            echo "SUCCESS! Found working server at: http://localhost:$port$path\n";
            echo "Response: $response\n\n";
            
            // Update the .env file
            $envFile = '.env';
            $envContent = file_get_contents($envFile);
            $newUrl = "http://localhost:$port$path";
            
            if (strpos($envContent, 'UPDATE_SERVER_URL=') !== false) {
                $envContent = preg_replace('/UPDATE_SERVER_URL=.*/', "UPDATE_SERVER_URL=$newUrl", $envContent);
            } else {
                $envContent .= "\nUPDATE_SERVER_URL=$newUrl\n";
            }
            
            file_put_contents($envFile, $envContent);
            echo "Updated .env file with: UPDATE_SERVER_URL=$newUrl\n";
            exit(0);
        } else {
            echo "Failed\n";
        }
    }
}

echo "\nNo working web server found on common ports.\n";
echo "Please make sure AMPPS is running and try manually:\n";
echo "1. Start AMPPS\n";
echo "2. Check what port Apache is running on\n";
echo "3. Update UPDATE_SERVER_URL in .env file manually\n";