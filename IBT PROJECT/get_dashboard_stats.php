<?php
require_once 'data_config.php';

$conn = getDBConnection();

// Get user_id from query parameter 
$user_id = $_GET['user_id'] ?? null;
$role = $_GET['role'] ?? 'Member';

$stats = [];

//ACTIVE BOOKINGS
if ($role === 'Admin') {
    // Admin sees ALL active bookings
    $sql = "SELECT COUNT(*) as count FROM booking 
            WHERE status IN ('Pending', 'Confirmed') 
            AND booking_date >= CURDATE()";
} else {
    // Members see only THEIR active bookings
    $sql = "SELECT COUNT(*) as count FROM booking 
            WHERE user_id = ? 
            AND status IN ('Pending', 'Confirmed') 
            AND booking_date >= CURDATE()";
}

if ($role === 'Admin') {
    $result = $conn->query($sql);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

$row = $result->fetch_assoc();
$stats['active_bookings'] = $row['count'] ?? 0;

// AVAILABLE COURTS (Currently available)
$sql = "SELECT COUNT(*) as count FROM court WHERE availability_status = 'Available'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$stats['available_courts'] = $row['count'] ?? 0;

// HOURS BOOKED 
if ($role === 'Admin') {
    // Admin: Hours booked TODAY
    $sql = "SELECT 
            SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_minutes
            FROM booking 
            WHERE booking_date = CURDATE() 
            AND status IN ('Pending', 'Confirmed', 'Completed')";
} else {
    // Members Their total hours THIS MONTH
    $sql = "SELECT 
            SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_minutes
            FROM booking 
            WHERE user_id = ?
            AND MONTH(booking_date) = MONTH(CURDATE())
            AND YEAR(booking_date) = YEAR(CURDATE())
            AND status IN ('Pending', 'Confirmed', 'Completed')";
}

if ($role === 'Admin') {
    $result = $conn->query($sql);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

$row = $result->fetch_assoc();
$total_minutes = $row['total_minutes'] ?? 0;
$stats['hours_booked'] = round($total_minutes / 60, 1); // Convert to hours

// 4. REVENUE (Admin only - Today's revenue)
if ($role === 'Admin') {
    // Calculate based on bookings (₱250 per hour)
    $sql = "SELECT 
            SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_minutes
            FROM booking 
            WHERE booking_date = CURDATE()
            AND status IN ('Confirmed', 'Completed')";

    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $total_minutes = $row['total_minutes'] ?? 0;
    $total_hours = $total_minutes / 60;
    $stats['revenue_today'] = $total_hours * 250; // ₱250 per hour
} else {
    // Members: Total spent this month
    $sql = "SELECT 
            SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_minutes
            FROM booking 
            WHERE user_id = ?
            AND MONTH(booking_date) = MONTH(CURDATE())
            AND YEAR(booking_date) = YEAR(CURDATE())
            AND status IN ('Confirmed', 'Completed')";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_minutes = $row['total_minutes'] ?? 0;
    $total_hours = $total_minutes / 60;
    $stats['total_spent'] = $total_hours * 250; // ₱250 per hour
}

// ADDITIONAL STATS
// Total users (Admin only)
if ($role === 'Admin') {
    $sql = "SELECT COUNT(*) as count FROM user WHERE role = 'Member'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $stats['total_members'] = $row['count'] ?? 0;

    // Bookings this month
    $sql = "SELECT COUNT(*) as count FROM booking 
            WHERE MONTH(booking_date) = MONTH(CURDATE())
            AND YEAR(booking_date) = YEAR(CURDATE())";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $stats['bookings_this_month'] = $row['count'] ?? 0;

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
    $stats['revenue_this_month'] = $total_hours * 250;
}

// 6. UPCOMING BOOKINGS COUNT (Next 7 days)
if ($role === 'Admin') {
    $sql = "SELECT COUNT(*) as count FROM booking 
            WHERE booking_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND status IN ('Pending', 'Confirmed')";
    $result = $conn->query($sql);
} else {
    $sql = "SELECT COUNT(*) as count FROM booking 
            WHERE user_id = ?
            AND booking_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND status IN ('Pending', 'Confirmed')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}
$row = $result->fetch_assoc();
$stats['upcoming_bookings'] = $row['count'] ?? 0;

// Return all stats
echo json_encode([
    'success' => true,
    'stats' => $stats,
    'role' => $role
]);

$conn->close();
?>