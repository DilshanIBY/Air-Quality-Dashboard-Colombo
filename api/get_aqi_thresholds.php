<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();

    // Get AQI thresholds ordered by min_value
    $stmt = $pdo->query("
        SELECT 
            category,
            min_value,
            max_value,
            color,
            description
        FROM alert_thresholds
        ORDER BY min_value ASC
    ");
    
    $thresholds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $thresholds
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_aqi_thresholds.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
?> 