<?php
require_once '../config/config.php';
require_once '../auth/auth_middleware.php';

// Require system admin role
requireRole('system_admin', "Sorry. You're unauthorized to access User Management section."); 

// Initialize variables
$message = '';
$error = '';
$users = [];
$systemLogs = [];

try {
    $pdo = getDBConnection();

    // Handle user creation
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        $email = $_POST['email'] ?? '';
        $full_name = $_POST['full_name'] ?? '';

        if (empty($username) || empty($password) || empty($role) || empty($email) || empty($full_name)) {
            $error = "All fields are required.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, password, role, email, full_name, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'active', CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$username, $hashedPassword, $role, $email, $full_name]);
                $message = "User added successfully.";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Duplicate entry error
                    $error = "Username or email already exists.";
                } else {
                    $error = "Error adding user: " . $e->getMessage();
                }
            }
        }
    }

    // Handle user status update
    if (isset($_POST['update_status']) && isset($_POST['user_id']) && isset($_POST['status'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$_POST['status'], $_POST['user_id']]);
            $message = "User status updated successfully.";
        } catch (PDOException $e) {
            $error = "Error updating user status: " . $e->getMessage();
        }
    }

    // Handle user deletion
    if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
            $message = "User deleted successfully.";
        } catch (PDOException $e) {
            $error = "Error deleting user: " . $e->getMessage();
        }
    }

    // Handle user update
    if (isset($_POST['user_id']) && isset($_POST['full_name']) && isset($_POST['email']) && isset($_POST['role'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['role'], $_POST['user_id']]);
            $message = "User updated successfully.";
        } catch (PDOException $e) {
            $error = "Error updating user: " . $e->getMessage();
        }
    }

    // Fetch all users
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent system logs
    $stmt = $pdo->query("
        SELECT sl.*, u.username 
        FROM system_logs sl
        LEFT JOIN users u ON sl.user_id = u.id
        ORDER BY sl.created_at DESC 
        LIMIT 10
    ");
    $systemLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Air Quality Monitoring System</title>
    
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
            --warning-color: #ff9800;
            --text-color: #333;
            --border-color: #ddd;
            --bg-color: #f5f5f5;
            --card-shadow: 0 4px 6px rgba(0,0,0,0.1);
            --transition-speed: 0.3s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            min-height: 100vh;
            transition: background-color var(--transition-speed);
        }

        .navbar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-brand {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-links a {
            color: var(--text-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all var(--transition-speed);
            padding: 0.5rem 1rem;
            border-radius: 6px;
        }

        .nav-links a:hover {
            color: var(--primary-color);
            background: rgba(76, 175, 80, 0.1);
        }

        .nav-links a.active {
            color: var(--primary-color);
            background: rgba(76, 175, 80, 0.1);
        }

        .main-content {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.5rem;
            color: var(--text-color);
        }

        .add-user-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all var(--transition-speed);
        }

        .add-user-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.2);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .users-table th, .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .users-table th {
            font-weight: 600;
        }

        .users-table td .action-btn {
            display: inline-flex;
            margin-right: 0.5rem;
        }

        .users-table td.active {
            color: var(--primary-color);
        }

        .users-table td.inactive {
            color: var(--error-color);
        }

        .users-table tr {
            position: relative;
            transition: all var(--transition-speed);
        }

        .users-table tr::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--primary-color);
            transform: scaleY(0);
            transition: transform var(--transition-speed);
        }

        .users-table tbody tr:hover {
            background-color: rgba(76, 175, 80, 0.05);
            border-radius: 8px;
        }

        .users-table tbody tr:hover::before {
            transform: scaleY(1);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .submit-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            width: 100%;
            margin-top: 1rem;
        }

        .submit-btn:hover {
            background: #43A047;
        }

        .message {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.1);
            color: var(--primary-color);
        }

        .error-message {
            background: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
        }

        .status-toggle {
            position: relative;
            width: 60px;
            height: 30px;
        }

        .toggle-input {
            display: none;
        }

        .toggle-label {
            position: absolute;
            top: 0;
            left: 0;
            width: 60px;
            height: 30px;
            border-radius: 15px;
            background: #ddd;
            cursor: pointer;
            transition: all var(--transition-speed);
        }

        .toggle-label:after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: white;
            transition: all var(--transition-speed);
        }

        .toggle-input:checked + .toggle-label {
            background: var(--primary-color);
        }

        .toggle-input:checked + .toggle-label:after {
            left: 32px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all var(--transition-speed);
        }

        .edit-btn {
            background: rgba(76, 175, 80, 0.1);
            color: var(--primary-color);
        }

        .delete-btn {
            background: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .action-btn i {
            font-size: 1rem;
        }

        .alerts-section {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-speed);
            border: 2px solid var(--border-color);
            margin: 2rem 0;
        }

        .alert-item {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            transition: all var(--transition-speed);
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            position: relative;
            overflow: hidden;
            margin: 0.5rem 0;
        }

        .alert-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--primary-color);
            transform: scaleY(0);
            transition: transform var(--transition-speed);
        }

        .alert-item:hover{
            background-color: rgba(76, 175, 80, 0.05);
            transform: translateX(8px);
            border-radius: 8px;
        }

        .alert-item:hover::before {
            transform: scaleY(1);
        }

        .alert-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .alert-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
            transition: transform var(--transition-speed);
            min-width: 40px;
            text-align: center;
            padding-top: 0.25rem;
        }

        .alert-item:hover .alert-icon {
            transform: scale(1.2) rotate(10deg);
        }

        .alert-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .alert-message {
            font-weight: 500;
            font-size: 1.1rem;
            transition: color var(--transition-speed);
            line-height: 1.4;
        }

        .alert-time {
            font-size: 0.9rem;
            color: #666;
            transition: color var(--transition-speed);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-time i {
            font-size: 1rem;
        }

        .alert-item:hover .alert-message,
        .alert-item:hover .alert-time {
            color: var(--primary-color);
        }

        /* Dark mode styles */
        body.dark-mode {
            --bg-color: #1a1a1a;
            --text-color: #ffffff;
            --border-color: #333;
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        body.dark-mode .navbar,
        body.dark-mode .users-table,
        body.dark-mode .alerts-section,
        body.dark-mode .modal-content {
            background: #2d2d2d;
            color: var(--text-color);
        }

        body.dark-mode .nav-links a {
            color: #aaa;
        }

        body.dark-mode .nav-links a.active {
            color: var(--primary-color);
        }

        body.dark-mode .form-input {
            background: #333;
            color: white;
            border-color: #444;
        }

        body.dark-mode .form-input:focus {
            border-color: var(--primary-color);
        }

        body.dark-mode .message {
            background: rgba(76, 175, 80, 0.2);
        }

        body.dark-mode .error-message {
            background: rgba(244, 67, 54, 0.2);
        }

        body.dark-mode .toggle-label {
            background: #444;
        }

        body.dark-mode .toggle-input:checked + .toggle-label {
            background: var(--primary-color);
        }

        body.dark-mode .users-table tbody tr:hover {
            background-color: rgba(76, 175, 80, 0.1);
        }

        body.dark-mode .alert-item {
            border-color: #444;
        }

        body.dark-mode .alert-time {
            color: #aaa;
        }
    </style>

    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-material-ui/material-ui.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <i class="fas fa-leaf"></i>
            <span>Air Quality Admin</span>
        </div>
        <div class="nav-links">
            <a href="dashboard.php">
                <i class="fas fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>
            <a href="sensors.php">
                <i class="fas fa-satellite-dish"></i>
                <span>Sensors</span>
            </a>
            <a href="simulation.php">
                <i class="fas fa-microchip"></i>
                <span>Simulation</span>
            </a>
            <a href="alerts.php">
                <i class="fas fa-bell"></i>
                <span>Alerts</span>
            </a>
            <a href="users.php" class="active">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="../auth/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
            <a href="../index.php" target="_blank">
                <i class="fas fa-leaf"></i>
            </a>
        </div>
    </nav>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Manage Users</h1>
            <button class="add-user-btn" onclick="showAddUserModal()">
                <i class="fas fa-user-plus"></i>
                Add New User
            </button>
        </div>

        <?php if ($message): ?>
            <div class="message success-message">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="alerts-section">
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php 
                                $role = htmlspecialchars($user['role']); 
                                if ($role === 'system_admin') {
                                    echo 'System Admin';
                                } elseif ($role === 'monitoring_admin') {
                                    echo 'Monitoring Admin';
                                } else {
                                    echo htmlspecialchars(ucfirst($role));
                                }
                            ?>
                        </td>
                        <td>
                            <div class="status-toggle">
                                <input type="checkbox" 
                                       class="toggle-input" 
                                       id="toggle_<?php echo $user['id']; ?>" 
                                       <?php echo $user['status'] === 'active' ? 'checked' : ''; ?> 
                                       onchange="toggleUserStatus('<?php echo $user['id']; ?>', this.checked)">
                                <label class="toggle-label" for="toggle_<?php echo $user['id']; ?>"></label>
                            </div>
                        </td>
                        <td>
                            <button class="action-btn edit-btn" onclick="showEditUserModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                <i class="fas fa-edit"></i>
                                <span>Edit</span>
                            </button>
                            <button class="action-btn delete-btn" onclick="confirmDeleteUser('<?php echo $user['id']; ?>')">
                                <i class="fas fa-trash"></i>
                                <span>Delete</span>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <!-- Recent System Logs Section -->
        <h2>Recent Admin Activities</h2>
        <div class="alerts-section">
            <?php if (empty($systemLogs)): ?>
                <div class="alert-item">
                    <i class="fas fa-info-circle alert-icon"></i>
                    <div class="alert-content">
                        <div class="alert-message">No recent logs available</div>
                        <div class="alert-time">
                            <i class="fas fa-clock"></i>
                            <span>System is running smoothly</span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($systemLogs as $log): ?>
                    <div class="alert-item">
                        <i class="fas fa-<?php echo $log['log_type'] === 'error' ? 'times-circle' : ($log['log_type'] === 'warning' ? 'exclamation-triangle' : 'info-circle'); ?> alert-icon"></i>
                        <div class="alert-content">
                            <div class="alert-message">
                                <?php echo htmlspecialchars($log['message']); ?>
                                <?php if ($log['username']): ?>
                                    <span>- by <?php echo htmlspecialchars($log['username']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="alert-time">
                                <i class="fas fa-clock"></i>
                                <span><?php echo date('M j, Y H:i', strtotime($log['created_at'])); ?></span>
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($log['full_name']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add User Modal -->
    <div class="modal" id="addUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New User</h3>
                <button class="close-btn" onclick="hideAddUserModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-input" required>
                        <option value="monitoring_admin">Monitoring Admin</option>
                        <option value="system_admin">System Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-input" required>
                </div>
                <button type="submit" name="add_user" class="submit-btn">Add User</button>
            </form>
        </div>
    </div>

    <script>
        // Show and hide the add user modal
        function showAddUserModal() {
            document.getElementById('addUserModal').style.display = 'flex';
        }

        // Hide the add user modal
        function hideAddUserModal() {
            document.getElementById('addUserModal').style.display = 'none';
        }

        // Show edit user modal
        function showEditUserModal(user) {
            const editUserModal = document.createElement('div');
            editUserModal.className = 'modal';
            editUserModal.id = 'editUserModal';
            editUserModal.style.display = 'flex';
            editUserModal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">Edit User</h3>
                        <button class="close-btn" onclick="document.getElementById('editUserModal').remove()">&times;</button>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="user_id" value="${user.id}">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-input" value="${user.full_name}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" value="${user.email}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-input" required>
                                <option value="monitoring_admin" ${user.role === 'monitoring_admin' ? 'selected' : ''}>Monitoring Admin</option>
                                <option value="system_admin" ${user.role === 'system_admin' ? 'selected' : ''}>System Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="submit-btn">Save Changes</button>
                    </form>
                </div>
            `;
            document.body.appendChild(editUserModal);
        }

        // Confirm delete user
        function confirmDeleteUser(userId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f44336',
                cancelButtonColor: '#666',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="user_id" value="${userId}">
                        <input type="hidden" name="delete_user" value="1">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Toggle user status
        function toggleUserStatus(userId, isActive) {
            const status = isActive ? 'active' : 'inactive';
            Swal.fire({
                title: isActive ? 'Activate User?' : 'Deactivate User?',
                text: `This will ${isActive ? 'activate' : 'deactivate'} the user account.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4CAF50',
                cancelButtonColor: '#f44336',
                confirmButtonText: isActive ? 'Activate' : 'Deactivate'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="user_id" value="${userId}">
                        <input type="hidden" name="status" value="${status}">
                        <input type="hidden" name="update_status" value="1">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                } else {
                    document.getElementById(`toggle_${userId}`).checked = !isActive;
                }
            });
        }

        // Theme toggle functionality
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        }

        // Check for saved theme preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>