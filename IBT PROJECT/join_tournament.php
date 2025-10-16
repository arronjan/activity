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

// Check if already joined
$check_stmt = $conn->prepare("SELECT * FROM tournament_player WHERE tournament_id = ? AND player_id = ?");
$check_stmt->bind_param("ii", $tournament_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already joined this tournament']);
    $check_stmt->close();
    $conn->close();
    exit;
}
$check_stmt->close();

// Join tournament
$insert_stmt = $conn->prepare("INSERT INTO tournament_player (tournament_id, player_id) VALUES (?, ?)");
$insert_stmt->bind_param("ii", $tournament_id, $user_id);

if ($insert_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Successfully joined tournament']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to join tournament: ' . $insert_stmt->error]);
}

$insert_stmt->close();
$conn->close();
?>