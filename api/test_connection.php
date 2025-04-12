<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    
    // Test sensors table
    $stmt = $pdo->query("SELECT COUNT(*) as sensor_count FROM sensors");
    $sensorCount = $stmt->fetch(PDO::FETCH_ASSOC)['sensor_count'];
    
    // Test aqi_readings table
    $stmt = $pdo->query("SELECT COUNT(*) as reading_count FROM aqi_readings");
    $readingCount = $stmt->fetch(PDO::FETCH_ASSOC)['reading_count'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'data' => [
            'sensor_count' => $sensorCount,
            'reading_count' => $readingCount
        ]
    ]);
} catch (PDOException $e) {
    error_log("Database connection test failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ]);
}
?> 