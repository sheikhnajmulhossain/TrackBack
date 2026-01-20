<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
 

set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
});


ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();


include '../Model/database.php';
include '../Model/functions.php';


setCorsHeaders();


validateSession('admin');


$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'get_students':
    case 'get_users':
        getUsers($conn);
        break;
    case 'get_posts':
        getPosts($conn);
        break;
    case 'get_database_stats':
        getDatabaseStatsHandler($conn);
        break;
    case 'get_dashboard_stats':
        getDashboardStats($conn);
        break;
    case 'approve_student':
    case 'approve_user':
        approveUser($conn);
        break;
    case 'ban_student':
    case 'ban_user':
        banUser($conn);
        break;
    case 'appoint_moderator':
        appointModerator($conn);
        break;
    case 'remove_moderator':
        removeModerator($conn);
        break;
    case 'delete_user':
        deleteUser($conn);
        break;
    case 'approve_post':
        approvePost($conn);
        break;
    case 'ban_post':
        banPost($conn);
        break;
    case 'get_post_details':
        getPostDetails($conn);
        break;
    case 'remove_warning':
        removeWarningAdmin($conn);
        break;
    case 'get_admin_profile':
        getAdminProfile($conn);
        break;
    case 'update_admin_profile':
        updateAdminProfile($conn);
        break;
    case 'create_user':
        createNewUser($conn);
        break;
    case 'remove_warning':
        removeWarningAdmin($conn);
        break;
    default:
        sendError('Invalid action', 400);
}


function getDashboardStats($conn) {
    $stats = getAdminDashboardStats($conn);
    sendSuccess($stats, 'Dashboard stats retrieved');
}


function getUsers($conn) {
    $filter = $_GET['filter'] ?? '';
    $users = getAllUsers($conn, $filter);

    if ($users === false) {
        $errorMsg = $conn->error;
        sendError('Database error: ' . $errorMsg, 500);
    }

    sendSuccess($users, 'Users retrieved');
}

function getPosts($conn) {
    $filter = $_GET['filter'] ?? '';

    $filters = [];
    if ($filter === 'approved' || $filter === 'banned' || $filter === 'pending') {
        $filters['status'] = $filter;
    } elseif ($filter === 'lost' || $filter === 'found') {
        $filters['type'] = $filter;
    } elseif ($filter === 'flagged') {
        $filters['flagged'] = true;
    }

    $posts = getAllPosts($conn, $filters);

    if ($posts === false) {
        sendError('Database error', 500);
    }

    sendSuccess($posts, 'Posts retrieved');
}

function createNewUser($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }

    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $status = $_POST['status'] ?? 'pending';

    if (empty($name) || empty($email) || empty($password)) {
        sendError('Name, email and password are required', 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format', 400);
    }

    if (emailExists($conn, $email)) {
        sendError('Email already exists', 409);
    }

    $studentId = 'USR' . time();
    $accountStatus = ($status === 'approved') ? 'active' : $status;

    $userData = [
        'student_id' => $studentId,
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'user_type' => $role,
        'account_status' => $accountStatus
    ];

    $result = createUser($conn, $studentId, $name, $email, $password, $role);

    if ($result && $result['success']) {
        $userId = $result['user_id'];

        if ($accountStatus !== 'pending') {
            updateUserStatus($conn, $userId, $accountStatus);
        }
        logAction($conn, $_SESSION['user_id'], 'CREATE_USER', ['new_user_id' => $userId]);
        sendSuccess(['user_id' => $userId], 'User created successfully');
    } else {
        sendError('Failed to create user', 500);
    }
}

function approveUser($conn) {
    try {
        $userId = (int)($_GET['id'] ?? $_POST['user_id'] ?? 0);

        if ($userId === 0) {
            sendError('User ID is required', 400);
        }

        $user = getUserById($conn, $userId);

        if (!$user) {
            sendError('User not found', 404);
        }

        if ($user['account_status'] === 'active') {
            sendSuccess([], 'User is already approved');
            return;
        }

        if (updateUserStatus($conn, $userId, 'active')) {
            logAction($conn, $_SESSION['user_id'], 'APPROVE_USER', ['target_user_id' => $userId]);
            sendSuccess([], 'User approved successfully');
        } else {
            sendError('Failed to approve user', 500);
        }
    } catch (Exception $e) {
        sendError('Error: ' . $e->getMessage(), 500);
    }
}

function banUser($conn) {
    try {
        $userId = (int)($_GET['id'] ?? $_POST['user_id'] ?? 0);
        $reason = $_POST['reason'] ?? 'Not specified';

        if ($userId === 0) {
            sendError('User ID is required', 400);
        }

        if (updateUserStatus($conn, $userId, 'banned')) {
            logAction($conn, $_SESSION['user_id'], 'BAN_USER', ['target_user_id' => $userId, 'reason' => $reason]);
            sendSuccess([], 'User banned successfully');
        } else {
            sendError('Failed to ban user', 500);
        }
    } catch (Exception $e) {
        sendError('Error: ' . $e->getMessage(), 500);
    }
}

function appointModerator($conn) {
    try {
        $userId = (int)($_GET['id'] ?? $_POST['user_id'] ?? 0);

        if ($userId === 0) {
            sendError('User ID is required', 400);
        }

        if (updateUserType($conn, $userId, 'moderator')) {
            logAction($conn, $_SESSION['user_id'], 'APPOINT_MODERATOR', ['user_id' => $userId]);
            sendSuccess([], 'User appointed as moderator');
        } else {
            sendError('Failed to appoint moderator', 500);
        }
    } catch (Exception $e) {
        sendError('Error: ' . $e->getMessage(), 500);
    }
}

function removeModerator($conn) {
    try {
        $userId = (int)($_GET['id'] ?? $_POST['user_id'] ?? 0);

        if ($userId === 0) {
            sendError('User ID is required', 400);
        }

        if (updateUserType($conn, $userId, 'student')) {
            logAction($conn, $_SESSION['user_id'], 'REMOVE_MODERATOR', ['user_id' => $userId]);
            sendSuccess([], 'Moderator role removed');
        } else {
            sendError('Failed to remove moderator', 500);
        }
    } catch (Exception $e) {
        sendError('Error: ' . $e->getMessage(), 500);
    }
}

function approvePost($conn) {
    $postId = (int)($_GET['id'] ?? $_POST['post_id'] ?? 0);

    if ($postId === 0) {
        sendError('Post ID is required', 400);
    }

    if (updatePostStatus($conn, $postId, 'approved')) {
        logAction($conn, $_SESSION['user_id'], 'APPROVE_POST', ['post_id' => $postId]);
        sendSuccess([], 'Post approved successfully');
    } else {
        sendError('Failed to approve post', 500);
    }
}

function banPost($conn) {
    $postId = (int)($_GET['id'] ?? $_POST['post_id'] ?? 0);
    $reason = $_POST['reason'] ?? 'Not specified';

    if ($postId === 0) {
        sendError('Post ID is required', 400);
    }

    if (updatePostStatus($conn, $postId, 'banned')) {
        logAction($conn, $_SESSION['user_id'], 'BAN_POST', ['post_id' => $postId, 'reason' => $reason]);
        sendSuccess([], 'Post banned successfully');
    } else {
        sendError('Failed to ban post', 500);
    }
}

function getPostDetails($conn) {
    $postId = (int)($_GET['id'] ?? 0);

    if ($postId === 0) {
        sendError('Post ID is required', 400);
    }

    $post = getPostById($conn, $postId);
    
    if ($post) {
        sendSuccess($post, 'Post retrieved successfully');
    } else {
        sendError('Post not found', 404);
    }
}


function getDatabaseStatsHandler($conn) {
    $stats = getDatabaseStats($conn);
    $stats['db_name'] = 'trackback_db';
    $stats['db_status'] = 'Connected';

    sendSuccess($stats, 'Database stats retrieved');
}


function getAdminProfile($conn) {
    $userId = (int)$_SESSION['user_id'];
    $profile = getUserProfile($conn, $userId);

    if ($profile) {
        sendSuccess($profile, 'Admin profile retrieved');
    } else {
        sendError('Profile not found', 404);
    }
}

function updateAdminProfile($conn) {
    $userId = (int)$_SESSION['user_id'];
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email)) {
        sendError('Name and email are required', 400);
    }

    if ($password && strlen($password) < 8) {
        sendError('Password must be at least 8 characters long', 400);
    }

    $data = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone
    ];

    if ($password) {
        $data['password'] = $password;
    }

    if (updateUserProfile($conn, $userId, $data)) {
        logAction($conn, $userId, 'UPDATE_PROFILE', []);
        sendSuccess([], 'Profile updated successfully');
    } else {
        sendError('Failed to update profile', 500);
    }
}


function removeWarningAdmin($conn) {
    $warningId = (int)($_POST['warning_id'] ?? 0);

    if ($warningId === 0) {
        sendError('Warning ID is required', 400);
        return;
    }

    $query = "UPDATE warnings SET removed_at = NOW() WHERE warning_id = ? AND removed_at IS NULL";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $warningId);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            logAction($conn, $_SESSION['user_id'], 'REMOVE_WARNING', ['warning_id' => $warningId, 'timestamp' => date('Y-m-d H:i:s')]);
            sendSuccess([], 'Warning removed successfully');
        } else {
            sendError('Warning not found or already removed', 404);
        }
    } else {
        sendError('Failed to remove warning', 500);
    }
}

function deleteUser($conn) {
    $userId = (int)($_POST['id'] ?? 0);
    
    if ($userId === 0) {
        sendError('User ID is required', 400);
        return;
    }
    
    if ($userId === (int)$_SESSION['user_id']) {
        sendError('You cannot delete your own account', 403);
        return;
    }
    
    $deletePostsQuery = "DELETE FROM posts WHERE user_id = ?";
    $stmt = $conn->prepare($deletePostsQuery);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    
    $deleteWarningsQuery = "DELETE FROM warnings WHERE user_id = ?";
    $stmt = $conn->prepare($deleteWarningsQuery);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    
    $deleteMessagesQuery = "DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?";
    $stmt = $conn->prepare($deleteMessagesQuery);
    $stmt->bind_param('ii', $userId, $userId);
    $stmt->execute();
    
    $deleteUserQuery = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($deleteUserQuery);
    $stmt->bind_param('i', $userId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            logAction($conn, $_SESSION['user_id'], 'DELETE_USER', ['deleted_user_id' => $userId, 'timestamp' => date('Y-m-d H:i:s')]);
            sendSuccess([], 'User deleted successfully');
        } else {
            sendError('User not found', 404);
        }
    } else {
        sendError('Failed to delete user', 500);
    }
}


?>
