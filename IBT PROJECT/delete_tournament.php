<?php
require_once 'data_config.php';

header('Content-Type: application/json');

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
$tournament_id = $data['tournament_id'];

$conn = getDBConnection();

// First delete all tournament_player entries
$delete_players = $conn->prepare("DELETE FROM tournament_player WHERE tournament_id = ?");
$delete_players->bind_param("i", $tournament_id);
$delete_players->execute();
$delete_players->close();

// Then delete tournament
$stmt = $conn->prepare("DELETE FROM tournament WHERE tournament_id = ?");
$stmt->bind_param("i", $tournament_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Tournament deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tournament not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete tournament: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>