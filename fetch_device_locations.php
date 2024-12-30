<?php
// Include database configuration
require_once 'config/database.php';

// Get database connection
$conn = getDBConnection();

// Fetch device locations
$query = "SELECT device_id, latitude, longitude, last_updated FROM device_locations";
$result = $conn->query($query);

// Create an array to hold location data
$locations = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
}

// Output the locations as JSON
echo json_encode($locations);

// Close the database connection
$conn->close();
?>
