<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

$message = '';
$error = '';

// Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $voter_id = $_POST['voter_id'] ?? '';
    
    if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($voter_id)) {
        $error = 'All fields are required';
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, voter_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $voter_id);
        
        if ($stmt->execute()) {
            $message = 'User added successfully';
        } else {
            $error = 'Error adding user (username or email may already exist)';
        }
        $stmt->close();
    }
}

// Delete User
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = intval($_GET['id']);
    
    // Prevent deleting admin account
    $check = $conn->query("SELECT role FROM users WHERE id = $id");
    $user = $check->fetch_assoc();
    
    if ($user['role'] === 'admin') {
        $error = 'Cannot delete admin accounts';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = 'User deleted successfully';
        } else {
            $error = 'Error deleting user';
        }
        $stmt->close();
    }
}

$result = $conn->query("SELECT id, username, email, full_name, voter_id, has_voted, role FROM users ORDER BY created_at DESC");
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">🗳️ Admin Panel</div>
            <div class="navbar-menu">
                <a href="index.php">Dashboard</a>
                <a href="candidates.php">Candidates</a>
                <a href="users.php">Users</a>
                <a href="settings.php">Settings</a>
                <a href="results.php">Results</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div id="alertContainer"></div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo escape_output($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo escape_output($error); ?></div>
        <?php endif; ?>

        <!-- Add User Form -->
        <div class="admin-section">
            <div class="admin-section-title">Add New User</div>
            
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="grid grid-cols-2">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>

                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="voter_id">Voter ID *</label>
                        <input type="text" id="voter_id" name="voter_id" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">Add User</button>
            </form>
        </div>

        <!-- Users List -->
        <div class="admin-section" style="margin-top: 2rem;">
            <div class="admin-section-title">All Users (<?php echo count($users); ?>)</div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Voter ID</th>
                        <th>Voted</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo escape_output($user['username']); ?></td>
                            <td><?php echo escape_output($user['email']); ?></td>
                            <td><?php echo escape_output($user['full_name']); ?></td>
                            <td><?php echo escape_output($user['voter_id']); ?></td>
                            <td>
                                <?php echo $user['has_voted'] ? '<span style="color: green;">✓ Yes</span>' : '<span style="color: orange;">✗ No</span>'; ?>
                            </td>
                            <td>
                                <span style="background-color: <?php echo $user['role'] === 'admin' ? '#2563eb' : '#6b7280'; ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.85rem;">
                                    <?php echo escape_output(ucfirst($user['role'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['role'] !== 'admin'): ?>
                                    <a href="?action=delete&id=<?php echo $user['id']; ?>" 
                                       class="btn btn-danger btn-small"
                                       onclick="return confirm('Are you sure you want to delete this user?');">
                                        Delete
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <a href="index.php" class="btn btn-secondary" style="margin-top: 2rem;">Back to Dashboard</a>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
