<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

$positions = get_positions();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detailed Results - Admin Panel</title>
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

        <h1 style="margin-bottom: 2rem; color: var(--primary-color);">Detailed Election Results</h1>

        <?php foreach ($positions as $position): ?>
            <div class="card" style="margin-bottom: 3rem;">
                <div class="card-header" style="margin-bottom: 1.5rem;">
                    <?php echo escape_output($position); ?>
                </div>

                <?php
                $results = get_vote_results($position);
                $total_votes = 0;
                foreach ($results as $result) {
                    $total_votes += $result['vote_count'];
                }
                ?>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Candidate Name</th>
                            <th>Votes</th>
                            <th>Percentage</th>
                            <th>Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                            <tr>
                                <td><?php echo escape_output($result['name']); ?></td>
                                <td><strong><?php echo number_format($result['vote_count']); ?></strong></td>
                                <td><?php echo number_format($result['percentage'], 2); ?>%</td>
                                <td>
                                    <div class="progress-bar" style="width: 100%;">
                                        <div class="progress-fill" style="width: <?php echo $result['percentage']; ?>%;"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color); text-align: right; color: var(--text-light);">
                    <strong>Total Votes: <?php echo number_format($total_votes); ?></strong>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Export Options -->
        <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            <button class="btn btn-primary" onclick="printPage()">Print Results</button>
            <button class="btn btn-primary" onclick="exportResultsCSV()">Export as CSV</button>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
