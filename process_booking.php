<?php
// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "taxi_app";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form data is received via POST method
if (isset($_POST['pickup_lat'], $_POST['pickup_lng'], $_POST['drop_lat'], $_POST['drop_lng'])) {
    // Get the form data
    $pickup_lat = $_POST['pickup_lat'];
    $pickup_lng = $_POST['pickup_lng'];
    $drop_lat = $_POST['drop_lat'];
    $drop_lng = $_POST['drop_lng'];

    // Validate if latitude and longitude are numeric
    if (!is_numeric($pickup_lat) || !is_numeric($pickup_lng) || !is_numeric($drop_lat) || !is_numeric($drop_lng)) {
        die('Invalid latitude or longitude values.');
    }

    // SQL query to insert booking data
    $sql = "INSERT INTO bookings (pickup_lat, pickup_lng, drop_lat, drop_lng) VALUES (?, ?, ?, ?)";
    
    // Prepare and bind the statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Error preparing the query: ' . $conn->error);
    }

    $stmt->bind_param("dddd", $pickup_lat, $pickup_lng, $drop_lat, $drop_lng);

    // Execute the query
    if ($stmt->execute()) {
        echo "Booking successfully processed.";
    } else {
        echo "Error processing booking: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    echo "All fields are required!";
}
?>
