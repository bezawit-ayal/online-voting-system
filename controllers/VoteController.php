<?php
// controllers/VoteController.php - Voting controller
require_once __DIR__ . '/../models/Vote.php';
require_once __DIR__ . '/../models/Election.php';
require_once __DIR__ . '/../utils/NotificationService.php';
require_once __DIR__ . '/../utils/AuditService.php';

class VoteController {
    private $voteModel;
    private $electionModel;
    private $notificationService;
    private $auditService;

    public function __construct() {
        $this->voteModel = new Vote();
        $this->electionModel = new Election();
        $this->notificationService = new NotificationService();
        $this->auditService = new AuditService();
    }

    public function castVote($electionId, $candidateId, $userId, $votingDuration = null) {
        try {
            $result = $this->voteModel->castVote($electionId, $userId, $candidateId, $votingDuration);

            if ($result['success']) {
                // Get election and candidate details for notification
                $election = $this->electionModel->findById($electionId);
                $candidate = $this->getCandidate($candidateId, $electionId);
                $user = $this->getUser($userId);

                // Send notifications
                $this->notificationService->sendVoteConfirmation(
                    $userId,
                    $user['email'],
                    $user['full_name'],
                    $election['title'],
                    $candidate['name']
                );

                $this->auditService->logAction($userId, 'vote_cast', 'votes', $result['vote_id'], null, [
                    'election_id' => $electionId,
                    'candidate_id' => $candidateId
                ]);
            }

            return $result;

        } catch (Exception $e) {
            $this->auditService->logAction($userId, 'vote_error', 'votes', null, null, null, [
                'election_id' => $electionId,
                'candidate_id' => $candidateId,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'message' => 'Vote casting failed: ' . $e->getMessage()];
        }
    }

    public function startVotingPeriod($electionId, $userId) {
        try {
            $result = $this->voteModel->startVotingPeriod($electionId, $userId);

            if ($result) {
                $this->auditService->logAction($userId, 'voting_period_started', 'voting_periods', null, null, [
                    'election_id' => $electionId
                ]);

                return ['success' => true, 'message' => 'Voting period started'];
            } else {
                return ['success' => false, 'message' => 'Failed to start voting period'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to start voting period: ' . $e->getMessage()];
        }
    }

    public function getVotingPeriod($electionId, $userId) {
        try {
            $period = $this->voteModel->getVotingPeriod($electionId, $userId);

            if ($period) {
                return [
                    'success' => true,
                    'period' => $period,
                    'time_remaining' => max(0, $period['time_remaining_seconds'])
                ];
            } else {
                return ['success' => false, 'message' => 'No active voting period'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get voting period: ' . $e->getMessage()];
        }
    }

    public function getElectionResults($electionId, $userId = null) {
        try {
            $results = $this->voteModel->getElectionResults($electionId);

            // Check if user can view results (only after voting or if election is complete)
            if ($userId) {
                $hasVoted = $this->voteModel->hasUserVoted($electionId, $userId);
                $election = $this->electionModel->findById($electionId);

                if (!$hasVoted && $election['status'] !== 'completed') {
                    return ['success' => false, 'message' => 'Results available after voting or election completion'];
                }
            }

            $this->auditService->logAction($userId, 'results_viewed', 'elections', $electionId);

            return [
                'success' => true,
                'results' => $results
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get results: ' . $e->getMessage()];
        }
    }

    public function getUserVoteHistory($userId) {
        try {
            $history = $this->voteModel->getUserVoteHistory($userId);

            $this->auditService->logAction($userId, 'vote_history_viewed', 'users', $userId);

            return [
                'success' => true,
                'history' => $history
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get vote history: ' . $e->getMessage()];
        }
    }

    public function getVotingStats($electionId = null, $userId = null) {
        try {
            $stats = $this->voteModel->getVotingStats($electionId);

            $this->auditService->logAction($userId, 'voting_stats_viewed', 'elections', $electionId);

            return [
                'success' => true,
                'stats' => $stats
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get voting stats: ' . $e->getMessage()];
        }
    }

    public function getRealTimeUpdates($electionId, $lastUpdate = null, $userId = null) {
        try {
            $updates = $this->voteModel->getRealTimeUpdates($electionId, $lastUpdate);

            // Only log if there are actual updates
            if (!empty($updates)) {
                $this->auditService->logAction($userId, 'realtime_updates_viewed', 'elections', $electionId);
            }

            return [
                'success' => true,
                'updates' => $updates,
                'last_update' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get updates: ' . $e->getMessage()];
        }
    }

    public function canUserVote($electionId, $userId) {
        try {
            $result = $this->electionModel->canUserVote($electionId, $userId);

            return [
                'success' => true,
                'can_vote' => $result['can_vote'],
                'reason' => $result['reason'] ?? null
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'can_vote' => false,
                'reason' => 'Error checking voting eligibility: ' . $e->getMessage()
            ];
        }
    }

    public function getElectionCandidates($electionId, $search = '', $party = '', $sortBy = 'name') {
        try {
            $sql = "SELECT c.*, COUNT(v.id) as vote_count
                    FROM candidates c
                    LEFT JOIN votes v ON c.id = v.candidate_id AND v.election_id = ?
                    WHERE c.election_id = ? AND c.is_active = TRUE";

            $params = [$electionId, $electionId];

            if ($search) {
                $sql .= " AND (c.name LIKE ? OR c.description LIKE ? OR c.party LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            if ($party) {
                $sql .= " AND c.party = ?";
                $params[] = $party;
            }

            $sql .= " GROUP BY c.id";

            // Sorting
            switch ($sortBy) {
                case 'party':
                    $sql .= " ORDER BY c.party ASC, c.name ASC";
                    break;
                case 'votes':
                    $sql .= " ORDER BY vote_count DESC, c.name ASC";
                    break;
                default:
                    $sql .= " ORDER BY c.name ASC";
            }

            $stmt = Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute($params);
            $candidates = $stmt->fetchAll();

            // Get unique parties for filter
            $partySql = "SELECT DISTINCT party FROM candidates WHERE election_id = ? AND is_active = TRUE ORDER BY party";
            $partyStmt = Database::getInstance()->getConnection()->prepare($partySql);
            $partyStmt->execute([$electionId]);
            $parties = $partyStmt->fetchAll(PDO::FETCH_COLUMN);

            return [
                'success' => true,
                'candidates' => $candidates,
                'parties' => $parties,
                'total' => count($candidates)
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get candidates: ' . $e->getMessage()];
        }
    }

    private function getCandidate($candidateId, $electionId) {
        $sql = "SELECT * FROM candidates WHERE id = ? AND election_id = ?";
        $stmt = Database::getInstance()->getConnection()->prepare($sql);
        $stmt->execute([$candidateId, $electionId]);
        return $stmt->fetch();
    }

    private function getUser($userId) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = Database::getInstance()->getConnection()->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
}
?>