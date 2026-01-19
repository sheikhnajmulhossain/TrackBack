<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);


ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();


include '../Model/database.php';
include '../Model/functions.php';


setCorsHeaders();


$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;


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

function handleRegister($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }

    $studentId = $_POST['student_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $university = $_POST['university'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($studentId) || empty($name) || empty($email) || empty($password)) {
        sendError('All fields are required', 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format', 400);
    }

    if ($password !== $confirmPassword) {
        sendError('Passwords do not match', 400);
    }

    if (strlen($password) < 6) {
        sendError('Password must be at least 6 characters long', 400);
    }

    if (studentIdExists($conn, $studentId)) {
        sendError('Student ID already registered', 409);
    }

    if (emailExists($conn, $email)) {
        sendError('Email already registered', 409);
    }

    $userId = registerUser($conn, $studentId, $name, $email, $university, $password);

    if ($userId) {
        sendSuccess(['user_id' => $userId], 'Registration successful. Please wait for admin approval.');
    } else {
        sendError('Registration failed', 500);
    }
}

function handleLogin($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        sendError('Email and password are required', 400);
    }

    $user = authenticateUser($conn, $email, $password);

    if (!$user) {
        sendError('Invalid credentials', 401);
    }


    if ($user['account_status'] === 'banned') {
        sendError('Your account has been banned. Please contact the administrator for assistance.', 403);
    }

    if ($user['account_status'] === 'pending') {
        sendError('Your account has not been approved by the administrator. Please contact the administrator for approval.', 403);
    }


    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['student_id'] = $user['student_id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['university'] = $user['university'];

    session_regenerate_id(true);

    $cookieParams = session_get_cookie_params();
    setcookie(session_name(), session_id(), time() + (int)$cookieParams['lifetime'], $cookieParams['path'], $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);

    logAction($conn, $user['user_id'], 'LOGIN', 'User logged in via API');

    sendSuccess([
        'user_id' => $user['user_id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'user_type' => $user['user_type'],
        'university' => $user['university']
    ], 'Login successful');
}

function handleLogout() {
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        
        $_SESSION = [];

        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        
        if (isset($_COOKIE['user_email'])) {
            setcookie('user_email', '', time() - 42000, '/');
        }

        session_destroy();

        sendSuccess([], 'Logout successful');
    } else {
        sendError('No active session', 400);
    }
}

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
