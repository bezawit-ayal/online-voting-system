<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

require_login();

$positions = get_positions();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - Online Voting System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="navbar-container">
        <div class="navbar-brand">Online Voting System</div>
        <div class="navbar-menu">
            <a href="index.php">Dashboard</a>
            <a href="voting.php">Vote</a>
            <a href="results.php">Results</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</nav>

<!-- CONTAINER -->
<div class="container">

    <h1 style="margin-bottom: 2rem; color: var(--primary-color);">
        Election Results
    </h1>

    <?php foreach ($positions as $position): ?>

        <?php
        $results = get_vote_results($position);

        // total votes for this position
        $total_votes = 0;
        foreach ($results as $r) {
            $total_votes += $r['vote_count'];
        }
        ?>

        <!-- POSITION CARD -->
        <div class="card" style="margin-bottom: 3rem;">

            <div class="card-header" style="margin-bottom: 1.5rem;">
                <?php echo escape_output($position); ?>
            </div>

            <!-- RESULTS GRID -->
            <div class="results-grid">

                <?php foreach ($results as $result): ?>

                    <?php
                    // safe percentage calculation
                    $percentage = ($total_votes > 0)
                        ? ($result['vote_count'] / $total_votes) * 100
                        : 0;
                    ?>

                    <div class="result-card">

                        <!-- TOP -->
                        <div class="result-top">

                            <div class="result-avatar">
                                <?php if (!empty($result['image_path'])): ?>
                                    <img src="<?php echo escape_output($result['image_path']); ?>" alt="candidate">
                                <?php else: ?>
                                    <div style="font-size:30px;">👤</div>
                                <?php endif; ?>
                            </div>

                            <div>
                                <div class="result-name">
                                    <?php echo escape_output($result['name']); ?>
                                </div>

                                <div class="result-meta">
                                    <?php echo escape_output($position); ?>
                                </div>
                            </div>

                        </div>

                        <!-- PROGRESS BAR -->
                        <div class="progress-bar">
                            <div class="progress-fill"
                                 style="width: <?php echo $percentage; ?>%;">
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="result-footer">

                            <div class="percent">
                                <?php echo number_format($percentage, 1); ?>%
                            </div>

                            <div class="votes">
                                <?php echo number_format($result['vote_count']); ?> votes
                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <!-- TOTAL -->
            <div style="margin-top: 1rem; text-align: right; color: #666;">
                <strong>Total Votes: <?php echo number_format($total_votes); ?></strong>
            </div>

        </div>

    <?php endforeach; ?>

</div>

</body>
</html>