<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
error_log('user_api.php: Script started');
session_start();
error_log('user_api.php: Session started');
include '../Model/database.php';
error_log('user_api.php: Database included');
include '../Model/functions.php';
error_log('user_api.php: Functions included');
setCorsHeaders();
error_log('user_api.php: CORS set');
$action = $_GET['action'] ?? $_POST['action'] ?? null;
error_log('user_api.php: Action set to ' . ($action ?? 'null'));
$publicActions = ['check_status'];
if (!in_array($action, $publicActions)) {
    validateSession();
}


function clearSessionAndCookies() {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    if (isset($_COOKIE['user_email'])) {
        setcookie('user_email', '', time() - 42000, '/');
    }

    session_destroy();
}
switch ($action) {
    case 'check_status':
        checkUserStatus($conn);
        break;
    case 'get_profile':
        getProfile($conn);
        break;
    case 'update_profile':
        updateProfile($conn);
        break;
    case 'change_password':
        changePassword($conn);
        break;
    case 'get_messages':
        getMessages($conn);
        break;
    case 'get_message':
        getMessage($conn);
        break;
    case 'send_message':
        sendMessage($conn);
        break;
    case 'mark_read':
        markMessageRead($conn);
        break;
    case 'delete_message':
        deleteMessageHandler($conn);
        break;
    case 'get_unread_count':
        getUnreadCount($conn);
        break;
    case 'get_dashboard_stats':
        getDashboardStats($conn);
        break;
    case 'delete_account':
        deleteAccountHandler($conn);
        break;
    default:
        sendError('Invalid action', 400);
}

function checkUserStatus($conn) {
    if (!isset($_SESSION['user_id'])) {
        sendError('Not logged in. Please login to continue.', 401);
    }
    $userId = (int)$_SESSION['user_id'];
    $user = getUserById($conn, $userId);
    if (!$user) {
        clearSessionAndCookies();
        sendError('User not found. Please login again.', 401);
    }
    if ($user['account_status'] === 'banned') {
        clearSessionAndCookies();
        sendError('Your account has been banned. Please contact the administrator for assistance.', 403);
    }
    if ($user['account_status'] === 'pending') {
        clearSessionAndCookies();
        sendError('Your account has not been approved by the administrator. Please contact the administrator for approval.', 403);
    }
    sendSuccess([
        'user_id' => $user['user_id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'user_type' => $user['user_type'],
        'account_status' => $user['account_status']
    ], 'Account is active');
}

function getProfile($conn) {
    $userId = (int)$_SESSION['user_id'];
    $user = getUserById($conn, $userId);
    if (!$user) {
        sendError('User not found', 404);
    }
    sendSuccess($user, 'Profile retrieved');
}

function updateProfile($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    $userId = (int)$_SESSION['user_id'];
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $university = $_POST['university'] ?? '';
    $email = $_POST['email'] ?? '';
    if (empty($name)) {
        sendError('Name is required', 400);
    }
    $profilePic = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
        $profilePic = uploadImage($_FILES['profile_picture'], 'profiles');
        if (!$profilePic) {
            sendError('Failed to upload profile picture', 400);
        }
    }
    $data = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'university' => $university
    ];
    if ($profilePic) {
        $data['profile_picture'] = $profilePic;
    }
    if (updateUserProfile($conn, $userId, $data)) {
        $_SESSION['name'] = $name;
        $_SESSION['university'] = $university;
        logAction($conn, $userId, 'UPDATE_PROFILE', []);
        sendSuccess([], 'Profile updated successfully');
    } else {
        sendError('Failed to update profile', 500);
    }
}

function changePassword($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    $userId = (int)$_SESSION['user_id'];
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        sendError('All password fields are required', 400);
    }
    if ($newPassword !== $confirmPassword) {
        sendError('New passwords do not match', 400);
    }
    if (strlen($newPassword) < 8) {
        sendError('Password must be at least 8 characters', 400);
    }
    $user = getUserById($conn, $userId);
    if (!$user || !verifyPassword($currentPassword, $user['password_hash'])) {
        sendError('Current password is incorrect', 400);
    }
    if (changeUserPassword($conn, $userId, $newPassword)) {
        logAction($conn, $userId, 'CHANGE_PASSWORD', []);
        sendSuccess([], 'Password changed successfully');
    } else {
        sendError('Failed to change password', 500);
    }
}

function getMessages($conn) {
    $userId = (int)$_SESSION['user_id'];
    $messages = getUserMessages($conn, $userId);
    if ($messages === false) {
        sendError('Database error', 500);
    }
    sendSuccess($messages, 'Messages retrieved');
}

function getMessage($conn) {
    $userId = (int)$_SESSION['user_id'];
    $messageId = (int)($_GET['message_id'] ?? 0);
    if ($messageId === 0) {
        sendError('Message ID is required', 400);
    }
    $message = getMessageById($conn, $messageId, $userId);
    if (!$message) {
        sendError('Message not found', 404);
    }
    if ($message['receiver_id'] == $userId && !$message['is_read']) {
        markMessageAsRead($conn, $messageId, $userId);
    }
    sendSuccess($message, 'Message retrieved');
}

function sendMessage($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    $senderId = (int)$_SESSION['user_id'];
    $recipientEmail = $_POST['recipient_email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $content = $_POST['message_content'] ?? '';
    if (empty($recipientEmail) || empty($subject) || empty($content)) {
        sendError('Recipient, subject and message are required', 400);
    }
    $recipient = getUserByEmail($conn, $recipientEmail);
    if (!$recipient) {
        sendError('Recipient not found', 404);
    }
    $receiverId = (int)$recipient['user_id'];
    if ($receiverId === $senderId) {
        sendError('Cannot send message to yourself', 400);
    }
    $messageId = createMessage($conn, $senderId, $receiverId, $subject, $content);
    if ($messageId) {
        logAction($conn, $senderId, 'SEND_MESSAGE', ['receiver_id' => $receiverId]);
        sendSuccess(['message_id' => $messageId], 'Message sent successfully');
    } else {
        sendError('Failed to send message', 500);
    }
}

function markMessageRead($conn) {
    $userId = (int)$_SESSION['user_id'];
    $messageId = (int)($_POST['message_id'] ?? 0);
    if ($messageId === 0) {
        sendError('Message ID is required', 400);
    }
    if (markMessageAsRead($conn, $messageId, $userId)) {
        sendSuccess([], 'Message marked as read');
    } else {
        sendError('Failed to update message', 500);
    }
}

function deleteMessageHandler($conn) {
    $userId = (int)$_SESSION['user_id'];
    $messageId = (int)($_POST['message_id'] ?? 0);
    if ($messageId === 0) {
        sendError('Message ID is required', 400);
    }
    if (deleteMessage($conn, $messageId, $userId)) {
        sendSuccess([], 'Message deleted');
    } else {
        sendError('Failed to delete message', 500);
    }
}

function getUnreadCount($conn) {
    $userId = (int)$_SESSION['user_id'];
    $count = getUnreadMessageCount($conn, $userId);
    sendSuccess(['count' => $count], 'Unread count retrieved');
}

function getDashboardStats($conn) {
    $userId = (int)$_SESSION['user_id'];
    $stats = getUserDashboardStats($conn, $userId);
    $stats['unread_messages'] = getUnreadMessageCount($conn, $userId);
    $stats['warnings'] = getWarningCount($conn, $userId);
    sendSuccess($stats, 'Dashboard stats retrieved');
}
?>
