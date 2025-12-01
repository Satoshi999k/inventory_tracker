<?php
// Inventory Tracker Router for PHP Built-in Server
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_uri = trim($request_uri, '/');

// Remove leading 'frontend/' if present
$request_uri = preg_replace('/^frontend\//', '', $request_uri);

// Serve static files from public folder
$public_dir = __DIR__ . '/public';

// Function to check if services are running
function areServicesRunning() {
    $ports = [8000, 8001, 8002, 8003]; // Check API Gateway and services
    foreach ($ports as $port) {
        $fp = @fsockopen('localhost', $port, $errno, $errstr, 1);
        if (!$fp) {
            return false; // At least one service not running
        } else {
            fclose($fp);
        }
    }
    return true; // All services running
}

// If requesting root and services not running, show startup page
if (($request_uri === '' || $request_uri === '/') && !areServicesRunning()) {
    $file = $public_dir . '/start-check.html';
    if (file_exists($file)) {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        readfile($file);
        return true;
    }
}

// If requesting a static file
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot|ico)$/', $request_uri)) {
    $file = $public_dir . '/' . $request_uri;
    if (file_exists($file)) {
        // Set proper content type
        $mime_types = array(
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'ico' => 'image/x-icon'
        );
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $content_type = isset($mime_types[$ext]) ? $mime_types[$ext] : 'application/octet-stream';
        header('Content-Type: ' . $content_type);
        readfile($file);
        return true;
    }
}

// Handle login route
if ($request_uri === 'login' || $request_uri === 'login.html') {
    $file = $public_dir . '/login.html';
    if (file_exists($file)) {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        readfile($file);
        return true;
    }
}

// Handle startup check route
if ($request_uri === 'startup' || $request_uri === 'start-check') {
    $file = $public_dir . '/start-check.html';
    if (file_exists($file)) {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        readfile($file);
        return true;
    }
}

// Route to HTML pages
$pages = ['products', 'inventory', 'sales'];
foreach ($pages as $page) {
    if ($request_uri === $page || strpos($request_uri, $page . '/') === 0) {
        $file = $public_dir . '/' . $page . '.html';
        if (file_exists($file)) {
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Pragma: no-cache");
            readfile($file);
            return true;
        }
    }
}

// Default to index.html for root and any unmatched routes
$index_file = $public_dir . '/index.html';
if (file_exists($index_file)) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    readfile($index_file);
    return true;
}

// If index.html doesn't exist, show error
http_response_code(404);
echo "Files not found";
return false;
?>
