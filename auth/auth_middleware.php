<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Check if user is logged in and redirect to login if not
 */
function requireLogin() {
    if (!isAuthenticated()) {
        header('Location: ../auth/login.php');
        exit;
    }
}

/**
 * Check if user has required role
 */
function requireRole($requiredRole, $errorMessage = 'Unauthorized') {
    requireLogin();
    
    if ($_SESSION['role'] !== $requiredRole && $_SESSION['role'] !== 'system_admin') {
        try {
            $pdo = getDBConnection();
            // Log unauthorized access attempt
            $stmt = $pdo->prepare("
                INSERT INTO system_logs (log_type, message, user_id, full_name, ip_address) 
                VALUES ('security', ?, ?, ?, ?)
            ");
            $message = "Unauthorized access attempt to {$_SERVER['REQUEST_URI']} requiring role: {$requiredRole}";
            $stmt->execute(['security', $message, $_SESSION['user_id'], $_SESSION['full_name'], $_SERVER['REMOTE_ADDR']]);
        } catch (PDOException $e) {
            error_log("Auth middleware error: " . $e->getMessage());
        }

        // Redirect to dashboard with error
        header("Location: ../admin/dashboard.php?error=" . urlencode($errorMessage));
        exit;
    }
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isset($_SESSION['role']) && ($_SESSION['role'] === $role || $_SESSION['role'] === 'system_admin');
}

/**
 * Get current user's ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username
 */
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Get current user's role
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}