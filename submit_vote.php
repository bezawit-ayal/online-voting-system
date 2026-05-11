<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

require_login();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// prevent double voting
$check = $conn->prepare("SELECT * FROM votes WHERE user_id = ?");
$check->bind_param("i", $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "You already voted"]);
    exit;
}

// save votes
foreach ($data as $position => $candidate_id) {

    $stmt = $conn->prepare("INSERT INTO votes (user_id, candidate_id, position) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $candidate_id, $position);
    $stmt->execute();
}

$_SESSION['has_voted'] = true;

echo json_encode(["success" => true]);
?>