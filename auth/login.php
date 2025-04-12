<?php
require_once '../config/config.php';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../admin/dashboard.php');
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $pdo = getDBConnection();
        
        // Get user from database
        $stmt = $pdo->prepare("
            SELECT id, username, password, role, status 
            FROM users 
            WHERE username = ? 
            AND status = 'active'
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password and status
        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $updateStmt = $pdo->prepare("
                UPDATE users 
                SET last_login = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $updateStmt->execute([$user['id']]);

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Log successful login
            $logStmt = $pdo->prepare("
                INSERT INTO system_logs (log_type, message, user_id, ip_address) 
                VALUES ('security', 'Successful login', ?, ?)
            ");
            $logStmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);

            header('Location: ../admin/dashboard.php');
            exit;
        } else {
            // Log failed attempt
            $logStmt = $pdo->prepare("
                INSERT INTO system_logs (log_type, message, ip_address) 
                VALUES ('security', 'Failed login attempt for username: ' || ?, ?)
            ");
            $logStmt->execute([$username, $_SERVER['REMOTE_ADDR']]);

            $error = 'Invalid username or password';
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = 'An error occurred. Please try again later.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Air Quality Monitoring System</title>
    
    <!-- Modern Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4CAF50;
            --error-color: #f44336;
            --text-color: #333;
            --border-color: #ddd;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .login-header h1 {
            color: var(--text-color);
            font-size: 1.5rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-login:hover {
            background-color: #43a047;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--text-color);
            text-decoration: none;
            margin-top: 1rem;
            font-weight: 500;
        }

        .back-link i {
            margin-right: 0.5rem;
        }

        .error-message {
            background-color: #ffebee;
            color: var(--error-color);
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }

        .error-message i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-leaf"></i>
            <h1>Admin Login</h1>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           required 
                           autocomplete="username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           required 
                           autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Login
            </button>
        </form>

        <a href="../index.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Homepage
        </a>
    </div>
</body>
</html> 