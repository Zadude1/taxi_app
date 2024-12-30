<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$booking_id = $_GET['id'];
$conn = getDBConnection();

$stmt = $conn->prepare("SELECT b.*, d.name as driver_name, d.phone as driver_phone, d.vehicle_number 
                       FROM bookings b 
                       LEFT JOIN drivers d ON b.driver_id = d.id 
                       WHERE b.id = ? AND b.user_id = ?");
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - Taxi Booking App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title text-center">Booking Confirmation</h3>
                    <div class="alert alert-success">
                        Your booking has been confirmed! Booking ID: #<?php echo $booking_id; ?>
                    </div>
                    <div class="booking-details">
                        <h5>Booking Details:</h5>
                        <p><strong>Pickup Location:</strong> <?php echo htmlspecialchars($booking['pickup_location']); ?></p>
                        <p><strong>Dropoff Location:</strong> <?php echo htmlspecialchars($booking['dropoff_location']); ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst($booking['booking_status']); ?></p>
                        <?php if ($booking['driver_name']): ?>
                            <h5 class="mt-4">Driver Details:</h5>
                            <p><strong>Driver Name:</strong> <?php echo htmlspecialchars($booking['driver_name']); ?></p>
                            <p><strong>Vehicle Number:</strong> <?php echo htmlspecialchars($booking['vehicle_number']); ?></p>
                            <p><strong>Contact:</strong> <?php echo htmlspecialchars($booking['driver_phone']); ?></p>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Looking for a driver... Please wait.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-primary">Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>