<?php
// Include database connection
require_once 'config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $pickup_lat = $_POST['pickup_lat'];
    $pickup_lng = $_POST['pickup_lng'];
    $drop_lat = $_POST['drop_lat'];
    $drop_lng = $_POST['drop_lng'];

    // Prepare the SQL query to insert the data
    $conn = getDBConnection();
    
    $query = "INSERT INTO ongoing_bookings (pickup_latitude, pickup_longitude, drop_latitude, drop_longitude) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("dddd", $pickup_lat, $pickup_lng, $drop_lat, $drop_lng);

        // Execute the query and check if it was successful
        if ($stmt->execute()) {
            echo "Booking details saved successfully!";
        } else {
            echo "Error saving details: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error preparing the SQL query: " . $conn->error;
    }

    $conn->close();
}
?>
