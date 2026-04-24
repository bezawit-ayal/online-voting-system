<?php
// models/Election.php - Election management model
require_once __DIR__ . '/Database.php';

class Election {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data, $createdBy) {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO elections (title, description, start_date, end_date, voting_time_limit, allow_multiple_votes, require_verification, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['title'],
                $data['description'] ?? '',
                $data['start_date'],
                $data['end_date'],
                $data['voting_time_limit'] ?? 300,
                $data['allow_multiple_votes'] ?? false,
                $data['require_verification'] ?? true,
                $createdBy
            ]);

            $electionId = $this->db->lastInsertId();

            // Log the action
            $this->logAction($createdBy, 'election_created', 'elections', $electionId, null, $data);

            $this->db->commit();
            return $electionId;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function update($electionId, $data, $updatedBy) {
        try {
            $this->db->beginTransaction();

            // Get old values for audit
            $oldElection = $this->findById($electionId);

            $fields = [];
            $params = [];

            $allowedFields = ['title', 'description', 'start_date', 'end_date', 'status', 'voting_time_limit', 'allow_multiple_votes', 'require_verification'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($fields)) {
                return false;
            }

            $params[] = $electionId;
            $sql = "UPDATE elections SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);

            if ($result) {
                $this->logAction($updatedBy, 'election_updated', 'elections', $electionId, $oldElection, $data);
            }

            $this->db->commit();
            return $result;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function delete($electionId, $deletedBy) {
        try {
            $this->db->beginTransaction();

            // Get election data for audit
            $election = $this->findById($electionId);

            // Delete related records first
            $this->db->prepare("DELETE FROM voting_periods WHERE election_id = ?")->execute([$electionId]);
            $this->db->prepare("DELETE FROM votes WHERE election_id = ?")->execute([$electionId]);
            $this->db->prepare("DELETE FROM candidates WHERE election_id = ?")->execute([$electionId]);

            // Delete election
            $sql = "DELETE FROM elections WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$electionId]);

            if ($result) {
                $this->logAction($deletedBy, 'election_deleted', 'elections', $electionId, $election, null);
            }

            $this->db->commit();
            return $result;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function findById($id) {
        $sql = "SELECT * FROM elections WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAll($page = 1, $limit = 20, $status = null, $search = '') {
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];

        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }

        if ($search) {
            $where[] = "(title LIKE ? OR description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereClause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT * FROM elections$whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getActiveElections() {
        $sql = "SELECT * FROM elections WHERE status = 'active' AND start_date <= NOW() AND end_date >= NOW() ORDER BY end_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getElectionStats($electionId) {
        // Total candidates
        $candidatesSql = "SELECT COUNT(*) as count FROM candidates WHERE election_id = ? AND is_active = TRUE";
        $candidatesStmt = $this->db->prepare($candidatesSql);
        $candidatesStmt->execute([$electionId]);
        $candidatesCount = $candidatesStmt->fetch()['count'];

        // Total votes
        $votesSql = "SELECT COUNT(*) as count FROM votes WHERE election_id = ?";
        $votesStmt = $this->db->prepare($votesSql);
        $votesStmt->execute([$electionId]);
        $votesCount = $votesStmt->fetch()['count'];

        // Total registered voters (users who can vote)
        $votersSql = "SELECT COUNT(*) as count FROM users WHERE is_verified = TRUE";
        $votersStmt = $this->db->prepare($votersSql);
        $votersStmt->execute();
        $votersCount = $votersStmt->fetch()['count'];

        // Voting percentage
        $votingPercentage = $votersCount > 0 ? round(($votesCount / $votersCount) * 100, 2) : 0;

        return [
            'candidates_count' => $candidatesCount,
            'votes_count' => $votesCount,
            'voters_count' => $votersCount,
            'voting_percentage' => $votingPercentage
        ];
    }

    public function getElectionResults($electionId) {
        $sql = "SELECT c.id, c.name, c.party, c.position, c.image_url,
                       COUNT(v.id) as vote_count
                FROM candidates c
                LEFT JOIN votes v ON c.id = v.candidate_id AND v.election_id = ?
                WHERE c.election_id = ? AND c.is_active = TRUE
                GROUP BY c.id, c.name, c.party, c.position, c.image_url
                ORDER BY vote_count DESC, c.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$electionId, $electionId]);
        $results = $stmt->fetchAll();

        // Calculate percentages
        $totalVotes = array_sum(array_column($results, 'vote_count'));
        foreach ($results as &$result) {
            $result['percentage'] = $totalVotes > 0 ? round(($result['vote_count'] / $totalVotes) * 100, 2) : 0;
        }

        return $results;
    }

    public function startElection($electionId, $startedBy) {
        $sql = "UPDATE elections SET status = 'active', updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$electionId]);

        if ($result) {
            $this->logAction($startedBy, 'election_started', 'elections', $electionId);
        }

        return $result;
    }

    public function endElection($electionId, $endedBy) {
        $sql = "UPDATE elections SET status = 'completed', updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$electionId]);

        if ($result) {
            $this->logAction($endedBy, 'election_ended', 'elections', $electionId);
        }

        return $result;
    }

    public function canUserVote($electionId, $userId) {
        // Check if election is active
        $election = $this->findById($electionId);
        if ($election['status'] !== 'active') {
            return ['can_vote' => false, 'reason' => 'Election is not active'];
        }

        // Check if user has already voted (unless multiple votes allowed)
        if (!$election['allow_multiple_votes']) {
            $sql = "SELECT COUNT(*) as count FROM votes WHERE election_id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$electionId, $userId]);
            if ($stmt->fetch()['count'] > 0) {
                return ['can_vote' => false, 'reason' => 'You have already voted in this election'];
            }
        }

        // Check if user is verified (if required)
        if ($election['require_verification']) {
            $sql = "SELECT is_verified FROM users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user['is_verified']) {
                return ['can_vote' => false, 'reason' => 'Account verification required'];
            }
        }

        return ['can_vote' => true];
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