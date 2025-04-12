<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    
    // Test query to get all sensors
    $query = "SELECT * FROM sensors";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test query to get all AQI readings
    $query2 = "SELECT * FROM aqi_readings";
    $stmt2 = $conn->prepare($query2);
    $stmt2->execute();
    $readings = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'sensors' => $sensors,
        'readings' => $readings,
        'sensor_count' => count($sensors),
        'reading_count' => count($readings)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
