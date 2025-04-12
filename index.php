<?php
require_once 'config/config.php';

// Check if user is logged in for admin pages
$isAdmin = false;
if (isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['admin', 'monitoring_admin', 'system_admin'])) {
    $isAdmin = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Air Quality Monitoring System - Colombo</title>
    
    <!-- Modern Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-brand">
            <i class="fas fa-leaf"></i> Colombo Air Quality
        </div>
        <div class="nav-links">
            <?php if ($isAdmin): ?>
                <a href="admin/dashboard.php">
                    <i class="fas fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin/sensors.php">
                    <i class="fas fa-satellite-dish"></i>
                    <span>Sensors</span>
                </a>
                <a href="admin/alerts.php">
                    <i class="fas fa-bell"></i>
                    <span>Alerts</span>
                </a>
                <a href="auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            <?php else: ?>
                <a href="auth/login.php">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Admin Login</span>
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <div id="map"></div>
        <div class="info-panel">
            <!-- Quick Stats Card -->
            <div class="card">
                <h3>
                    <i class="fas fa-chart-line"></i>
                    <span>Quick Statistics</span>
                </h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-label">Active Sensors</div>
                        <div class="stat-value">
                            <i class="fas fa-satellite-dish text-success"></i>
                            <span id="activeSensors">Loading...</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Average AQI</div>
                        <div class="stat-value">
                            <i class="fas fa-wind text-primary"></i>
                            <span id="averageAQI">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sensor Information Card -->
            <div class="card" id="sensor-details">
                <h3>
                    <i class="fas fa-satellite"></i>
                    <span>Sensor Information</span>
                </h3>
                <div id="sensor-info">
                    <div class="text-secondary text-center">
                        <i class="fas fa-info-circle"></i>
                        <p>Select a sensor on the map to view detailed information</p>
                    </div>
                </div>
                <div id="sensor-readings" class="hidden">
                    <div class="sensor-header">
                        <h4 id="sensor-location">Location Name</h4>
                        <span id="sensor-status" class="status-badge">Status</span>
                    </div>
                    <div class="sensor-grid">
                        <div class="sensor-detail">
                            <label>Sensor ID</label>
                            <span id="sensor-id"></span>
                        </div>
                        <div class="sensor-detail">
                            <label>Installation Date</label>
                            <span id="installation-date"></span>
                        </div>
                        <div class="sensor-detail">
                            <label>Last Maintenance</label>
                            <span id="last-maintenance"></span>
                        </div>
                        <div class="sensor-detail">
                            <label>Next Maintenance Due</label>
                            <span id="maintenance-due"></span>
                        </div>
                    </div>
                    <div class="readings-section">
                        <h5>Latest Readings</h5>
                        <div class="readings-grid">
                            <div class="reading-box">
                                <div class="reading-label">AQI</div>
                                <div id="current-aqi" class="reading-value">-</div>
                            </div>
                            <div class="reading-box">
                                <div class="reading-label">PM2.5</div>
                                <div id="current-pm25" class="reading-value">-</div>
                            </div>
                            <div class="reading-box">
                                <div class="reading-label">PM10</div>
                                <div id="current-pm10" class="reading-value">-</div>
                            </div>
                            <div class="reading-box">
                                <div class="reading-label">Temperature</div>
                                <div id="current-temp" class="reading-value">-</div>
                            </div>
                            <div class="reading-box">
                                <div class="reading-label">Humidity</div>
                                <div id="current-humidity" class="reading-value">-</div>
                            </div>
                        </div>
                    </div>
                    <div id="sensor-chart-container">
                        <canvas id="sensor-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- AQI Legend Card -->
            <div class="card aqi-legend">
                <h3>
                    <i class="fas fa-chart-pie"></i>
                    <span>Air Quality Index (AQI)</span>
                </h3>
                <div id="aqi-legend-items">
                    <div class="text-secondary">
                        <i class="fas fa-spinner fa-spin"></i>
                        Loading AQI thresholds...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/map.js"></script>
    <script src="assets/js/charts.js"></script>
    <script src="assets/js/statistics.js"></script>
    <script src="assets/js/aqi-legend.js"></script>
    <script src="assets/js/sensor-details.js"></script>
</body>
</html>
