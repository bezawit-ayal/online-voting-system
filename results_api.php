<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';

try {
    // Get results with candidate details
    $stmt = $pdo->query(
        'SELECT c.id, c.name, c.party, c.image_url, COUNT(v.id) AS votes
         FROM candidates c
         LEFT JOIN votes v ON v.candidate_id = c.id
         GROUP BY c.id, c.name, c.party, c.image_url
         ORDER BY votes DESC, c.name ASC'
    );

    $results = $stmt->fetchAll();

    // Get total votes and candidates count
    $totalVotes = array_sum(array_column($results, 'votes'));
    $totalCandidates = count($results);

    // Get total registered users for participation rate
    $stmt = $pdo->query('SELECT COUNT(*) FROM users');
    $totalUsers = $stmt->fetchColumn();

    $participationRate = $totalUsers > 0 ? round(($totalVotes / $totalUsers) * 100, 1) : 0;

    echo json_encode([
        'success' => true,
        'results' => $results,
        'summary' => [
            'total_votes' => $totalVotes,
            'total_candidates' => $totalCandidates,
            'total_users' => $totalUsers,
            'participation_rate' => $participationRate
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load results.']);
}