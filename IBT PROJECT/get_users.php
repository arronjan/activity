<?php
require_once 'data_config.php';

// Check if admin (optional security check)
$conn = getDBConnection();

$sql = "
    SELECT 
        user_id,
        name,
        email,
        role,
        created_at
    FROM user 
    ORDER BY created_at DESC
";

$result = $conn->query($sql);

$users = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'users' => $users,
    'count' => count($users)
]);

$conn->close();
?>