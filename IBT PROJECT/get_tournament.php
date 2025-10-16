<?php
require_once 'data_config.php';

header('Content-Type: application/json');

$conn = getDBConnection();

// Get all tournaments with participant count
$sql = "SELECT 
    t.*,
    COUNT(DISTINCT tp.player_id) as participant_count
FROM tournament t
LEFT JOIN tournament_player tp ON t.tournament_id = tp.tournament_id
GROUP BY t.tournament_id
ORDER BY 
    CASE 
        WHEN t.status = 'ongoing' THEN 1
        WHEN t.status = 'upcoming' THEN 2
        WHEN t.status = 'completed' THEN 3
        ELSE 4
    END,
    t.start_date DESC";

$result = $conn->query($sql);

$tournaments = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Get participants for this tournament
        $participants_sql = "SELECT 
            tp.tournament_id,
            tp.player_id,
            p.name as user_name,
            p.player_id as user_id
        FROM tournament_player tp
        JOIN player p ON tp.player_id = p.player_id
        WHERE tp.tournament_id = ?";

        $stmt = $conn->prepare($participants_sql);
        $stmt->bind_param("i", $row['tournament_id']);
        $stmt->execute();
        $participants_result = $stmt->get_result();

        $participants = [];
        while ($participant = $participants_result->fetch_assoc()) {
            $participants[] = $participant;
        }

        $row['participants'] = $participants;
        $tournaments[] = $row;
        $stmt->close();
    }
}

echo json_encode([
    'success' => true,
    'tournaments' => $tournaments
]);

$conn->close();
?>