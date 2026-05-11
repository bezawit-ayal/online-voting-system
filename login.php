<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (is_logged_in()) {
    redirect(BASE_URL . '/index.php');
}

$login_error = '';
$login_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = login_user($username, $password);
    
    if ($result['success']) {
        redirect(BASE_URL . '/index.php');
    } else {
        $login_error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Online Voting System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">🗳️ Online Voting System</div>
            <div class="navbar-menu">
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            </div>
        </div>
    </nav>

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-title">Login</div>

            <?php if ($login_error): ?>
                <div class="alert alert-error">
                    <?php echo escape_output($login_error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>

          <a href="admin/admin_login.php" class="btn btn-secondary btn-block" style="margin-top: 10px; text-align: center; display: block;">
                Admin Login
            </a>
            </form>

            <div class="auth-link">
                Don't have an account? <a href="register.php">Register here</a>
            </div>

            <!-- Demo Credentials -->
            <div class="alert alert-info" style="margin-top: 1.5rem;">
                <strong>Demo Credentials:</strong>
                <br>Username: admin
                <br>Password: admin123
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
