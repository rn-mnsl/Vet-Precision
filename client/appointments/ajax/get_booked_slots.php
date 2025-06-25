<?php
// ajax/get_booked_slots.php

require_once '../../../config/init.php'; // Adjust path as needed

header('Content-Type: application/json');

// Get the date from the query string (e.g., ?date=2025-07-03)
$date = $_GET['date'] ?? null;

if (!$date) {
    echo json_encode([]); // Return empty array if no date is provided
    exit();
}

$booked_slots = [];

try {
    // We select times for appointments that are NOT cancelled.
    $sql = "SELECT appointment_time FROM appointments WHERE appointment_date = ? AND status != 'cancelled'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    
    // Fetch all booked times as a simple array
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    // We can directly use the results
    $booked_slots = $results;

} catch (PDOException $e) {
    // In a real app, log this error. For now, return an empty array on failure.
    // This prevents the front-end from breaking if the database query fails.
    echo json_encode([]);
    exit();
}

echo json_encode($booked_slots);