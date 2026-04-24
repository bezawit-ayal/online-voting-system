<?php
// vote_api.php - Enhanced Voting API
require_once __DIR__ . '/controllers/VoteController.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$voteController = new VoteController();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$electionId = $_GET['election_id'] ?? null;

try {
    switch ($method) {
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            requireLogin(); // All voting actions require login

            switch ($action) {
                case 'cast_vote':
                    if (!$electionId) {
                        $result = ['success' => false, 'message' => 'Election ID required'];
                    } else {
                        $result = $voteController->castVote(
                            $electionId,
                            $data['candidate_id'] ?? 0,
                            $_SESSION['user_id'],
                            $data['voting_duration'] ?? null
                        );
                    }
                    break;

                case 'start_voting_period':
                    if (!$electionId) {
                        $result = ['success' => false, 'message' => 'Election ID required'];
                    } else {
                        $result = $voteController->startVotingPeriod($electionId, $_SESSION['user_id']);
                    }
                    break;

                default:
                    $result = ['success' => false, 'message' => 'Invalid action'];
            }
            break;

        case 'GET':
            switch ($action) {
                case 'candidates':
                    if (!$electionId) {
                        $result = ['success' => false, 'message' => 'Election ID required'];
                    } else {
                        $result = $voteController->getElectionCandidates(
                            $electionId,
                            $_GET['search'] ?? '',
                            $_GET['party'] ?? '',
                            $_GET['sort'] ?? 'name'
                        );
                    }
                    break;

                case 'results':
                    if (!$electionId) {
                        $result = ['success' => false, 'message' => 'Election ID required'];
                    } else {
                        $userId = $_SESSION['user_id'] ?? null;
                        $result = $voteController->getElectionResults($electionId, $userId);
                    }
                    break;

                case 'voting_period':
                    if (!$electionId) {
                        $result = ['success' => false, 'message' => 'Election ID required'];
                    } else {
                        requireLogin();
                        $result = $voteController->getVotingPeriod($electionId, $_SESSION['user_id']);
                    }
                    break;

                case 'can_vote':
                    if (!$electionId) {
                        $result = ['success' => false, 'message' => 'Election ID required'];
                    } else {
                        requireLogin();
                        $result = $voteController->canUserVote($electionId, $_SESSION['user_id']);
                    }
                    break;

                case 'vote_history':
                    requireLogin();
                    $result = $voteController->getUserVoteHistory($_SESSION['user_id']);
                    break;

                case 'stats':
                    $userId = $_SESSION['user_id'] ?? null;
                    $result = $voteController->getVotingStats($electionId, $userId);
                    break;

                case 'realtime_updates':
                    if (!$electionId) {
                        $result = ['success' => false, 'message' => 'Election ID required'];
                    } else {
                        $userId = $_SESSION['user_id'] ?? null;
                        $result = $voteController->getRealTimeUpdates(
                            $electionId,
                            $_GET['last_update'] ?? null,
                            $userId
                        );
                    }
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