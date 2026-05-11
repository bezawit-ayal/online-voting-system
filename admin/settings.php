<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

$message = '';
$error = '';

// Update Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voting_open = isset($_POST['voting_open']) ? 1 : 0;
    $election_title = $_POST['election_title'] ?? '';
    $election_start = $_POST['election_start'] ?? '';
    $election_end = $_POST['election_end'] ?? '';
    
    if (empty($election_title)) {
        $error = 'Election title is required';
    } else {
        // Update or insert settings
        $stmt = $conn->prepare("INSERT INTO election_settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        $stmt->bind_param("sss", $name, $value, $value);
        
        $name = 'voting_open';
        $value = $voting_open;
        $stmt->execute();
        
        $name = 'election_title';
        $value = $election_title;
        $stmt->execute();
        
        $name = 'election_start';
        $value = $election_start;
        $stmt->execute();
        
        $name = 'election_end';
        $value = $election_end;
        $stmt->execute();
        
        $message = 'Settings updated successfully';
        $stmt->close();
    }
}

$settings = get_election_settings();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Settings - Admin Panel</title>
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

        <!-- Settings Form -->
        <div class="admin-section">
            <div class="admin-section-title">Election Settings</div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="election_title">Election Title *</label>
                    <input type="text" id="election_title" name="election_title" 
                           value="<?php echo escape_output($settings['election_title'] ?? ''); ?>" required>
                </div>

                <div class="grid grid-cols-2">
                    <div class="form-group">
                        <label for="election_start">Start Date & Time *</label>
                        <input type="datetime-local" id="election_start" name="election_start" 
                               value="<?php echo isset($settings['election_start']) ? str_replace(' ', 'T', $settings['election_start']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="election_end">End Date & Time *</label>
                        <input type="datetime-local" id="election_end" name="election_end" 
                               value="<?php echo isset($settings['election_end']) ? str_replace(' ', 'T', $settings['election_end']) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="voting_open" value="1" 
                               <?php echo (isset($settings['voting_open']) && $settings['voting_open'] == 1) ? 'checked' : ''; ?>>
                        <strong style="margin-left: 0.5rem;">Enable Voting</strong>
                    </label>
                </div>

                <div class="alert alert-info">
                    <strong>Note:</strong> When "Enable Voting" is unchecked, voters will not be able to cast or change their votes.
                </div>

                <button type="submit" class="btn btn-success">Update Settings</button>
            </form>
        </div>

        <!-- Current Status -->
        <div class="admin-section" style="margin-top: 2rem;">
            <div class="admin-section-title">Current Status</div>
            
            <table class="table">
                <tr>
                    <td style="font-weight: bold; width: 200px;">Election Title</td>
                    <td><?php echo escape_output($settings['election_title'] ?? 'Not set'); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Start Time</td>
                    <td><?php echo escape_output($settings['election_start'] ?? 'Not set'); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">End Time</td>
                    <td><?php echo escape_output($settings['election_end'] ?? 'Not set'); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Voting Status</td>
                    <td><?php echo isset($settings['voting_open']) && $settings['voting_open'] == 1 ? '<span style="color: green; font-weight: bold;">🟢 Enabled</span>' : '<span style="color: red; font-weight: bold;">🔴 Disabled</span>'; ?></td>
                </tr>
            </table>
        </div>

        <a href="index.php" class="btn btn-secondary" style="margin-top: 2rem;">Back to Dashboard</a>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
