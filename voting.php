<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

require_login();

$user_id = $_SESSION['user_id'];
$has_voted = $_SESSION['has_voted'];
$positions = get_positions();
$voting_open = is_voting_open();

if ($has_voted) {
    redirect(BASE_URL . '/voting_complete.php');
}

if (!$voting_open) {
    redirect(BASE_URL . '/index.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cast Your Vote - Online Voting System</title>
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

        <div class="voting-section">
            <div class="voting-title">Cast Your Votes</div>
            
            <div class="voting-status">
                <strong>✓ Voting is currently open</strong> - Please select one candidate for each position
            </div>

            <div class="voting-instructions">
                <strong>Instructions:</strong>
                <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                    <li>Click on a candidate card to select them</li>
                    <li>You must vote for all positions</li>
                    <li>Once you submit your votes, they cannot be changed</li>
                    <li>Review your selections carefully before confirming</li>
                </ul>
            </div>

            <!-- Voting Interface -->
            <?php foreach ($positions as $position): ?>
                <div class="voting-section">
                    <h2 style="margin-bottom: 1.5rem; color: var(--primary-color);"><?php echo escape_output($position); ?></h2>
                    
                    <div class="grid grid-cols-3">
                        <?php 
                        $candidates = get_candidates_by_position($position);
                        foreach ($candidates as $candidate):
                        ?>
                            <div class="candidate-card" 
                                 data-candidate-id="<?php echo $candidate['id']; ?>"
                                 data-position="<?php echo escape_output($position); ?>">
                                
                                <div class="candidate-card-image">
                                    <?php if ($candidate['image_path']): ?>
                                        <img src="<?php echo escape_output($candidate['image_path']); ?>" alt="<?php echo escape_output($candidate['name']); ?>">
                                    <?php else: ?>
                                        👤
                                    <?php endif; ?>
                                </div>
                                
                                <div class="candidate-card-content">
                                    <div class="candidate-name"><?php echo escape_output($candidate['name']); ?></div>
                                    <?php if ($candidate['bio']): ?>
                                        <div class="candidate-bio"><?php echo escape_output($candidate['bio']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Selected Candidates Display -->
            <div style="margin-top: 2rem; background: var(--light-bg); padding: 1.5rem; border-radius: var(--radius);" id="selectedCandidates">
                <h3>Your Selections:</h3>
                <p style="color: var(--text-light);">No candidates selected yet</p>
            </div>

            <!-- Submit Button -->
            <button id="submitVotes" class="btn btn-success" style="margin-top: 2rem; display: none; width: 100%; padding: 1rem; font-size: 1.1rem;">
                Review & Submit Votes
            </button>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
