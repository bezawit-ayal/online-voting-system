<?php
// Authentication functions

function register_user($username, $email, $password, $full_name, $voter_id) {
    global $conn;
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($voter_id)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }
    
    // Check if username already exists
    $check_user = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check_user->bind_param("ss", $username, $email);
    $check_user->execute();
    $check_user->store_result();
    
    if ($check_user->num_rows > 0) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }
    $check_user->close();
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    // Insert user
    $insert = $conn->prepare("INSERT INTO users (username, email, password, full_name, voter_id) VALUES (?, ?, ?, ?, ?)");
    $insert->bind_param("sssss", $username, $email, $hashed_password, $full_name, $voter_id);
    
    if ($insert->execute()) {
        return ['success' => true, 'message' => 'Registration successful. Please login.'];
    } else {
        return ['success' => false, 'message' => 'Error during registration'];
    }
    $insert->close();
}

function login_user($username, $password) {
    global $conn;
    
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Username and password required'];
    }
    
    $stmt = $conn->prepare("SELECT id, username, password, role, has_voted FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['has_voted'] = $user['has_voted'];
            return ['success' => true, 'message' => 'Login successful', 'role' => $user['role']];
        } else {
            return ['success' => false, 'message' => 'Invalid password'];
        }
    } else {
        return ['success' => false, 'message' => 'User not found'];
    }
    $stmt->close();
}

function logout_user() {
    session_destroy();
    return true;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

function require_admin() {
    if (!is_admin()) {
        header("Location: " . BASE_URL . "/index.php");
        exit();
    }
}
