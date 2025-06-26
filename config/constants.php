<?php
// Application constants for Lightsail Server
define('SITE_NAME', 'Vet Precision');

// Detect if we're on local development or production server
$isLocal = (
    $_SERVER['HTTP_HOST'] === 'localhost' || 
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
    strpos($_SERVER['HTTP_HOST'], '.local') !== false
);

if ($isLocal) {
    // Local development configuration
    $scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'];
    $base   = '/vet-precision';
    define('SITE_URL', "$scheme://$host$base");
} else {
    // Production server configuration
    // Use HTTP for now until SSL is fixed
    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host   = 'vet-precision.roanmanansala.com';
    $base   = '/vet-precision';
    define('SITE_URL', "$scheme://$host$base");
}

define('ADMIN_EMAIL', 'admin@vetprecision.com');

// Paths
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ASSETS_PATH', '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour

// Pagination
define('RECORDS_PER_PAGE', 10);

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp'
]); // <-- CORRECTED DEFINITION

// Date format
define('DATE_FORMAT', 'F j, Y');
define('TIME_FORMAT', 'g:i A');
define('DATETIME_FORMAT', 'F j, Y g:i A');
?>
