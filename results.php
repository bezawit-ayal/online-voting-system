<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Results - Online Voting System</title>
  <link rel="stylesheet" href="css/styles.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
  <?php
  require_once 'includes/auth.php';
  $isLoggedIn = isLoggedIn();
  ?>

  <nav class="navbar">
    <div class="nav-container">
      <a href="index.html" class="nav-logo">
        <i class="fas fa-vote-yea"></i>
        VoteSecure
      </a>
      <div class="nav-links">
        <a href="index.html">Home</a>
        <?php if ($isLoggedIn): ?>
        <a href="dashboard.php">Dashboard</a>
        <a href="vote.php">Vote</a>
        <?php endif; ?>
        <a href="results.php" class="active">Results</a>
        <?php if ($isLoggedIn): ?>
        <?php if (isAdmin()): ?>
        <a href="admin.php">Admin</a>
        <?php endif; ?>
        <a href="profile.php">Profile</a>
        <a href="auth_api.php?action=logout">Logout</a>
        <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
        <?php endif; ?>
      </div>
      <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </nav>

  <div class="results-container">
    <div class="results-header">
      <h1><i class="fas fa-chart-bar"></i> Live Election Results</h1>
      <p>Real-time voting results updated automatically</p>
      <button id="refreshResults" class="btn secondary">
        <i class="fas fa-sync-alt"></i>
        Refresh Results
      </button>
    </div>

    <div class="results-content">
      <div id="resultsArea">
        <div class="loading-state">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Loading election results...</p>
        </div>
      </div>

      <div class="results-summary" id="resultsSummary" style="display: none;">
        <div class="summary-card">
          <h3>Election Summary</h3>
          <div class="summary-stats">
            <div class="summary-stat">
              <span class="stat-number" id="totalCandidates">0</span>
              <span class="stat-label">Candidates</span>
            </div>
            <div class="summary-stat">
              <span class="stat-number" id="totalVotes">0</span>
              <span class="stat-label">Total Votes</span>
            </div>
            <div class="summary-stat">
              <span class="stat-number" id="participationRate">0%</span>
              <span class="stat-label">Participation</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="js/results.js"></script>
</body>
</html>
