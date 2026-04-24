<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vote - Online Voting System</title>
  <link rel="stylesheet" href="css/styles.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
  <?php
  require_once 'includes/auth.php';
  requireLogin();
  $user = getCurrentUser();
  ?>

  <nav class="navbar">
    <div class="nav-container">
      <a href="index.html" class="nav-logo">
        <i class="fas fa-vote-yea"></i>
        VoteSecure
      </a>
      <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="vote.php" class="active">Vote</a>
        <a href="results.php">Results</a>
        <?php if (isAdmin()): ?>
        <a href="admin.php">Admin</a>
        <?php endif; ?>
        <a href="profile.php">Profile</a>
        <a href="auth_api.php?action=logout">Logout</a>
      </div>
      <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </nav>

  <div class="vote-container">
    <div class="vote-header">
      <h1><i class="fas fa-vote-yea"></i> Cast Your Vote</h1>
      <p>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>. Your voter ID: <strong><?php echo htmlspecialchars($user['voter_id']); ?></strong></p>
    </div>

    <div class="vote-content">
      <div id="votingInterface">
        <div class="loading-state">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Loading voting interface...</p>
        </div>
      </div>

      <div id="voteConfirmation" class="hidden">
        <div class="confirmation-card">
          <div class="confirmation-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <h2>Vote Submitted Successfully!</h2>
          <p>Your vote has been recorded and counted. Thank you for participating in this democratic process.</p>
          <div class="confirmation-details">
            <p><strong>Voter ID:</strong> <?php echo htmlspecialchars($user['voter_id']); ?></p>
            <p><strong>Time:</strong> <span id="voteTime"></span></p>
          </div>
          <div class="confirmation-actions">
            <a href="results.php" class="btn primary">
              <i class="fas fa-chart-bar"></i>
              View Results
            </a>
            <a href="dashboard.php" class="btn secondary">
              <i class="fas fa-home"></i>
              Back to Dashboard
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="js/vote.js"></script>
</body>
</html>
