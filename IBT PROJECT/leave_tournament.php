<?php
require_once 'data_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$tournament_id = $data['tournament_id'];
$user_id = $data['user_id'];

$conn = getDBConnection();

// Leave tournament
$delete_stmt = $conn->prepare("DELETE FROM tournament_player WHERE tournament_id = ? AND player_id = ?");
$delete_stmt->bind_param("ii", $tournament_id, $user_id);

if ($delete_stmt->execute()) {
    if ($delete_stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Successfully left tournament']);
    } else {
        echo json_encode(['success' => false, 'message' => 'You are not registered for this tournament']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to leave tournament: ' . $delete_stmt->error]);
}

$delete_stmt->close();
$conn->close();
?>