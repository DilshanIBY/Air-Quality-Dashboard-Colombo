<?php
require_once '../config/config.php';
require_once '../auth/auth_middleware.php';

// Require login and admin role
requireRole('monitoring_admin');

// Get user information
$username = getCurrentUsername();
$role = getUserRole();

// Initialize variables
$message = '';
$error = '';
$sensors = [];
$sensorTypes = [];

try {
    $pdo = getDBConnection();
    
    // Handle sensor deletion
    if (isset($_POST['delete_sensor']) && isset($_POST['sensor_id'])) {
        $stmt = $pdo->prepare("DELETE FROM sensors WHERE sensor_id = ?");
        $stmt->execute([$_POST['sensor_id']]);
        $message = "Sensor deleted successfully";
    }
    
    // Handle sensor status update
    if (isset($_POST['update_status']) && isset($_POST['sensor_id']) && isset($_POST['status'])) {
        try {
            $stmt = $pdo->prepare("UPDATE sensors SET status = ? WHERE sensor_id = ?");
            $stmt->execute([$_POST['status'], $_POST['sensor_id']]);
            $message = "Sensor status updated successfully";
        } catch (PDOException $e) {
            $error = "Error updating sensor status: " . $e->getMessage();
        }
    }
    
    // Handle new sensor creation
    if (isset($_POST['add_sensor'])) {
        $sensor_id = $_POST['sensor_id'] ?? '';
        $location_name = $_POST['location_name'] ?? '';
        $latitude = $_POST['latitude'] ?? '';
        $longitude = $_POST['longitude'] ?? '';
        
        if (empty($sensor_id) || empty($location_name) || empty($latitude) || empty($longitude)) {
            $error = "All fields are required";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO sensors (
                        sensor_id, 
                        location_name, 
                        latitude, 
                        longitude, 
                        status, 
                        installation_date, 
                        created_by
                    ) VALUES (?, ?, ?, ?, 'active', CURDATE(), 1)
                ");
                $stmt->execute([$sensor_id, $location_name, $latitude, $longitude]);
                $message = "New sensor added successfully";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Duplicate entry error
                    $error = "Sensor ID already exists. Please choose a different ID.";
                } else {
                    $error = "Error adding sensor: " . $e->getMessage();
                }
            }
        }
    }
    
    // Handle sensor editing
    if (isset($_POST['edit_sensor'])) {
        $sensor_id = $_POST['sensor_id'] ?? '';
        $location_name = $_POST['location'] ?? '';
        $latitude = $_POST['latitude'] ?? '';
        $longitude = $_POST['longitude'] ?? '';
        
        if (empty($sensor_id) || empty($location_name) || empty($latitude) || empty($longitude)) {
            $error = "All fields are required";
        } else {
            $stmt = $pdo->prepare("
                UPDATE sensors 
                SET location_name = ?, latitude = ?, longitude = ? 
                WHERE sensor_id = ?
            ");
            $stmt->execute([$location_name, $latitude, $longitude, $sensor_id]);
            $message = "Sensor updated successfully";
        }
    }
    
    // Get all sensors with their readings
    $stmt = $pdo->query("
        SELECT s.*, 
               COUNT(a.id) as reading_count,
               MAX(a.timestamp) as last_reading
        FROM sensors s
        LEFT JOIN aqi_readings a ON s.sensor_id = a.sensor_id
        GROUP BY s.sensor_id
        ORDER BY s.installation_date DESC
    ");
    $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get sensor types for dropdown (using location names as types for now)
    $stmt = $pdo->query("SELECT DISTINCT location_name FROM sensors");
    $sensorTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    error_log("Sensors page error: " . $e->getMessage());
    $error = "Error loading sensor data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sensors - Air Quality Monitoring System</title>
    
    <!-- Modern Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4CAF50;
            --error-color: #f44336;
            --warning-color: #ff9800;
            --text-color: #333;
            --border-color: #ddd;
            --bg-color: #f5f5f5;
            --card-shadow: 0 4px 6px rgba(0,0,0,0.1);
            --transition-speed: 0.3s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            min-height: 100vh;
            transition: background-color var(--transition-speed);
        }

        .navbar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-brand {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-links a {
            color: var(--text-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all var(--transition-speed);
            padding: 0.5rem 1rem;
            border-radius: 6px;
        }

        .nav-links a:hover {
            color: var(--primary-color);
            background: rgba(76, 175, 80, 0.1);
        }

        .nav-links a.active {
            color: var(--primary-color);
            background: rgba(76, 175, 80, 0.1);
        }

        .main-content {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.5rem;
            color: var(--text-color);
        }

        .add-sensor-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all var(--transition-speed);
        }

        .add-sensor-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.2);
        }

        .sensors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .sensor-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-speed);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .sensor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }

        .sensor-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .sensor-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sensor-name i {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .sensor-status {
            padding: 0.35rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: rgba(76, 175, 80, 0.1);
            color: var(--primary-color);
        }

        .status-inactive {
            background: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
        }

        .status-maintenance {
            background: rgba(255, 152, 0, 0.1);
            color: var(--warning-color);
        }

        .sensor-info {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 0.5rem 0;
        }

        .sensor-info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #666;
            font-size: 0.9rem;
            padding: 0.25rem 0;
        }

        .sensor-info-item i {
            width: 1.5rem;
            text-align: center;
            color: var(--primary-color);
            font-size: 1rem;
        }

        .sensor-info-item span {
            flex: 1;
        }

        .sensor-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            flex-wrap: wrap;
        }

        .status-toggle {
            flex: 1;
            min-width: 120px;
        }

        .status-form {
            width: 100%;
        }

        .action-btn {
            width: 100%;
            min-width: 120px;
            padding: 0.75rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all var(--transition-speed);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .edit-btn-container, 
        .delete-btn-container {
            flex: 1;
            min-width: 120px;
        }

        .edit-btn {
            background: rgba(76, 175, 80, 0.1);
            color: var(--primary-color);
        }

        .delete-btn {
            background: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .action-btn i {
            font-size: 1rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .submit-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            width: 100%;
            margin-top: 1rem;
        }

        .submit-btn:hover {
            background: #43A047;
        }

        .message {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.1);
            color: var(--primary-color);
        }

        .error-message {
            background: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
        }

        /* Dark mode styles */
        body.dark-mode {
            --bg-color: #1a1a1a;
            --text-color: #ffffff;
            --border-color: #333;
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        body.dark-mode .navbar,
        body.dark-mode .sensor-card,
        body.dark-mode .modal-content {
            background: #2d2d2d;
        }

        body.dark-mode .sensor-header {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }

        body.dark-mode .sensor-actions {
            border-top-color: rgba(255, 255, 255, 0.1);
        }

        body.dark-mode .sensor-info-item {
            color: #aaa;
        }

        body.dark-mode .form-input {
            background: #333;
            color: white;
            border-color: #444;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 1rem;
            }

            .nav-links {
                margin-top: 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .sensors-grid {
                grid-template-columns: 1fr;
            }

            .sensor-card {
                padding: 1.25rem;
            }

            .sensor-info-item {
                font-size: 0.85rem;
            }

            .action-btn {
                width: 100%;
                padding: 0.75rem;
            }

            .status-toggle,
            .edit-btn-container,
            .delete-btn-container {
                width: 100%;
            }
        }

        /* Add these styles to your existing CSS */
        .status-toggle {
            margin-right: 0.5rem;
        }

        .status-form {
            display: inline;
        }

        .status-btn {
            background: rgba(76, 175, 80, 0.1);
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all var(--transition-speed);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-btn.inactive {
            background: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
        }

        .status-btn:hover {
            transform: translateY(-2px);
        }

        .status-btn i {
            transition: transform var(--transition-speed);
        }

        .status-btn:hover i {
            transform: rotate(180deg);
        }

        .status-options {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin: 1.5rem 0;
        }

        .status-option {
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .status-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .status-option.selected {
            border-color: #4CAF50;
            background: rgba(76, 175, 80, 0.1);
        }

        .status-option[data-status="active"] {
            border-color: #4CAF50;
        }

        .status-option[data-status="active"]:hover,
        .status-option[data-status="active"].selected {
            background: rgba(76, 175, 80, 0.1);
        }

        .status-option[data-status="inactive"] {
            border-color: #f44336;
        }

        .status-option[data-status="inactive"]:hover,
        .status-option[data-status="inactive"].selected {
            background: rgba(244, 67, 54, 0.1);
        }

        .status-option[data-status="maintenance"] {
            border-color: #ff9800;
        }

        .status-option[data-status="maintenance"]:hover,
        .status-option[data-status="maintenance"].selected {
            background: rgba(255, 152, 0, 0.1);
        }

        /* Update status button colors */
        .status-btn.active {
            background: rgba(76, 175, 80, 0.1);
            color: var(--primary-color);
        }

        .status-btn.inactive {
            background: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
        }

        .status-btn.maintenance {
            background: rgba(255, 152, 0, 0.1);
            color: var(--warning-color);
        }

        /* Dark mode support */
        body.dark-mode .status-option {
            background: #333;
            color: white;
        }

        body.dark-mode .status-option.selected {
            background: rgba(76, 175, 80, 0.2);
        }

        body.dark-mode .status-option[data-status="active"]:hover,
        body.dark-mode .status-option[data-status="active"].selected {
            background: rgba(76, 175, 80, 0.2);
        }

        body.dark-mode .status-option[data-status="inactive"]:hover,
        body.dark-mode .status-option[data-status="inactive"].selected {
            background: rgba(244, 67, 54, 0.2);
        }

        body.dark-mode .status-option[data-status="maintenance"]:hover,
        body.dark-mode .status-option[data-status="maintenance"].selected {
            background: rgba(255, 152, 0, 0.2);
        }
    </style>

    <!-- Add this in the head section after other CSS links -->
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-material-ui/material-ui.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <i class="fas fa-leaf"></i>
            <span>Air Quality Admin</span>
        </div>
        <div class="nav-links">
            <a href="dashboard.php">
                <i class="fas fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>
            <a href="sensors.php" class="active">
                <i class="fas fa-satellite-dish"></i>
                <span>Sensors</span>
            </a>
            <a href="simulation.php">
                <i class="fas fa-microchip"></i>
                <span>Simulation</span>
            </a>
            <a href="alerts.php">
                <i class="fas fa-bell"></i>
                <span>Alerts</span>
            </a>
            <a href="users.php">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="../auth/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
            <a href="../index.php" target="_blank">
                <i class="fas fa-leaf"></i>
            </a>
        </div>
    </nav>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Manage Sensors</h1>
            <button class="add-sensor-btn" onclick="showAddSensorModal()">
                <i class="fas fa-plus"></i>
                Add New Sensor
            </button>
        </div>

        <?php if ($message): ?>
            <div class="message success-message">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="sensors-grid">
            <?php if (!empty($sensors)): ?>
                <?php foreach ($sensors as $sensor): ?>
                    <div class="sensor-card">
                        <div class="sensor-header">
                            <h3 class="sensor-name">
                                <i class="fas fa-satellite-dish"></i>
                                <?php echo htmlspecialchars($sensor['sensor_id']); ?>
                            </h3>
                            <div class="sensor-status status-<?php echo htmlspecialchars($sensor['status']); ?>">
                                <?php echo htmlspecialchars(ucfirst($sensor['status'])); ?>
                            </div>
                        </div>
                        
                        <div class="sensor-info">
                            <div class="sensor-info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($sensor['location_name']); ?></span>
                            </div>
                            <div class="sensor-info-item">
                                <i class="fas fa-map"></i>
                                <span>Lat: <?php echo htmlspecialchars($sensor['latitude']); ?>, Long: <?php echo htmlspecialchars($sensor['longitude']); ?></span>
                            </div>
                            <div class="sensor-info-item">
                                <i class="fas fa-chart-line"></i>
                                <span><?php echo $sensor['reading_count']; ?> readings</span>
                            </div>
                            <?php if ($sensor['last_reading']): ?>
                                <div class="sensor-info-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Last reading: <?php echo date('M j, Y H:i', strtotime($sensor['last_reading'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="sensor-info-item">
                                <i class="fas fa-calendar"></i>
                                <span>Installed: <?php echo date('M j, Y', strtotime($sensor['installation_date'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="sensor-actions">
                            <div class="status-toggle">
                                <button type="button" class="action-btn status-btn <?php echo $sensor['status']; ?>" 
                                        onclick="toggleSensorStatus('<?php echo htmlspecialchars($sensor['sensor_id']); ?>', '<?php echo $sensor['status']; ?>')">
                                    <i class="fas fa-power-off"></i>
                                    <span>Action</span>
                                </button>
                            </div>
                            <div class="edit-btn-container">
                                <button class="action-btn edit-btn" onclick="showEditSensorModal(<?php echo htmlspecialchars(json_encode($sensor)); ?>)">
                                    <i class="fas fa-edit"></i>
                                    <span>Edit</span>
                                </button>
                            </div>
                            <div class="delete-btn-container">
                                <button class="action-btn delete-btn" onclick="confirmDelete('<?php echo $sensor['sensor_id']; ?>')">
                                    <i class="fas fa-trash"></i>
                                    <span>Delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-sensors">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>No sensors found. Add a new sensor to get started.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add Sensor Modal -->
    <div class="modal" id="addSensorModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Sensor</h3>
                <button class="close-btn" onclick="hideAddSensorModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Sensor ID</label>
                    <input type="text" name="sensor_id" class="form-input" required 
                           pattern="SEN[0-9]{3}" 
                           title="Sensor ID must start with 'SEN' followed by 3 digits"
                           placeholder="e.g., SEN001">
                </div>
                <div class="form-group">
                    <label class="form-label">Location Name</label>
                    <input type="text" name="location_name" class="form-input" required 
                           placeholder="Enter location name">
                </div>
                <div class="form-group">
                    <label class="form-label">Latitude</label>
                    <input type="number" name="latitude" class="form-input" required 
                           step="0.00000001" 
                           min="-90" 
                           max="90"
                           placeholder="e.g., 6.9344">
                </div>
                <div class="form-group">
                    <label class="form-label">Longitude</label>
                    <input type="number" name="longitude" class="form-input" required 
                           step="0.00000001" 
                           min="-180" 
                           max="180"
                           placeholder="e.g., 79.8428">
                </div>
                <button type="submit" name="add_sensor" class="submit-btn">Add Sensor</button>
            </form>
        </div>
    </div>

    <!-- Edit Sensor Modal -->
    <div class="modal" id="editSensorModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Sensor</h3>
                <button class="close-btn" onclick="hideEditSensorModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="sensor_id" id="edit_sensor_id">
                <div class="form-group">
                    <label class="form-label">Location Name</label>
                    <input type="text" name="location" id="edit_location" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Latitude</label>
                    <input type="text" name="latitude" id="edit_latitude" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Longitude</label>
                    <input type="text" name="longitude" id="edit_longitude" class="form-input" required>
                </div>
                <button type="submit" name="edit_sensor" class="submit-btn">Update Sensor</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Delete</h3>
                <button class="close-btn" onclick="hideDeleteModal()">&times;</button>
            </div>
            <p>Are you sure you want to delete this sensor? This action cannot be undone.</p>
            <form method="POST" action="">
                <input type="hidden" name="sensor_id" id="delete_sensor_id">
                <div class="sensor-actions" style="margin-top: 1rem;">
                    <button type="button" class="action-btn edit-btn" onclick="hideDeleteModal()">Cancel</button>
                    <button type="submit" name="delete_sensor" class="action-btn delete-btn">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function showAddSensorModal() {
            document.getElementById('addSensorModal').style.display = 'flex';
        }

        function hideAddSensorModal() {
            document.getElementById('addSensorModal').style.display = 'none';
        }

        function showEditSensorModal(sensor) {
            Swal.fire({
                title: 'Edit Sensor',
                html: `
                    <form id="editForm" class="swal2-form">
                        <input type="hidden" name="sensor_id" value="${sensor.sensor_id}">
                        <div class="form-group">
                            <label class="form-label">Location Name</label>
                            <input type="text" name="location" class="swal2-input" value="${sensor.location_name}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Latitude</label>
                            <input type="number" name="latitude" class="swal2-input" value="${sensor.latitude}" step="0.00000001" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Longitude</label>
                            <input type="number" name="longitude" class="swal2-input" value="${sensor.longitude}" step="0.00000001" required>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: 'Update',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#4CAF50',
                cancelButtonColor: '#666',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                },
                preConfirm: () => {
                    const form = document.getElementById('editForm');
                    const formData = new FormData(form);
                    return fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    }).then(response => {
                        if (!response.ok) throw new Error(response.statusText);
                        return response.text();
                    }).catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Sensor updated successfully',
                        icon: 'success',
                        confirmButtonColor: '#4CAF50'
                    }).then(() => {
                        window.location.reload();
                    });
                }
            });
        }

        function hideEditSensorModal() {
            document.getElementById('editSensorModal').style.display = 'none';
        }

        function confirmDelete(sensorId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f44336',
                cancelButtonColor: '#666',
                confirmButtonText: 'Yes, delete it!',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="sensor_id" value="${sensorId}">
                        <input type="hidden" name="delete_sensor" value="1">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function hideDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modals when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        });

        // Theme toggle functionality
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        }

        // Check for saved theme preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }

        // Add this new function for status toggle
        function toggleSensorStatus(sensorId, currentStatus) {
            Swal.fire({
                title: 'Change Sensor Status',
                html: `
                    <div class="status-options">
                        <button type="button" class="status-option ${currentStatus === 'active' ? 'selected' : ''}" data-status="active">
                            🟢 Active
                        </button>
                        <button type="button" class="status-option ${currentStatus === 'inactive' ? 'selected' : ''}" data-status="inactive">
                            🔴 Inactive
                        </button>
                        <button type="button" class="status-option ${currentStatus === 'maintenance' ? 'selected' : ''}" data-status="maintenance">
                            🟡 Maintenance
                        </button>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Update Status',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#4CAF50',
                cancelButtonColor: '#666',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                },
                didOpen: () => {
                    // Add click handlers for status options
                    const options = document.querySelectorAll('.status-option');
                    options.forEach(option => {
                        option.addEventListener('click', () => {
                            options.forEach(opt => opt.classList.remove('selected'));
                            option.classList.add('selected');
                        });
                    });
                },
                preConfirm: () => {
                    const selectedOption = document.querySelector('.status-option.selected');
                    if (!selectedOption) {
                        Swal.showValidationMessage('Please select a status');
                        return false;
                    }
                    return selectedOption.dataset.status;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="sensor_id" value="${sensorId}">
                        <input type="hidden" name="status" value="${result.value}">
                        <input type="hidden" name="update_status" value="1">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html> 