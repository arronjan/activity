<?php
require_once 'data_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $booking_id = $input['booking_id'] ?? null;
    $status = $input['status'] ?? null;

    if (!$booking_id || !$status) {
        echo json_encode([
            'success' => false,
            'message' => 'Booking ID and status are required'
        ]);
        exit;
    }

    // Validate status
    $valid_statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
    if (!in_array($status, $valid_statuses)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid status'
        ]);
        exit;
    }

    $conn = getDBConnection();

    // Check if trying to cancel within 3 days
    if ($status === 'Cancelled') {
        $stmt = $conn->prepare("SELECT booking_date FROM booking WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $booking = $result->fetch_assoc();
            $booking_date = new DateTime($booking['booking_date']);
            $today = new DateTime();
            $interval = $today->diff($booking_date);

            if ($interval->days < 3 && $interval->invert == 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cancellation must be at least 3 days before the booking date'
                ]);
                $stmt->close();
                $conn->close();
                exit;
            }
        }
    }

    // Update booking status
    $stmt = $conn->prepare("UPDATE booking SET status = ? WHERE booking_id = ?");
    $stmt->bind_param("si", $status, $booking_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Booking updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update booking: ' . $stmt->error
        ]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>