<?php
// General functions

function get_election_settings() {
    global $conn;
    
    $query = "SELECT setting_name, setting_value FROM election_settings";
    $result = $conn->query($query);
    
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_name']] = $row['setting_value'];
    }
    return $settings;
}

function is_voting_open() {
    $settings = get_election_settings();
    return isset($settings['voting_open']) && $settings['voting_open'] == 1;
}

function get_candidates_by_position($position = null) {
    global $conn;
    
    if ($position) {
        $stmt = $conn->prepare("SELECT id, name, position, bio, image_path FROM candidates WHERE position = ? AND is_active = 1 ORDER BY name");
        $stmt->bind_param("s", $position);
    } else {
        $stmt = $conn->prepare("SELECT id, name, position, bio, image_path FROM candidates WHERE is_active = 1 ORDER BY position, name");
        $stmt = $conn->query("SELECT id, name, position, bio, image_path FROM candidates WHERE is_active = 1 ORDER BY position, name");
    }
    
    if (is_object($stmt)) {
        $result = $stmt->execute() ? $stmt->get_result() : null;
    } else {
        $result = $stmt;
    }
    
    $candidates = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $candidates[] = $row;
        }
    }
    return $candidates;
}

function get_positions() {
    global $conn;
    
    $query = "SELECT DISTINCT position FROM candidates WHERE is_active = 1 ORDER BY position";
    $result = $conn->query($query);
    
    $positions = [];
    while ($row = $result->fetch_assoc()) {
        $positions[] = $row['position'];
    }
    return $positions;
}

function has_user_voted($user_id, $position) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id FROM votes WHERE user_id = ? AND position = ?");
    $stmt->bind_param("is", $user_id, $position);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

function cast_vote($user_id, $candidate_id, $position) {
    global $conn;
    
    // Check if user already voted for this position
    if (has_user_voted($user_id, $position)) {
        return ['success' => false, 'message' => 'You have already voted for this position'];
    }
    
    // Check if voting is open
    if (!is_voting_open()) {
        return ['success' => false, 'message' => 'Voting is currently closed'];
    }
    
    // Insert vote
    $stmt = $conn->prepare("INSERT INTO votes (user_id, candidate_id, position) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $candidate_id, $position);
    
    if ($stmt->execute()) {
        // Check if user has voted for all positions
        $positions = get_positions();
        $user_voted_count = 0;
        foreach ($positions as $pos) {
            if (has_user_voted($user_id, $pos)) {
                $user_voted_count++;
            }
        }
        
        if ($user_voted_count === count($positions)) {
            $update = $conn->prepare("UPDATE users SET has_voted = 1 WHERE id = ?");
            $update->bind_param("i", $user_id);
            $update->execute();
            $_SESSION['has_voted'] = true;
        }
        
        return ['success' => true, 'message' => 'Vote recorded successfully'];
    } else {
        return ['success' => false, 'message' => 'Error recording vote'];
    }
    $stmt->close();
}

function get_vote_results($position = null) {
    global $conn;
    
    if ($position) {
        $stmt = $conn->prepare("
            SELECT c.id, c.name, c.position, c.image_path,
                   COUNT(v.id) as vote_count
            FROM candidates c
            LEFT JOIN votes v ON c.id = v.candidate_id
            WHERE c.position = ? AND c.is_active = 1
            GROUP BY c.id
            ORDER BY vote_count DESC
        ");
        $stmt->bind_param("s", $position);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("
            SELECT c.id, c.name, c.position, c.image_path,
                   COUNT(v.id) as vote_count
            FROM candidates c
            LEFT JOIN votes v ON c.id = v.candidate_id
            WHERE c.is_active = 1
            GROUP BY c.id
            ORDER BY c.position, vote_count DESC
        ");
    }
    
    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    return $results;
}

function escape_output($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}
