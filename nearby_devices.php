<?php
// Database connection
require_once 'config/database.php';

// Get user's current location from the request
$currentLat = $_GET['lat'] ?? 0;
$currentLng = $_GET['lng'] ?? 0;
$radius = $_GET['radius'] ?? 5; // Default radius in km

// Query to find nearby devices within the radius
$sql = "SELECT id, name, lat, lng FROM devices
        WHERE (6371 * acos(
            cos(radians(:currentLat)) * cos(radians(lat)) *
            cos(radians(lng) - radians(:currentLng)) +
            sin(radians(:currentLat)) * sin(radians(lat))
        )) < :radius";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':currentLat' => $currentLat,
    ':currentLng' => $currentLng,
    ':radius' => $radius
]);

// Fetch and return results as JSON
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($devices);
