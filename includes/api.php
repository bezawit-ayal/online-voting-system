<?php
header('Content-Type: application/json');

require_once '../config/db.php';
require_once 'auth.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'cast_vote':
            handleCastVote($input);
            break;
        
        case 'get_results':
            handleGetResults($input);
            break;
        
        case 'check_voting_status':
            handleCheckVotingStatus();
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

function handleCastVote($input) {
    global $conn;
    
    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $votes = $input['votes'] ?? [];
    
    if (empty($votes)) {
        echo json_encode(['success' => false, 'message' => 'No votes provided']);
        return;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        foreach ($votes as $vote) {
            $position = $vote['position'];
            $candidate_id = intval($vote['candidateId']);
            
            // Check if user already voted for this position
            if (has_user_voted($user_id, $position)) {
                throw new Exception("You have already voted for $position");
            }
            
            // Cast vote
            $result = cast_vote($user_id, $candidate_id, $position);
            if (!$result['success']) {
                throw new Exception($result['message']);
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Votes recorded successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleGetResults($input) {
    $position = $input['position'] ?? null;
    
    $results = get_vote_results($position);
    
    // Calculate total votes for percentage
    $total_votes = 0;
    foreach ($results as $result) {
        $total_votes += $result['vote_count'];
    }
    
    // Add percentage to each result
    foreach ($results as &$result) {
        $result['percentage'] = $total_votes > 0 ? round(($result['vote_count'] / $total_votes) * 100, 2) : 0;
    }
    
    echo json_encode(['success' => true, 'data' => $results, 'total_votes' => $total_votes]);
}

function handleCheckVotingStatus() {
    $settings = get_election_settings();
    $voting_open = isset($settings['voting_open']) && $settings['voting_open'] == 1;
    
    echo json_encode([
        'success' => true,
        'voting_open' => $voting_open,
        'user_voted' => is_logged_in() ? $_SESSION['has_voted'] : false
    ]);
}
