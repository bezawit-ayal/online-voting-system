<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Online Voting System</title>
  <link rel="stylesheet" href="css/styles.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
  <?php
  require_once 'includes/auth.php';
  requireAdmin();
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
        <a href="admin.php" class="active">Admin</a>
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

  <div class="admin-container">
    <div class="admin-header">
      <h1><i class="fas fa-cog"></i> Admin Panel</h1>
      <p>Manage the voting system and monitor activities</p>
    </div>

    <div class="admin-grid">
      <div class="admin-card">
        <div class="card-header">
          <h3><i class="fas fa-users"></i> User Management</h3>
        </div>
        <div class="card-content">
          <div class="admin-stats">
            <div class="stat-item">
              <span class="stat-number" id="totalUsers">0</span>
              <span class="stat-label">Total Users</span>
            </div>
            <div class="stat-item">
              <span class="stat-number" id="activeUsers">0</span>
              <span class="stat-label">Active Today</span>
            </div>
          </div>
          <button class="btn primary" onclick="adminManager.switchTab('users')">
            <i class="fas fa-user-cog"></i>
            Manage Users
          </button>
        </div>
      </div>

      <div class="admin-card">
        <div class="card-header">
          <h3><i class="fas fa-vote-yea"></i> Election Management</h3>
        </div>
        <div class="card-content">
          <div class="admin-stats">
            <div class="stat-item">
              <span class="stat-number" id="totalCandidates">0</span>
              <span class="stat-label">Candidates</span>
            </div>
            <div class="stat-item">
              <span class="stat-number" id="totalVotes">0</span>
              <span class="stat-label">Total Votes</span>
            </div>
          </div>
          <button class="btn primary" onclick="adminManager.switchTab('candidates')">
            <i class="fas fa-edit"></i>
            Manage Election
          </button>
        </div>
      </div>

      <div class="admin-card">
        <div class="card-header">
          <h3><i class="fas fa-chart-bar"></i> System Statistics</h3>
        </div>
        <div class="card-content">
          <div class="admin-stats">
            <div class="stat-item">
              <span class="stat-number" id="participationRate">0%</span>
              <span class="stat-label">Participation</span>
            </div>
            <div class="stat-item">
              <span class="stat-number" id="systemHealth">100%</span>
              <span class="stat-label">System Health</span>
            </div>
          </div>
          <button class="btn secondary" onclick="adminManager.switchTab('dashboard')">
            <i class="fas fa-chart-line"></i>
            View Details
          </button>
        </div>
      </div>

      <div class="admin-card">
        <div class="card-header">
          <h3><i class="fas fa-shield-alt"></i> Security & Audit</h3>
        </div>
        <div class="card-content">
          <div class="admin-stats">
            <div class="stat-item">
              <span class="stat-number" id="failedLogins">0</span>
              <span class="stat-label">Failed Logins</span>
            </div>
            <div class="stat-item">
              <span class="stat-number" id="suspiciousActivity">0</span>
              <span class="stat-label">Suspicious Activity</span>
            </div>
          </div>
          <button class="btn outline" onclick="adminManager.switchTab('audit')">
            <i class="fas fa-search"></i>
            View Logs
          </button>
        </div>
      </div>
    </div>

    <!-- Tab Navigation -->
    <div class="admin-tabs">
      <button class="admin-tab active" data-tab="dashboard" onclick="adminManager.switchTab('dashboard')">
        <i class="fas fa-tachometer-alt"></i> Dashboard
      </button>
      <button class="admin-tab" data-tab="users" onclick="adminManager.switchTab('users')">
        <i class="fas fa-users"></i> Users
      </button>
      <button class="admin-tab" data-tab="candidates" onclick="adminManager.switchTab('candidates')">
        <i class="fas fa-user-tie"></i> Candidates
      </button>
      <button class="admin-tab" data-tab="elections" onclick="adminManager.switchTab('elections')">
        <i class="fas fa-vote-yea"></i> Elections
      </button>
      <button class="admin-tab" data-tab="audit" onclick="adminManager.switchTab('audit')">
        <i class="fas fa-history"></i> Audit Logs
      </button>
      <button class="admin-tab" data-tab="backup" onclick="adminManager.switchTab('backup')">
        <i class="fas fa-database"></i> Backups
      </button>
    </div>

    <!-- Dashboard Section -->
    <div id="dashboardSection" class="admin-section active">
      <div class="section-header">
        <h2><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h2>
        <button class="btn secondary refresh-btn" onclick="adminManager.refreshCurrentView()">
          <i class="fas fa-sync-alt"></i> Refresh
        </button>
      </div>
      <div class="election-selector">
        <label for="electionSelect">Select Election:</label>
        <select id="electionSelect" onchange="adminManager.changeElection(this.value)">
          <option value="">Loading...</option>
        </select>
      </div>
      <div id="electionList" class="election-grid">
        <div class="loading-state">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Loading elections...</p>
        </div>
      </div>
    </div>

    <!-- Users Section -->
    <div id="usersSection" class="admin-section">
      <div class="section-header">
        <h2><i class="fas fa-users"></i> User Management</h2>
        <div class="search-box">
          <input type="text" class="admin-search" placeholder="Search users..." oninput="adminManager.handleSearch(this.value)">
        </div>
      </div>
      <div id="userList" class="data-container">
        <div class="loading-state">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Loading users...</p>
        </div>
      </div>
    </div>

    <!-- Candidates Section -->
    <div id="candidatesSection" class="admin-section">
      <div class="section-header">
        <h2><i class="fas fa-user-tie"></i> Candidate Management</h2>
        <button class="btn primary" onclick="adminManager.showAddCandidate()">
          <i class="fas fa-plus"></i> Add Candidate
        </button>
      </div>
      <div id="candidateList" class="data-container">
        <div class="loading-state">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Loading candidates...</p>
        </div>
      </div>
    </div>

    <!-- Elections Section -->
    <div id="electionsSection" class="admin-section">
      <div class="section-header">
        <h2><i class="fas fa-vote-yea"></i> Election Management</h2>
        <button class="btn primary" onclick="adminManager.showAddElection()">
          <i class="fas fa-plus"></i> Create Election
        </button>
        <button class="btn danger" onclick="adminManager.resetElection()">
          <i class="fas fa-trash"></i> Reset All
        </button>
      </div>
      <div id="electionManagementList" class="data-container">
        <div class="loading-state">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Loading elections...</p>
        </div>
      </div>
    </div>

    <!-- Audit Section -->
    <div id="auditSection" class="admin-section">
      <div class="section-header">
        <h2><i class="fas fa-history"></i> Audit Logs</h2>
        <button class="btn secondary export-btn" data-type="audit" onclick="adminManager.exportData('audit')">
          <i class="fas fa-download"></i> Export
        </button>
      </div>
      <div id="auditList" class="data-container">
        <div class="loading-state">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Loading audit logs...</p>
        </div>
      </div>
    </div>

    <!-- Backup Section -->
    <div id="backupSection" class="admin-section">
      <div class="section-header">
        <h2><i class="fas fa-database"></i> Backup Management</h2>
        <button class="btn primary" onclick="adminManager.createBackup()">
          <i class="fas fa-plus"></i> Create Backup
        </button>
      </div>
      <div id="backupStatus" class="data-container">
        <div class="loading-state">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Loading backup status...</p>
        </div>
      </div>
    </div>

    <!-- User Management Modal -->
    <div id="userModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>User Management</h3>
          <button class="modal-close" onclick="adminManager.closeModal('userModal')">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div id="userListModal">
            <div class="loading-state">
              <i class="fas fa-spinner fa-spin"></i>
              <p>Loading users...</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Election Management Modal -->
    <div id="electionModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Election Management</h3>
          <button class="modal-close" onclick="adminManager.closeModal('electionModal')">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="election-controls">
            <button class="btn primary" onclick="adminManager.showAddCandidate()">
              <i class="fas fa-plus"></i>
              Add Candidate
            </button>
            <button class="btn secondary" onclick="adminManager.resetElection()">
              <i class="fas fa-refresh"></i>
              Reset Election
            </button>
          </div>
          <div id="candidateList">
            <div class="loading-state">
              <i class="fas fa-spinner fa-spin"></i>
              <p>Loading candidates...</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Candidate Modal -->
    <div id="addCandidateModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Add New Candidate</h3>
          <button class="modal-close" onclick="adminManager.closeModal('addCandidateModal')">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form id="addCandidateForm">
            <div class="form-group">
              <label for="candidateName">Candidate Name</label>
              <input type="text" id="candidateName" name="name" required>
            </div>
            <div class="form-group">
              <label for="candidateParty">Party</label>
              <input type="text" id="candidateParty" name="party" placeholder="Optional">
            </div>
            <div class="form-group">
              <label for="candidateDescription">Description</label>
              <textarea id="candidateDescription" name="description" required></textarea>
            </div>
            <div class="form-group">
              <label for="candidateImage">Image URL</label>
              <input type="url" id="candidateImage" name="image_url" placeholder="https://example.com/image.jpg">
            </div>
            <div class="form-actions">
              <button type="submit" class="btn primary">Add Candidate</button>
              <button type="button" class="btn secondary" onclick="adminManager.closeModal('addCandidateModal')">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Add Election Modal -->
    <div id="addElectionModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Create New Election</h3>
          <button class="modal-close" onclick="adminManager.closeModal('addElectionModal')">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form id="addElectionForm">
            <div class="form-group">
              <label for="electionTitle">Election Title</label>
              <input type="text" id="electionTitle" name="title" required>
            </div>
            <div class="form-group">
              <label for="electionDescription">Description</label>
              <textarea id="electionDescription" name="description" required></textarea>
            </div>
            <div class="form-group">
              <label for="electionType">Election Type</label>
              <select id="electionType" name="election_type" required>
                <option value="presidential">Presidential</option>
                <option value="parliamentary">Parliamentary</option>
                <option value="local">Local</option>
                <option value="referendum">Referendum</option>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="startDate">Start Date</label>
                <input type="datetime-local" id="startDate" name="start_date" required>
              </div>
              <div class="form-group">
                <label for="endDate">End Date</label>
                <input type="datetime-local" id="endDate" name="end_date" required>
              </div>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn primary">Create Election</button>
              <button type="button" class="btn secondary" onclick="adminManager.closeModal('addElectionModal')">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
      <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading...</p>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="js/admin.js"></script>
</body>
</html>