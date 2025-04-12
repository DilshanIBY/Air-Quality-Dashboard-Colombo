<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();

    // Get count of active sensors
    $stmt = $pdo->query("SELECT COUNT(*) as active_sensors FROM sensors WHERE status = 'active'");
    $activeSensors = $stmt->fetch(PDO::FETCH_ASSOC)['active_sensors'];

    // Get latest AQI readings for each active sensor and calculate average
    $stmt = $pdo->query("
        WITH LatestReadings AS (
            SELECT ar.sensor_id, ar.aqi_value
            FROM aqi_readings ar
            INNER JOIN (
                SELECT sensor_id, MAX(timestamp) as max_timestamp
                FROM aqi_readings
                GROUP BY sensor_id
            ) latest ON ar.sensor_id = latest.sensor_id 
            AND ar.timestamp = latest.max_timestamp
            INNER JOIN sensors s ON ar.sensor_id = s.sensor_id
            WHERE s.status = 'active'
        )
        SELECT 
            COUNT(*) as sensor_count,
            SUM(aqi_value) as total_aqi,
            ROUND(SUM(aqi_value) / COUNT(*), 1) as average_aqi
        FROM LatestReadings
    ");
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $averageAQI = $result['average_aqi'] ?? 0;

    echo json_encode([
        'success' => true,
        'data' => [
            'active_sensors' => $activeSensors,
            'average_aqi' => $averageAQI
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_statistics.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
?> 