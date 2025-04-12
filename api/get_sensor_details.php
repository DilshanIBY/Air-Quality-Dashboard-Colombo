<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_GET['sensor_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Sensor ID is required']);
    exit;
}

$sensor_id = $_GET['sensor_id'];

try {
    $pdo = getDBConnection();

    // Get sensor details with latest readings
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            ar.aqi_value,
            ar.pm25_value,
            ar.pm10_value,
            ar.temperature,
            ar.humidity,
            ar.timestamp as reading_timestamp,
            (
                SELECT COUNT(*) 
                FROM aqi_readings 
                WHERE sensor_id = s.sensor_id
            ) as total_readings
        FROM sensors s
        LEFT JOIN (
            SELECT ar1.*
            FROM aqi_readings ar1
            INNER JOIN (
                SELECT sensor_id, MAX(timestamp) as max_timestamp
                FROM aqi_readings
                GROUP BY sensor_id
            ) ar2 ON ar1.sensor_id = ar2.sensor_id 
            AND ar1.timestamp = ar2.max_timestamp
        ) ar ON s.sensor_id = ar.sensor_id
        WHERE s.sensor_id = ?
    ");
    
    $stmt->execute([$sensor_id]);
    $sensorData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sensorData) {
        http_response_code(404);
        echo json_encode(['error' => 'Sensor not found']);
        exit;
    }

    // Get historical readings (last 24 hours)
    $stmt = $pdo->prepare("
        SELECT 
            aqi_value,
            pm25_value,
            pm10_value,
            temperature,
            humidity,
            timestamp
        FROM aqi_readings
        WHERE sensor_id = ?
        AND timestamp >= NOW() - INTERVAL 24 HOUR
        ORDER BY timestamp ASC
    ");
    
    $stmt->execute([$sensor_id]);
    $historicalData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'sensor' => $sensorData,
            'historical' => $historicalData
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_sensor_details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
?> 