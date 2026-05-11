<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    redirect(BASE_URL . '/index.php');
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$has_voted = $_SESSION['has_voted'];
$settings = get_election_settings();
$voting_open = is_voting_open();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Online Voting System</title>
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
        <div id="alertContainer"></div>

        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-welcome">Welcome, <?php echo escape_output($username); ?>!</div>
            <div class="dashboard-subtitle"><?php echo escape_output($settings['election_title']); ?></div>
        </div>

        <!-- Status Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo is_logged_in() ? '✓' : '✗'; ?></div>
                <div class="stat-label">Login Status</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $voting_open ? '✓' : '✗'; ?></div>
                <div class="stat-label">Voting Open</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $has_voted ? '✓' : '○'; ?></div>
                <div class="stat-label">Voting Status</div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-2">
            <!-- Voting Section -->
            <div class="card">
                <div class="card-header">Cast Your Vote</div>
                <p>Use this election to cast your vote for your preferred candidates.</p>
                
                <?php if ($voting_open): ?>
                    <?php if ($has_voted): ?>
                        <div class="alert alert-success" style="margin-top: 1rem;">
                            ✓ You have successfully cast your votes in this election.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" style="margin-top: 1rem;">
                            You have not voted yet. Click the button below to begin voting.
                        </div>
                        <a href="voting.php" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                            Start Voting
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-error" style="margin-top: 1rem;">
                        ✗ Voting is currently closed.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Results Section -->
            <div class="card">
                <div class="card-header">View Results</div>
                <p>See the current voting results for all positions.</p>
                <a href="results.php" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                    View Results
                </a>
            </div>
        </div>

        <!-- Election Information -->
        <div class="card">
            <div class="card-header">Election Information</div>
            <table class="table">
                <tr>
                    <td style="font-weight: bold; width: 200px;">Election Title</td>
                    <td><?php echo escape_output($settings['election_title']); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Start Date & Time</td>
                    <td><?php echo escape_output($settings['election_start']); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">End Date & Time</td>
                    <td><?php echo escape_output($settings['election_end']); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Voting Status</td>
                    <td><?php echo $voting_open ? '<span style="color: green; font-weight: bold;">🟢 Open</span>' : '<span style="color: red; font-weight: bold;">🔴 Closed</span>'; ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Your Voting Status</td>
                    <td><?php echo $has_voted ? '<span style="color: green; font-weight: bold;">✓ Completed</span>' : '<span style="color: orange; font-weight: bold;">⏳ Pending</span>'; ?></td>
                </tr>
            </table>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
