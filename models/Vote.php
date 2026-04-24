<?php
// models/Vote.php - Enhanced voting model
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Election.php';

class Vote {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function castVote($electionId, $userId, $candidateId, $votingDuration = null) {
        try {
            $this->db->beginTransaction();

            $election = new Election();
            $canVote = $election->canUserVote($electionId, $userId);

            if (!$canVote['can_vote']) {
                return ['success' => false, 'message' => $canVote['reason']];
            }

            // Check if candidate exists and is active
            $candidate = $this->getCandidate($candidateId, $electionId);
            if (!$candidate || !$candidate['is_active']) {
                return ['success' => false, 'message' => 'Invalid candidate'];
            }

            // Insert vote
            $sql = "INSERT INTO votes (user_id, election_id, candidate_id, ip_address, user_agent, voting_duration)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $userId,
                $electionId,
                $candidateId,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $votingDuration
            ]);

            $voteId = $this->db->lastInsertId();

            // Mark voting period as completed if it exists
            $this->completeVotingPeriod($electionId, $userId);

            // Log the action
            $this->logAction($userId, 'vote_cast', 'votes', $voteId, null, [
                'election_id' => $electionId,
                'candidate_id' => $candidateId
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Vote cast successfully', 'vote_id' => $voteId];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to cast vote: ' . $e->getMessage()];
        }
    }

    public function startVotingPeriod($electionId, $userId) {
        $election = new Election();
        $electionData = $election->findById($electionId);

        if (!$electionData) {
            return false;
        }

        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$electionData['voting_time_limit']} seconds"));

        $sql = "INSERT INTO voting_periods (election_id, user_id, expires_at, time_remaining)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                started_at = NOW(),
                expires_at = VALUES(expires_at),
                time_remaining = VALUES(time_remaining),
                completed = FALSE";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$electionId, $userId, $expiresAt, $electionData['voting_time_limit']]);
    }

    public function getVotingPeriod($electionId, $userId) {
        $sql = "SELECT *, TIMESTAMPDIFF(SECOND, NOW(), expires_at) as time_remaining_seconds
                FROM voting_periods
                WHERE election_id = ? AND user_id = ? AND completed = FALSE AND expires_at > NOW()";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$electionId, $userId]);
        return $stmt->fetch();
    }

    public function getElectionResults($electionId) {
        $sql = "SELECT c.id, c.name, c.party, c.position, c.image_url, c.description,
                       COUNT(v.id) as vote_count
                FROM candidates c
                LEFT JOIN votes v ON c.id = v.candidate_id AND v.election_id = ?
                WHERE c.election_id = ? AND c.is_active = TRUE
                GROUP BY c.id, c.name, c.party, c.position, c.image_url, c.description
                ORDER BY vote_count DESC, c.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$electionId, $electionId]);
        $results = $stmt->fetchAll();

        // Calculate percentages and rankings
        $totalVotes = array_sum(array_column($results, 'vote_count'));
        $rank = 1;

        foreach ($results as &$result) {
            $result['percentage'] = $totalVotes > 0 ? round(($result['vote_count'] / $totalVotes) * 100, 2) : 0;
            $result['rank'] = $rank++;
            $result['is_winner'] = ($rank - 1) === 1 && $totalVotes > 0;
        }

        return [
            'results' => $results,
            'total_votes' => $totalVotes,
            'total_candidates' => count($results)
        ];
    }

    public function getUserVoteHistory($userId, $electionId = null) {
        $where = "WHERE v.user_id = ?";
        $params = [$userId];

        if ($electionId) {
            $where .= " AND v.election_id = ?";
            $params[] = $electionId;
        }

        $sql = "SELECT v.id, v.election_id, v.candidate_id, v.voted_at, v.voting_duration,
                       e.title as election_title, c.name as candidate_name, c.party
                FROM votes v
                JOIN elections e ON v.election_id = e.id
                JOIN candidates c ON v.candidate_id = c.id
                $where
                ORDER BY v.voted_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getVotingStats($electionId = null) {
        if ($electionId) {
            // Election-specific stats
            $sql = "SELECT
                        COUNT(DISTINCT v.user_id) as total_voters,
                        COUNT(v.id) as total_votes,
                        AVG(v.voting_duration) as avg_voting_time,
                        MIN(v.voted_at) as first_vote,
                        MAX(v.voted_at) as last_vote
                    FROM votes v
                    WHERE v.election_id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$electionId]);
            $stats = $stmt->fetch();

            // Hourly voting pattern
            $hourlySql = "SELECT HOUR(voted_at) as hour, COUNT(*) as votes
                         FROM votes WHERE election_id = ?
                         GROUP BY HOUR(voted_at) ORDER BY hour";
            $hourlyStmt = $this->db->prepare($hourlySql);
            $hourlyStmt->execute([$electionId]);
            $stats['hourly_pattern'] = $hourlyStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        } else {
            // Global stats
            $sql = "SELECT
                        COUNT(DISTINCT v.user_id) as total_voters,
                        COUNT(v.id) as total_votes,
                        COUNT(DISTINCT v.election_id) as total_elections,
                        AVG(v.voting_duration) as avg_voting_time
                    FROM votes v";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats = $stmt->fetch();
        }

        return $stats;
    }

    public function hasUserVoted($electionId, $userId) {
        $sql = "SELECT COUNT(*) as count FROM votes WHERE election_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$electionId, $userId]);
        return $stmt->fetch()['count'] > 0;
    }

    public function getRealTimeUpdates($electionId, $lastUpdate = null) {
        $where = $lastUpdate ? "AND v.voted_at > ?" : "";
        $params = [$electionId];
        if ($lastUpdate) {
            $params[] = $lastUpdate;
        }

        $sql = "SELECT v.id, v.user_id, v.candidate_id, v.voted_at,
                       c.name as candidate_name, c.party
                FROM votes v
                JOIN candidates c ON v.candidate_id = c.id
                WHERE v.election_id = ? $where
                ORDER BY v.voted_at DESC LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function getCandidate($candidateId, $electionId) {
        $sql = "SELECT * FROM candidates WHERE id = ? AND election_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$candidateId, $electionId]);
        return $stmt->fetch();
    }

    private function completeVotingPeriod($electionId, $userId) {
        $sql = "UPDATE voting_periods SET completed = TRUE, time_remaining = 0
                WHERE election_id = ? AND user_id = ? AND completed = FALSE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$electionId, $userId]);
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