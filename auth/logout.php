<?php
require_once '../config/config.php';

if (isset($_SESSION['user_id'])) {
    try {
        $pdo = getDBConnection();
        
        // Ensure full_name is set in session
        $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['full_name'] = $user['full_name'] ?? 'Unknown';

        // Log the logout
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (log_type, message, user_id, full_name, ip_address) 
            VALUES ('security', 'User logged out', ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['full_name'], $_SERVER['REMOTE_ADDR']]);
    } catch (PDOException $e) {
        error_log("Logout error: " . $e->getMessage());
    }
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;