<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Online Voting System</title>
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
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="vote.php">Vote</a>
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

  <div class="dashboard-container">
    <div class="dashboard-header">
      <div class="welcome-section">
        <h1>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
        <p>Your voter ID: <strong><?php echo htmlspecialchars($user['voter_id']); ?></strong></p>
      </div>
      <div class="user-status">
        <div class="status-card">
          <i class="fas fa-user-check"></i>
          <span>Verified Voter</span>
        </div>
      </div>
    </div>

    <div class="dashboard-grid">
      <div class="dashboard-card voting-status">
        <div class="card-header">
          <h3><i class="fas fa-vote-yea"></i> Voting Status</h3>
        </div>
        <div class="card-content">
          <div id="votingStatus">
            <div class="status-loading">
              <i class="fas fa-spinner fa-spin"></i>
              <p>Checking voting status...</p>
            </div>
          </div>
        </div>
      </div>

      <div class="dashboard-card quick-stats">
        <div class="card-header">
          <h3><i class="fas fa-chart-bar"></i> Quick Stats</h3>
        </div>
        <div class="card-content">
          <div class="stats-grid">
            <div class="stat-item">
              <div class="stat-value" id="totalCandidates">0</div>
              <div class="stat-label">Candidates</div>
            </div>
            <div class="stat-item">
              <div class="stat-value" id="totalVotesCast">0</div>
              <div class="stat-label">Votes Cast</div>
            </div>
            <div class="stat-item">
              <div class="stat-value" id="myVoteStatus">-</div>
              <div class="stat-label">My Vote</div>
            </div>
          </div>
        </div>
      </div>

      <div class="dashboard-card recent-activity">
        <div class="card-header">
          <h3><i class="fas fa-history"></i> Recent Activity</h3>
        </div>
        <div class="card-content">
          <div id="recentActivity">
            <div class="activity-item">
              <i class="fas fa-sign-in-alt"></i>
              <div class="activity-content">
                <p>Logged in to your account</p>
                <span class="activity-time">Just now</span>
              </div>
            </div>
            <div class="activity-item">
              <i class="fas fa-user-plus"></i>
              <div class="activity-content">
                <p>Account created successfully</p>
                <span class="activity-time">Recently</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="dashboard-card quick-actions">
        <div class="card-header">
          <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
        </div>
        <div class="card-content">
          <div class="action-buttons">
            <a href="vote.php" class="action-btn primary">
              <i class="fas fa-vote-yea"></i>
              Cast Vote
            </a>
            <a href="results.php" class="action-btn secondary">
              <i class="fas fa-chart-bar"></i>
              View Results
            </a>
            <a href="profile.php" class="action-btn outline">
              <i class="fas fa-user-edit"></i>
              Edit Profile
            </a>
          </div>
        </div>
      </div>
    </div>

    <div class="dashboard-notifications">
      <div class="notification-card">
        <i class="fas fa-info-circle"></i>
        <div class="notification-content">
          <h4>Election Information</h4>
          <p>The current election is open for voting. Make sure to cast your vote before the deadline.</p>
        </div>
      </div>
    </div>
  </div>

  <script src="js/dashboard.js"></script>
</body>
</html>