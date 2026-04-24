<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'users':
        getUserCount();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

function getUserCount() {
    global $pdo;

    try {
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
        $result = $stmt->fetch();
        echo json_encode(['success' => true, 'count' => $result['count']]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to get user count.']);
    }
}