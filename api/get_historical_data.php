<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_GET['sensor_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Sensor ID is required']);
    exit;
}

try {
    $conn = getDBConnection();
    
    $query = "
        SELECT 
            aqi_value,
            timestamp
        FROM aqi_readings
        WHERE sensor_id = :sensor_id
        AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY timestamp ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute(['sensor_id' => $_GET['sensor_id']]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($data);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
