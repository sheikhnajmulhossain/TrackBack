<?php
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

include '../Model/database.php';
include '../Model/functions.php';

$error = '';
$success = '';
$showOtp = false;

// values for re-populating form
$fullname = '';
$student_id = '';
$email = '';
$university = '';

if (!isset($_SESSION['pending_register'])) {
    $_SESSION['pending_register'] = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $step = $_POST['step'] ?? 'send_otp';

    // ==================================================
    // STEP 1: Validate form and SEND OTP
    // ==================================================
    if ($step === 'send_otp') {

        $fullname = $_POST['fullname'] ?? '';
        $student_id = $_POST['student_id'] ?? '';
        $email = $_POST['email'] ?? '';
        $university = $_POST['university'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm'] ?? '';

        if (empty($fullname) || empty($student_id) || empty($email) || empty($password) || empty($confirm_password) || empty($university)) {
            $error = 'All fields are required';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } else if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long';
        } else if ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } else if (emailExists($conn, $email)) {
            $error = 'Email already registered. Please login instead.';
        } else if (studentIdExists($conn, $student_id)) {
            $error = 'Student ID already registered. Please login instead.';
        } else {
            // store pending info in session (store hash, not plain password)
            $_SESSION['pending_register'] = [
                'fullname' => $fullname,
                'student_id' => $student_id,
                'email' => $email,
                'university' => $university,
                'password_hash' => hashPassword($password)
            ];

            [$sent, $msg] = sendOtpEmail($conn, $email, $fullname, 'register');
            if ($sent) {
                $success = 'OTP sent to your email. Please enter it below.';
                $showOtp = true;
            } else {
                $error = $msg;
            }
        }
    }

    // ==================================================
    // STEP 2: Verify OTP and COMPLETE registration
    // ==================================================
    if ($step === 'verify_otp') {

        $otp = $_POST['otp'] ?? '';
        $pending = $_SESSION['pending_register'];

        if (!$pending) {
            $error = 'Session expired. Please try again.';
        } else if (empty($otp)) {
            $error = 'Please enter the OTP.';
            $showOtp = true;
            $fullname = $pending['fullname'];
            $student_id = $pending['student_id'];
            $email = $pending['email'];
            $university = $pending['university'];
        } else {
            $ok = otpVerifyAndConsume($conn, $pending['email'], 'register', $otp);

            if (!$ok) {
                $error = 'Invalid or expired OTP. Please try again.';
                $showOtp = true;

                $fullname = $pending['fullname'];
                $student_id = $pending['student_id'];
                $email = $pending['email'];
                $university = $pending['university'];
            } else {
                // Use your existing registerUser function but it expects plain password.
                // So we insert directly using the stored hash.
                $studentId = sanitizeInput($pending['student_id']);
                $fullName  = sanitizeInput($pending['fullname']);
                $mail      = sanitizeInput($pending['email']);
                $uni       = sanitizeInput($pending['university']);
                $passHash  = $conn->real_escape_string($pending['password_hash']);

                $q = "INSERT INTO users (student_id, name, email, password_hash, user_type, university, account_status)
                      VALUES ('$studentId', '$fullName', '$mail', '$passHash', 'student', '$uni', 'pending')";

                if ($conn->query($q)) {
                    $_SESSION['pending_register'] = null;
                    $success = 'Registration successful! Your account is pending admin approval.';
                    $showOtp = false;

                    $fullname = $student_id = $email = $university = '';
                } else {
                    $error = 'Registration failed. Please try again.';
                    $showOtp = true;

                    $fullname = $pending['fullname'];
                    $student_id = $pending['student_id'];
                    $email = $pending['email'];
                    $university = $pending['university'];
                }
            }
        }
    }
}

// If user refreshed after OTP sent, keep OTP box visible
if ($_SESSION['pending_register'] && empty($success) && empty($error)) {
    $showOtp = true;
    $fullname = $_SESSION['pending_register']['fullname'];
    $student_id = $_SESSION['pending_register']['student_id'];
    $email = $_SESSION['pending_register']['email'];
    $university = $_SESSION['pending_register']['university'];
}
?>
