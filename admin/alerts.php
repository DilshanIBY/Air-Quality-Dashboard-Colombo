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
$alerts = [];
$thresholds = [];

try {
    $pdo = getDBConnection();
    
    // Get all alert thresholds
    $stmt = $pdo->query("
        SELECT * FROM alert_thresholds
        ORDER BY min_value ASC
    ");
    $thresholds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent alerts from system logs
    $stmt = $pdo->query("
        SELECT sl.*, u.username
        FROM system_logs sl
        LEFT JOIN users u ON sl.user_id = u.id
        WHERE sl.log_type = 'warning'
        ORDER BY sl.created_at DESC
        LIMIT 50
    ");
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Update the form handling section
if (isset($_POST['update_threshold'])) {
    $id = $_POST['threshold_id'] ?? '';
    $min_value = $_POST['min_value'] ?? '';
    $max_value = $_POST['max_value'] ?? '';
    $color = $_POST['color'] ?? '';
    $description = $_POST['description'] ?? '';
    $alert_message = $_POST['alert_message'] ?? '';
    
    try {
        $stmt = $pdo->prepare("
            UPDATE alert_thresholds 
            SET min_value = ?,
                max_value = ?,
                color = ?,
                description = ?,
                alert_message = ?
            WHERE id = ?
        ");
        $stmt->execute([$min_value, $max_value, $color, $description, $alert_message, $id]);
        $message = "Alert threshold updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating alert threshold: " . $e->getMessage();
    }
}

// Function to get severity class based on AQI value
function getSeverityClass($aqi) {
    if ($aqi >= 300) return 'hazardous';
    if ($aqi >= 200) return 'very-unhealthy';
    if ($aqi >= 150) return 'unhealthy';
    if ($aqi >= 100) return 'moderate';
    return 'good';
}

// Function to get severity text based on AQI value
function getSeverityText($aqi) {
    if ($aqi >= 300) return 'Hazardous';
    if ($aqi >= 200) return 'Very Unhealthy';
    if ($aqi >= 150) return 'Unhealthy';
    if ($aqi >= 100) return 'Moderate';
    return 'Good';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alert Management - Air Quality Monitoring</title>
    
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
            
            /* Alert severity colors */
            --good-color: #4CAF50;
            --moderate-color: #FF9800;
            --unhealthy-color: #F44336;
            --very-unhealthy-color: #9C27B0;
            --hazardous-color: #880E4F;
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

        .alerts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .thresholds-section,
        .history-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
        }

        .section-title {
            font-size: 1.5rem;
            color: var(--text-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .threshold-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            transition: all var(--transition-speed);
        }

        .threshold-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .threshold-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .sensor-info {
            flex: 1;
        }

        .sensor-name {
            font-size: 1.1rem;
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

        .threshold-form {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.2);
        }

        .alert-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .alert-item {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: all var(--transition-speed);
        }

        .alert-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .alert-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .alert-content {
            flex: 1;
        }

        .alert-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .alert-title {
            font-weight: 600;
            color: var(--text-color);
        }

        .alert-time {
            font-size: 0.85rem;
            color: #666;
        }

        .alert-message {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        /* Severity classes */
        .good {
            background: rgba(76, 175, 80, 0.1);
            color: var(--good-color);
        }

        .moderate {
            background: rgba(255, 152, 0, 0.1);
            color: var(--moderate-color);
        }

        .unhealthy {
            background: rgba(244, 67, 54, 0.1);
            color: var(--unhealthy-color);
        }

        .very-unhealthy {
            background: rgba(156, 39, 176, 0.1);
            color: var(--very-unhealthy-color);
        }

        .hazardous {
            background: rgba(136, 14, 79, 0.1);
            color: var(--hazardous-color);
        }

        /* Message styles */
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
        body.dark-mode .threshold-card,
        body.dark-mode .alert-item,
        body.dark-mode .thresholds-section,
        body.dark-mode .history-section {
            background: #2d2d2d;
        }

        body.dark-mode .form-input {
            background: #333;
            color: white;
            border-color: #444;
        }

        /* Responsive design */
        @media (max-width: 1024px) {
            .alerts-container {
                grid-template-columns: 1fr;
            }
        }

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

            .threshold-form {
                grid-template-columns: 1fr;
            }

            .alert-item {
                flex-direction: column;
            }

            .alert-icon {
                align-self: flex-start;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <i class="fas fa-bell"></i>
            <span>Alert Management</span>
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
            <a href="simulation.php">
                <i class="fas fa-microchip"></i>
                <span>Simulation</span>
            </a>
            <a href="alerts.php" class="active">
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
            <h1 class="page-title">Alert Management</h1>
            <p class="page-description">Configure alert thresholds and monitor system alerts</p>
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

        <div class="alerts-container">
            <section class="thresholds-section">
                <h2 class="section-title">
                    <i class="fas fa-sliders"></i>
                    Alert Thresholds
                </h2>
                
                <?php foreach ($thresholds as $threshold): ?>
                    <div class="threshold-card">
                        <div class="threshold-header">
                            <div class="sensor-info">
                                <h3 class="sensor-name"><?php echo htmlspecialchars($threshold['category']); ?></h3>
                                <div class="threshold-range">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Range: <?php echo htmlspecialchars($threshold['min_value']); ?> - <?php echo htmlspecialchars($threshold['max_value']); ?></span>
                                </div>
                            </div>
                        </div>

                        <form class="threshold-form" method="POST" action="">
                            <input type="hidden" name="threshold_id" value="<?php echo htmlspecialchars($threshold['id']); ?>">
                            
                            <div class="form-group">
                                <label class="form-label">Minimum Value</label>
                                <input type="number" 
                                       name="min_value" 
                                       class="form-input" 
                                       value="<?php echo htmlspecialchars($threshold['min_value']); ?>" 
                                       required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Maximum Value</label>
                                <input type="number" 
                                       name="max_value" 
                                       class="form-input" 
                                       value="<?php echo htmlspecialchars($threshold['max_value']); ?>" 
                                       required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Color</label>
                                <input type="color" 
                                       name="color" 
                                       class="form-input" 
                                       value="<?php echo htmlspecialchars($threshold['color']); ?>" 
                                       required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" 
                                          class="form-input" 
                                          required><?php echo htmlspecialchars($threshold['description']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Alert Message</label>
                                <textarea name="alert_message" 
                                          class="form-input" 
                                          required><?php echo htmlspecialchars($threshold['alert_message']); ?></textarea>
                            </div>

                            <button type="submit" name="update_threshold" class="submit-btn">
                                <i class="fas fa-save"></i>
                                Save Changes
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="history-section">
                <h2 class="section-title">
                    <i class="fas fa-history"></i>
                    Recent Alerts
                </h2>
                
                <div class="alert-list">
                    <?php foreach ($alerts as $alert): ?>
                        <div class="alert-item">
                            <div class="alert-icon <?php echo strtolower($alert['log_type']); ?>">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="alert-content">
                                <div class="alert-header">
                                    <h3 class="alert-title">
                                        <?php echo htmlspecialchars($alert['log_type']); ?>
                                        <?php if ($alert['username']): ?>
                                            - by <?php echo htmlspecialchars($alert['username']); ?>
                                        <?php endif; ?>
                                    </h3>
                                    <span class="alert-time">
                                        <?php echo date('M d, Y H:i', strtotime($alert['created_at'])); ?>
                                    </span>
                                </div>
                                <p class="alert-message">
                                    <?php echo htmlspecialchars($alert['message']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <script>
        // Theme toggle functionality
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        }

        // Check for saved theme preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }

        // Handle form submissions with confirmation
        document.querySelectorAll('.threshold-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: 'Update Alert Thresholds?',
                    text: 'This will update the alert thresholds for this sensor.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#4CAF50',
                    cancelButtonColor: '#666',
                    confirmButtonText: 'Update',
                    showClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOutUp'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
</body>
</html> 