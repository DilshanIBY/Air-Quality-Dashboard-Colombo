<?php
require_once '../config/config.php';

try {
    $pdo = getDBConnection();
    
    // Hash the password 'admin'
    $password = 'admin';
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Update existing admin password
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password = ?, 
                status = 'active',
                updated_at = CURRENT_TIMESTAMP 
            WHERE username = 'admin'
        ");
        $stmt->execute([$hashedPassword]);
        echo "Admin password updated successfully. Username: admin, Password: admin";
    } else {
        // Create new admin user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, role, email, full_name, status) 
            VALUES ('admin', ?, 'system_admin', 'admin@airquality.lk', 'System Administrator', 'active')
        ");
        $stmt->execute([$hashedPassword]);
        echo "Admin user created successfully. Username: admin, Password: admin";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 