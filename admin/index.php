<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_candidates = $conn->query("SELECT COUNT(*) as count FROM candidates")->fetch_assoc()['count'];
$total_votes = $conn->query("SELECT COUNT(*) as count FROM votes")->fetch_assoc()['count'];
$users_voted = $conn->query("SELECT COUNT(*) as count FROM users WHERE has_voted = 1")->fetch_assoc()['count'];
$settings = get_election_settings();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Online Voting System</title>
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

        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-welcome">Admin Dashboard</div>
            <div class="dashboard-subtitle"><?php echo escape_output($settings['election_title']); ?></div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card" style="border-top-color: #2563eb;">
                <div class="stat-value"><?php echo number_format($total_users); ?></div>
                <div class="stat-label">Total Voters</div>
            </div>
            <div class="stat-card" style="border-top-color: #16a34a;">
                <div class="stat-value"><?php echo number_format($users_voted); ?></div>
                <div class="stat-label">Votes Cast</div>
            </div>
            <div class="stat-card" style="border-top-color: #ea580c;">
                <div class="stat-value"><?php echo number_format($total_candidates); ?></div>
                <div class="stat-label">Candidates</div>
            </div>
            <div class="stat-card" style="border-top-color: #dc2626;">
                <div class="stat-value"><?php echo number_format($total_votes); ?></div>
                <div class="stat-label">Total Votes</div>
            </div>
        </div>

        <!-- Admin Sections -->
        <div class="grid grid-cols-2">
            <!-- Candidates Management -->
            <div class="admin-section">
                <div class="admin-section-title">Manage Candidates</div>
                <p>Add, edit, or remove candidates from the election.</p>
                <a href="candidates.php" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                    Manage Candidates
                </a>
            </div>

            <!-- Users Management -->
            <div class="admin-section">
                <div class="admin-section-title">Manage Voters</div>
                <p>View and manage voter accounts.</p>
                <a href="users.php" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                    Manage Voters
                </a>
            </div>

            <!-- Settings -->
            <div class="admin-section">
                <div class="admin-section-title">Election Settings</div>
                <p>Configure election parameters and voting schedule.</p>
                <a href="settings.php" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                    Election Settings
                </a>
            </div>

            <!-- Results -->
            <div class="admin-section">
                <div class="admin-section-title">View Results</div>
                <p>View detailed election results and analytics.</p>
                <a href="results.php" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                    View Results
                </a>
            </div>
        </div>

        <!-- Quick Stats Table -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">Election Overview</div>
            <table class="table">
                <tr>
                    <td style="font-weight: bold; width: 200px;">Total Registered Voters</td>
                    <td><?php echo number_format($total_users); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Votes Cast</td>
                    <td><?php echo number_format($users_voted); ?> (<?php echo $total_users > 0 ? number_format(($users_voted / $total_users) * 100, 2) : 0; ?>%)</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Total Candidates</td>
                    <td><?php echo number_format($total_candidates); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Total Votes Recorded</td>
                    <td><?php echo number_format($total_votes); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Voting Status</td>
                    <td><?php echo is_voting_open() ? '<span style="color: green; font-weight: bold;">🟢 Open</span>' : '<span style="color: red; font-weight: bold;">🔴 Closed</span>'; ?></td>
                </tr>
            </table>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
