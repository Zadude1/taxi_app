// Example for updating location in the devices table
$sql = "UPDATE devices SET lat = :lat, lng = :lng WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':lat' => $newLat,      // Replace with the new latitude
    ':lng' => $newLng,      // Replace with the new longitude
    ':id' => $deviceId      // Replace with the device's unique ID
]);
