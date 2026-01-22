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
include '../Model/mailer.php'; // ✅ NEW (PHPMailer helper)

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {

    // ✅ NEW: Registration with Email OTP
    case 'register_start':
        handleRegisterStart($conn);
        break;
    case 'register_verify':
        handleRegisterVerify($conn);
        break;

    // Existing
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

    // ✅ NEW: Forgot Password via Email OTP
    case 'forgot_start':
        handleForgotStart($conn);
        break;
    case 'forgot_verify':
        handleForgotVerify($conn);
        break;

    default:
        sendError('Invalid action', 400);
}

/* =========================================================
   ✅ NEW: REGISTER START (Send OTP Email, store pending)
   Requires table: pending_registrations
   ========================================================= */
function handleRegisterStart($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('Method not allowed', 405);

    $studentId = trim($_POST['student_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $university = trim($_POST['university'] ?? '');
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($studentId === '' || $name === '' || $email === '' || $password === '' || $confirmPassword === '' || $university === '') {
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

    // Clear old pending requests for same email
    $emailEsc = $conn->real_escape_string($email);
    $conn->query("DELETE FROM pending_registrations WHERE email='$emailEsc'");

    $otp = (string)random_int(100000, 999999);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);

    // Use your existing password hashing (from functions.php)
    // If you don't have hashPassword(), we fallback safely below
    if (function_exists('hashPassword')) {
        $passwordHash = hashPassword($password);
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    }

    $studentIdEsc = $conn->real_escape_string($studentId);
    $nameEsc = $conn->real_escape_string($name);
    $uniEsc = $conn->real_escape_string($university);
    $passEsc = $conn->real_escape_string($passwordHash);
    $otpHashEsc = $conn->real_escape_string($otpHash);

    $expires = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 minutes
    $expiresEsc = $conn->real_escape_string($expires);

    $q = "INSERT INTO pending_registrations (student_id, name, email, university, password_hash, otp_hash, otp_expires_at)
          VALUES ('$studentIdEsc', '$nameEsc', '$emailEsc', '$uniEsc', '$passEsc', '$otpHashEsc', '$expiresEsc')";

    if (!$conn->query($q)) {
        sendError('Failed to create OTP request', 500);
    }

    $html = "
      <div style='font-family:Arial,sans-serif;line-height:1.6'>
        <h2 style='margin:0 0 10px'>TrackBack Email Verification</h2>
        <p>Your OTP code is:</p>
        <div style='font-size:28px;font-weight:700;letter-spacing:4px;margin:10px 0'>{$otp}</div>
        <p>This code expires in <b>10 minutes</b>.</p>
        <p>If you didn’t request this, ignore this email.</p>
      </div>
    ";

    $sent = sendMailSMTP($email, $name, 'TrackBack OTP Verification', $html);
    if (!$sent) {
        sendError('OTP could not be sent. Check SMTP settings.', 500);
    }

    sendSuccess(['email' => $email], 'OTP sent to your email. Please verify to finish registration.');
}

/* =========================================================
   ✅ NEW: REGISTER VERIFY (Verify OTP and create user)
   ========================================================= */
function handleRegisterVerify($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('Method not allowed', 405);

    $email = trim($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');

    if ($email === '' || $otp === '') {
        sendError('Email and OTP are required', 400);
    }

    $emailEsc = $conn->real_escape_string($email);
    $res = $conn->query("SELECT * FROM pending_registrations WHERE email='$emailEsc' LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        sendError('No pending registration found', 404);
    }

    $row = $res->fetch_assoc();

    if (strtotime($row['otp_expires_at']) < time()) {
        $conn->query("DELETE FROM pending_registrations WHERE email='$emailEsc'");
        sendError('OTP expired. Please register again.', 400);
    }

    if (!password_verify($otp, $row['otp_hash'])) {
        sendError('Invalid OTP', 401);
    }

    // Double-check again
    if (emailExists($conn, $email)) {
        $conn->query("DELETE FROM pending_registrations WHERE email='$emailEsc'");
        sendError('Email already registered. Please login.', 409);
    }

    if (studentIdExists($conn, $row['student_id'])) {
        $conn->query("DELETE FROM pending_registrations WHERE email='$emailEsc'");
        sendError('Student ID already registered. Please login.', 409);
    }

    // Create user as pending (same behavior as your current system)
    // registerUser() expects plain password - but we already have a hash.
    // So we insert directly to match your schema:
    $studentIdEsc = $conn->real_escape_string($row['student_id']);
    $nameEsc = $conn->real_escape_string($row['name']);
    $uniEsc = $conn->real_escape_string($row['university']);
    $passEsc = $conn->real_escape_string($row['password_hash']);

    $q = "INSERT INTO users (student_id, name, email, password_hash, user_type, university, account_status)
          VALUES ('$studentIdEsc', '$nameEsc', '$emailEsc', '$passEsc', 'student', '$uniEsc', 'pending')";

    if (!$conn->query($q)) {
        sendError('Registration failed', 500);
    }

    $conn->query("DELETE FROM pending_registrations WHERE email='$emailEsc'");

    sendSuccess([], 'Email verified! Registration complete. Please wait for admin approval.');
}

/* =========================================================
   ✅ NEW: FORGOT START (Send OTP)
   Requires table: password_reset_tokens (token stores OTP HASH)
   ========================================================= */
function handleForgotStart($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('Method not allowed', 405);

    $email = trim($_POST['email'] ?? '');
    if ($email === '') sendError('Email is required', 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) sendError('Invalid email format', 400);

    // Get user by email (if you have a helper use it, else query)
    $emailEsc = $conn->real_escape_string($email);
    $res = $conn->query("SELECT user_id, name FROM users WHERE email='$emailEsc' LIMIT 1");
    if (!$res || $res->num_rows === 0) sendError('No account found with this email', 404);

    $user = $res->fetch_assoc();
    $userId = (int)$user['user_id'];
    $name = $user['name'] ?? $email;

    // Delete old tokens for this user
    $conn->query("DELETE FROM password_reset_tokens WHERE user_id=$userId");

    $otp = (string)random_int(100000, 999999);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);

    $otpHashEsc = $conn->real_escape_string($otpHash);
    $expires = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 minutes
    $expiresEsc = $conn->real_escape_string($expires);

    $q = "INSERT INTO password_reset_tokens (user_id, token, expires_at)
          VALUES ($userId, '$otpHashEsc', '$expiresEsc')";
    if (!$conn->query($q)) sendError('Failed to create reset token', 500);

    $html = "
      <div style='font-family:Arial,sans-serif;line-height:1.6'>
        <h2 style='margin:0 0 10px'>TrackBack Password Reset</h2>
        <p>Your OTP code is:</p>
        <div style='font-size:28px;font-weight:700;letter-spacing:4px;margin:10px 0'>{$otp}</div>
        <p>This code expires in <b>10 minutes</b>.</p>
      </div>
    ";

    $sent = sendMailSMTP($email, $name, 'TrackBack Password Reset OTP', $html);
    if (!$sent) sendError('OTP could not be sent. Check SMTP settings.', 500);

    sendSuccess(['email' => $email], 'OTP sent to your email.');
}

/* =========================================================
   ✅ NEW: FORGOT VERIFY (Verify OTP + update password)
   ========================================================= */
function handleForgotVerify($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('Method not allowed', 405);

    $email = trim($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($email === '' || $otp === '' || $newPass === '' || $confirm === '') {
        sendError('All fields are required', 400);
    }

    if ($newPass !== $confirm) {
        sendError('Passwords do not match', 400);
    }

    if (strlen($newPass) < 6) {
        sendError('Password must be at least 6 characters long', 400);
    }

    $emailEsc = $conn->real_escape_string($email);
    $resU = $conn->query("SELECT user_id FROM users WHERE email='$emailEsc' LIMIT 1");
    if (!$resU || $resU->num_rows === 0) sendError('No account found with this email', 404);

    $u = $resU->fetch_assoc();
    $userId = (int)$u['user_id'];

    $resT = $conn->query("SELECT * FROM password_reset_tokens WHERE user_id=$userId ORDER BY token_id DESC LIMIT 1");
    if (!$resT || $resT->num_rows === 0) sendError('No reset request found', 404);

    $tokenRow = $resT->fetch_assoc();

    if (strtotime($tokenRow['expires_at']) < time()) {
        $conn->query("DELETE FROM password_reset_tokens WHERE user_id=$userId");
        sendError('OTP expired. Please request again.', 400);
    }

    if (!password_verify($otp, $tokenRow['token'])) {
        sendError('Invalid OTP', 401);
    }

    // Hash new password (use your existing method if available)
    if (function_exists('hashPassword')) {
        $newHash = hashPassword($newPass);
    } else {
        $newHash = password_hash($newPass, PASSWORD_DEFAULT);
    }

    $newHashEsc = $conn->real_escape_string($newHash);

    if (!$conn->query("UPDATE users SET password_hash='$newHashEsc' WHERE user_id=$userId")) {
        sendError('Failed to update password', 500);
    }

    $conn->query("DELETE FROM password_reset_tokens WHERE user_id=$userId");

    sendSuccess([], 'Password updated successfully. You can login now.');
}

/* =======================
   EXISTING FUNCTIONS
   ======================= */

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