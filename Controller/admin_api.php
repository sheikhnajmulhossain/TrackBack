<?php
session_start();

// Include database connection and functions
include '../Model/database.php';
include '../Model/functions.php';

// Check if admin is logged in
validateSession('admin');

// Get action parameter
$action = $_GET['action'] ?? $_POST['action'] ?? null;

// Handle requests
switch ($action) {
    case 'get_users':
        getUsers($conn);
        break;
    case 'get_posts':
        getPosts($conn);
        break;
    case 'get_database_stats':
        getDatabaseStats($conn);
        break;
    case 'approve_user':
        approveUser($conn);
        break;
    case 'ban_user':
        banUser($conn);
        break;
    case 'appoint_moderator':
        appointModerator($conn);
        break;
    case 'remove_moderator':
        removeModerator($conn);
        break;
    case 'approve_post':
        approvePost($conn);
        break;
    case 'ban_post':
        banPost($conn);
        break;
    case 'get_admin_profile':
        getAdminProfile($conn);
        break;
    case 'update_admin_profile':
        updateAdminProfile($conn);
        break;
    case 'get_all_users':
        getAllUsers($conn);
        break;
    default:
        sendError('Invalid action', 400);
}

// ============ USERS ============
function getUsers($conn) {
    $filter = $_GET['filter'] ?? '';
    
    $query = "SELECT user_id, student_id, name, email, user_type, account_status, university, created_at FROM users";
    
    if ($filter === 'approved') {
        $query .= " WHERE account_status = 'active'";
    } elseif ($filter === 'banned') {
        $query .= " WHERE account_status = 'banned'";
    } elseif ($filter === 'moderator') {
        $query .= " WHERE user_type = 'moderator'";
    } elseif ($filter === 'pending') {
        $query .= " WHERE account_status = 'pending'";
    } elseif ($filter === 'student') {
        $query .= " WHERE user_type = 'student'";
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        sendError('Database error: ' . $conn->error, 500);
    }
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    sendSuccess($users, 'Users retrieved successfully');
}

function getAllUsers($conn) {
    $query = "SELECT user_id, student_id, name, email, user_type, account_status, university, phone, created_at 
              FROM users ORDER BY created_at DESC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        sendError('Database error: ' . $conn->error, 500);
    }
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    sendSuccess($users, 'All users retrieved');
}

function approveUser($conn) {
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if ($userId === 0) {
        sendError('User ID is required', 400);
    }
    
    $query = "UPDATE users SET account_status = 'active' WHERE user_id = $userId";
    
    if ($conn->query($query)) {
        logAction($conn, $_SESSION['user_id'], 'APPROVE_USER', ['target_user_id' => $userId]);
        sendSuccess([], 'User approved successfully');
    } else {
        sendError('Failed to approve user: ' . $conn->error, 500);
    }
}

function banUser($conn) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $reason = $_POST['reason'] ?? 'Not specified';
    
    if ($userId === 0) {
        sendError('User ID is required', 400);
    }
    
    $reason = sanitizeInput($reason);
    $query = "UPDATE users SET account_status = 'banned' WHERE user_id = $userId";
    
    if ($conn->query($query)) {
        logAction($conn, $_SESSION['user_id'], 'BAN_USER', ['target_user_id' => $userId, 'reason' => $reason]);
        sendSuccess([], 'User banned successfully');
    } else {
        sendError('Failed to ban user: ' . $conn->error, 500);
    }
}

function appointModerator($conn) {
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if ($userId === 0) {
        sendError('User ID is required', 400);
    }
    
    $query = "UPDATE users SET user_type = 'moderator' WHERE user_id = $userId AND user_type = 'student'";
    
    if ($conn->query($query)) {
        logAction($conn, $_SESSION['user_id'], 'APPOINT_MODERATOR', ['user_id' => $userId]);
        sendSuccess([], 'User appointed as moderator');
    } else {
        sendError('Failed to appoint moderator: ' . $conn->error, 500);
    }
}

function removeModerator($conn) {
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if ($userId === 0) {
        sendError('User ID is required', 400);
    }
    
    $query = "UPDATE users SET user_type = 'student' WHERE user_id = $userId AND user_type = 'moderator'";
    
    if ($conn->query($query)) {
        logAction($conn, $_SESSION['user_id'], 'REMOVE_MODERATOR', ['user_id' => $userId]);
        sendSuccess([], 'Moderator role removed');
    } else {
        sendError('Failed to remove moderator: ' . $conn->error, 500);
    }
}

// ============ POSTS ============
function getPosts($conn) {
    $filter = $_GET['filter'] ?? '';
    
    $query = "
        SELECT p.post_id, p.user_id, p.post_type, p.item_name, p.item_description, p.category, 
               p.location, p.date_time, p.image_url, p.post_status, p.flagged, p.created_at,
               u.name as user_name, u.email as user_email
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
    ";
    
    if ($filter === 'approved') {
        $query .= " WHERE p.post_status = 'approved'";
    } elseif ($filter === 'banned') {
        $query .= " WHERE p.post_status = 'banned'";
    } elseif ($filter === 'pending') {
        $query .= " WHERE p.post_status = 'pending'";
    } elseif ($filter === 'lost') {
        $query .= " WHERE p.post_type = 'lost'";
    } elseif ($filter === 'found') {
        $query .= " WHERE p.post_type = 'found'";
    } elseif ($filter === 'flagged') {
        $query .= " WHERE p.flagged = 1";
    }
    
    $query .= " ORDER BY p.created_at DESC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        sendError('Database error: ' . $conn->error, 500);
    }
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    
    sendSuccess($posts, 'Posts retrieved successfully');
}

function approvePost($conn) {
    $postId = (int)($_POST['post_id'] ?? 0);
    
    if ($postId === 0) {
        sendError('Post ID is required', 400);
    }
    
    $query = "UPDATE posts SET post_status = 'approved' WHERE post_id = $postId";
    
    if ($conn->query($query)) {
        logAction($conn, $_SESSION['user_id'], 'APPROVE_POST', ['post_id' => $postId]);
        sendSuccess([], 'Post approved successfully');
    } else {
        sendError('Failed to approve post: ' . $conn->error, 500);
    }
}

function banPost($conn) {
    $postId = (int)($_POST['post_id'] ?? 0);
    $reason = $_POST['reason'] ?? 'Not specified';
    
    if ($postId === 0) {
        sendError('Post ID is required', 400);
    }
    
    $reason = sanitizeInput($reason);
    $query = "UPDATE posts SET post_status = 'banned' WHERE post_id = $postId";
    
    if ($conn->query($query)) {
        logAction($conn, $_SESSION['user_id'], 'BAN_POST', ['post_id' => $postId, 'reason' => $reason]);
        sendSuccess([], 'Post banned successfully');
    } else {
        sendError('Failed to ban post: ' . $conn->error, 500);
    }
}

// ============ DATABASE STATS ============
function getDatabaseStats($conn) {
    $totalUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'student'")->fetch_assoc()['count'];
    $approvedUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE account_status = 'active' AND user_type = 'student'")->fetch_assoc()['count'];
    $bannedUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE account_status = 'banned'")->fetch_assoc()['count'];
    $pendingUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE account_status = 'pending'")->fetch_assoc()['count'];
    
    $pendingPosts = $conn->query("SELECT COUNT(*) as count FROM posts WHERE post_status = 'pending'")->fetch_assoc()['count'];
    $approvedPosts = $conn->query("SELECT COUNT(*) as count FROM posts WHERE post_status = 'approved'")->fetch_assoc()['count'];
    $bannedPosts = $conn->query("SELECT COUNT(*) as count FROM posts WHERE post_status = 'banned'")->fetch_assoc()['count'];
    
    $moderators = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'moderator'")->fetch_assoc()['count'];
    
    sendSuccess([
        'total_users' => $totalUsers,
        'approved_users' => $approvedUsers,
        'pending_users' => $pendingUsers,
        'banned_users' => $bannedUsers,
        'total_posts' => ($approvedPosts + $pendingPosts),
        'approved_posts' => $approvedPosts,
        'pending_posts' => $pendingPosts,
        'banned_posts' => $bannedPosts,
        'moderators' => $moderators
    ], 'Database statistics retrieved');
}

// ============ ADMIN PROFILE ============
function getAdminProfile($conn) {
    $userId = (int)$_SESSION['user_id'];
    
    $query = "SELECT user_id, student_id, name, email, university, phone, created_at 
              FROM users WHERE user_id = $userId AND user_type = 'admin'";
    
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        sendSuccess($row, 'Admin profile retrieved');
    } else {
        sendError('Profile not found', 404);
    }
}

function updateAdminProfile($conn) {
    $userId = (int)$_SESSION['user_id'];
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!$name || !$email) {
        sendError('Name and email are required', 400);
    }
    
    $query = "UPDATE users SET name = '$name', email = '$email', phone = '$phone'";
    
    if ($password) {
        if (strlen($password) < 8) {
            sendError('Password must be at least 8 characters long', 400);
        }
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $query .= ", password_hash = '$hashedPassword'";
    }
    
    $query .= " WHERE user_id = $userId AND user_type = 'admin'";
    
    if ($conn->query($query)) {
        logAction($conn, $userId, 'UPDATE_PROFILE', []);
        sendSuccess([], 'Profile updated successfully');
    } else {
        sendError('Failed to update profile: ' . $conn->error, 500);
    }
}
