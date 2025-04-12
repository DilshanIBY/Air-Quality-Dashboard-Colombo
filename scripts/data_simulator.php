<?php
require_once '../config/config.php';

// Function to generate simulated sensor data
function generateSensorData($baseAQI, $minVar, $maxVar) {
    // Generate random variation within the configured range
    $variation = rand($minVar * 100, $maxVar * 100) / 100;
    $aqi = max(0, min(500, $baseAQI + $variation));
    
    // Generate correlated PM2.5 and PM10 values
    $pm25 = $aqi * 0.8 + (rand(-50, 50) / 10);
    $pm10 = $aqi * 0.6 + (rand(-50, 50) / 10);
    
    // Generate temperature (20-35°C) and humidity (40-90%)
    $temperature = rand(200, 350) / 10;
    $humidity = rand(400, 900) / 10;
    
    return [
        'aqi_value' => round($aqi, 2),
        'pm25_value' => round($pm25, 2),
        'pm10_value' => round($pm10, 2),
        'temperature' => $temperature,
        'humidity' => $humidity
    ];
}

// Function to log simulation activity
function logSimulation($message, $type = 'info') {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO system_logs (log_type, message)
        VALUES (?, ?)
    ");
    $stmt->execute([$type, $message]);
}

try {
    $conn = getDBConnection();
    
    // Get active simulation settings
    $stmt = $conn->prepare("
        SELECT s.sensor_id, s.location_name, sim.*
        FROM sensors s
        INNER JOIN simulation_settings sim ON s.sensor_id = sim.sensor_id
        WHERE s.status = 'active' AND sim.is_active = TRUE
    ");
    $stmt->execute();
    $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $simulatedCount = 0;
    foreach ($sensors as $sensor) {
        // Generate simulated data
        $data = generateSensorData(
            $sensor['base_aqi'],
            $sensor['variation_min'],
            $sensor['variation_max']
        );
        
        // Insert the simulated reading
        $stmt = $conn->prepare("
            INSERT INTO aqi_readings 
            (sensor_id, aqi_value, pm25_value, pm10_value, temperature, humidity, is_simulated)
            VALUES (?, ?, ?, ?, ?, ?, TRUE)
        ");
        
        $stmt->execute([
            $sensor['sensor_id'],
            $data['aqi_value'],
            $data['pm25_value'],
            $data['pm10_value'],
            $data['temperature'],
            $data['humidity']
        ]);
        
        $simulatedCount++;
    }
    
    // Log simulation results
    logSimulation("Successfully generated data for {$simulatedCount} sensors.");
    
    echo json_encode([
        'success' => true,
        'message' => "Generated data for {$simulatedCount} sensors",
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    logSimulation("Simulation error: " . $e->getMessage(), 'error');
    echo json_encode([
        'success' => false,
        'error' => 'Data simulation failed',
        'message' => $e->getMessage()
    ]);
}
?> 