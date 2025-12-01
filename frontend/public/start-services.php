<?php
/**
 * Service Startup Trigger
 * This file is called when user visits localhost:3000
 * It triggers the batch file to start all services in background
 */

// Don't output anything - just trigger the startup
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

// Check if services are already running
$ports = [8000, 8001, 8002, 8003];
$allRunning = true;

foreach ($ports as $port) {
    $fp = @fsockopen('localhost', $port, $errno, $errstr, 1);
    if (!$fp) {
        $allRunning = false;
        break;
    } else {
        fclose($fp);
    }
}

// If services are running, just return success
if ($allRunning) {
    http_response_code(200);
    echo json_encode(['status' => 'services_running', 'message' => 'All services are running']);
    exit;
}

// Otherwise, trigger startup
// Use exec with nohup to run in background (Linux/Mac) or cmd.exe (Windows)
$batch_file = __DIR__ . '/../../startup_services.bat';

if (PHP_OS_FAMILY === 'Windows') {
    // Windows: use cmd.exe to start the batch file in minimized mode
    $cmd = 'start "" /MIN "' . $batch_file . '"';
    shell_exec($cmd);
    // Also try with cmd /c as backup
    pclose(popen($cmd, 'r'));
} else {
    // Linux/Mac: use bash
    $cmd = 'nohup bash "' . str_replace('"', '\\"', $batch_file) . '" > /dev/null 2>&1 &';
    shell_exec($cmd);
}

http_response_code(200);
echo json_encode([
    'status' => 'startup_triggered',
    'message' => 'Services are starting in background...',
    'batch_file' => $batch_file
]);

?>
