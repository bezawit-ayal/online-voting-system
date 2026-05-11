<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

$message = '';
$error = '';

// Add Candidate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $_POST['name'] ?? '';
    $position = $_POST['position'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    if (empty($name) || empty($position)) {
        $error = 'Name and Position are required';
    } else {
        $stmt = $conn->prepare("INSERT INTO candidates (name, position, bio) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $position, $bio);
        
        if ($stmt->execute()) {
            $message = 'Candidate added successfully';
        } else {
            $error = 'Error adding candidate';
        }
        $stmt->close();
    }
}

// Delete Candidate
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM candidates WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = 'Candidate deleted successfully';
    } else {
        $error = 'Error deleting candidate';
    }
    $stmt->close();
}

$candidates = get_candidates_by_position();
$positions = get_positions();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates - Admin Panel</title>
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
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo escape_output($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo escape_output($error); ?></div>
        <?php endif; ?>

        <!-- Add Candidate Form -->
        <div class="admin-section">
            <div class="admin-section-title">Add New Candidate</div>
            
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="name">Candidate Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="position">Position *</label>
                    <input type="text" id="position" name="position" placeholder="e.g., President, Vice President" required>
                </div>

                <div class="form-group">
                    <label for="bio">Biography</label>
                    <textarea id="bio" name="bio" placeholder="Brief biography of the candidate"></textarea>
                </div>

                <button type="submit" class="btn btn-success">Add Candidate</button>
            </form>
        </div>

        <!-- Candidates List -->
        <div class="admin-section" style="margin-top: 2rem;">
            <div class="admin-section-title">All Candidates</div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Biography</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($candidates) > 0): ?>
                        <?php foreach ($candidates as $candidate): ?>
                            <tr>
                                <td><?php echo escape_output($candidate['name']); ?></td>
                                <td><?php echo escape_output($candidate['position']); ?></td>
                                <td><?php echo escape_output(substr($candidate['bio'] ?? '', 0, 50)) . (strlen($candidate['bio'] ?? '') > 50 ? '...' : ''); ?></td>
                                <td>
                                    <a href="?action=delete&id=<?php echo $candidate['id']; ?>" 
                                       class="btn btn-danger btn-small"
                                       onclick="return confirm('Are you sure you want to delete this candidate?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-light);">No candidates found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <a href="index.php" class="btn btn-secondary" style="margin-top: 2rem;">Back to Dashboard</a>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
