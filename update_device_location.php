<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$device_id = $_SESSION['user_id']; // Using user_id as device_id
$latitude = $_POST['latitude'];
$longitude = $_POST['longitude'];

$conn = getDBConnection();

// Check if the device_id exists in the device_locations table
$stmt = $conn->prepare("SELECT id FROM device_locations WHERE device_id = ?");
$stmt->bind_param("s", $device_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Device exists, update the location
    $stmt = $conn->prepare("UPDATE device_locations SET latitude = ?, longitude = ?, last_updated = CURRENT_TIMESTAMP WHERE device_id = ?");
    $stmt->bind_param("dss", $latitude, $longitude, $device_id);
    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'Location updated']);
} else {
    // Device does not exist, insert new record
    $stmt = $conn->prepare("INSERT INTO device_locations (device_id, latitude, longitude, last_updated) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
    $stmt->bind_param("sdd", $device_id, $latitude, $longitude);
    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'Location inserted']);
}

$stmt->close();
$conn->close();
?>
