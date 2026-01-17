<?php
session_start();

// Include database connection and functions
include '../Model/database.php';
include '../Model/functions.php';

// Check if moderator is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'moderator') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get action parameter
$action = $_GET['action'] ?? $_POST['action'] ?? null;

// Handle requests
switch ($action) {
    case 'get_dashboard_stats':
        getDashboardStats($conn);
        break;
    case 'get_flagged_posts':
        getFlaggedPosts($conn);
        break;
    case 'get_disputes':
        getDisputes($conn);
        break;
    case 'get_users':
        getUsers($conn);
        break;
    case 'issue_warning':
        issueWarning($conn);
        break;
    case 'remove_warning':
        removeWarning($conn);
        break;
    case 'flag_post':
        flagPost($conn);
        break;
    case 'resolve_dispute':
        resolveDispute($conn);
        break;
    case 'get_moderator_profile':
        getModeratorProfile($conn);
        break;
    case 'update_moderator_profile':
        updateModeratorProfile($conn);
        break;
    default:
        sendError('Invalid action', 400);
}

// ============ DASHBOARD STATS ============
function getDashboardStats($conn) {
    $moderatorId = (int)$_SESSION['user_id'];
    
    $totalWarnings = $conn->query("SELECT COUNT(*) as count FROM warnings WHERE moderator_id = $moderatorId")->fetch_assoc()['count'];
    $totalFlagged = $conn->query("SELECT COUNT(*) as count FROM post_flags WHERE moderator_id = $moderatorId")->fetch_assoc()['count'];
    $totalDisputes = $conn->query("SELECT COUNT(*) as count FROM disputes WHERE moderator_id = $moderatorId AND dispute_status = 'resolved'")->fetch_assoc()['count'];
    
    $pendingFlagged = $conn->query("SELECT COUNT(*) as count FROM posts WHERE post_status = 'pending' AND flagged = 1")->fetch_assoc()['count'];
    $openDisputes = $conn->query("SELECT COUNT(*) as count FROM disputes WHERE dispute_status = 'open'")->fetch_assoc()['count'];
    
    sendSuccess([
        'total_warnings' => $totalWarnings,
        'total_flagged' => $totalFlagged,
        'total_disputes_resolved' => $totalDisputes,
        'pending_flagged_posts' => $pendingFlagged,
        'open_disputes' => $openDisputes
    ], 'Dashboard stats retrieved');
}

// ============ FLAGGED POSTS ============
function getFlaggedPosts($conn) {
    $status = $_GET['status'] ?? '';
    
    $query = "
        SELECT 
            pf.flag_id,
            pf.post_id,
            pf.moderator_id,
            pf.flag_reason,
            pf.flagged_at,
            p.item_name,
            p.post_type,
            p.post_status,
            u.name as posted_by,
            u.email
        FROM post_flags pf
        JOIN posts p ON pf.post_id = p.post_id
        JOIN users u ON p.user_id = u.user_id
    ";
    
    if ($status) {
        $status = sanitizeInput($status);
        $query .= " WHERE p.post_status = '$status'";
    }
    
    $query .= " ORDER BY pf.flagged_at DESC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        sendError('Database error: ' . $conn->error, 500);
    }
    
    $flags = [];
    while ($row = $result->fetch_assoc()) {
        $flags[] = $row;
    }
    
    sendSuccess($flags, 'Flagged posts retrieved');
}

// ============ WARNINGS ============
function issueWarning($conn) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $reason = $_POST['reason'] ?? '';
    
    if ($userId === 0 || empty($reason)) {
        sendError('User ID and reason are required', 400);
    }
    
    $reason = sanitizeInput($reason);
    $moderatorId = (int)$_SESSION['user_id'];
    
    $query = "INSERT INTO warnings (user_id, moderator_id, warning_reason) 
              VALUES ($userId, $moderatorId, '$reason')";
    
    if ($conn->query($query)) {
        logAction($conn, $moderatorId, 'ISSUE_WARNING', ['user_id' => $userId]);
        sendSuccess([], 'Warning issued successfully');
    } else {
        sendError('Failed to issue warning: ' . $conn->error, 500);
    }
}

function removeWarning($conn) {
    $warningId = (int)($_POST['warning_id'] ?? 0);
    
    if ($warningId === 0) {
        sendError('Warning ID is required', 400);
    }
    
    $moderatorId = (int)$_SESSION['user_id'];
    $query = "DELETE FROM warnings WHERE warning_id = $warningId AND moderator_id = $moderatorId";
    
    if ($conn->query($query)) {
        logAction($conn, $moderatorId, 'REMOVE_WARNING', ['warning_id' => $warningId]);
        sendSuccess([], 'Warning removed');
    } else {
        sendError('Failed to remove warning: ' . $conn->error, 500);
    }
}

// ============ FLAG POST ============
function flagPost($conn) {
    $postId = (int)($_POST['post_id'] ?? 0);
    $flagReason = $_POST['flag_reason'] ?? '';
    
    if ($postId === 0 || empty($flagReason)) {
        sendError('Post ID and flag reason are required', 400);
    }
    
    $flagReason = sanitizeInput($flagReason);
    $moderatorId = (int)$_SESSION['user_id'];
    
    $query = "INSERT INTO post_flags (post_id, moderator_id, flag_reason) 
              VALUES ($postId, $moderatorId, '$flagReason')";
    
    if ($conn->query($query)) {
        // Update post to mark as flagged
        $conn->query("UPDATE posts SET flagged = 1 WHERE post_id = $postId");
        logAction($conn, $moderatorId, 'FLAG_POST', ['post_id' => $postId]);
        sendSuccess([], 'Post flagged successfully');
    } else {
        sendError('Failed to flag post: ' . $conn->error, 500);
    }
}

// ============ DISPUTES ============
function getDisputes($conn) {
    $status = $_GET['status'] ?? '';
    
    $query = "
        SELECT 
            d.dispute_id,
            d.post_id,
            d.claimant1_id,
            d.claimant2_id,
            d.moderator_id,
            d.dispute_description,
            d.dispute_status,
            d.resolution,
            d.created_at,
            d.resolved_at,
            u1.name as claimant1_name,
            u1.email as claimant1_email,
            u2.name as claimant2_name,
            u2.email as claimant2_email,
            p.item_name
        FROM disputes d
        JOIN users u1 ON d.claimant1_id = u1.user_id
        JOIN users u2 ON d.claimant2_id = u2.user_id
        JOIN posts p ON d.post_id = p.post_id
    ";
    
    if ($status) {
        $status = sanitizeInput($status);
        $query .= " WHERE d.dispute_status = '$status'";
    }
    
    $query .= " ORDER BY d.created_at DESC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        sendError('Database error: ' . $conn->error, 500);
    }
    
    $disputes = [];
    while ($row = $result->fetch_assoc()) {
        $disputes[] = $row;
    }
    
    sendSuccess($disputes, 'Disputes retrieved');
}

function resolveDispute($conn) {
    $disputeId = (int)($_POST['dispute_id'] ?? 0);
    $resolution = $_POST['resolution'] ?? '';
    
    if ($disputeId === 0 || empty($resolution)) {
        sendError('Dispute ID and resolution are required', 400);
    }
    
    $resolution = sanitizeInput($resolution);
    $moderatorId = (int)$_SESSION['user_id'];
    
    $query = "UPDATE disputes SET dispute_status = 'resolved', resolution = '$resolution', 
              moderator_id = $moderatorId, resolved_at = NOW() 
              WHERE dispute_id = $disputeId";
    
    if ($conn->query($query)) {
        logAction($conn, $moderatorId, 'RESOLVE_DISPUTE', ['dispute_id' => $disputeId]);
        sendSuccess([], 'Dispute resolved successfully');
    } else {
        sendError('Failed to resolve dispute: ' . $conn->error, 500);
    }
}

// ============ USERS ============
function getUsers($conn) {
    $query = "SELECT user_id, student_id, name, email, account_status FROM users 
              WHERE account_status = 'active' AND user_type = 'student' 
              ORDER BY name ASC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        sendError('Database error: ' . $conn->error, 500);
    }
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    sendSuccess($users, 'Users retrieved');
}

// ============ MODERATOR PROFILE ============
function getModeratorProfile($conn) {
    $userId = (int)$_SESSION['user_id'];
    
    $query = "SELECT user_id, student_id, name, email, university, phone, created_at 
              FROM users WHERE user_id = $userId AND user_type = 'moderator'";
    
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        sendSuccess($row, 'Moderator profile retrieved');
    } else {
        sendError('Profile not found', 404);
    }
}

function updateModeratorProfile($conn) {
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
    
    $query .= " WHERE user_id = $userId AND user_type = 'moderator'";
    
    if ($conn->query($query)) {
        logAction($conn, $userId, 'UPDATE_PROFILE', []);
        sendSuccess([], 'Profile updated successfully');
    } else {
        sendError('Failed to update profile: ' . $conn->error, 500);
    }
}
