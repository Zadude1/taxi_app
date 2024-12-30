<?php
require_once 'config/database.php';

// Fetch all device locations from the database
$conn = getDBConnection();
$sql = "SELECT device_id, latitude, longitude FROM device_locations";
$result = $conn->query($sql);

$locations = [];
while ($row = $result->fetch_assoc()) {
    $locations[] = [
        'device_id' => $row['device_id'],
        'latitude' => $row['latitude'],
        'longitude' => $row['longitude']
    ];

}

$conn->close();

// Return the locations as JSON
header('Content-Type: application/json');
echo json_encode($locations);
?>
