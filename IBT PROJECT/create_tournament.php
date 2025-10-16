<?php
require_once 'data_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$conn = getDBConnection();

$stmt = $conn->prepare("INSERT INTO tournament (name, start_date, end_date, status) VALUES (?, ?, ?, ?)");

$stmt->bind_param(
    "ssss",
    $data['name'],
    $data['start_date'],
    $data['end_date'],
    $data['status']
);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Tournament created successfully',
        'tournament_id' => $stmt->insert_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create tournament: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>