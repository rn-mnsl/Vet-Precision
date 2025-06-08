<?php
// Application constants
define('SITE_NAME', 'Vet Precision');

$scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];         // localhost *or* vet-precision.roanmanansala.com
$base   = '/vet-precision';              // folder where the app lives
define('SITE_URL', "$scheme://$host$base");

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
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Date format
define('DATE_FORMAT', 'F j, Y');
define('TIME_FORMAT', 'g:i A');
define('DATETIME_FORMAT', 'F j, Y g:i A');
?>
