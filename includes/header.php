<?php require_once dirname(__DIR__) . '/config/init.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Vet Precision'; ?></title>
    <?php include 'favicon.php'; ?>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <?php if (isset($extraCSS)) echo $extraCSS; ?>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <?php if ($flash = getFlash()): ?>
    <div class="alert alert-<?php echo $flash['type']; ?>">
        <?php echo sanitize($flash['message']); ?>
    </div>
    <?php endif; ?>
