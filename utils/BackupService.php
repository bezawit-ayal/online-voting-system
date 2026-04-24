<?php
// utils/BackupService.php - Database backup and recovery service
require_once __DIR__ . '/../models/Database.php';

class BackupService {
    private $db;
    private $backupDir;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->backupDir = __DIR__ . '/../backups/';

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    public function createFullBackup($filename = null) {
        if (!$filename) {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        }

        $filepath = $this->backupDir . $filename;

        try {
            // Get all tables
            $tables = $this->getAllTables();

            $sql = "-- VoteSecure Pro Database Backup\n";
            $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Version: 2.0.0\n\n";

            $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

            foreach ($tables as $table) {
                $sql .= $this->getTableStructure($table);
                $sql .= $this->getTableData($table);
            }

            $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

            file_put_contents($filepath, $sql);

            // Compress the file
            $this->compressFile($filepath);

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function createTableBackup($tableName, $filename = null) {
        if (!$filename) {
            $filename = "backup_{$tableName}_" . date('Y-m-d_H-i-s') . '.sql';
        }

        $filepath = $this->backupDir . $filename;

        try {
            $sql = "-- Table Backup: $tableName\n";
            $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";

            $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
            $sql .= $this->getTableStructure($tableName);
            $sql .= $this->getTableData($tableName);
            $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

            file_put_contents($filepath, $sql);
            $this->compressFile($filepath);

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'table' => $tableName
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function restoreBackup($filepath) {
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'Backup file not found'];
        }

        try {
            $this->db->beginTransaction();

            // Read and execute SQL file
            $sql = file_get_contents($filepath);
            $statements = array_filter(array_map('trim', explode(';', $sql)));

            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    $this->db->exec($statement);
                }
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Backup restored successfully'
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'error' => 'Restore failed: ' . $e->getMessage()
            ];
        }
    }

    public function getBackupList() {
        $files = glob($this->backupDir . '*.sql*');
        $backups = [];

        foreach ($files as $file) {
            $filename = basename($file);
            $backups[] = [
                'filename' => $filename,
                'filepath' => $file,
                'size' => filesize($file),
                'created' => date('Y-m-d H:i:s', filemtime($file)),
                'compressed' => strpos($filename, '.gz') !== false
            ];
        }

        // Sort by creation date (newest first)
        usort($backups, function($a, $b) {
            return strtotime($b['created']) - strtotime($a['created']);
        });

        return $backups;
    }

    public function deleteBackup($filename) {
        $filepath = $this->backupDir . $filename;

        if (file_exists($filepath)) {
            unlink($filepath);
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Backup file not found'];
    }

    public function cleanupOldBackups($daysToKeep = 30) {
        $files = $this->getBackupList();
        $deleted = 0;

        foreach ($files as $file) {
            $fileAge = (time() - strtotime($file['created'])) / (60 * 60 * 24);

            if ($fileAge > $daysToKeep) {
                if ($this->deleteBackup($file['filename'])) {
                    $deleted++;
                }
            }
        }

        return ['deleted' => $deleted];
    }

    public function exportData($table, $format = 'csv', $filters = []) {
        $where = '';
        $params = [];

        if (!empty($filters)) {
            $conditions = [];
            foreach ($filters as $column => $value) {
                $conditions[] = "$column = ?";
                $params[] = $value;
            }
            $where = ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql = "SELECT * FROM $table$where";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($format === 'csv') {
            return $this->arrayToCSV($data);
        } elseif ($format === 'json') {
            return json_encode($data, JSON_PRETTY_PRINT);
        }

        return false;
    }

    private function getAllTables() {
        $stmt = $this->db->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getTableStructure($tableName) {
        $stmt = $this->db->query("SHOW CREATE TABLE `$tableName`");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return "-- Table structure for $tableName\n" .
               "DROP TABLE IF EXISTS `$tableName`;\n" .
               $result['Create Table'] . ";\n\n";
    }

    private function getTableData($tableName) {
        $stmt = $this->db->query("SELECT * FROM `$tableName`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return "-- No data in $tableName\n\n";
        }

        $sql = "-- Data for $tableName\n";
        $sql .= "INSERT INTO `$tableName` VALUES\n";

        $values = [];
        foreach ($rows as $row) {
            $rowValues = [];
            foreach ($row as $value) {
                $rowValues[] = $this->db->quote($value);
            }
            $values[] = "(" . implode(", ", $rowValues) . ")";
        }

        $sql .= implode(",\n", $values) . ";\n\n";
        return $sql;
    }

    private function compressFile($filepath) {
        if (function_exists('gzencode')) {
            $compressed = gzencode(file_get_contents($filepath), 9);
            file_put_contents($filepath . '.gz', $compressed);
            unlink($filepath); // Remove uncompressed file
        }
    }

    private function arrayToCSV($data) {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'w');

        // CSV headers
        fputcsv($output, array_keys($data[0]));

        // CSV data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    public function getBackupStats() {
        $backups = $this->getBackupList();

        $totalSize = 0;
        $totalBackups = count($backups);
        $oldestBackup = null;
        $newestBackup = null;

        foreach ($backups as $backup) {
            $totalSize += $backup['size'];

            if (!$oldestBackup || strtotime($backup['created']) < strtotime($oldestBackup)) {
                $oldestBackup = $backup['created'];
            }

            if (!$newestBackup || strtotime($backup['created']) > strtotime($newestBackup)) {
                $newestBackup = $backup['created'];
            }
        }

        return [
            'total_backups' => $totalBackups,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'oldest_backup' => $oldestBackup,
            'newest_backup' => $newestBackup,
            'average_size' => $totalBackups > 0 ? $totalSize / $totalBackups : 0
        ];
    }

    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
?>