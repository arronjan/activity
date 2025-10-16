<?php
require_once 'data_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $user_id = $input['user_id'] ?? null;

    if (!$user_id) {
        echo json_encode([
            'success' => false,
            'message' => 'User ID is required'
        ]);
        exit;
    }

    $conn = getDBConnection();

    // Check if user has bookings
    $stmt = $conn->prepare("SELECT COUNT(*) as booking_count FROM booking WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['booking_count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete user with existing bookings. Please cancel their bookings first or keep the user account.'
        ]);
        $stmt->close();
        $conn->close();
        exit;
    }

    // Check if this is the last admin
    $stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM user WHERE role = 'Admin'");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt_role = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
    $stmt_role->bind_param("i", $user_id);
    $stmt_role->execute();
    $result_role = $stmt_role->get_result();

    if ($result_role->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        $stmt->close();
        $stmt_role->close();
        $conn->close();
        exit;
    }

    $user_role = $result_role->fetch_assoc();

    if ($user_role['role'] === 'Admin' && $row['admin_count'] <= 1) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete the last admin user. System must have at least one administrator.'
        ]);
        $stmt->close();
        $stmt_role->close();
        $conn->close();
        exit;
    }

    // Delete user
    $stmt = $conn->prepare("DELETE FROM user WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'User not found or already deleted'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete user: ' . $stmt->error
        ]);
    }

    $stmt->close();
    $stmt_role->close();
    $conn->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>