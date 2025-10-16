<?php
require_once 'data_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $user_id = $input['user_id'] ?? null;
    $name = $input['name'] ?? null;
    $email = $input['email'] ?? null;
    $role = $input['role'] ?? null;

    // Validate required fields
    if (!$user_id || !$name || !$email || !$role) {
        echo json_encode([
            'success' => false,
            'message' => 'All fields are required'
        ]);
        exit;
    }

    // Validate role
    if (!in_array($role, ['Admin', 'Member'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid role. Must be Admin or Member'
        ]);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email format'
        ]);
        exit;
    }

    $conn = getDBConnection();

    // Check if email already exists for another user
    $stmt = $conn->prepare("SELECT user_id FROM user WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Email already in use by another user'
        ]);
        $stmt->close();
        $conn->close();
        exit;
    }

    // Check if trying to change last admin to member
    $stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_user = $result->fetch_assoc();

    if ($current_user['role'] === 'Admin' && $role === 'Member') {
        // Count remaining admins
        $admin_count = $conn->query("SELECT COUNT(*) as count FROM user WHERE role = 'Admin'")->fetch_assoc()['count'];

        if ($admin_count <= 1) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot change role. Must have at least one admin in the system'
            ]);
            $stmt->close();
            $conn->close();
            exit;
        }
    }

    // Update user
    $stmt = $conn->prepare("UPDATE user SET name = ?, email = ?, role = ? WHERE user_id = ?");
    $stmt->bind_param("sssi", $name, $email, $role, $user_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update user: ' . $stmt->error
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