<?php

function deleteUserAccount($conn, $userId) {
    $userId = (int)$userId;
    $query = "DELETE FROM users WHERE user_id = $userId";
    return $conn->query($query);
}

// =============================
// OTP (Email) Helpers
// =============================
require_once __DIR__ . '/mailer.php';

function otpUpsert($conn, $email, $purpose, $otp, $minutes = 10) {
    $email = sanitizeInput($email);
    $purpose = sanitizeInput($purpose);

    $otpHash = password_hash($otp, PASSWORD_BCRYPT);
    $expiresAt = date('Y-m-d H:i:s', time() + ($minutes * 60));

    $query = "
        INSERT INTO email_otps (email, purpose, otp_hash, expires_at)
        VALUES ('$email', '$purpose', '$otpHash', '$expiresAt')
        ON DUPLICATE KEY UPDATE otp_hash = VALUES(otp_hash), expires_at = VALUES(expires_at)
    ";

    return $conn->query($query);
}

function otpVerifyAndConsume($conn, $email, $purpose, $otp) {
    $email = sanitizeInput($email);
    $purpose = sanitizeInput($purpose);

    $query = "SELECT * FROM email_otps WHERE email='$email' AND purpose='$purpose' LIMIT 1";
    $res = $conn->query($query);

    if (!$res || $res->num_rows === 0) return false;

    $row = $res->fetch_assoc();
    if (strtotime($row['expires_at']) < time()) {
        // expired -> remove
        $conn->query("DELETE FROM email_otps WHERE id=".(int)$row['id']);
        return false;
    }

    $ok = password_verify($otp, $row['otp_hash']);
    if ($ok) {
        $conn->query("DELETE FROM email_otps WHERE id=".(int)$row['id']);
        return true;
    }
    return false;
}

function sendOtpEmail($conn, $email, $name, $purpose) {
    $otp = (string) random_int(100000, 999999);

    if (!otpUpsert($conn, $email, $purpose, $otp, 10)) {
        return [false, 'Failed to generate OTP'];
    }

    $title = ($purpose === 'reset') ? 'Reset your TrackBack password' : 'Verify your TrackBack account';
    $html = "
      <div style='font-family:Arial,sans-serif;line-height:1.6'>
        <h2>$title</h2>
        <p>Hello ".htmlspecialchars($name).",</p>
        <p>Your OTP code is:</p>
        <div style='font-size:28px;font-weight:700;letter-spacing:4px;padding:10px 14px;background:#f3f5f7;display:inline-block;border-radius:8px'>
          $otp
        </div>
        <p style='margin-top:14px'>This OTP will expire in <b>10 minutes</b>.</p>
        <p>If you didn’t request this, you can ignore this email.</p>
        <p>— TrackBack Team</p>
      </div>
    ";

    $sent = sendMailSMTP($email, $name, $title, $html);
    return [$sent, $sent ? 'OTP sent' : 'Failed to send OTP (SMTP)'];
}

function updateUserPasswordByEmail($conn, $email, $newPassword) {
    $email = sanitizeInput($email);
    $hash = hashPassword($newPassword);
    $q = "UPDATE users SET password_hash='$hash', updated_at=NOW() WHERE email='$email' LIMIT 1";
    return $conn->query($q);
}


function deleteAccountHandler($conn) {
    $userId = (int)$_SESSION['user_id'];
    if (!$userId) {
        sendError('User not authenticated', 401);
    }
    if (deleteUserAccount($conn, $userId)) {
        session_destroy();
        sendSuccess([], 'Account deleted successfully');
    } else {
        sendError('Failed to delete account', 500);
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Direct access not allowed']);
    exit;
}


function setCorsHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json');
}

function validateSession($requiredRole = null) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    if ($requiredRole) {
        if (is_array($requiredRole)) {
            if (!in_array($_SESSION['user_type'], $requiredRole)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden']);
                exit;
            }
        } else if ($_SESSION['user_type'] !== $requiredRole) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }
    }
}

function sanitizeInput($data) {
    global $conn;
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return $conn->real_escape_string(strip_tags(trim($data)));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}


function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}


function getUserById($conn, $userId) {
    $userId = (int)$userId;
    $query = "SELECT * FROM users WHERE user_id = $userId";
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc() : null;
}


function getUserByEmail($conn, $email) {
    $email = $conn->real_escape_string($email);
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc() : null;
}


function createUser($conn, $studentId, $name, $email, $password, $userType = 'student', $university = null) {
    $studentId = sanitizeInput($studentId);
    $name = sanitizeInput($name);
    $email = sanitizeInput($email);
    $userType = sanitizeInput($userType);
    $university = $university ? sanitizeInput($university) : null;
    $passwordHash = hashPassword($password);
    
    $query = "INSERT INTO users (student_id, name, email, password_hash, user_type, university, account_status) 
              VALUES ('$studentId', '$name', '$email', '$passwordHash', '$userType', " . ($university ? "'$university'" : "NULL") . ", 'pending')";
    
    if ($conn->query($query)) {
        return ['success' => true, 'user_id' => $conn->insert_id];
    }
    return ['success' => false, 'error' => $conn->error];
}


function logAction($conn, $userId, $action, $details = null) {
    $userId = (int)$userId;
    $action = $conn->real_escape_string($action);
    
  
    if ($details !== null) {
        if (is_array($details)) {
            $details = $conn->real_escape_string(json_encode($details));
        } else {
            $details = $conn->real_escape_string($details);
        }
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $tableCheck = $conn->query("SHOW TABLES LIKE 'audit_logs'");
    if ($tableCheck->num_rows == 0) {
        $conn->query("CREATE TABLE audit_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action_type VARCHAR(100) NOT NULL,
            action_details TEXT,
            ip_address VARCHAR(45),
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    $query = "INSERT INTO audit_logs (user_id, action_type, action_details, ip_address) 
              VALUES ($userId, '$action', " . ($details ? "'$details'" : "NULL") . ", '$ip')";
    
    return $conn->query($query);
}

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}




function sendSuccess($data = [], $message = 'Success') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

function emailExists($conn, $email) {
    return getUserByEmail($conn, $email) !== null;
}

function studentIdExists($conn, $studentId) {
    $studentId = $conn->real_escape_string($studentId);
    $query = "SELECT user_id FROM users WHERE student_id = '$studentId'";
    $result = $conn->query($query);
    return $result && $result->num_rows > 0;
}

function uploadImage($file, $folder = 'uploads') {
    $uploadDir = "../View/img/$folder/";
    

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return "img/$folder/$filename";
    }
    
    return false;
}


function getUnreadMessageCount($conn, $userId) {
    $userId = (int)$userId;
    $query = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = $userId AND is_read = 0";
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc()['count'] : 0;
}


function getWarningCount($conn, $userId) {
    $userId = (int)$userId;
    $query = "SELECT COUNT(*) as count FROM warnings WHERE user_id = $userId";
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc()['count'] : 0;
}

function getAllUsers($conn, $filter = '') {
    $query = "SELECT u.user_id, u.student_id, u.name, u.email, u.user_type as role, u.account_status as status, u.university, u.created_at, COUNT(CASE WHEN w.removed_at IS NULL THEN w.warning_id END) as warning_count FROM users u LEFT JOIN warnings w ON u.user_id = w.user_id WHERE u.user_type != 'admin'";

    if ($filter === 'approved') {
        $query .= " AND account_status = 'active'";
    } elseif ($filter === 'banned') {
        $query .= " AND account_status = 'banned'";
    } elseif ($filter === 'moderator') {
        $query .= " AND user_type = 'moderator'";
    } elseif ($filter === 'pending') {
        $query .= " AND account_status = 'pending'";
    } elseif ($filter === 'student') {
        $query .= " AND user_type = 'student'";
    }

    $query .= " GROUP BY u.user_id, u.student_id, u.name, u.email, u.user_type, u.account_status, u.university, u.created_at ORDER BY u.created_at DESC";

    $result = $conn->query($query);

    if (!$result) {
        return false;
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    return $users;
}

function updateUserStatus($conn, $userId, $status) {
    $userId = (int)$userId;
    $status = sanitizeInput($status);

    $query = "UPDATE users SET account_status = '$status' WHERE user_id = $userId";

    if ($conn->query($query)) {
        return $conn->affected_rows > 0;
    }
    return false;
}

function updateUserType($conn, $userId, $userType) {
    $userId = (int)$userId;
    $userType = sanitizeInput($userType);

    $query = "UPDATE users SET user_type = '$userType' WHERE user_id = $userId";

    if ($conn->query($query)) {
        return $conn->affected_rows > 0;
    }
    return false;
}

function updateUserProfile($conn, $userId, $data) {
    $userId = (int)$userId;
    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $university = trim($data['university'] ?? '');
    $email = isset($data['email']) ? trim($data['email']) : null;
    $profilePicture = isset($data['profile_picture']) ? trim($data['profile_picture']) : null;

    $fields = ["name = ?", "phone = ?", "university = ?"];
    $params = [$name, $phone, $university];
    $types = "sss";
    if ($email !== null && $email !== "") {
        $fields[] = "email = ?";
        $params[] = $email;
        $types .= "s";
    }
    if ($profilePicture !== null) {
        $fields[] = "profile_picture = ?";
        $params[] = $profilePicture;
        $types .= "s";
    }
    $fieldsStr = implode(", ", $fields);
    $query = "UPDATE users SET $fieldsStr WHERE user_id = ?";
    $params[] = $userId;
    $types .= "i";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log('Prepare failed: ' . $conn->error);
        return false;
    }
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        return true;
    } else {
        error_log('Execute failed: ' . $stmt->error);
    }
    return false;
}

function getUserProfile($conn, $userId) {
    $userId = (int)$userId;
    $query = "SELECT user_id, student_id, name, email, university, phone, created_at
              FROM users WHERE user_id = $userId";

    $result = $conn->query($query);
    return $result ? $result->fetch_assoc() : null;
}

function changeUserPassword($conn, $userId, $newPassword) {
    $userId = (int)$userId;
    $newHash = hashPassword($newPassword);

    $query = "UPDATE users SET password_hash = '$newHash' WHERE user_id = $userId";

    return $conn->query($query);
}

function authenticateUser($conn, $email, $password) {
    $user = getUserByEmail($conn, $email);

    if (!$user) {
        return false;
    }

    if (!verifyPassword($password, $user['password_hash'])) {
        return false;
    }

    return $user;
}

function getAllPosts($conn, $filters = []) {
    $query = "SELECT p.post_id, p.user_id, p.post_type, p.item_name, p.item_description, p.category,
                     p.location, p.date_time, p.image_url, p.post_status, p.flagged, p.created_at,
                     u.name as user_name, u.email as user_email
              FROM posts p
              LEFT JOIN users u ON p.user_id = u.user_id";

    $where = [];
    if (isset($filters['status'])) {
        $status = sanitizeInput($filters['status']);
        $where[] = "p.post_status = '$status'";
    }
    if (isset($filters['user_id'])) {
        $userId = (int)$filters['user_id'];
        $where[] = "p.user_id = $userId";
    }
    if (isset($filters['type'])) {
        $type = sanitizeInput($filters['type']);
        $where[] = "p.post_type = '$type'";
    }
    if (isset($filters['flagged']) && $filters['flagged']) {
        $where[] = "p.flagged = 1";
    }

    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }

    $query .= " ORDER BY p.created_at DESC";

    $result = $conn->query($query);

    if (!$result) {
        return false;
    }

    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }

    return $posts;
}

function getPostById($conn, $postId) {
    $postId = (int)$postId;
    $query = "SELECT p.*, u.name as user_name, u.email, u.phone, u.university
              FROM posts p
              LEFT JOIN users u ON p.user_id = u.user_id
              WHERE p.post_id = $postId";

    $result = $conn->query($query);
    return $result ? $result->fetch_assoc() : null;
}

function createPost($conn, $userId, $data) {
    $userId = (int)$userId;
    $postType = sanitizeInput($data['post_type']);
    $itemName = sanitizeInput($data['item_name']);
    $itemDescription = sanitizeInput($data['item_description']);
    $category = sanitizeInput($data['category'] ?? '');
    $location = sanitizeInput($data['location']);
    $dateTime = sanitizeInput($data['date_time']);
    $imageUrl = isset($data['image_url']) ? sanitizeInput($data['image_url']) : null;
    $postStatus = sanitizeInput($data['post_status'] ?? 'pending');

    $query = "INSERT INTO posts (user_id, post_type, item_name, item_description, category, location, date_time, image_url, post_status)
              VALUES ($userId, '$postType', '$itemName', '$itemDescription', '$category', '$location', '$dateTime', " .
              ($imageUrl ? "'$imageUrl'" : "NULL") . ", '$postStatus')";

    if ($conn->query($query)) {
        return $conn->insert_id;
    }
    return false;
}


function updatePost($conn, $postId, $userId, $data) {
    $postId = (int)$postId;
    $userId = (int)$userId;
    $itemName = sanitizeInput($data['item_name']);
    $itemDescription = sanitizeInput($data['item_description']);
    $category = sanitizeInput($data['category'] ?? '');
    $location = sanitizeInput($data['location']);

    $query = "UPDATE posts SET item_name = '$itemName', item_description = '$itemDescription',
              category = '$category', location = '$location' WHERE post_id = $postId AND user_id = $userId";

    return $conn->query($query);
}


function deletePost($conn, $postId, $userId) {
    $postId = (int)$postId;
    $userId = (int)$userId;

    $query = "DELETE FROM posts WHERE post_id = $postId AND user_id = $userId";

    return $conn->query($query);
}


function updatePostStatus($conn, $postId, $status) {
    $postId = (int)$postId;
    $status = sanitizeInput($status);
    
    $query = "UPDATE posts SET post_status = '$status' WHERE post_id = $postId";
    
    if ($conn->query($query)) {
        return true;
    }
    
    return false;
}

function getUserMessages($conn, $userId) {
    $userId = (int)$userId;
    $query = "SELECT m.message_id, m.sender_id, m.receiver_id, m.subject, m.message_content,
                     m.is_read, m.sent_at, u.name as sender_name, u.email as sender_email
              FROM messages m
              LEFT JOIN users u ON m.sender_id = u.user_id
              WHERE m.receiver_id = $userId
              ORDER BY m.sent_at DESC";

    $result = $conn->query($query);

    if (!$result) {
        return false;
    }

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    return $messages;
}


function getMessageById($conn, $messageId, $userId) {
    $messageId = (int)$messageId;
    $userId = (int)$userId;

    $query = "SELECT m.*, u.name as sender_name, u.email as sender_email
              FROM messages m
              LEFT JOIN users u ON m.sender_id = u.user_id
              WHERE m.message_id = $messageId AND (m.sender_id = $userId OR m.receiver_id = $userId)";

    $result = $conn->query($query);
    return $result ? $result->fetch_assoc() : null;
}


function createMessage($conn, $senderId, $receiverId, $subject, $content) {
    $senderId = (int)$senderId;
    $receiverId = (int)$receiverId;
    $subject = sanitizeInput($subject);
    $content = sanitizeInput($content);

    $query = "INSERT INTO messages (sender_id, receiver_id, subject, message_content)
              VALUES ($senderId, $receiverId, '$subject', '$content')";

    if ($conn->query($query)) {
        return $conn->insert_id;
    }
    return false;
}


function markMessageAsRead($conn, $messageId, $userId) {
    $messageId = (int)$messageId;
    $userId = (int)$userId;

    $query = "UPDATE messages SET is_read = 1 WHERE message_id = $messageId AND receiver_id = $userId";

    return $conn->query($query);
}

function deleteMessage($conn, $messageId, $userId) {
    $messageId = (int)$messageId;
    $userId = (int)$userId;

    $query = "DELETE FROM messages WHERE message_id = $messageId AND (receiver_id = $userId OR sender_id = $userId)";

    return $conn->query($query);
}

function getAdminDashboardStats($conn) {
    $totalStudents = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'student'")->fetch_assoc()['count'];
    $approvedStudents = $conn->query("SELECT COUNT(*) as count FROM users WHERE account_status = 'active' AND user_type = 'student'")->fetch_assoc()['count'];
    $bannedStudents = $conn->query("SELECT COUNT(*) as count FROM users WHERE account_status = 'banned'")->fetch_assoc()['count'];
    $pendingPosts = $conn->query("SELECT COUNT(*) as count FROM posts WHERE post_status = 'pending'")->fetch_assoc()['count'];

    return [
        'total_students' => $totalStudents,
        'approved_students' => $approvedStudents,
        'banned_students' => $bannedStudents,
        'pending_posts' => $pendingPosts
    ];
}


function getDatabaseStats($conn) {
    $studentsCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'student'")->fetch_assoc()['count'];
    $postsCount = $conn->query("SELECT COUNT(*) as count FROM posts")->fetch_assoc()['count'];
    $messagesCount = $conn->query("SELECT COUNT(*) as count FROM messages")->fetch_assoc()['count'];
    $moderatorsCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'moderator'")->fetch_assoc()['count'];

    return [
        'students' => $studentsCount,
        'posts' => $postsCount,
        'messages' => $messagesCount,
        'moderators' => $moderatorsCount
    ];
}


function getUserDashboardStats($conn, $userId) {
    $userId = (int)$userId;

    $myPosts = $conn->query("SELECT COUNT(*) as count FROM posts WHERE user_id = $userId")->fetch_assoc()['count'];
    $approvedPosts = $conn->query("SELECT COUNT(*) as count FROM posts WHERE user_id = $userId AND post_status = 'approved'")->fetch_assoc()['count'];
    $pendingPosts = $conn->query("SELECT COUNT(*) as count FROM posts WHERE user_id = $userId AND post_status = 'pending'")->fetch_assoc()['count'];

    return [
        'my_posts' => $myPosts,
        'approved_posts' => $approvedPosts,
        'pending_posts' => $pendingPosts
    ];
}

function getModeratorDashboardStats($conn, $moderatorId) {
    $moderatorId = (int)$moderatorId;

    $totalWarnings = $conn->query("SELECT COUNT(*) as count FROM warnings WHERE moderator_id = $moderatorId")->fetch_assoc()['count'];
    $totalFlagged = $conn->query("SELECT COUNT(*) as count FROM post_flags WHERE moderator_id = $moderatorId")->fetch_assoc()['count'];
    $totalDisputes = $conn->query("SELECT COUNT(*) as count FROM disputes WHERE moderator_id = $moderatorId AND dispute_status = 'resolved'")->fetch_assoc()['count'];
    $totalPosts = $conn->query("SELECT COUNT(*) as count FROM posts")->fetch_assoc()['count'];

    $pendingFlagged = $conn->query("SELECT COUNT(*) as count FROM posts WHERE post_status = 'pending' AND flagged = 1")->fetch_assoc()['count'];
    $openDisputes = $conn->query("SELECT COUNT(*) as count FROM disputes WHERE dispute_status = 'open'")->fetch_assoc()['count'];

    return [
        'total_warnings' => $totalWarnings,
        'total_flagged' => $totalFlagged,
        'total_disputes' => $totalDisputes,
        'total_posts' => $totalPosts,
        'pending_flagged' => $pendingFlagged,
        'open_disputes' => $openDisputes
    ];
}


function getModeratorWarnings($conn, $moderatorId) {
    $moderatorId = (int)$moderatorId;

    $query = "SELECT w.warning_id as id, w.user_id, w.warning_reason as reason, w.warning_date as last_warning,
                     u.student_id, u.name as student_name, u.email,
                     (SELECT COUNT(*) FROM warnings WHERE user_id = w.user_id) as warning_count
              FROM warnings w
              LEFT JOIN users u ON w.user_id = u.user_id
              WHERE w.moderator_id = $moderatorId
              ORDER BY w.warning_date DESC";

    $result = $conn->query($query);

    if (!$result) {
        return false;
    }

    $warnings = [];
    while ($row = $result->fetch_assoc()) {
        $warnings[] = $row;
    }

    return $warnings;
}


function issueWarning($conn, $userId, $moderatorId, $reason, $sendMessage = true) {
    $userId = (int)$userId;
    $moderatorId = (int)$moderatorId;
    $reason = sanitizeInput($reason);

    $query = "INSERT INTO warnings (user_id, moderator_id, warning_reason)
              VALUES ($userId, $moderatorId, '$reason')";

    if ($conn->query($query)) {
        if ($sendMessage) {
            $warningCount = getWarningCount($conn, $userId);
            $subject = "Warning Issued - Action Required";
            $message = "Dear User,\n\nYou have received a warning from the moderation team.\n\nReason: $reason\n\nThis is warning #$warningCount on your account. Please ensure you follow the community guidelines to avoid further action.\n\nIf you believe this warning was issued in error, please contact the moderation team.\n\nThank you,\nTrackBack Team";
            createMessage($conn, $moderatorId, $userId, $subject, $message);
        }
        return $conn->insert_id;
    }
    return false;
}


function deleteWarning($conn, $warningId, $moderatorId) {
    $warningId = (int)$warningId;
    $moderatorId = (int)$moderatorId;

    $query = "DELETE FROM warnings WHERE warning_id = $warningId AND moderator_id = $moderatorId";

    return $conn->query($query);
}


function flagPost($conn, $postId, $moderatorId, $reason, $sendMessage = true) {
    $postId = (int)$postId;
    $moderatorId = (int)$moderatorId;
    $reason = sanitizeInput($reason);

    $query = "INSERT INTO post_flags (post_id, moderator_id, flag_reason)
              VALUES ($postId, $moderatorId, '$reason')";

    if ($conn->query($query)) {

        $conn->query("UPDATE posts SET flagged = 1 WHERE post_id = $postId");
        

        if ($sendMessage) {
            $post = getPostById($conn, $postId);
            if ($post && isset($post['user_id'])) {
                $subject = "Your post has been flagged";
                $message = "Dear User,\n\nYour post \"" . ($post['item_name'] ?? 'Unknown') . "\" has been flagged by a moderator.\n\nReason: $reason\n\nPlease review your post and make necessary corrections. If you believe this was a mistake, you can contact the moderator team.\n\nThank you,\nTrackBack Team";
                createMessage($conn, $moderatorId, $post['user_id'], $subject, $message);
            }
        }
        return true;
    }
    return false;
}


function getAllWarnings($conn) {
    $query = "SELECT w.warning_id as id, w.user_id, w.warning_reason as reason, w.warning_date as last_warning,
                     u.student_id, u.name as student_name, u.email,
                     (SELECT COUNT(*) FROM warnings WHERE user_id = w.user_id) as warning_count
              FROM warnings w
              LEFT JOIN users u ON w.user_id = u.user_id
              ORDER BY w.warning_date DESC";

    $result = $conn->query($query);

    if (!$result) {
        return false;
    }

    $warnings = [];
    while ($row = $result->fetch_assoc()) {
        $warnings[] = $row;
    }

    return $warnings;
}

function getFlaggedPosts($conn, $status = null) {
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
        LEFT JOIN posts p ON pf.post_id = p.post_id
        LEFT JOIN users u ON p.user_id = u.user_id
    ";
    
    if ($status) {
        $status = sanitizeInput($status);
        $query .= " AND p.post_status = '$status'";
    }
    
    $query .= " ORDER BY pf.flagged_at DESC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        return false;
    }
    
    $flags = [];
    while ($row = $result->fetch_assoc()) {
        $flags[] = $row;
    }
    
    return $flags;
}


function getDisputes($conn, $status = null) {
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
        LEFT JOIN users u1 ON d.claimant1_id = u1.user_id
        LEFT JOIN users u2 ON d.claimant2_id = u2.user_id
        LEFT JOIN posts p ON d.post_id = p.post_id
    ";

    if ($status) {
        $status = sanitizeInput($status);
        $query .= " WHERE d.dispute_status = '$status'";
    }

    $query .= " ORDER BY d.created_at DESC";

    $result = $conn->query($query);

    if (!$result) {
        return false;
    }

    $disputes = [];
    while ($row = $result->fetch_assoc()) {
        $disputes[] = $row;
    }

    return $disputes;
}


function resolveDispute($conn, $disputeId, $moderatorId, $resolution) {
    $disputeId = (int)$disputeId;
    $moderatorId = (int)$moderatorId;
    $resolution = sanitizeInput($resolution);

    $query = "UPDATE disputes SET dispute_status = 'resolved', resolution = '$resolution', 
              moderator_id = $moderatorId, resolved_at = NOW() 
              WHERE dispute_id = $disputeId";

    return $conn->query($query);
}


function getActiveStudents($conn) {
    $query = "SELECT user_id, student_id, name, email, account_status FROM users 
              WHERE account_status = 'active' AND user_type = 'student' 
              ORDER BY name ASC";

    $result = $conn->query($query);

    if (!$result) {
        return false;
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    return $users;
}


function getApprovedPosts($conn, $filters = []) {
    $query = "SELECT p.post_id, p.user_id, p.post_type, p.item_name, p.item_description, p.category, 
                     p.location, p.date_time, p.image_url, p.post_status, p.flagged, p.created_at,
                     u.name as user_name, u.email, u.university
              FROM posts p
              LEFT JOIN users u ON p.user_id = u.user_id
              WHERE p.post_status = 'approved'";


    if (!empty($filters['university'])) {
        $university = sanitizeInput($filters['university']);
        $query .= " AND u.university = '$university'";
    }

    if (isset($filters['type']) && in_array($filters['type'], ['lost', 'found'])) {
        $type = sanitizeInput($filters['type']);
        $query .= " AND p.post_type = '$type'";
    }

    if (!empty($filters['category'])) {
        $category = sanitizeInput($filters['category']);
        $query .= " AND p.category = '$category'";
    }

    if (!empty($filters['location'])) {
        $location = sanitizeInput($filters['location']);
        $query .= " AND p.location LIKE '%$location%'";
    }

    $query .= " ORDER BY p.created_at DESC";

    if (isset($filters['limit'])) {
        $limit = (int)$filters['limit'];
        $offset = (int)($filters['offset'] ?? 0);
        $query .= " LIMIT $offset, $limit";
    }

    $result = $conn->query($query);

    if (!$result) {
        return false;
    }

    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }

    return $posts;
}


function getApprovedPostById($conn, $postId) {
    $postId = (int)$postId;
    $query = "SELECT p.*, u.name as user_name, u.email, u.phone, u.university
              FROM posts p
              LEFT JOIN users u ON p.user_id = u.user_id
              WHERE p.post_id = $postId AND p.post_status = 'approved'";

    $result = $conn->query($query);
    return $result ? $result->fetch_assoc() : null;
}


function getUserPosts($conn, $userId) {
    $userId = (int)$userId;
    $query = "SELECT post_id, post_type, item_name, item_description, category, location, 
                     date_time, image_url, post_status, flagged, created_at, updated_at
              FROM posts
              WHERE user_id = $userId
              ORDER BY created_at DESC";

    $result = $conn->query($query);

    if (!$result) {
        return false;
    }

    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }

    return $posts;
}


function userOwnsPost($conn, $postId, $userId) {
    $postId = (int)$postId;
    $userId = (int)$userId;

    $query = "SELECT user_id FROM posts WHERE post_id = $postId";
    $result = $conn->query($query);

    if (!$result || $result->num_rows === 0) {
        return null; // Post not found
    }

    $post = $result->fetch_assoc();
    return $post['user_id'] == $userId;
}

function searchPosts($conn, $keyword, $university = null) {
    $keyword = sanitizeInput($keyword);

    $query = "SELECT p.post_id, p.user_id, p.post_type, p.item_name, p.item_description, p.category,
                     p.location, p.date_time, p.image_url, p.post_status, p.created_at,
                     u.name as user_name, u.university
              FROM posts p
              LEFT JOIN users u ON p.user_id = u.user_id
              WHERE p.post_status = 'approved' AND
                    (p.item_name LIKE '%$keyword%' OR 
                     p.item_description LIKE '%$keyword%' OR
                     p.location LIKE '%$keyword%')";
    
    if (!empty($university)) {
        $university = sanitizeInput($university);
        $query .= " AND u.university = '$university'";
    }
    
    $query .= " ORDER BY p.created_at DESC LIMIT 20";

    $result = $conn->query($query);

    if (!$result) {
        return false;
    }

    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }

    return $posts;
}

function insertPost($conn, $userId, $postType, $itemName, $itemDescription, $category, $location, $dateTime, $imageUrl = null) {
    $userId = (int)$userId;
    $postType = sanitizeInput($postType);
    $itemName = sanitizeInput($itemName);
    $itemDescription = sanitizeInput($itemDescription);
    $category = sanitizeInput($category);
    $location = sanitizeInput($location);
    $dateTime = sanitizeInput($dateTime);
    $imageUrl = $imageUrl ? $conn->real_escape_string($imageUrl) : null;

    $query = "INSERT INTO posts (user_id, post_type, item_name, item_description, category, location, date_time, image_url, post_status)
              VALUES ($userId, '$postType', '$itemName', '$itemDescription', '$category', '$location', '$dateTime', " . 
              ($imageUrl ? "'$imageUrl'" : "NULL") . ", 'pending')";

    if ($conn->query($query)) {
        return $conn->insert_id;
    }
    return false;
}


function updatePostDetails($conn, $postId, $itemName, $itemDescription, $category, $location) {
    $postId = (int)$postId;
    $itemName = sanitizeInput($itemName);
    $itemDescription = sanitizeInput($itemDescription);
    $category = sanitizeInput($category);
    $location = sanitizeInput($location);

    $query = "UPDATE posts SET item_name = '$itemName', item_description = '$itemDescription',
              category = '$category', location = '$location' WHERE post_id = $postId";

    return $conn->query($query);
}

function updatePostFull($conn, $postId, $postType, $itemName, $itemDescription, $category, $location, $dateTime, $imageUrl = null) {
    $postId = (int)$postId;
    $postType = sanitizeInput($postType);
    $itemName = sanitizeInput($itemName);
    $itemDescription = sanitizeInput($itemDescription);
    $category = sanitizeInput($category);
    $location = sanitizeInput($location);
    $dateTime = sanitizeInput($dateTime);

    $query = "UPDATE posts SET post_type = '$postType', item_name = '$itemName', item_description = '$itemDescription',
              category = '$category', location = '$location', date_time = '$dateTime'";

    if ($imageUrl !== null) {
        $query .= ", image_url = '$imageUrl'";
    }

    $query .= " WHERE post_id = $postId";

    return $conn->query($query);
}


function deletePostByUser($conn, $postId, $userId) {
    $postId = (int)$postId;
    $userId = (int)$userId;

    $query = "DELETE FROM posts WHERE post_id = $postId AND user_id = $userId";

    return $conn->query($query);
}

function registerUser($conn, $studentId, $fullname, $email, $university, $password) {
    $studentId = sanitizeInput($studentId);
    $fullname = sanitizeInput($fullname);
    $email = sanitizeInput($email);
    $university = sanitizeInput($university);
    $passwordHash = hashPassword($password);

    $query = "INSERT INTO users (student_id, name, email, password_hash, user_type, university, account_status) 
              VALUES ('$studentId', '$fullname', '$email', '$passwordHash', 'student', '$university', 'pending')";

    if ($conn->query($query)) {
        return $conn->insert_id;
    }
    return false;
}

function createOrResetAdmin($conn, $email, $password, $name) {
    $email = sanitizeInput($email);
    $name = sanitizeInput($name);
    $hashedPassword = hashPassword($password);

    
    $user = getUserByEmail($conn, $email);

    if ($user) {
        
        $query = "UPDATE users SET password_hash = '$hashedPassword', user_type = 'admin', account_status = 'active' WHERE email = '$email'";
        if ($conn->query($query)) {
            return ['success' => true, 'action' => 'reset'];
        }
        return ['success' => false, 'error' => $conn->error];
    } else {
        
        $studentId = 'ADMIN' . time();
        $query = "INSERT INTO users (student_id, name, email, password_hash, user_type, account_status) 
                  VALUES ('$studentId', '$name', '$email', '$hashedPassword', 'admin', 'active')";
        if ($conn->query($query)) {
            return ['success' => true, 'action' => 'created', 'user_id' => $conn->insert_id];
        }
        return ['success' => false, 'error' => $conn->error];
    }
}


function checkUserApprovalStatus($conn, $email) {
    $email = $conn->real_escape_string($email);
    $query = "SELECT account_status FROM users WHERE email = '$email' LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return $user['account_status'];
    }
    return null;
}


function getUserStatusMessage($status) {
    switch ($status) {
        case 'pending':
            return 'Your account has not been approved by the administrator. Please contact the administrator for approval.';
        case 'banned':
            return 'Your account has been banned. Please contact the administrator for assistance.';
        case 'active':
            return 'Your account is active.';
        default:
            return 'Account status unknown. Please contact the administrator.';
    }
}


function isUserApproved($conn, $email) {
    $status = checkUserApprovalStatus($conn, $email);
    return $status === 'active';
}
?>
