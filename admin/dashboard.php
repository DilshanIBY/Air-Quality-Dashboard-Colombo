<?php
require_once '../config/config.php';
require_once '../auth/auth_middleware.php';

// Require login and admin role
requireRole('monitoring_admin');

// Get user information
$username = getCurrentUsername();
$role = getUserRole();

// Get system statistics
try {
    $pdo = getDBConnection();
    
    // Get sensor counts
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_sensors,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_sensors,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_sensors,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_sensors
        FROM sensors
    ");
    $sensorStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get simulation status
    $stmt = $pdo->query("
        SELECT COUNT(*) as active_simulations 
        FROM simulation_settings 
        WHERE is_active = 1
    ");
    $simulationStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent alerts
    $stmt = $pdo->query("
        SELECT message, created_at 
        FROM system_logs 
        WHERE log_type IN ('warning', 'error') 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = 'Error loading dashboard data';
}

// Handle error message
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Air Quality Monitoring System</title>
    
    <!-- Modern Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4CAF50;
            --error-color: #f44336;
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
            transition: transform var(--transition-speed);
        }

        .nav-brand:hover {
            transform: scale(1.05);
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
            transform: translateY(-2px);
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

        .dashboard-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-header h1 {
            font-size: 1.5rem;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .user-info {
            color: #666;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: white;
            border-radius: 6px;
            box-shadow: var(--card-shadow);
            transition: transform var(--transition-speed);
        }

        .user-info:hover {
            transform: translateY(-2px);
        }

        .role-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .error-message {
            background-color: #ffebee;
            color: var(--error-color);
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .dashboard-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-speed);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .card-header i {
            color: var(--primary-color);
            font-size: 1.25rem;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            text-align: center;
            transition: all var(--transition-speed);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-color);
            transform: scaleX(0);
            transition: transform var(--transition-speed);
        }
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0.5rem 0;
            transition: color var(--transition-speed);
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
            padding: 1rem;
        }
        
        .action-button {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 2rem;
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 16px;
            text-decoration: none;
            color: var(--text-color);
            transition: all var(--transition-speed);
            position: relative;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin: 0.5rem;
        }
        
        .action-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(76, 175, 80, 0.1);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform var(--transition-speed);
            z-index: 0;
        }
        
        .action-button:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }
        
        .action-button:hover::before {
            transform: scaleX(1);
            transform-origin: left;
        }
        
        .action-button i {
            font-size: 2rem;
            color: var(--primary-color);
            transition: all var(--transition-speed);
            z-index: 1;
            min-width: 40px;
            text-align: center;
        }
        
        .action-button:hover i {
            transform: scale(1.2) rotate(10deg);
        }
        
        .action-button-content {
            z-index: 1;
            flex: 1;
        }
        
        .action-button strong {
            display: block;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            transition: color var(--transition-speed);
        }
        
        .action-button p {
            font-size: 0.95rem;
            color: #666;
            margin: 0;
            transition: color var(--transition-speed);
            line-height: 1.4;
        }
        
        .action-button:hover strong,
        .action-button:hover p {
            color: var(--primary-color);
        }

        .alerts-section {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-speed);
            border: 2px solid var(--border-color);
            margin: 2rem 0;
        }
        
        .alerts-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }
        
        .alert-item {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            transition: all var(--transition-speed);
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            position: relative;
            overflow: hidden;
            margin: 0.5rem 0;
        }
        
        .alert-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--primary-color);
            transform: scaleY(0);
            transition: transform var(--transition-speed);
        }
        
        .alert-item:hover {
            background-color: rgba(76, 175, 80, 0.05);
            transform: translateX(8px);
            border-radius: 8px;
        }
        
        .alert-item:hover::before {
            transform: scaleY(1);
        }
        
        .alert-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .alert-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
            transition: transform var(--transition-speed);
            min-width: 40px;
            text-align: center;
            padding-top: 0.25rem;
        }
        
        .alert-item:hover .alert-icon {
            transform: scale(1.2) rotate(10deg);
        }
        
        .alert-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .alert-message {
            font-weight: 500;
            font-size: 1.1rem;
            transition: color var(--transition-speed);
            line-height: 1.4;
        }
        
        .alert-time {
            font-size: 0.9rem;
            color: #666;
            transition: color var(--transition-speed);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-time i {
            font-size: 1rem;
        }
        
        .alert-item:hover .alert-message,
        .alert-item:hover .alert-time {
            color: var(--primary-color);
        }

        /* Dark mode styles for alerts and actions */
        body.dark-mode .action-button {
            background: #2d2d2d;
            border-color: #333;
        }
        
        body.dark-mode .action-button p {
            color: #aaa;
        }
        
        body.dark-mode .alerts-section {
            background: #2d2d2d;
            border-color: #333;
        }
        
        body.dark-mode .alert-item {
            border-color: #333;
        }
        
        body.dark-mode .alert-time {
            color: #aaa;
        }

        /* Dark mode toggle */
        .theme-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 0.75rem;
            border-radius: 50%;
            box-shadow: var(--card-shadow);
            cursor: pointer;
            transition: all var(--transition-speed);
            z-index: 1000;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--primary-color);
        }

        .theme-toggle:hover {
            transform: scale(1.1) rotate(15deg);
            background: var(--primary-color);
        }

        .theme-toggle:hover i {
            color: white;
            transform: scale(1.2);
        }

        .theme-toggle i {
            font-size: 1.25rem;
            color: var(--primary-color);
            transition: all var(--transition-speed);
        }

        body.dark-mode .theme-toggle {
            background: #2d2d2d;
            border-color: #4CAF50;
        }

        body.dark-mode .theme-toggle i {
            color: #4CAF50;
        }

        body.dark-mode .theme-toggle:hover {
            background: #4CAF50;
        }

        body.dark-mode .theme-toggle:hover i {
            color: #2d2d2d;
        }

        /* Add floating animation */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }

        .theme-toggle {
            animation: float 3s ease-in-out infinite;
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
        body.dark-mode .dashboard-card,
        body.dark-mode .stat-card,
        body.dark-mode .action-button,
        body.dark-mode .alerts-section {
            background: #2d2d2d;
        }

        body.dark-mode .user-info {
            background: #2d2d2d;
            color: #ffffff;
        }

        body.dark-mode .stat-label,
        body.dark-mode .alert-time {
            color: #aaa;
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

            .dashboard-header {
                flex-direction: column;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Dark mode toggle animations */
        @keyframes darkModePulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        @keyframes darkModeStars {
            0% { transform: translateY(0) rotate(0deg); opacity: 0; }
            50% { transform: translateY(-20px) rotate(180deg); opacity: 1; }
            100% { transform: translateY(-40px) rotate(360deg); opacity: 0; }
        }

        @keyframes darkModeGlow {
            0% { box-shadow: 0 0 5px rgba(76, 175, 80, 0.5); }
            50% { box-shadow: 0 0 20px rgba(76, 175, 80, 0.8); }
            100% { box-shadow: 0 0 5px rgba(76, 175, 80, 0.5); }
        }

        /* Logout Confirmation Modal */
        .logout-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .logout-modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            transform: scale(0.9);
            animation: modalPop 0.3s ease-out forwards;
            max-width: 400px;
            width: 90%;
        }

        @keyframes modalPop {
            0% { transform: scale(0.9); opacity: 0; }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); opacity: 1; }
        }

        .logout-modal-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .logout-modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .logout-modal-message {
            color: #666;
            margin-bottom: 1.5rem;
        }

        .logout-modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .logout-modal-button {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-speed);
            border: 2px solid var(--border-color);
        }

        .logout-modal-button.cancel {
            background: white;
            color: var(--text-color);
        }

        .logout-modal-button.confirm {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .logout-modal-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .logout-modal-button.cancel:hover {
            background: #f5f5f5;
        }

        .logout-modal-button.confirm:hover {
            background: #43A047;
        }

        body.dark-mode .logout-modal-content {
            background: #2d2d2d;
        }

        body.dark-mode .logout-modal-title {
            color: white;
        }

        body.dark-mode .logout-modal-message {
            color: #aaa;
        }

        body.dark-mode .logout-modal-button.cancel {
            background: #2d2d2d;
            color: white;
            border-color: #333;
        }

        body.dark-mode .logout-modal-button.cancel:hover {
            background: #333;
        }

        .dark-mode-transition {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
            display: none;
        }

        .dark-mode-transition .star {
            position: absolute;
            width: 4px;
            height: 4px;
            background: #4CAF50;
            border-radius: 50%;
            animation: darkModeStars 1s ease-out forwards;
        }

        body.dark-mode .theme-toggle {
            animation: darkModePulse 0.5s ease-out, darkModeGlow 2s infinite;
        }

        body.dark-mode .navbar,
        body.dark-mode .dashboard-card,
        body.dark-mode .stat-card,
        body.dark-mode .action-button,
        body.dark-mode .alerts-section {
            animation: darkModePulse 0.5s ease-out;
        }

        /* Responsive adjustments for Quick Actions */
        @media (max-width: 1200px) {
            .action-buttons {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .action-buttons {
                grid-template-columns: 1fr;
                gap: 1rem;
                padding: 0.5rem;
            }
            
            .action-button {
                padding: 1.5rem;
                margin: 0.25rem;
            }
            
            .action-button i {
                font-size: 1.75rem;
            }
        }

        /* Responsive adjustments for Alerts */
        @media (max-width: 768px) {
            .alerts-section {
                padding: 1.5rem;
            }
            
            .alert-item {
                padding: 1.25rem;
                gap: 1rem;
            }
            
            .alert-icon {
                font-size: 1.25rem;
                min-width: 32px;
            }
            
            .alert-message {
                font-size: 1rem;
            }
            
            .alert-time {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <i class="fas fa-leaf"></i>
            <span>Air Quality Admin</span>
        </div>
        <div class="nav-links">
            <a href="dashboard.php" class="active">
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
        <div class="dashboard-header">
            <h1>System Dashboard</h1>
            <div class="user-info">
                <i class="fas fa-user"></i>
                <span><?php echo htmlspecialchars($username); ?></span>
                <span class="role-badge"><?php echo htmlspecialchars(ucfirst($role)); ?></span>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $sensorStats['total_sensors']; ?></div>
                <div class="stat-label">Total Sensors</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $sensorStats['active_sensors']; ?></div>
                <div class="stat-label">Active Sensors</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $simulationStats['active_simulations']; ?></div>
                <div class="stat-label">Active Simulations</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $sensorStats['maintenance_sensors']; ?></div>
                <div class="stat-label">Sensors in Maintenance</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <a href="sensors.php?action=add" class="action-button">
                <i class="fas fa-plus-circle"></i>
                <div class="action-button-content">
                    <strong>Add New Sensor</strong>
                    <p>Register a new sensor location</p>
                </div>
            </a>
            <a href="simulation.php" class="action-button">
                <i class="fas fa-play-circle"></i>
                <div class="action-button-content">
                    <strong>Manage Simulation</strong>
                    <p>Configure and control data simulation</p>
                </div>
            </a>
            <a href="alerts.php" class="action-button">
                <i class="fas fa-bell"></i>
                <div class="action-button-content">
                    <strong>Configure Alerts</strong>
                    <p>Set up alert thresholds</p>
                </div>
            </a>
            <a href="users.php" class="action-button">
                <i class="fas fa-user-plus"></i>
                <div class="action-button-content">
                    <strong>Manage Users</strong>
                    <p>Add or modify admin accounts</p>
                </div>
            </a>
        </div>

        <!-- Recent Alerts -->
        <h2>Recent System Alerts</h2>
        <div class="alerts-section">
            <?php if (empty($recentAlerts)): ?>
                <div class="alert-item">
                    <i class="fas fa-check-circle alert-icon"></i>
                    <div class="alert-content">
                        <div class="alert-message">No recent alerts</div>
                        <div class="alert-time">
                            <i class="fas fa-clock"></i>
                            <span>All systems are running smoothly</span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($recentAlerts as $alert): ?>
                    <div class="alert-item">
                        <i class="fas fa-exclamation-triangle alert-icon"></i>
                        <div class="alert-content">
                            <div class="alert-message"><?php echo htmlspecialchars($alert['message']); ?></div>
                            <div class="alert-time">
                                <i class="fas fa-clock"></i>
                                <span><?php echo date('M j, Y H:i', strtotime($alert['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Theme Toggle Button -->
    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark Mode">
        <i class="fas fa-moon"></i>
    </button>

    <!-- Dark Mode Transition Overlay -->
    <div class="dark-mode-transition" id="darkModeTransition"></div>

    <!-- Logout Confirmation Modal -->
    <div class="logout-modal" id="logoutModal">
        <div class="logout-modal-content">
            <i class="fas fa-sign-out-alt logout-modal-icon"></i>
            <h3 class="logout-modal-title">Ready to Leave?</h3>
            <p class="logout-modal-message">Are you sure you want to log out? We'll miss you! 🌱</p>
            <div class="logout-modal-buttons">
                <button class="logout-modal-button cancel" onclick="hideLogoutModal()">Stay Here</button>
                <a href="../auth/logout.php" class="logout-modal-button confirm">Yes, Logout</a>
            </div>
        </div>
    </div>

    <script>
        // Theme toggle functionality with animations
        function toggleTheme() {
            const body = document.body;
            const icon = document.querySelector('.theme-toggle i');
            const transitionOverlay = document.getElementById('darkModeTransition');
            
            // Create stars animation
            transitionOverlay.style.display = 'block';
            transitionOverlay.innerHTML = '';
            
            for (let i = 0; i < 20; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                star.style.left = Math.random() * 100 + '%';
                star.style.top = Math.random() * 100 + '%';
                star.style.animationDelay = Math.random() * 0.5 + 's';
                transitionOverlay.appendChild(star);
            }
            
            // Toggle dark mode after a short delay
            setTimeout(() => {
                body.classList.toggle('dark-mode');
                icon.classList.toggle('fa-moon');
                icon.classList.toggle('fa-sun');
                
                // Add bounce effect to theme toggle
                const button = document.querySelector('.theme-toggle');
                button.style.animation = 'none';
                button.offsetHeight; // Trigger reflow
                button.style.animation = 'float 3s ease-in-out infinite';
                
                // Hide transition overlay
                setTimeout(() => {
                    transitionOverlay.style.display = 'none';
                }, 1000);
            }, 500);
        }

        // Logout confirmation functionality
        function showLogoutModal() {
            const modal = document.getElementById('logoutModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function hideLogoutModal() {
            const modal = document.getElementById('logoutModal');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        // Close modal when clicking outside
        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideLogoutModal();
            }
        });

        // Update logout link to show modal
        document.querySelector('a[href="../auth/logout.php"]').addEventListener('click', function(e) {
            e.preventDefault();
            showLogoutModal();
        });

        // Add hover effects to cards
        document.querySelectorAll('.dashboard-card, .stat-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });

        // Add animation to stats
        document.querySelectorAll('.stat-value').forEach(stat => {
            const value = parseInt(stat.textContent);
            let current = 0;
            const duration = 2000;
            const increment = value / (duration / 16);
            
            const animate = () => {
                current += increment;
                if (current < value) {
                    stat.textContent = Math.floor(current);
                    requestAnimationFrame(animate);
                } else {
                    stat.textContent = value;
                }
            };
            
            animate();
        });

        // Add smooth scroll behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html> 