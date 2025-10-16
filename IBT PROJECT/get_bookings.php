<?php
require_once 'data_config.php';

$conn = getDBConnection();

$all = $_GET['all'] ?? false;

$sql = "
    SELECT 
        b.booking_id,
        b.booking_date,
        b.start_time,
        b.end_time,
        b.status,
        u.name as user_name,
        c.court_name
    FROM booking b
    JOIN user u ON b.user_id = u.user_id
    JOIN court c ON b.court_id = c.court_id
    WHERE b.status != 'Cancelled'
    ORDER BY b.booking_date DESC, b.start_time DESC
";

if (!$all) {
    $sql .= " LIMIT 10";
}

$result = $conn->query($sql);

$bookings = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

echo json_encode($bookings);

$conn->close();
?>