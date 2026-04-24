<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'update':
        if ($method === 'POST') {
            updateProfile();
        }
        break;
    case 'password':
        if ($method === 'POST') {
            changePassword();
        }
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

function updateProfile() {
    global $pdo;

    requireLogin();
    $user = getCurrentUser();

    $payload = json_decode(file_get_contents('php://input'), true);

    $fullName = trim($payload['full_name'] ?? '');
    $username = trim($payload['username'] ?? '');
    $email = trim($payload['email'] ?? '');

    // Validation
    if (empty($fullName) || empty($username) || empty($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        return;
    }

    try {
        // Check if username or email already exists (excluding current user)
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE (username = :username OR email = :email) AND id != :user_id');
        $stmt->execute(['username' => $username, 'email' => $email, 'user_id' => $user['id']]);

        if ($stmt->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Username or email already exists.']);
            return;
        }

        // Update user profile
        $stmt = $pdo->prepare('UPDATE users SET full_name = :full_name, username = :username, email = :email WHERE id = :user_id');
        $stmt->execute([
            'full_name' => $fullName,
            'username' => $username,
            'email' => $email,
            'user_id' => $user['id']
        ]);

        // Update session data
        $_SESSION['user']['full_name'] = $fullName;
        $_SESSION['user']['username'] = $username;
        $_SESSION['user']['email'] = $email;

        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update profile.']);
    }
}

function changePassword() {
    global $pdo;

    requireLogin();
    $user = getCurrentUser();

    $payload = json_decode(file_get_contents('php://input'), true);

    $currentPassword = $payload['current_password'] ?? '';
    $newPassword = $payload['new_password'] ?? '';

    // Validation
    if (empty($currentPassword) || empty($newPassword)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
        return;
    }

    if (strlen($newPassword) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long.']);
        return;
    }

    try {
        // Verify current password
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :user_id');
        $stmt->execute(['user_id' => $user['id']]);
        $userData = $stmt->fetch();

        if (!$userData || !password_verify($currentPassword, $userData['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            return;
        }

        // Update password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :user_id');
        $stmt->execute([
            'password_hash' => $newPasswordHash,
            'user_id' => $user['id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to change password.']);
    }
}