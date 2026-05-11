<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

require_login();

if (!$_SESSION['has_voted']) {
    redirect(BASE_URL . '/index.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Complete - Online Voting System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">🗳️ Online Voting System</div>
            <div class="navbar-menu">
                <a href="index.php">Dashboard</a>
                <a href="voting.php">Vote</a>
                <a href="results.php">Results</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div style="max-width: 600px; margin: 4rem auto; text-align: center;">
            <div class="card">
                <div style="font-size: 4rem; margin-bottom: 1rem;">✓</div>
                
                <h1 style="color: var(--success-color); margin-bottom: 1rem;">Voting Complete!</h1>
                
                <p style="font-size: 1.1rem; color: var(--text-light); margin-bottom: 1.5rem;">
                    Thank you for participating in the election. Your votes have been successfully recorded and counted.
                </p>

                <div class="alert alert-success">
                    <strong>Your vote is important!</strong> We appreciate your participation in this democratic process.
                </div>

                <div style="margin-top: 2rem;">
                    <p style="color: var(--text-light); margin-bottom: 1rem;">
                        You can view the current election results and return to the dashboard using the buttons below.
                    </p>

                    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
                        <a href="index.php" class="btn btn-primary">Back to Dashboard</a>
                        <a href="results.php" class="btn btn-secondary">View Results</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
