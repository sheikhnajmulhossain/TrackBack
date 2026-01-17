<?php
/**
 * TrackBack Common Functions and Security
 */

// Security: Prevent direct access
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    die('Direct access not allowed');
}

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

/**
 * Validate user session
 */
function validateSession($requiredRole = null) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    if ($requiredRole && $_SESSION['user_type'] !== $requiredRole) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    global $conn;
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return $conn->real_escape_string(strip_tags(trim($data)));
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get user by ID
 */
function getUserById($conn, $userId) {
    $userId = (int)$userId;
    $query = "SELECT * FROM users WHERE user_id = $userId";
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc() : null;
}

/**
 * Get user by email
 */
function getUserByEmail($conn, $email) {
    $email = $conn->real_escape_string($email);
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc() : null;
}

/**
 * Create new user
 */
function createUser($conn, $studentId, $name, $email, $password, $userType = 'student', $university = null) {
    $studentId = sanitizeInput($studentId);
    $name = sanitizeInput($name);
    $email = sanitizeInput($email);
    $userType = sanitizeInput($userType);
    $university = $university ? sanitizeInput($university) : null;
    $passwordHash = hashPassword($password);
    
    $query = "INSERT INTO users (student_id, name, email, password_hash, user_type, university, account_status) 
              VALUES ('$studentId', '$name', '$email', '$passwordHash', '$userType', " . ($university ? "'$university'" : "NULL") . ", 'pending')";
    
    if ($conn->query($query)) {
        return ['success' => true, 'user_id' => $conn->insert_id];
    }
    return ['success' => false, 'error' => $conn->error];
}

/**
 * Log user action
 */
function logAction($conn, $userId, $action, $details = null) {
    $userId = (int)$userId;
    $action = $conn->real_escape_string($action);
    $details = $details ? $conn->real_escape_string(json_encode($details)) : null;
    
    $query = "INSERT INTO audit_logs (user_id, action, details, timestamp) 
              VALUES ($userId, '$action', " . ($details ? "'$details'" : "NULL") . ", NOW())";
    
    return $conn->query($query);
}

/**
 * Send error response
 */
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

/**
 * Send success response
 */
function sendSuccess($data = [], $message = 'Success') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

/**
 * Check if email exists
 */
function emailExists($conn, $email) {
    return getUserByEmail($conn, $email) !== null;
}

/**
 * Check if student_id exists
 */
function studentIdExists($conn, $studentId) {
    $studentId = $conn->real_escape_string($studentId);
    $query = "SELECT user_id FROM users WHERE student_id = '$studentId'";
    $result = $conn->query($query);
    return $result && $result->num_rows > 0;
}
?>
