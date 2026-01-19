<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');


ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();


include '../Model/database.php';
include '../Model/functions.php';


setCorsHeaders();


validateSession(['moderator', 'admin']);


$action = $_GET['action'] ?? $_POST['action'] ?? null;


switch ($action) {
    case 'get_dashboard_stats':
        getDashboardStats($conn);
        break;
    case 'get_warnings':
        getWarnings($conn);
        break;
    case 'get_flagged_posts':
    case 'get_flags':
        getFlaggedPostsHandler($conn);
        break;
    case 'get_disputes':
        getDisputesHandler($conn);
        break;
    case 'get_students':
    case 'get_users':
        getUsers($conn);
        break;
    case 'issue_warning':
        issueWarningHandler($conn);
        break;
    case 'remove_warning':
        removeWarningHandler($conn);
        break;
    case 'flag_post':
        flagPostHandler($conn);
        break;
    case 'resolve_dispute':
        resolveDisputeHandler($conn);
        break;
    case 'get_moderator_profile':
        getModeratorProfile($conn);
        break;
    case 'update_moderator_profile':
        updateModeratorProfile($conn);
        break;
    case 'get_all_posts':
        getAllPostsHandler($conn);
        break;
    case 'unflag_post':
        unflagPostHandler($conn);
        break;
    case 'get_flag_details':
        getFlagDetailsHandler($conn);
        break;
    case 'remove_post':
        removePostHandler($conn);
        break;
    default:
        sendError('Invalid action', 400);
}


function getDashboardStats($conn) {
    $moderatorId = (int)$_SESSION['user_id'];
    $stats = getModeratorDashboardStats($conn, $moderatorId);
    sendSuccess($stats, 'Dashboard stats retrieved');
}


function getWarnings($conn) {
    $warnings = getAllWarnings($conn);
    
    if ($warnings === false) {
        sendError('Database error', 500);
    }
    
    sendSuccess($warnings, 'Warnings retrieved');
}


function getFlaggedPostsHandler($conn) {
    $status = $_GET['status'] ?? '';
    
    $flags = getFlaggedPosts($conn, $status ?: null);
    
    if ($flags === false) {
        sendError('Database error', 500);
        return;
    }
    
    sendSuccess($flags, 'Flagged posts retrieved');
}


function issueWarningHandler($conn) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $reason = $_POST['reason'] ?? '';
    
    if ($userId === 0 || empty($reason)) {
        sendError('User ID and reason are required', 400);
    }
    
    $moderatorId = (int)$_SESSION['user_id'];
    
    if (issueWarning($conn, $userId, $moderatorId, $reason)) {
        logAction($conn, $moderatorId, 'ISSUE_WARNING', ['user_id' => $userId]);
        sendSuccess([], 'Warning issued successfully');
    } else {
        sendError('Failed to issue warning', 500);
    }
}

function removeWarningHandler($conn) {
    $warningId = (int)($_POST['warning_id'] ?? 0);

    if ($warningId === 0) {
        sendError('Warning ID is required', 400);
        return;
    }

    $moderatorId = (int)$_SESSION['user_id'];

    if (deleteWarning($conn, $warningId, $moderatorId)) {
        logAction($conn, $moderatorId, 'REMOVE_WARNING', ['warning_id' => $warningId]);
        sendSuccess([], 'Warning removed');
    } else {
        sendError('Failed to remove warning', 500);
    }
}


function flagPostHandler($conn) {
    $postId = (int)($_POST['post_id'] ?? 0);
    $flagReason = $_POST['flag_reason'] ?? '';
    
    if ($postId === 0 || empty($flagReason)) {
        sendError('Post ID and flag reason are required', 400);
        return;
    }
    
    $moderatorId = (int)$_SESSION['user_id'];
    
    if (flagPost($conn, $postId, $moderatorId, $flagReason)) {
        logAction($conn, $moderatorId, 'FLAG_POST', ['post_id' => $postId]);
        sendSuccess([], 'Post flagged successfully');
    } else {
        sendError('Failed to flag post', 500);
    }
}


function getDisputesHandler($conn) {
    $status = $_GET['status'] ?? '';
    
    $disputes = getDisputes($conn, $status ?: null);
    
    if ($disputes === false) {
        sendError('Database error', 500);
    }
    
    sendSuccess($disputes, 'Disputes retrieved');
}

function resolveDisputeHandler($conn) {
    $disputeId = (int)($_POST['dispute_id'] ?? 0);
    $resolution = $_POST['resolution'] ?? '';
    
    if ($disputeId === 0 || empty($resolution)) {
        sendError('Dispute ID and resolution are required', 400);
    }
    
    $moderatorId = (int)$_SESSION['user_id'];
    
    if (resolveDispute($conn, $disputeId, $moderatorId, $resolution)) {
        logAction($conn, $moderatorId, 'RESOLVE_DISPUTE', ['dispute_id' => $disputeId]);
        sendSuccess([], 'Dispute resolved successfully');
    } else {
        sendError('Failed to resolve dispute', 500);
    }
}


function getUsers($conn) {
    $users = getActiveStudents($conn);
    
    if ($users === false) {
        sendError('Database error', 500);
    }
    
    sendSuccess($users, 'Users retrieved');
}


function getModeratorProfile($conn) {
    $userId = (int)$_SESSION['user_id'];
    $profile = getUserProfile($conn, $userId);
    
    if ($profile) {
        sendSuccess($profile, 'Moderator profile retrieved');
    } else {
        sendError('Profile not found', 404);
    }
}

function updateModeratorProfile($conn) {
    $userId = (int)$_SESSION['user_id'];
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!$name || !$email) {
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


function getAllPostsHandler($conn) {
    $filters = [];
    
    // Apply filters if provided
    if (isset($_GET['status']) && $_GET['status'] !== 'all') {
        $filters['status'] = $_GET['status'];
    }
    if (isset($_GET['type']) && $_GET['type'] !== 'all') {
        $filters['type'] = $_GET['type'];
    }
    if (isset($_GET['flagged']) && $_GET['flagged'] === '1') {
        $filters['flagged'] = 1;
    }
    
    $posts = getAllPosts($conn, $filters);
    
    if ($posts === false) {
        sendError('Database error', 500);
        return;
    }
    
    sendSuccess($posts, 'Posts retrieved successfully');
}


function unflagPostHandler($conn) {
    $moderatorId = (int)$_SESSION['user_id'];
    $postId = (int)($_POST['post_id'] ?? 0);
    if ($postId === 0) {
        sendError('Post ID is required', 400);
        return;
    }
 
    $query = "DELETE FROM post_flags WHERE post_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $postId);

    if ($stmt->execute()) {

        $conn->query("UPDATE posts SET flagged = 0, post_status = 'approved' WHERE post_id = $postId");
        logAction($conn, $moderatorId, 'UNFLAG_POST', ['post_id' => $postId]);
        sendSuccess([], 'Post unflagged and approved successfully');
    } else {
        sendError('Failed to unflag post', 500);
    }
}


function getFlagDetailsHandler($conn) {
    $flagId = (int)($_GET['flag_id'] ?? 0);
    if ($flagId === 0) {
        sendError('Flag ID is required', 400);
        return;
    }

    $query = "SELECT * FROM post_flags WHERE flag_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $flagId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $flag = $result->fetch_assoc();
        sendSuccess($flag, 'Flag details retrieved');
    } else {
        sendError('Flag not found', 404);
    }
}


function removePostHandler($conn) {
    $moderatorId = (int)$_SESSION['user_id'];
    $postId = (int)($_POST['post_id'] ?? 0);
    if ($postId === 0) {
        sendError('Post ID is required', 400);
        return;
    }
    if (updatePostStatus($conn, $postId, 'banned')) {
        $conn->query("UPDATE posts SET flagged = 0 WHERE post_id = $postId");
        logAction($conn, $moderatorId, 'BAN_POST', ['post_id' => $postId]);
        sendSuccess([], 'Post removed successfully');
    } else {
        sendError('Failed to remove post', 500);
    }
}
