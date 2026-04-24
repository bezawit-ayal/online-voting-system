<?php
// utils/AuditService.php - Audit logging service
require_once __DIR__ . '/../models/Database.php';

class AuditService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function logAction($userId, $action, $entityType, $entityId = null, $oldValues = null, $newValues = null, $metadata = []) {
        $sql = "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);

        // Also log to file for critical actions
        if (in_array($action, ['user_deleted', 'election_deleted', 'admin_login', 'security_breach'])) {
            $this->logToFile($userId, $action, $entityType, $entityId, $metadata);
        }

        return $this->db->lastInsertId();
    }

    public function getAuditLogs($filters = [], $page = 1, $limit = 50) {
        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = "user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = "action = ?";
            $params[] = $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = "entity_type = ?";
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

        $offset = ($page - 1) * $limit;

        $sql = "SELECT al.*, u.username, u.full_name
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                $whereClause
                ORDER BY al.created_at DESC
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAuditStats($period = '30 days') {
        $dateCondition = "created_at >= DATE_SUB(NOW(), INTERVAL $period)";

        // Action counts
        $actionSql = "SELECT action, COUNT(*) as count
                     FROM audit_logs
                     WHERE $dateCondition
                     GROUP BY action
                     ORDER BY count DESC";

        $actionStmt = $this->db->prepare($actionSql);
        $actionStmt->execute();
        $actions = $actionStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Entity type counts
        $entitySql = "SELECT entity_type, COUNT(*) as count
                     FROM audit_logs
                     WHERE $dateCondition
                     GROUP BY entity_type
                     ORDER BY count DESC";

        $entityStmt = $this->db->prepare($entitySql);
        $entityStmt->execute();
        $entities = $entityStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Daily activity
        $dailySql = "SELECT DATE(created_at) as date, COUNT(*) as count
                    FROM audit_logs
                    WHERE $dateCondition
                    GROUP BY DATE(created_at)
                    ORDER BY date";

        $dailyStmt = $this->db->prepare($dailySql);
        $dailyStmt->execute();
        $daily = $dailyStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Top users by activity
        $userSql = "SELECT u.username, u.full_name, COUNT(al.id) as actions
                   FROM audit_logs al
                   JOIN users u ON al.user_id = u.id
                   WHERE $dateCondition
                   GROUP BY al.user_id, u.username, u.full_name
                   ORDER BY actions DESC
                   LIMIT 10";

        $userStmt = $this->db->prepare($userSql);
        $userStmt->execute();
        $topUsers = $userStmt->fetchAll();

        return [
            'actions' => $actions,
            'entities' => $entities,
            'daily_activity' => $daily,
            'top_users' => $topUsers,
            'total_logs' => array_sum($actions)
        ];
    }

    public function getUserActivity($userId, $limit = 100) {
        $sql = "SELECT * FROM audit_logs
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function exportAuditLogs($filters = [], $format = 'csv') {
        $logs = $this->getAuditLogs($filters, 1, 10000); // Get all logs for export

        if ($format === 'csv') {
            return $this->exportToCSV($logs);
        } elseif ($format === 'json') {
            return $this->exportToJSON($logs);
        }

        return false;
    }

    private function exportToCSV($logs) {
        $output = fopen('php://temp', 'w');

        // CSV headers
        fputcsv($output, [
            'ID', 'User ID', 'Username', 'Full Name', 'Action', 'Entity Type',
            'Entity ID', 'Old Values', 'New Values', 'IP Address', 'User Agent', 'Created At'
        ]);

        // CSV data
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['user_id'],
                $log['username'],
                $log['full_name'],
                $log['action'],
                $log['entity_type'],
                $log['entity_id'],
                $log['old_values'],
                $log['new_values'],
                $log['ip_address'],
                $log['user_agent'],
                $log['created_at']
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    private function exportToJSON($logs) {
        return json_encode($logs, JSON_PRETTY_PRINT);
    }

    private function logToFile($userId, $action, $entityType, $entityId, $metadata) {
        $logFile = __DIR__ . '/../logs/security.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $logEntry = sprintf(
            "[%s] SECURITY: User %d performed %s on %s:%s | IP: %s | UA: %s | Metadata: %s\n",
            $timestamp,
            $userId,
            $action,
            $entityType,
            $entityId ?? 'null',
            $ip,
            substr($userAgent, 0, 100),
            json_encode($metadata)
        );

        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    // Clean up old logs (keep last 90 days)
    public function cleanupOldLogs($daysToKeep = 90) {
        $sql = "DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$daysToKeep]);
    }

    // Get suspicious activities
    public function getSuspiciousActivities($hours = 24) {
        $sql = "SELECT al.*, u.username, u.email,
                       COUNT(*) as similar_actions
                FROM audit_logs al
                JOIN users u ON al.user_id = u.id
                WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                AND al.action IN ('failed_login', 'password_reset', 'account_locked')
                GROUP BY al.user_id, al.action, DATE(al.created_at), HOUR(al.created_at)
                HAVING similar_actions > 3
                ORDER BY al.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$hours]);
        return $stmt->fetchAll();
    }
}
?>