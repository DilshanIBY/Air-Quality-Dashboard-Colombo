<?php
require_once '../config/config.php';

if (isset($_SESSION['user_id'])) {
    try {
        $pdo = getDBConnection();
        
        // Log the logout
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (log_type, message, user_id, ip_address) 
            VALUES ('security', 'User logged out', ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
    } catch (PDOException $e) {
        error_log("Logout error: " . $e->getMessage());
    }
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit; 