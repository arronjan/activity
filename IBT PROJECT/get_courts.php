<?php
require_once 'data_config.php';

$conn = getDBConnection();

$sql = "SELECT court_id, court_name, availability_status FROM court ORDER BY court_id ASC";
$result = $conn->query($sql);

$courts = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $courts[] = $row;
    }
}

echo json_encode($courts);

$conn->close();
?>