<?php
// Inventory Tracker Admin Dashboard Router
// Add cache-busting headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_uri = trim($request_uri, '/');

// Remove 'public/' if it's in the path
$request_uri = str_replace('public/', '', $request_uri);

// Handle static files (CSS, JS, etc) FIRST
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot|ico)$/', $request_uri)) {
    $file = __DIR__ . '/' . $request_uri;
    if (file_exists($file)) {
        // Determine content type
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
        
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $content_type = isset($mime_types[$ext]) ? $mime_types[$ext] : 'application/octet-stream';
        
        header('Content-Type: ' . $content_type);
        // For JS files, add cache busting
        if ($ext === 'js') {
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        }
        readfile($file);
        exit;
    }
}

// Default to index.html
if ($request_uri === '' || $request_uri === 'index.php') {
    $file = __DIR__ . '/index.html';
    if (file_exists($file)) {
        readfile($file);
    } else {
        http_response_code(404);
        echo 'index.html not found';
    }
    exit;
}

// Route to specific pages
$pages = ['products', 'inventory', 'sales'];
foreach ($pages as $page) {
    if (strpos($request_uri, $page) === 0) {
        $file = __DIR__ . '/' . $page . '.html';
        if (file_exists($file)) {
            readfile($file);
            exit;
        }
    }
}

// If nothing matches, return 404
http_response_code(404);
$file = __DIR__ . '/index.html';
if (file_exists($file)) {
    readfile($file);
} else {
    echo 'Not Found';
}
?>
