<?php
// auth_api.php - Enhanced Authentication API
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$authController = new AuthController();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    // Auto-initialize database and required core tables on every auth request.
    Database::getInstance()->getConnection();

    switch ($method) {
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            switch ($action) {
                case 'register':
                    $result = $authController->register($data);
                    break;

                case 'login':
                    $result = $authController->login($data['email'] ?? '', $data['password'] ?? '');
                    break;

                case 'logout':
                    requireLogin(); // Ensure user is logged in
                    $result = $authController->logout();
                    break;

                case 'verify_email':
                    $result = $authController->verifyEmail($data['token'] ?? '');
                    break;

                case 'send_password_reset':
                    $result = $authController->sendPasswordReset($data['email'] ?? '');
                    break;

                case 'reset_password':
                    $result = $authController->resetPassword(
                        $data['token'] ?? '',
                        $data['password'] ?? ''
                    );
                    break;

                case 'send_otp':
                    requireLogin();
                    $result = $authController->sendOTP($_SESSION['user_id']);
                    break;

                case 'verify_otp':
                    requireLogin();
                    $result = $authController->verifyOTP(
                        $_SESSION['user_id'],
                        $data['otp'] ?? ''
                    );
                    break;

                default:
                    $result = ['success' => false, 'message' => 'Invalid action'];
            }
            break;

        case 'GET':
            switch ($action) {
                case 'check_session':
                    $result = $authController->checkSession();
                    break;

                case 'user_profile':
                    requireLogin();
                    $userModel = new User();
                    $user = $userModel->findById($_SESSION['user_id']);
                    unset($user['password_hash']); // Remove sensitive data
                    $result = ['success' => true, 'user' => $user];
                    break;

                default:
                    $result = ['success' => false, 'message' => 'Invalid action'];
            }
            break;

        default:
            $result = ['success' => false, 'message' => 'Method not allowed'];
    }

} catch (Exception $e) {
    $result = ['success' => false, 'message' => 'API Error: ' . $e->getMessage()];
}

echo json_encode($result);
