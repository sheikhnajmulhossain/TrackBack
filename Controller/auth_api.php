<?php
declare(strict_types=1);

/* =========================================================
   TrackBack Auth API (JSON-safe, copy-paste ready)
   File: Controller/auth_api.php
   ========================================================= */

// Always JSON
header('Content-Type: application/json; charset=utf-8');

// --- Fatal/Warning/Exception -> JSON (prevents res.json() crash) ---
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error (fatal). Check error logs.',
            'error'   => $err['message'],
            'file'    => $err['file'],
            'line'    => $err['line'],
        ]);
    }
});

set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error (exception).',
        'error'   => $e->getMessage(),
    ]);
    exit;
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error (PHP warning/notice).',
        'error'   => $message,
        'file'    => $file,
        'line'    => $line,
    ]);
    exit;
});

// PHP settings
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Session settings
ini_set('session.cookie_lifetime', '86400');
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
// NOTE: session.cookie_secure is best set when HTTPS is enabled; we'll set cookie secure at runtime.
session_start();

/* -------------------------
   Helper: CORS
------------------------- */
if (!function_exists('setCorsHeaders')) {
    function setCorsHeaders(): void {
        // If your site uses same-origin only, this is safe.
        // If you need cross-origin, set a specific Origin instead of "*".
        header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
        header('Vary: Origin');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    }
}
setCorsHeaders();

// Preflight
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/* -------------------------
   Includes
------------------------- */
require_once __DIR__ . '/../Model/database.php';
require_once __DIR__ . '/../Model/functions.php';
require_once __DIR__ . '/../Model/mailer.php'; // PHPMailer helper

/* -------------------------
   Ensure DB is ready
------------------------- */
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'DB connection not initialized. Check Model/database.php',
    ]);
    exit;
}
if ($conn->connect_errno) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'DB connection failed.',
        'error'   => $conn->connect_error,
    ]);
    exit;
}

/* -------------------------
   JSON helpers (fallbacks)
------------------------- */
if (!function_exists('sendSuccess')) {
    function sendSuccess(array $data = [], string $message = 'OK', int $code = 200): void {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ]);
        exit;
    }
}
if (!function_exists('sendError')) {
    function sendError(string $message = 'Error', int $code = 400, array $extra = []): void {
        http_response_code($code);
        echo json_encode(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra));
        exit;
    }
}

/* =========================================================
   Routing
========================================================= */
$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {

    // Registration OTP
    case 'register_start':
        handleRegisterStart($conn);
        break;

    case 'register_verify':
        handleRegisterVerify($conn);
        break;

    // Login/Session
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

    // Forgot Password OTP
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
   REGISTER START (Send OTP Email, store pending)
   Requires table: pending_registrations
========================================================= */
function handleRegisterStart(mysqli $conn): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sendError('Method not allowed', 405);

    $studentId       = trim($_POST['student_id'] ?? '');
    $name            = trim($_POST['name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = (string)($_POST['password'] ?? '');
    $university      = trim($_POST['university'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

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

    if (!function_exists('studentIdExists') || !function_exists('emailExists')) {
        sendError('Missing helpers in Model/functions.php (studentIdExists/emailExists)', 500);
    }

    if (studentIdExists($conn, $studentId)) {
        sendError('Student ID already registered', 409);
    }

    if (emailExists($conn, $email)) {
        sendError('Email already registered', 409);
    }

    // Clear old pending requests
    $stmt = $conn->prepare("DELETE FROM pending_registrations WHERE email=?");
    if (!$stmt) sendError('DB error (prepare failed)', 500);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();

    $otp     = (string)random_int(100000, 999999);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);

    // Use existing hashPassword if present
    $passwordHash = function_exists('hashPassword')
        ? hashPassword($password)
        : password_hash($password, PASSWORD_DEFAULT);

    $expires = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 min

    $stmt = $conn->prepare("
        INSERT INTO pending_registrations
            (student_id, name, email, university, password_hash, otp_hash, otp_expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) sendError('DB error (prepare failed)', 500);
    $stmt->bind_param("sssssss", $studentId, $name, $email, $university, $passwordHash, $otpHash, $expires);

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to create OTP request', 500);
    }
    $stmt->close();

    $html = "
      <div style='font-family:Arial,sans-serif;line-height:1.6'>
        <h2 style='margin:0 0 10px'>TrackBack Email Verification</h2>
        <p>Your OTP code is:</p>
        <div style='font-size:28px;font-weight:700;letter-spacing:4px;margin:10px 0'>{$otp}</div>
        <p>This code expires in <b>10 minutes</b>.</p>
        <p>If you didn’t request this, ignore this email.</p>
      </div>
    ";

    if (!function_exists('sendMailSMTP')) {
        sendError('Missing mail helper (sendMailSMTP) in Model/mailer.php', 500);
    }

    $sent = sendMailSMTP($email, $name, 'TrackBack OTP Verification', $html);
    if (!$sent) {
        sendError('OTP could not be sent. Check SMTP settings.', 500);
    }

    sendSuccess(['email' => $email], 'OTP sent to your email. Please verify to finish registration.');
}

/* =========================================================
   REGISTER VERIFY (Verify OTP and create user)
========================================================= */
function handleRegisterVerify(mysqli $conn): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sendError('Method not allowed', 405);

    $email = trim($_POST['email'] ?? '');
    $otp   = trim($_POST['otp'] ?? '');

    if ($email === '' || $otp === '') {
        sendError('Email and OTP are required', 400);
    }

    if (!function_exists('emailExists') || !function_exists('studentIdExists')) {
        sendError('Missing helpers in Model/functions.php (studentIdExists/emailExists)', 500);
    }

    $stmt = $conn->prepare("SELECT * FROM pending_registrations WHERE email=? LIMIT 1");
    if (!$stmt) sendError('DB error (prepare failed)', 500);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        sendError('No pending registration found', 404);
    }

    if (strtotime((string)$row['otp_expires_at']) < time()) {
        $stmt = $conn->prepare("DELETE FROM pending_registrations WHERE email=?");
        if ($stmt) { $stmt->bind_param("s", $email); $stmt->execute(); $stmt->close(); }
        sendError('OTP expired. Please register again.', 400);
    }

    if (!password_verify($otp, (string)$row['otp_hash'])) {
        sendError('Invalid OTP', 401);
    }

    // Double check again
    if (emailExists($conn, $email)) {
        $stmt = $conn->prepare("DELETE FROM pending_registrations WHERE email=?");
        if ($stmt) { $stmt->bind_param("s", $email); $stmt->execute(); $stmt->close(); }
        sendError('Email already registered. Please login.', 409);
    }

    if (studentIdExists($conn, (string)$row['student_id'])) {
        $stmt = $conn->prepare("DELETE FROM pending_registrations WHERE email=?");
        if ($stmt) { $stmt->bind_param("s", $email); $stmt->execute(); $stmt->close(); }
        sendError('Student ID already registered. Please login.', 409);
    }

    // Insert user (pending approval)
    $studentId = (string)$row['student_id'];
    $name      = (string)$row['name'];
    $uni       = (string)$row['university'];
    $passHash  = (string)$row['password_hash'];
    $userType  = 'student';
    $status    = 'pending';

    $stmt = $conn->prepare("
        INSERT INTO users (student_id, name, email, password_hash, user_type, university, account_status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) sendError('DB error (prepare failed)', 500);
    $stmt->bind_param("sssssss", $studentId, $name, $email, $passHash, $userType, $uni, $status);

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Registration failed', 500);
    }
    $stmt->close();

    // Cleanup pending row
    $stmt = $conn->prepare("DELETE FROM pending_registrations WHERE email=?");
    if ($stmt) { $stmt->bind_param("s", $email); $stmt->execute(); $stmt->close(); }

    sendSuccess([], 'Email verified! Registration complete. Please wait for admin approval.');
}

/* =========================================================
   FORGOT START (Send OTP)
   Requires table: password_reset_tokens (token stores OTP HASH)
========================================================= */
function handleForgotStart(mysqli $conn): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sendError('Method not allowed', 405);

    $email = trim($_POST['email'] ?? '');
    if ($email === '') sendError('Email is required', 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) sendError('Invalid email format', 400);

    $stmt = $conn->prepare("SELECT user_id, name FROM users WHERE email=? LIMIT 1");
    if (!$stmt) sendError('DB error (prepare failed)', 500);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$user) sendError('No account found with this email', 404);

    $userId = (int)$user['user_id'];
    $name   = (string)($user['name'] ?? $email);

    // Delete old tokens
    $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id=?");
    if ($stmt) { $stmt->bind_param("i", $userId); $stmt->execute(); $stmt->close(); }

    $otp     = (string)random_int(100000, 999999);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
    $expires = date('Y-m-d H:i:s', time() + (10 * 60));

    $stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    if (!$stmt) sendError('DB error (prepare failed)', 500);
    $stmt->bind_param("iss", $userId, $otpHash, $expires);

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to create reset token', 500);
    }
    $stmt->close();

    $html = "
      <div style='font-family:Arial,sans-serif;line-height:1.6'>
        <h2 style='margin:0 0 10px'>TrackBack Password Reset</h2>
        <p>Your OTP code is:</p>
        <div style='font-size:28px;font-weight:700;letter-spacing:4px;margin:10px 0'>{$otp}</div>
        <p>This code expires in <b>10 minutes</b>.</p>
      </div>
    ";

    if (!function_exists('sendMailSMTP')) {
        sendError('Missing mail helper (sendMailSMTP) in Model/mailer.php', 500);
    }

    $sent = sendMailSMTP($email, $name, 'TrackBack Password Reset OTP', $html);
    if (!$sent) sendError('OTP could not be sent. Check SMTP settings.', 500);

    sendSuccess(['email' => $email], 'OTP sent to your email.');
}

/* =========================================================
   FORGOT VERIFY (Verify OTP + update password)
========================================================= */
function handleForgotVerify(mysqli $conn): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sendError('Method not allowed', 405);

    $email    = trim($_POST['email'] ?? '');
    $otp      = trim($_POST['otp'] ?? '');
    $newPass  = (string)($_POST['new_password'] ?? '');
    $confirm  = (string)($_POST['confirm_password'] ?? '');

    if ($email === '' || $otp === '' || $newPass === '' || $confirm === '') {
        sendError('All fields are required', 400);
    }
    if ($newPass !== $confirm) {
        sendError('Passwords do not match', 400);
    }
    if (strlen($newPass) < 6) {
        sendError('Password must be at least 6 characters long', 400);
    }

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email=? LIMIT 1");
    if (!$stmt) sendError('DB error (prepare failed)', 500);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $u = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$u) sendError('No account found with this email', 404);
    $userId = (int)$u['user_id'];

    $stmt = $conn->prepare("SELECT * FROM password_reset_tokens WHERE user_id=? ORDER BY token_id DESC LIMIT 1");
    if (!$stmt) sendError('DB error (prepare failed)', 500);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $tokenRow = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$tokenRow) sendError('No reset request found', 404);

    if (strtotime((string)$tokenRow['expires_at']) < time()) {
        $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id=?");
        if ($stmt) { $stmt->bind_param("i", $userId); $stmt->execute(); $stmt->close(); }
        sendError('OTP expired. Please request again.', 400);
    }

    if (!password_verify($otp, (string)$tokenRow['token'])) {
        sendError('Invalid OTP', 401);
    }

    $newHash = function_exists('hashPassword')
        ? hashPassword($newPass)
        : password_hash($newPass, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE user_id=?");
    if (!$stmt) sendError('DB error (prepare failed)', 500);
    $stmt->bind_param("si", $newHash, $userId);

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to update password', 500);
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id=?");
    if ($stmt) { $stmt->bind_param("i", $userId); $stmt->execute(); $stmt->close(); }

    sendSuccess([], 'Password updated successfully. You can login now.');
}

/* =========================================================
   LOGIN
========================================================= */
function handleLogin(mysqli $conn): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        sendError('Method not allowed', 405);
    }

    $email    = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        sendError('Email and password are required', 400);
    }

    if (!function_exists('authenticateUser')) {
        sendError('Missing authenticateUser() in Model/functions.php', 500);
    }

    $user = authenticateUser($conn, $email, $password);

    if (!$user) {
        sendError('Invalid credentials', 401);
    }

    if (($user['account_status'] ?? '') === 'banned') {
        sendError('Your account has been banned. Please contact the administrator for assistance.', 403);
    }

    if (($user['account_status'] ?? '') === 'pending') {
        sendError('Your account has not been approved by the administrator. Please contact the administrator for approval.', 403);
    }

    $_SESSION['user_id']     = $user['user_id'];
    $_SESSION['student_id']  = $user['student_id'];
    $_SESSION['name']        = $user['name'];
    $_SESSION['email']       = $user['email'];
    $_SESSION['user_type']   = $user['user_type'];
    $_SESSION['university']  = $user['university'];

    session_regenerate_id(true);

    // Ensure cookie flags are correct
    $cookieParams = session_get_cookie_params();
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    setcookie(
        session_name(),
        session_id(),
        [
            'expires'  => time() + (int)$cookieParams['lifetime'],
            'path'     => $cookieParams['path'] ?: '/',
            'domain'   => $cookieParams['domain'] ?? '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );

    if (function_exists('logAction')) {
        logAction($conn, (int)$user['user_id'], 'LOGIN', 'User logged in via API');
    }

    sendSuccess([
        'user_id'     => $user['user_id'],
        'name'        => $user['name'],
        'email'       => $user['email'],
        'user_type'   => $user['user_type'],
        'university'  => $user['university']
    ], 'Login successful');
}

/* =========================================================
   LOGOUT
========================================================= */
function handleLogout(): void {
    if (isset($_SESSION['user_id'])) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'] ?: '/', $params['domain'] ?? '');
        }

        if (isset($_COOKIE['user_email'])) {
            setcookie('user_email', '', time() - 42000, '/');
        }

        session_destroy();
        sendSuccess([], 'Logout successful');
    }

    sendError('No active session', 400);
}

/* =========================================================
   CHECK SESSION
========================================================= */
function handleCheckSession(): void {
    if (isset($_SESSION['user_id'])) {
        sendSuccess([
            'user_id'   => $_SESSION['user_id'],
            'name'      => $_SESSION['name'],
            'email'     => $_SESSION['email'],
            'user_type' => $_SESSION['user_type']
        ], 'Session active');
    }

    sendError('No active session', 401);
}

/* =========================================================
   VERIFY EMAIL (availability)
========================================================= */
function handleVerifyEmail(mysqli $conn): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        sendError('Method not allowed', 405);
    }

    $email = trim($_POST['email'] ?? '');
    if ($email === '') sendError('Email is required', 400);

    if (!function_exists('emailExists')) {
        sendError('Missing emailExists() in Model/functions.php', 500);
    }

    if (emailExists($conn, $email)) {
        sendSuccess([], 'Email is already registered');
    }

    sendSuccess([], 'Email is available');
}
