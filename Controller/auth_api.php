<?php
session_start();

// Include database connection and functions
include '../Model/database.php';
include '../Model/functions.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

// Handle requests
switch ($action) {
    case 'register':
        handleRegister($conn);
        break;
    case 'login':
        handleLogin($conn);
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check_session':
        handleCheckSession();
        break;
    case 'verify_email':
        handleVerifyEmail($conn);
        break;
    default:
        sendError('Invalid action', 400);
}

/**
 * Handle user registration
 */
function handleRegister($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $studentId = $_POST['student_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $university = $_POST['university'] ?? '';
    
    // Validation
    if (empty($studentId) || empty($name) || empty($email) || empty($password)) {
        sendError('All fields are required', 400);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format', 400);
    }
    
    if ($password !== $confirmPassword) {
        sendError('Passwords do not match', 400);
    }
    
    if (strlen($password) < 8) {
        sendError('Password must be at least 8 characters long', 400);
    }
    
    if (studentIdExists($conn, $studentId)) {
        sendError('Student ID already registered', 409);
    }
    
    if (emailExists($conn, $email)) {
        sendError('Email already registered', 409);
    }
    
    // Create user
    $result = createUser($conn, $studentId, $name, $email, $password, 'student', $university);
    
    if ($result['success']) {
        sendSuccess(['user_id' => $result['user_id']], 'Registration successful. Please wait for admin approval.');
    } else {
        sendError('Registration failed: ' . $result['error'], 500);
    }
}

/**
 * Handle user login
 */
function handleLogin($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        sendError('Email and password are required', 400);
    }
    
    $user = getUserByEmail($conn, $email);
    
    if (!$user) {
        sendError('Invalid credentials', 401);
    }
    
    // Check if account is approved
    if ($user['account_status'] === 'banned') {
        sendError('Your account has been banned', 403);
    }
    
    if ($user['account_status'] === 'pending') {
        sendError('Your account is pending approval. Please wait for admin approval.', 403);
    }
    
    // Verify password
    if (!verifyPassword($password, $user['password_hash'])) {
        sendError('Invalid credentials', 401);
    }
    
    // Create session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['student_id'] = $user['student_id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['university'] = $user['university'];
    
    // Log action
    logAction($conn, $user['user_id'], 'LOGIN', ['ip' => $_SERVER['REMOTE_ADDR']]);
    
    sendSuccess([
        'user_id' => $user['user_id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'user_type' => $user['user_type'],
        'university' => $user['university']
    ], 'Login successful');
}

/**
 * Handle user logout
 */
function handleLogout() {
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        session_destroy();
        sendSuccess([], 'Logout successful');
    } else {
        sendError('No active session', 400);
    }
}

/**
 * Check user session
 */
function handleCheckSession() {
    if (isset($_SESSION['user_id'])) {
        sendSuccess([
            'user_id' => $_SESSION['user_id'],
            'name' => $_SESSION['name'],
            'email' => $_SESSION['email'],
            'user_type' => $_SESSION['user_type']
        ], 'Session active');
    } else {
        sendError('No active session', 401);
    }
}

/**
 * Handle email verification
 */
function handleVerifyEmail($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        sendError('Email is required', 400);
    }
    
    if (emailExists($conn, $email)) {
        sendSuccess([], 'Email is already registered');
    } else {
        sendSuccess([], 'Email is available');
    }
}
?>
