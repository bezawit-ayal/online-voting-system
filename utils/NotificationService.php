<?php
// utils/NotificationService.php - Unified notification service
require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/SMSService.php';
require_once __DIR__ . '/../models/Database.php';

class NotificationService {
    private $emailService;
    private $smsService;
    private $db;

    public function __construct() {
        $this->emailService = new EmailService();
        $this->smsService = new SMSService();
        $this->db = Database::getInstance()->getConnection();
    }

    public function sendEmailVerification($userId, $userEmail, $userName, $token) {
        $result = $this->emailService->sendVerificationEmail($userEmail, $userName, $token);

        $this->logNotification($userId, 'email', 'email_verification', [
            'email' => $userEmail,
            'token' => $token,
            'success' => $result
        ]);

        return $result;
    }

    public function sendPasswordReset($userId, $userEmail, $userName, $token) {
        $result = $this->emailService->sendPasswordResetEmail($userEmail, $userName, $token);

        $this->logNotification($userId, 'email', 'password_reset', [
            'email' => $userEmail,
            'token' => $token,
            'success' => $result
        ]);

        return $result;
    }

    public function sendVoteConfirmation($userId, $userEmail, $userName, $electionTitle, $candidateName) {
        $userPrefs = $this->getUserPreferences($userId);

        $results = [];

        // Send email if enabled
        if ($userPrefs['email_notifications']) {
            $emailResult = $this->emailService->sendVoteConfirmationEmail($userEmail, $userName, $electionTitle, $candidateName);
            $results['email'] = $emailResult;
        }

        // Send SMS if enabled and phone exists
        if ($userPrefs['sms_notifications'] && !empty($userPrefs['phone'])) {
            $smsResult = $this->smsService->sendVoteConfirmation($userPrefs['phone'], $electionTitle, $candidateName);
            $results['sms'] = $smsResult;
        }

        $this->logNotification($userId, 'vote_confirmation', 'vote_cast', [
            'election' => $electionTitle,
            'candidate' => $candidateName,
            'results' => $results
        ]);

        return $results;
    }

    public function sendElectionNotification($electionId, $electionTitle, $startDate) {
        // Get all verified users who want notifications
        $sql = "SELECT u.id, u.email, u.full_name, up.email_notifications, up.sms_notifications, u.phone
                FROM users u
                LEFT JOIN user_preferences up ON u.id = up.user_id
                WHERE u.is_verified = TRUE AND (up.email_notifications = TRUE OR up.sms_notifications = TRUE)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll();

        $results = ['email' => 0, 'sms' => 0];

        foreach ($users as $user) {
            // Send email notification
            if ($user['email_notifications']) {
                $emailResult = $this->emailService->sendElectionNotification(
                    $user['email'],
                    $user['full_name'],
                    $electionTitle,
                    $startDate
                );
                if ($emailResult) $results['email']++;
            }

            // Send SMS notification
            if ($user['sms_notifications'] && !empty($user['phone'])) {
                $smsResult = $this->smsService->sendElectionReminder(
                    $user['phone'],
                    $electionTitle,
                    date('g:i A', strtotime($startDate))
                );
                if ($smsResult) $results['sms']++;
            }

            // Log individual notification
            $this->logNotification($user['id'], 'election_notification', 'election_started', [
                'election_id' => $electionId,
                'election_title' => $electionTitle,
                'start_date' => $startDate
            ]);
        }

        return $results;
    }

    public function sendElectionResults($electionId, $electionTitle, $winnerName) {
        // Get all users who voted in this election
        $sql = "SELECT DISTINCT u.id, u.email, u.full_name, up.email_notifications, up.sms_notifications, u.phone
                FROM users u
                JOIN votes v ON u.id = v.user_id
                LEFT JOIN user_preferences up ON u.id = up.user_id
                WHERE v.election_id = ? AND (up.email_notifications = TRUE OR up.sms_notifications = TRUE)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$electionId]);
        $users = $stmt->fetchAll();

        $results = ['email' => 0, 'sms' => 0];

        foreach ($users as $user) {
            // Send email results
            if ($user['email_notifications']) {
                $emailResult = $this->emailService->sendElectionResults(
                    $user['email'],
                    $user['full_name'],
                    $electionTitle,
                    $winnerName
                );
                if ($emailResult) $results['email']++;
            }

            // Send SMS results
            if ($user['sms_notifications'] && !empty($user['phone'])) {
                $smsResult = $this->smsService->sendElectionResults(
                    $user['phone'],
                    $electionTitle,
                    $winnerName
                );
                if ($smsResult) $results['sms']++;
            }

            // Log individual notification
            $this->logNotification($user['id'], 'election_results', 'election_completed', [
                'election_id' => $electionId,
                'election_title' => $electionTitle,
                'winner' => $winnerName
            ]);
        }

        return $results;
    }

    public function sendOTP($userId, $phoneNumber, $otp) {
        $result = $this->smsService->sendOTP($phoneNumber, $otp);

        $this->logNotification($userId, 'sms', 'otp_sent', [
            'phone' => $phoneNumber,
            'otp' => $otp,
            'success' => $result
        ]);

        return $result;
    }

    private function getUserPreferences($userId) {
        $sql = "SELECT * FROM user_preferences WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $prefs = $stmt->fetch();

        if (!$prefs) {
            // Return defaults
            return [
                'email_notifications' => true,
                'sms_notifications' => false,
                'phone' => null
            ];
        }

        // Get phone number from users table
        $phoneSql = "SELECT phone FROM users WHERE id = ?";
        $phoneStmt = $this->db->prepare($phoneSql);
        $phoneStmt->execute([$userId]);
        $user = $phoneStmt->fetch();

        $prefs['phone'] = $user['phone'];
        return $prefs;
    }

    private function logNotification($userId, $type, $title, $data) {
        $sql = "INSERT INTO notifications (user_id, type, title, data, created_at)
                VALUES (?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $type,
            $title,
            json_encode($data)
        ]);
    }

    public function getUserNotifications($userId, $limit = 20) {
        $sql = "SELECT * FROM notifications
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function markNotificationAsRead($notificationId, $userId) {
        $sql = "UPDATE notifications SET is_read = TRUE
                WHERE id = ? AND user_id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$notificationId, $userId]);
    }

    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count FROM notifications
                WHERE user_id = ? AND is_read = FALSE";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'];
    }
}
?>