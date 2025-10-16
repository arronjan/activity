<?php
require_once 'data_config.php';

$court_id = $_GET['court_id'] ?? null;
$date = $_GET['date'] ?? null;
$start_time = $_GET['start_time'] ?? null;
$end_time = $_GET['end_time'] ?? null;

if (!$court_id || !$date || !$start_time || !$end_time) {
    echo json_encode([
        'available' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$conn = getDBConnection();

$stmt = $conn->prepare("
    SELECT booking_id FROM booking 
    WHERE court_id = ? 
    AND booking_date = ? 
    AND status != 'Cancelled'
    AND (
        (start_time < ? AND end_time > ?) OR
        (start_time < ? AND end_time > ?) OR
        (start_time >= ? AND end_time <= ?)
    )
");

$stmt->bind_param(
    "isssssss",
    $court_id,
    $date,
    $end_time,
    $start_time,
    $end_time,
    $start_time,
    $start_time,
    $end_time
);

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        'available' => false,
        'message' => 'Time slot is not available'
    ]);
} else {
    echo json_encode([
        'available' => true,
        'message' => 'Time slot is available'
    ]);
}

$stmt->close();
$conn->close();
?>