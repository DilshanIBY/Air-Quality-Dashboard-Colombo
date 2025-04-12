<?php
require_once '../config/config.php';
require_once '../auth/auth_middleware.php';

// Require admin role
requireRole('monitoring_admin');

// Get user information
$username = getCurrentUsername();
$role = getUserRole();

// Initialize variables
$message = '';
$error = '';
$simulations = [];

try {
    $pdo = getDBConnection();
    
    // Handle simulation settings updates
    if (isset($_POST['update_simulation'])) {
        $sensor_id = $_POST['sensor_id'] ?? '';
        $base_aqi = $_POST['base_aqi'] ?? '';
        $variation_min = $_POST['variation_min'] ?? '';
        $variation_max = $_POST['variation_max'] ?? '';
        $update_frequency = $_POST['update_frequency'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE simulation_settings 
                SET base_aqi = ?, 
                    variation_min = ?, 
                    variation_max = ?, 
                    update_frequency = ?,
                    is_active = ?
                WHERE sensor_id = ?
            ");
            $stmt->execute([$base_aqi, $variation_min, $variation_max, $update_frequency, $is_active, $sensor_id]);
            $message = "Simulation settings updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating simulation settings: " . $e->getMessage();
        }
    }
    
    // Get all simulation settings with sensor information
    $stmt = $pdo->query("
        SELECT ss.*, s.location_name, s.status as sensor_status
        FROM simulation_settings ss
        JOIN sensors s ON ss.sensor_id = s.sensor_id
        ORDER BY ss.created_at DESC
    ");
    $simulations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add this new section for handling simulation data generation
    if (isset($_POST['generate_readings'])) {
        try {
            // Get all active sensors with their simulation settings
            $stmt = $pdo->query("
                SELECT s.sensor_id, ss.base_aqi, ss.variation_min, ss.variation_max
                FROM sensors s
                JOIN simulation_settings ss ON s.sensor_id = ss.sensor_id
                WHERE ss.is_active = 1
            ");
            $activeSensors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generate and insert readings for each active sensor
            foreach ($activeSensors as $sensor) {
                // Generate random AQI value based on settings
                $baseAqi = $sensor['base_aqi'];
                $minVar = $sensor['variation_min'];
                $maxVar = $sensor['variation_max'];
                
                $variation = rand($minVar * 100, $maxVar * 100) / 100;
                $direction = rand(0, 1) ? 1 : -1;
                $aqi_value = max(0, $baseAqi + ($variation * $direction));
                
                // Calculate PM2.5 and PM10 values (simplified calculation for simulation)
                $pm25_value = $aqi_value * 0.8;
                $pm10_value = $aqi_value * 1.2;
                
                // Generate random temperature (20-35°C) and humidity (30-80%)
                $temperature = rand(200, 350) / 10;
                $humidity = rand(300, 800) / 10;
                
                // Insert the reading
                $stmt = $pdo->prepare("
                    INSERT INTO aqi_readings (
                        sensor_id, aqi_value, pm25_value, pm10_value, 
                        temperature, humidity, is_simulated
                    ) VALUES (?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([
                    $sensor['sensor_id'],
                    $aqi_value,
                    $pm25_value,
                    $pm10_value,
                    $temperature,
                    $humidity
                ]);
            }
            
            $message = "Generated new readings for " . count($activeSensors) . " active sensors!";
        } catch (PDOException $e) {
            $error = "Error generating readings: " . $e->getMessage();
        }
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulation Settings - Air Quality Monitoring</title>
    
    <!-- Modern Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-material-ui/material-ui.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-title {
            font-size: 2rem;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .page-description {
            color: #666;
            font-size: 1.1rem;
        }

        .simulation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .simulation-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-speed);
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .simulation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .simulation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .sensor-info {
            flex: 1;
        }

        .sensor-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }

        .sensor-location {
            color: #666;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .simulation-toggle {
            position: relative;
            width: 60px;
            height: 30px;
        }

        .toggle-input {
            display: none;
        }

        .toggle-label {
            position: absolute;
            top: 0;
            left: 0;
            width: 60px;
            height: 30px;
            border-radius: 15px;
            background: #ddd;
            cursor: pointer;
            transition: all var(--transition-speed);
        }

        .toggle-label:after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: white;
            transition: all var(--transition-speed);
        }

        .toggle-input:checked + .toggle-label {
            background: var(--primary-color);
        }

        .toggle-input:checked + .toggle-label:after {
            left: 32px;
        }

        .simulation-form {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-input {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all var(--transition-speed);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .submit-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-speed);
            margin-top: 1rem;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.2);
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
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
        }

        body.dark-mode .navbar,
        body.dark-mode .simulation-card {
            background: #2d2d2d;
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

            .simulation-grid {
                grid-template-columns: 1fr;
            }

            .simulation-card {
                padding: 1.25rem;
            }
        }

        /* Add these styles to your existing CSS */
        .simulation-controls {
            margin-top: 1.5rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .generate-btn {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all var(--transition-speed);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 6px rgba(76, 175, 80, 0.2);
        }

        .generate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(76, 175, 80, 0.3);
        }

        .generate-btn i {
            font-size: 1.2rem;
        }

        .generate-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(76, 175, 80, 0.2);
        }

        /* Dark mode support */
        body.dark-mode .generate-btn {
            background: linear-gradient(45deg, #43a047, #388e3c);
            box-shadow: 0 4px 6px rgba(76, 175, 80, 0.4);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <i class="fas fa-microchip"></i>
            <span>Air Quality Simulation</span>
        </div>
        <div class="nav-links">
            <a href="dashboard.php">
                <i class="fas fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>
            <a href="sensors.php">
                <i class="fas fa-satellite-dish"></i>
                <span>Sensors</span>
            </a>
            <a href="simulation.php" class="active">
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
            <h1 class="page-title">Simulation Settings</h1>
            <p class="page-description">Configure and manage air quality data simulation for each sensor</p>
            
            <div class="simulation-controls">
                <form method="POST" action="" class="generate-form">
                    <button type="button" onclick="confirmGeneration()" class="generate-btn">
                        <i class="fas fa-play"></i>
                        Generate Readings
                    </button>
                </form>
            </div>
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

        <div class="simulation-grid">
            <?php foreach ($simulations as $sim): ?>
                <div class="simulation-card">
                    <div class="simulation-header">
                        <div class="sensor-info">
                            <h3 class="sensor-name"><?php echo htmlspecialchars($sim['sensor_id']); ?></h3>
                            <div class="sensor-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($sim['location_name']); ?></span>
                            </div>
                        </div>
                        <div class="simulation-toggle">
                            <input type="checkbox" 
                                   class="toggle-input" 
                                   id="toggle_<?php echo $sim['sensor_id']; ?>"
                                   <?php echo $sim['is_active'] ? 'checked' : ''; ?>>
                            <label class="toggle-label" for="toggle_<?php echo $sim['sensor_id']; ?>"></label>
                        </div>
                    </div>

                    <form class="simulation-form" method="POST" action="">
                        <input type="hidden" name="sensor_id" value="<?php echo htmlspecialchars($sim['sensor_id']); ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Base AQI Value</label>
                            <input type="number" 
                                   name="base_aqi" 
                                   class="form-input" 
                                   value="<?php echo htmlspecialchars($sim['base_aqi']); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Minimum Variation</label>
                            <input type="number" 
                                   name="variation_min" 
                                   class="form-input" 
                                   value="<?php echo htmlspecialchars($sim['variation_min']); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Maximum Variation</label>
                            <input type="number" 
                                   name="variation_max" 
                                   class="form-input" 
                                   value="<?php echo htmlspecialchars($sim['variation_max']); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Update Frequency (minutes)</label>
                            <input type="number" 
                                   name="update_frequency" 
                                   class="form-input" 
                                   value="<?php echo htmlspecialchars($sim['update_frequency']); ?>" 
                                   required>
                        </div>

                        <input type="hidden" name="is_active" value="<?php echo $sim['is_active']; ?>">
                        <button type="submit" name="update_simulation" class="submit-btn">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        // Handle simulation toggle changes
        document.querySelectorAll('.toggle-input').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const sensorId = this.id.replace('toggle_', '');
                const form = this.closest('.simulation-card').querySelector('form');
                const isActiveInput = form.querySelector('[name="is_active"]');
                isActiveInput.value = this.checked ? '1' : '0';
                
                // Show confirmation message
                Swal.fire({
                    title: this.checked ? 'Start Simulation?' : 'Stop Simulation?',
                    text: this.checked ? 
                        'This will start generating simulated data for this sensor.' : 
                        'This will stop generating simulated data for this sensor.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#4CAF50',
                    cancelButtonColor: '#666',
                    confirmButtonText: this.checked ? 'Start Simulation' : 'Stop Simulation',
                    showClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOutUp'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    } else {
                        this.checked = !this.checked;
                        isActiveInput.value = this.checked ? '1' : '0';
                    }
                });
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

        // Add this function to your existing JavaScript
        function confirmGeneration() {
            Swal.fire({
                title: 'Generate New Readings?',
                html: `
                    <div style="text-align: left; margin-top: 1rem;">
                        <p>This will generate new readings for all active sensors based on their simulation settings.</p>
                        <ul style="margin-top: 1rem; margin-left: 1.5rem;">
                            <li>AQI values will be generated based on base value and variations</li>
                            <li>Temperature and humidity will be simulated</li>
                            <li>Readings will be marked as simulated data</li>
                        </ul>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4CAF50',
                cancelButtonColor: '#666',
                confirmButtonText: 'Generate',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Generating Readings...',
                        html: 'Please wait while we generate new readings for all active sensors.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit the form
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = '<input type="hidden" name="generate_readings" value="1">';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html> 