<?php
// controllers/AuthController.php - Authentication controller
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/NotificationService.php';
require_once __DIR__ . '/../utils/AuditService.php';
require_once __DIR__ . '/../config/config.php';

class AuthController {
    private $userModel;
    private $notificationService;
    private $auditService;
    private $config;

    public function __construct() {
        $this->userModel = new User();
        $this->notificationService = new NotificationService();
        $this->auditService = new AuditService();
        $this->config = require __DIR__ . '/../config/config.php';
    }

    public function register($data) {
        try {
            // Validate input
            $validation = $this->validateRegistrationData($data);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }

            // Check if email already exists
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser) {
                return ['success' => false, 'message' => 'Email already registered'];
            }

            // Check if username already exists
            $existingUser = $this->userModel->findByUsername($data['username']);
            if ($existingUser) {
                return ['success' => false, 'message' => 'Username already taken'];
            }

            // Hash password
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);

            // Create user
            $userId = $this->userModel->create($data);

            if (!$userId) {
                return ['success' => false, 'message' => 'Registration failed'];
            }

            // Send verification email if enabled
            if ($this->config['features']['email_verification']) {
                try {
                    $user = $this->userModel->findById($userId);
                    if ($user) {
                        $this->notificationService->sendEmailVerification(
                            $userId,
                            $user['email'],
                            $user['full_name'],
                            $user['verification_token']
                        );
                    }
                } catch (Exception $e) {
                    // Keep registration successful even if notification fails.
                }
            }

            return [
                'success' => true,
                'message' => 'Registration successful! Please check your email for verification.',
                'user_id' => $userId
            ];

        } catch (Exception $e) {
            $this->auditService->logAction(null, 'registration_error', 'users', null, null, null, [
                'error' => $e->getMessage(),
                'email' => $data['email'] ?? 'unknown'
            ]);

            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    public function login($identifier, $password) {
        try {
            $result = $this->userModel->authenticate($identifier, $password);

            if ($result['success']) {
                // Set session
                $_SESSION['user_id'] = $result['user']['id'];
                $_SESSION['user'] = $result['user'];
                $_SESSION['is_admin'] = $result['user']['is_admin'];
                $_SESSION['session_token'] = $result['session_token'];

                // Update last login
                $this->userModel->updateLastLogin($result['user']['id']);

                $this->auditService->logAction($result['user']['id'], 'login_success', 'users', $result['user']['id']);
            } else {
                $this->auditService->logAction(null, 'login_failed', 'users', null, null, null, [
                    'email' => $identifier,
                    'reason' => $result['message']
                ]);
            }

            return $result;

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }

    public function logout() {
        $userId = $_SESSION['user_id'] ?? null;

        if ($userId) {
            $this->auditService->logAction($userId, 'logout', 'users', $userId);
        }

        // Clear session
        session_unset();
        session_destroy();

        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    public function verifyEmail($token) {
        try {
            // Find user by verification token
            $sql = "SELECT id FROM users WHERE verification_token = ? AND email_verified = FALSE";
            $stmt = Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if (!$user) {
                return ['success' => false, 'message' => 'Invalid or expired verification token'];
            }

            $result = $this->userModel->verifyEmail($user['id'], $token);

            if ($result) {
                $this->auditService->logAction($user['id'], 'email_verified', 'users', $user['id']);
                return ['success' => true, 'message' => 'Email verified successfully!'];
            } else {
                return ['success' => false, 'message' => 'Email verification failed'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()];
        }
    }

    public function sendPasswordReset($email) {
        try {
            $user = $this->userModel->findByEmail($email);

            if (!$user) {
                // Don't reveal if email exists or not for security
                return ['success' => true, 'message' => 'If the email exists, a reset link has been sent.'];
            }

            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store reset token (you might want to create a separate table for this)
            $sql = "UPDATE users SET verification_token = ?, otp_expires_at = ? WHERE id = ?";
            $stmt = Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute([$resetToken, $expires, $user['id']]);

            // Send reset email
            $result = $this->notificationService->sendPasswordReset(
                $user['id'],
                $user['email'],
                $user['full_name'],
                $resetToken
            );

            $this->auditService->logAction($user['id'], 'password_reset_requested', 'users', $user['id']);

            return ['success' => true, 'message' => 'If the email exists, a reset link has been sent.'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Password reset failed: ' . $e->getMessage()];
        }
    }

    public function resetPassword($token, $newPassword) {
        try {
            // Find user by reset token
            $sql = "SELECT id FROM users WHERE verification_token = ? AND otp_expires_at > NOW()";
            $stmt = Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if (!$user) {
                return ['success' => false, 'message' => 'Invalid or expired reset token'];
            }

            // Validate new password
            if (strlen($newPassword) < $this->config['security']['password_min_length']) {
                return ['success' => false, 'message' => 'Password must be at least ' . $this->config['security']['password_min_length'] . ' characters long'];
            }

            // Update password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password_hash = ?, verification_token = NULL, otp_expires_at = NULL WHERE id = ?";
            $stmt = Database::getInstance()->getConnection()->prepare($sql);
            $result = $stmt->execute([$newHash, $user['id']]);

            if ($result) {
                $this->auditService->logAction($user['id'], 'password_reset_completed', 'users', $user['id']);
                return ['success' => true, 'message' => 'Password reset successfully!'];
            } else {
                return ['success' => false, 'message' => 'Password reset failed'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Password reset failed: ' . $e->getMessage()];
        }
    }

    public function sendOTP($userId) {
        try {
            $user = $this->userModel->findById($userId);

            if (!$user || empty($user['phone'])) {
                return ['success' => false, 'message' => 'Phone number not found'];
            }

            $otp = $this->userModel->generateOTP($userId);

            $result = $this->notificationService->sendOTP($userId, $user['phone'], $otp);

            if ($result) {
                $this->auditService->logAction($userId, 'otp_sent', 'users', $userId);
                return ['success' => true, 'message' => 'OTP sent successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to send OTP'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'OTP send failed: ' . $e->getMessage()];
        }
    }

    public function verifyOTP($userId, $otp) {
        try {
            $result = $this->userModel->verifyOTP($userId, $otp);

            if ($result) {
                $this->auditService->logAction($userId, 'otp_verified', 'users', $userId);
                return ['success' => true, 'message' => 'OTP verified successfully'];
            } else {
                $this->auditService->logAction($userId, 'otp_verification_failed', 'users', $userId);
                return ['success' => false, 'message' => 'Invalid or expired OTP'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'OTP verification failed: ' . $e->getMessage()];
        }
    }

    public function checkSession() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
            return ['valid' => false, 'message' => 'No active session'];
        }

        // Verify session token in database
        $sql = "SELECT u.* FROM users u
                JOIN user_sessions us ON u.id = us.user_id
                WHERE u.id = ? AND us.session_token = ? AND us.expires_at > NOW()";

        $stmt = Database::getInstance()->getConnection()->prepare($sql);
        $stmt->execute([$_SESSION['user_id'], $_SESSION['session_token']]);
        $user = $stmt->fetch();

        if ($user) {
            return ['valid' => true, 'user' => $user];
        } else {
            // Invalid session, clear it
            $this->logout();
            return ['valid' => false, 'message' => 'Session expired'];
        }
    }

    private function validateRegistrationData($data) {
        if (empty($data['username']) || strlen($data['username']) < 3) {
            return ['valid' => false, 'message' => 'Username must be at least 3 characters long'];
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Valid email is required'];
        }

        if (empty($data['password']) || strlen($data['password']) < $this->config['security']['password_min_length']) {
            return ['valid' => false, 'message' => 'Password must be at least ' . $this->config['security']['password_min_length'] . ' characters long'];
        }

        if (empty($data['full_name']) || strlen($data['full_name']) < 2) {
            return ['valid' => false, 'message' => 'Full name is required'];
        }

        if (!empty($data['phone']) && !preg_match('/^\+?[1-9]\d{1,14}$/', $data['phone'])) {
            return ['valid' => false, 'message' => 'Invalid phone number format'];
        }

        return ['valid' => true];
    }
}
?>