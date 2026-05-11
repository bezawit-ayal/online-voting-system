<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Status - Online Voting System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">🗳️ Online Voting System</div>
        </div>
    </nav>

    <div class="container">
        <h1 style="margin-bottom: 2rem; color: var(--primary-color);">System Status Check</h1>

        <div class="grid grid-cols-2">
            <!-- PHP Version -->
            <div class="card">
                <div class="card-header">PHP Version</div>
                <p style="margin: 0;">
                    <?php 
                    echo phpversion() >= '7.4' ? '✅' : '⚠️';
                    echo ' ' . phpversion();
                    ?>
                </p>
            </div>

            <!-- Server -->
            <div class="card">
                <div class="card-header">Server</div>
                <p style="margin: 0;">
                    <?php echo isset($_SERVER['SERVER_SOFTWARE']) ? htmlspecialchars($_SERVER['SERVER_SOFTWARE']) : 'Unknown'; ?>
                </p>
            </div>

            <!-- Extensions -->
            <div class="card">
                <div class="card-header">MySQL Extension</div>
                <p style="margin: 0;">
                    <?php echo extension_loaded('mysqli') ? '✅ Loaded' : '❌ Not Loaded'; ?>
                </p>
            </div>

            <!-- Session -->
            <div class="card">
                <div class="card-header">Session Support</div>
                <p style="margin: 0;">
                    <?php 
                    @session_start();
                    echo '✅ Working';
                    session_destroy();
                    ?>
                </p>
            </div>

            <!-- File Permissions -->
            <div class="card">
                <div class="card-header">Directory Writable</div>
                <p style="margin: 0;">
                    <?php echo is_writable(__DIR__) ? '✅ Yes' : '❌ No'; ?>
                </p>
            </div>

            <!-- Database -->
            <div class="card">
                <div class="card-header">Database Connection</div>
                <p style="margin: 0;">
                    <?php 
                    try {
                        require_once 'config/db.php';
                        echo $conn->ping() ? '✅ Connected' : '❌ Failed';
                        $conn->close();
                    } catch (Exception $e) {
                        echo '❌ Failed: ' . $e->getMessage();
                    }
                    ?>
                </p>
            </div>
        </div>

        <!-- Detailed Information -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">Configuration Details</div>
            
            <table class="table">
                <tr>
                    <td style="font-weight: bold; width: 200px;">PHP Version</td>
                    <td><?php echo phpversion(); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Server API</td>
                    <td><?php echo php_sapi_name(); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Max Upload Size</td>
                    <td><?php echo ini_get('upload_max_filesize'); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Max Post Size</td>
                    <td><?php echo ini_get('post_max_size'); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Memory Limit</td>
                    <td><?php echo ini_get('memory_limit'); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Script Time Limit</td>
                    <td><?php echo ini_get('max_execution_time'); ?> seconds</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Display Errors</td>
                    <td><?php echo ini_get('display_errors') ? 'Enabled' : 'Disabled'; ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Current Directory</td>
                    <td><?php echo htmlspecialchars(__DIR__); ?></td>
                </tr>
            </table>
        </div>

        <!-- Extensions List -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">Installed Extensions</div>
            
            <table class="table">
                <tr>
                    <td style="font-weight: bold;">mysqli</td>
                    <td><?php echo extension_loaded('mysqli') ? '✅ Loaded' : '❌ Not Loaded'; ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">PDO</td>
                    <td><?php echo extension_loaded('pdo') ? '✅ Loaded' : '❌ Not Loaded'; ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">OpenSSL</td>
                    <td><?php echo extension_loaded('openssl') ? '✅ Loaded' : '❌ Not Loaded'; ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">GD</td>
                    <td><?php echo extension_loaded('gd') ? '✅ Loaded' : '❌ Not Loaded'; ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Curl</td>
                    <td><?php echo extension_loaded('curl') ? '✅ Loaded' : '❌ Not Loaded'; ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">JSON</td>
                    <td><?php echo extension_loaded('json') ? '✅ Loaded' : '❌ Not Loaded'; ?></td>
                </tr>
            </table>
        </div>

        <a href="login.php" class="btn btn-primary" style="margin-top: 2rem;">Go to Login</a>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
