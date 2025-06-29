<?php
require_once '../../config/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode([]);
    exit();
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$notifications = getRecentNotifications(getCurrentUserId(), $limit);

foreach ($notifications as &$n) {
    $n['message'] = sanitize($n['message']);
    $n['created_at'] = date('M d, Y h:i A', strtotime($n['created_at']));
}
unset($n);

echo json_encode($notifications);