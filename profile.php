<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile - Online Voting System</title>
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
        <a href="vote.php">Vote</a>
        <a href="results.php">Results</a>
        <?php if (isAdmin()): ?>
        <a href="admin.php">Admin</a>
        <?php endif; ?>
        <a href="profile.php" class="active">Profile</a>
        <a href="auth_api.php?action=logout">Logout</a>
      </div>
      <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </nav>

  <div class="profile-container">
    <div class="profile-header">
      <div class="profile-avatar">
        <i class="fas fa-user"></i>
      </div>
      <div class="profile-info">
        <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
        <p>@<?php echo htmlspecialchars($user['username']); ?></p>
        <div class="profile-status">
          <span class="status-badge">
            <i class="fas fa-shield-alt"></i>
            Verified Voter
          </span>
          <?php if (isAdmin()): ?>
          <span class="status-badge admin">
            <i class="fas fa-crown"></i>
            Administrator
          </span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="profile-content">
      <div class="profile-section">
        <h2><i class="fas fa-user-edit"></i> Personal Information</h2>
        <form id="profileForm" class="profile-form">
          <div class="form-row">
            <div class="form-group">
              <label for="fullName">Full Name</label>
              <input type="text" id="fullName" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>
            <div class="form-group">
              <label for="username">Username</label>
              <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
              <label for="voterId">Voter ID</label>
              <input type="text" id="voterId" value="<?php echo htmlspecialchars($user['voter_id']); ?>" readonly>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn primary">
              <i class="fas fa-save"></i>
              Update Profile
            </button>
          </div>
        </form>
      </div>

      <div class="profile-section">
        <h2><i class="fas fa-lock"></i> Change Password</h2>
        <form id="passwordForm" class="profile-form">
          <div class="form-group">
            <label for="currentPassword">Current Password</label>
            <input type="password" id="currentPassword" name="current_password" required>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="newPassword">New Password</label>
              <input type="password" id="newPassword" name="new_password" required>
            </div>
            <div class="form-group">
              <label for="confirmPassword">Confirm New Password</label>
              <input type="password" id="confirmPassword" name="confirm_password" required>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn secondary">
              <i class="fas fa-key"></i>
              Change Password
            </button>
          </div>
        </form>
      </div>

      <div class="profile-section">
        <h2><i class="fas fa-history"></i> Account Activity</h2>
        <div class="activity-timeline">
          <div class="activity-item">
            <div class="activity-icon">
              <i class="fas fa-calendar-plus"></i>
            </div>
            <div class="activity-content">
              <h4>Account Created</h4>
              <p>Joined VoteSecure platform</p>
              <span class="activity-date">Recently</span>
            </div>
          </div>
          <div class="activity-item">
            <div class="activity-icon">
              <i class="fas fa-sign-in-alt"></i>
            </div>
            <div class="activity-content">
              <h4>Last Login</h4>
              <p>Successfully logged into account</p>
              <span class="activity-date">Today</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="js/profile.js"></script>
</body>
</html>