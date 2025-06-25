<?php
// ajax/get_booked_slots.php
require_once '../../../config/init.php';

header('Content-Type: application/json');

$date = $_GET['date'] ?? null;

// We no longer need staff_id from the form
if (!$date) {
    echo json_encode([]);
    exit();
}

try {
    // This query now checks for any appointment on the given date, regardless of staff.
    $sql = "SELECT appointment_time FROM appointments WHERE appointment_date = ? AND status != 'cancelled'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    $booked_slots = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    echo json_encode($booked_slots);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}