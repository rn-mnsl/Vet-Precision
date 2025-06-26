<?php
require_once '../../config/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit();
}

markNotificationsRead(getCurrentUserId());

echo json_encode(['success' => true]);
>