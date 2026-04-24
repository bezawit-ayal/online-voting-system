<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

requireAdmin();

switch ($action) {
    case 'stats':
        if ($method === 'GET') {
            getAdminStats();
        }
        break;
    case 'users':
        if ($method === 'GET') {
            getUsers();
        }
        break;
    case 'candidates':
        if ($method === 'GET') {
            getCandidates();
        } elseif ($method === 'POST') {
            addCandidate();
        }
        break;
    case 'delete_candidate':
        if ($method === 'DELETE') {
            deleteCandidate();
        }
        break;
    case 'reset_election':
        if ($method === 'POST') {
            resetElection();
        }
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

function getAdminStats() {
    global $pdo;

    try {
        // Total users
        $stmt = $pdo->query('SELECT COUNT(*) FROM users');
        $totalUsers = $stmt->fetchColumn();

        // Active users today
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE DATE(last_login) = CURDATE()');
        $stmt->execute();
        $activeUsers = $stmt->fetchColumn();

        // Total candidates
        $stmt = $pdo->query('SELECT COUNT(*) FROM candidates');
        $totalCandidates = $stmt->fetchColumn();

        // Total votes
        $stmt = $pdo->query('SELECT COUNT(*) FROM votes');
        $totalVotes = $stmt->fetchColumn();

        // Participation rate
        $participationRate = $totalUsers > 0 ? round(($totalVotes / $totalUsers) * 100, 1) : 0;

        echo json_encode([
            'success' => true,
            'stats' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'total_candidates' => $totalCandidates,
                'total_votes' => $totalVotes,
                'participation_rate' => $participationRate,
                'system_health' => 100, // Placeholder
                'failed_logins' => 0, // Placeholder
                'suspicious_activity' => 0 // Placeholder
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load admin statistics.']);
    }
}

function getUsers() {
    global $pdo;

    try {
        $stmt = $pdo->query('SELECT id, username, email, full_name, voter_id, is_admin, created_at, last_login FROM users ORDER BY created_at DESC');
        $users = $stmt->fetchAll();

        echo json_encode(['success' => true, 'users' => $users]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load users.']);
    }
}

function getCandidates() {
    global $pdo;

    try {
        $stmt = $pdo->query('SELECT id, name, party, description, image_url, created_at FROM candidates ORDER BY name ASC');
        $candidates = $stmt->fetchAll();

        echo json_encode(['success' => true, 'candidates' => $candidates]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load candidates.']);
    }
}

function addCandidate() {
    global $pdo;

    $payload = json_decode(file_get_contents('php://input'), true);

    $name = trim($payload['name'] ?? '');
    $party = trim($payload['party'] ?? '');
    $description = trim($payload['description'] ?? '');
    $imageUrl = trim($payload['image_url'] ?? '');

    if (empty($name) || empty($description)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name and description are required.']);
        return;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO candidates (name, party, description, image_url) VALUES (:name, :party, :description, :image_url)');
        $stmt->execute([
            'name' => $name,
            'party' => $party,
            'description' => $description,
            'image_url' => $imageUrl
        ]);

        echo json_encode(['success' => true, 'message' => 'Candidate added successfully!']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to add candidate.']);
    }
}

function deleteCandidate() {
    global $pdo;

    $candidateId = intval($_GET['id'] ?? 0);

    if ($candidateId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid candidate ID.']);
        return;
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM candidates WHERE id = :id');
        $stmt->execute(['id' => $candidateId]);

        echo json_encode(['success' => true, 'message' => 'Candidate deleted successfully!']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete candidate.']);
    }
}

function resetElection() {
    global $pdo;

    try {
        // Delete all votes
        $pdo->exec('DELETE FROM votes');

        echo json_encode(['success' => true, 'message' => 'Election has been reset. All votes have been cleared.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to reset election.']);
    }
}