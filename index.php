<?php
// First, ensure session is started before any output
session_start();

// Include database configuration
require_once 'config/database.php';

// Create header.php if it doesn't exist
if (!file_exists('includes/header.php')) {
    $headerContent = '<?php
    if (!isset($_SESSION)) { session_start(); }
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Taxi App</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if(isset($_SESSION["user_id"])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>';
    
    // Create includes directory if it doesn't exist
    if (!file_exists('includes')) {
        mkdir('includes', 0777, true);
    }
    
    // Save header file
    file_put_contents('includes/header.php', $headerContent);
}
?>
       










<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxi App</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <style>
        #map {
            height: 400px;
            width: 100%;
            margin-bottom: 20px;
        }
        form {
            margin-top: 20px;
        }
        input {
            margin: 5px 0;
            padding: 10px;
            width: 100%;
        }
        .result {
            margin-top: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <h1>Taxi Booking</h1>
    
    <!-- Search bar for drop-off location -->
    <input type="text" id="dropoff_search" placeholder="Search for drop-off location" />

    <div id="map"></div>

    <!-- Form for pickup, drop-off, and cost -->
    <form action="save_taxi_details.php" method="POST">
    <label for="pickup_lat">Pickup Latitude:</label>
    <input type="text" id="pickup_lat" name="pickup_lat" readonly>

    <label for="pickup_lng">Pickup Longitude:</label>
    <input type="text" id="pickup_lng" name="pickup_lng" readonly>

    <label for="drop_lat">Drop Latitude:</label>
    <input type="text" id="drop_lat" name="drop_lat" readonly>

    <label for="drop_lng">Drop Longitude:</label>
    <input type="text" id="drop_lng" name="drop_lng" readonly>

    <!-- Submit Button -->
    <button type="submit">Submit</button>
</form>

    <!-- Include Leaflet.js and Routing plugin -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
    <script>
        const COST_PER_KM = 1500; // Cost per kilometer in IQD

        // Initialize the map
        let map = L.map('map');
        var customIcon = L.icon({
            iconUrl: 'car.png', // Path to your custom icon image
            iconSize: [32, 32],              // Size of the icon [width, height]
            iconAnchor: [16, 32],            // Point of the icon which will correspond to marker's location
            popupAnchor: [0, -32]            // Point from which the popup should open relative to the iconAnchor
        });

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        let pickupMarker, dropMarker;
        let routeControl;

        // Show the current location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                // Center the map on the current location
                map.setView([lat, lng], 13);

                // Add a pickup marker at the current location
                pickupMarker = L.marker([lat, lng], { draggable: true }).addTo(map)
                    .bindPopup("Pickup Location")
                    .openPopup();

                // Set initial pickup coordinates
                document.getElementById('pickup_lat').value = lat;
                document.getElementById('pickup_lng').value = lng;

                // Listen for pickup marker drag
                pickupMarker.on('dragend', function(e) {
                    const coords = e.target.getLatLng();
                    document.getElementById('pickup_lat').value = coords.lat;
                    document.getElementById('pickup_lng').value = coords.lng;
                    calculateCost();
                    drawRoute(); // Update the route when pickup is changed
                });
            }, function() {
                alert('Error fetching location. Please enable location services.');
                map.setView([0, 0], 2); // Default view in case of error
            });
        } else {
            alert('Geolocation is not supported by your browser.');
            map.setView([0, 0], 2); // Default view if geolocation is unavailable
        }

        // Add click event to set the drop-off location
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;

            if (dropMarker) {
                map.removeLayer(dropMarker); // Remove previous drop marker
            }

            // Add a drop marker on the map
            dropMarker = L.marker([lat, lng], { icon: customIcon }).addTo(map)
                .bindPopup("Drop-off Location")
                .openPopup();

            // Update the drop-off coordinates
            document.getElementById('drop_lat').value = lat;
            document.getElementById('drop_lng').value = lng;

            calculateCost();
            drawRoute(); // Update the route when drop-off is set
        });

        // Function to calculate cost
        function calculateCost() {
            const pickupLat = parseFloat(document.getElementById('pickup_lat').value);
            const pickupLng = parseFloat(document.getElementById('pickup_lng').value);
            const dropLat = parseFloat(document.getElementById('drop_lat').value);
            const dropLng = parseFloat(document.getElementById('drop_lng').value);

            if (!isNaN(pickupLat) && !isNaN(pickupLng) && !isNaN(dropLat) && !isNaN(dropLng)) {
                const distance = getDistanceFromLatLonInKm(pickupLat, pickupLng, dropLat, dropLng);
                const cost = Math.round(distance * COST_PER_KM);
                document.getElementById('cost').innerText = cost - 500;
            }
        }

        // Function to calculate distance in km
        function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radius of the earth in km
            const dLat = deg2rad(lat2 - lat1);
            const dLon = deg2rad(lon2 - lon1);
            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            const distance = R * c; // Distance in km
            return distance;
        }

        function deg2rad(deg) {
            return deg * (Math.PI / 180);
        }

        // Draw route from pickup to drop-off
        function drawRoute() {
            const pickupLat = parseFloat(document.getElementById('pickup_lat').value);
            const pickupLng = parseFloat(document.getElementById('pickup_lng').value);
            const dropLat = parseFloat(document.getElementById('drop_lat').value);
            const dropLng = parseFloat(document.getElementById('drop_lng').value);

            if (pickupLat && pickupLng && dropLat && dropLng) {
                // Remove previous route if it exists
                if (routeControl) {
                    map.removeControl(routeControl);
                }

                // Draw new route
                routeControl = L.Routing.control({
                    waypoints: [
                        L.latLng(pickupLat, pickupLng),
                        L.latLng(dropLat, dropLng)
                    ],
                    routeWhileDragging: true
                }).addTo(map);
            }
        }

        // Add geocoding search functionality
        document.getElementById('dropoff_search').addEventListener('input', function() {
            const query = this.value;
            if (query.length > 2) {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            const lat = data[0].lat;
                            const lon = data[0].lon;

                            // Center map on the drop-off location
                            map.setView([lat, lon], 13);

                            if (dropMarker) {
                                map.removeLayer(dropMarker); // Remove previous drop marker
                            }

                            // Add a new drop marker at the searched location
                            dropMarker = L.marker([lat, lon]).addTo(map)
                                .bindPopup("Drop-off Location")
                                .openPopup();

                            // Update the drop-off coordinates
                            document.getElementById('drop_lat').value = lat;
                            document.getElementById('drop_lng').value = lon;

                            calculateCost();
                            drawRoute(); // Update the route when drop-off is set
                        }
                    });
            }
        });
    </script>
    <script>
// Fetch device locations and display them on the map
fetch('fetch_device_locations.php')
    .then(response => response.json())
    .then(data => {
        data.forEach(location => {
            const lat = parseFloat(location.latitude);
            const lng = parseFloat(location.longitude);
            const popupContent = `
                <b>Device ID:</b> ${location.device_id}<br>
                <b>Last Updated:</b> ${location.last_updated}
            `;
            
            // Add a marker for each device location
            L.marker([lat, lng], { icon: customIcon }).addTo(map)
                .bindPopup(popupContent);
        });
    })
    .catch(error => console.error('Error fetching locations:', error));
</script>
<script>
    document.getElementById('submitDetails').addEventListener('click', function () {
        const pickupLat = document.getElementById('pickup_lat').value;
        const pickupLng = document.getElementById('pickup_lng').value;
        const dropLat = document.getElementById('drop_lat').value;
        const dropLng = document.getElementById('drop_lng').value;
        const cost = document.getElementById('cost').innerText;

        if (pickupLat && pickupLng && dropLat && dropLng && cost) {
            fetch('save_taxi_details.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    pickup_lat: pickupLat,
                    pickup_lng: pickupLng,
                    drop_lat: dropLat,
                    drop_lng: dropLng,
                    cost: cost
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Details saved successfully!');
                } else {
                    alert('Failed to save details.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving details.');
            });
        } else {
            alert('Please complete all fields before submitting.');
        }
    });
</script>




</body>
</html>
