<?php
// filepath: c:\xampp\htdocs\Vet-Precision\includes\favicon.php
// Determine the correct path to assets based on current location
$favicon_path = '';
$current_dir = dirname($_SERVER['PHP_SELF']);

// Count directory levels to determine relative path
$levels = substr_count($current_dir, '/') - 1;
if ($levels > 0) {
    $favicon_path = str_repeat('../', $levels);
}
$favicon_path .= 'assets/images/vet-precision-logo-half.png';
?>
<link rel="icon" type="image/png" href="<?php echo $favicon_path; ?>">
<link rel="shortcut icon" href="<?php echo $favicon_path; ?>">
<link rel="apple-touch-icon" href="<?php echo $favicon_path; ?>">