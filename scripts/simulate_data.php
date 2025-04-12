<?php
require_once '../config/config.php';

// Function to generate realistic AQI value
function generateAQIValue($baseValue = 50, $variance = 20) {
    // Add random variation to base value
    $variation = rand(-$variance, $variance);
    $aqi = $baseValue + $variation;
    
    // Ensure AQI is within valid range (0-500)
    return max(0, min(500, $aqi));
}

try {
    $conn = getDBConnection();
    
    // Get all active sensors
    $query = "SELECT id, sensor_id FROM sensors WHERE status = 'active'";
    $stmt = $conn->query($query);
    $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate and insert new readings for each sensor
    foreach ($sensors as $sensor) {
        $aqi = generateAQIValue();
        
        $query = "
            INSERT INTO aqi_readings (sensor_id, aqi_value)
            VALUES (:sensor_id, :aqi_value)
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            'sensor_id' => $sensor['id'],
            'aqi_value' => $aqi
        ]);
    }
    
    echo "Data simulation completed successfully\n";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
