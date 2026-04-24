<?php
// models/User.php - Enhanced User model
require_once __DIR__ . '/Database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data) {
        try {
            $this->db->beginTransaction();

            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));
            $otpCode = sprintf("%06d", mt_rand(100000, 999999));
            $otpExpires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

            $sql = "INSERT INTO users (username, email, password_hash, full_name, voter_id, phone, date_of_birth, address, verification_token, otp_code, otp_expires_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['username'],
                $data['email'],
                $data['password_hash'],
                $data['full_name'],
                $data['voter_id'] ?? null,
                $data['phone'] ?? null,
                $data['date_of_birth'] ?? null,
                $data['address'] ?? null,
                $verificationToken,
                $otpCode,
                $otpExpires
            ]);

            $userId = $this->db->lastInsertId();
            $this->db->commit();

            // Optional setup should not block successful registration.
            try {
                $this->createPreferences($userId);
            } catch (Exception $e) {
                // Best effort only.
            }

            try {
                $this->logAction($userId, 'user_registered', 'users', $userId, null, $data);
            } catch (Exception $e) {
                // Best effort only.
            }

            return $userId;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function authenticate($identifier, $password) {
        $user = $this->findByIdentifier($identifier);

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        // Check if account is locked
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return ['success' => false, 'message' => 'Account is temporarily locked'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            $this->incrementFailedAttempts($user['id']);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        // Reset failed attempts on successful login
        $this->resetFailedAttempts($user['id']);

        // Update last login
        $this->updateLastLogin($user['id']);

        // Create session
        $sessionToken = $this->createSession($user['id']);

        // Log login
        $this->logAction($user['id'], 'user_login', 'users', $user['id']);

        return [
            'success' => true,
            'user' => $user,
            'session_token' => $sessionToken
        ];
    }

    public function verifyEmail($userId, $token) {
        $sql = "UPDATE users SET email_verified = TRUE, verification_token = NULL
                WHERE id = ? AND verification_token = ? AND email_verified = FALSE";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$userId, $token]);

        if ($result && $stmt->rowCount() > 0) {
            $this->logAction($userId, 'email_verified', 'users', $userId);
            return true;
        }

        return false;
    }

    public function verifyOTP($userId, $otp) {
        $sql = "SELECT otp_code, otp_expires_at FROM users
                WHERE id = ? AND otp_code = ? AND otp_expires_at > NOW()";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $otp]);
        $result = $stmt->fetch();

        if ($result) {
            // Clear OTP
            $sql = "UPDATE users SET otp_code = NULL, otp_expires_at = NULL, phone_verified = TRUE WHERE id = ?";
            $this->db->prepare($sql)->execute([$userId]);

            $this->logAction($userId, 'otp_verified', 'users', $userId);
            return true;
        }

        return false;
    }

    public function generateOTP($userId) {
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $sql = "UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$otp, $expires, $userId]);

        return $otp;
    }

    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function findByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public function findByIdentifier($identifier) {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $this->findByEmail($identifier);
        }

        $user = $this->findByUsername($identifier);
        if ($user) {
            return $user;
        }

        return $this->findByEmail($identifier);
    }

    public function updateProfile($userId, $data) {
        $fields = [];
        $params = [];

        $allowedFields = ['full_name', 'phone', 'date_of_birth', 'address'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            $this->logAction($userId, 'profile_updated', 'users', $userId, null, $data);
        }

        return $result;
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->findById($userId);

        if (!password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$newHash, $userId]);

        if ($result) {
            $this->logAction($userId, 'password_changed', 'users', $userId);
        }

        return ['success' => $result, 'message' => $result ? 'Password changed successfully' : 'Failed to change password'];
    }

    public function getAllUsers($page = 1, $limit = 20, $search = '') {
        $offset = ($page - 1) * $limit;
        $where = '';
        $params = [];

        if ($search) {
            $where = " WHERE full_name LIKE ? OR email LIKE ? OR username LIKE ?";
            $params = ["%$search%", "%$search%", "%$search%"];
        }

        $sql = "SELECT id, username, email, full_name, voter_id, phone, is_admin, is_verified,
                       email_verified, phone_verified, created_at, last_login
                FROM users$where ORDER BY created_at DESC LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getUserCount($search = '') {
        $where = '';
        $params = [];

        if ($search) {
            $where = " WHERE full_name LIKE ? OR email LIKE ? OR username LIKE ?";
            $params = ["%$search%", "%$search%", "%$search%"];
        }

        $sql = "SELECT COUNT(*) as count FROM users$where";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['count'];
    }

    private function createPreferences($userId) {
        $sql = "INSERT INTO user_preferences (user_id) VALUES (?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
    }

    private function createSession($userId) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));

        $sql = "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $token,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $expires
        ]);

        return $token;
    }

    private function incrementFailedAttempts($userId) {
        $sql = "UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?";
        $this->db->prepare($sql)->execute([$userId]);

        // Check if should lock account
        $user = $this->findById($userId);
        if ($user['failed_login_attempts'] >= 5) {
            $lockUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $sql = "UPDATE users SET locked_until = ? WHERE id = ?";
            $this->db->prepare($sql)->execute([$lockUntil, $userId]);
        }
    }

    private function resetFailedAttempts($userId) {
        $sql = "UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?";
        $this->db->prepare($sql)->execute([$userId]);
    }

    public function updateLastLogin($userId) {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $this->db->prepare($sql)->execute([$userId]);
    }

    private function logAction($userId, $action, $entityType, $entityId, $oldValues = null, $newValues = null) {
        $sql = "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
}
?>