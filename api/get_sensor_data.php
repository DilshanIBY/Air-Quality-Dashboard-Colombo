<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    
    // Get latest AQI readings for active sensors only
    $query = "
        SELECT 
            s.sensor_id,
            s.location_name,
            CAST(s.latitude AS DECIMAL(10,8)) as latitude,
            CAST(s.longitude AS DECIMAL(11,8)) as longitude,
            s.status,
            COALESCE(ar.aqi_value, 0) as aqi_value,
            COALESCE(ar.timestamp, s.created_at) as timestamp,
            COALESCE(
                (SELECT AVG(aqi_value) 
                FROM aqi_readings 
                WHERE sensor_id = s.sensor_id 
                AND timestamp >= NOW() - INTERVAL 24 HOUR),
                0
            ) as avg_24h
        FROM sensors s
        LEFT JOIN (
            SELECT ar1.*
            FROM aqi_readings ar1
            INNER JOIN (
                SELECT sensor_id, MAX(timestamp) as max_timestamp
                FROM aqi_readings
                GROUP BY sensor_id
            ) ar2 ON ar1.sensor_id = ar2.sensor_id AND ar1.timestamp = ar2.max_timestamp
        ) ar ON s.sensor_id = ar.sensor_id
        WHERE s.status = 'active'
        AND s.latitude BETWEEN -90 AND 90
        AND s.longitude BETWEEN -180 AND 180
        ORDER BY s.location_name
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no active sensors found
    if (empty($result)) {
        echo json_encode([
            'sensors' => [],
            'message' => 'No active sensors found'
        ]);
        exit;
    }

    // Format the response
    $formattedResult = array_map(function($row) {
        return [
            'sensor_id' => $row['sensor_id'],
            'location_name' => $row['location_name'],
            'latitude' => floatval($row['latitude']),
            'longitude' => floatval($row['longitude']),
            'status' => $row['status'],
            'aqi_value' => floatval($row['aqi_value']),
            'avg_24h' => floatval($row['avg_24h']),
            'timestamp' => $row['timestamp']
        ];
    }, $result);

    echo json_encode([
        'sensors' => $formattedResult,
        'count' => count($formattedResult),
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    error_log("Error in get_sensor_data.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred',
        'message' => 'Unable to fetch sensor data. Please try again later.'
    ]);
}
?>
