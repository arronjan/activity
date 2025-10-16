<?php
require_once 'data_config.php';

$conn = getDBConnection();

$stats = [];

// Total bookings (all time)
$sql = "SELECT COUNT(*) as count FROM booking";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$stats['total_bookings'] = $row['count'] ?? 0;

// Pending bookings
$sql = "SELECT COUNT(*) as count FROM booking WHERE status = 'Pending'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$stats['pending_bookings'] = $row['count'] ?? 0;

// Revenue this month
$sql = "SELECT 
        SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_minutes
        FROM booking 
        WHERE MONTH(booking_date) = MONTH(CURDATE())
        AND YEAR(booking_date) = YEAR(CURDATE())
        AND status IN ('Confirmed', 'Completed')";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$total_minutes = $row['total_minutes'] ?? 0;
$total_hours = $total_minutes / 60;
$stats['revenue_month'] = $total_hours * 250; // ₱250 per hour

// Confirmed bookings today
$sql = "SELECT COUNT(*) as count FROM booking 
        WHERE booking_date = CURDATE() 
        AND status = 'Confirmed'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$stats['confirmed_today'] = $row['count'] ?? 0;

echo json_encode([
    'success' => true,
    'stats' => $stats
]);

$conn->close();
?>