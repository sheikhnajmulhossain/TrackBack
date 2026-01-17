<?php
session_start();

// Include database connection and functions
include '../Model/database.php';
include '../Model/functions.php';

// Check if user is logged in
validateSession();

// Get action parameter
$action = $_GET['action'] ?? $_POST['action'] ?? null;

// Handle requests
switch ($action) {
    case 'get_posts':
        getPosts($conn);
        break;
    case 'get_post':
        getPost($conn);
        break;
    case 'create_post':
        createPost($conn);
        break;
    case 'update_post':
        updatePost($conn);
        break;
    case 'delete_post':
        deletePost($conn);
        break;
    case 'search_posts':
        searchPosts($conn);
        break;
    case 'get_my_posts':
        getMyPosts($conn);
        break;
    default:
        sendError('Invalid action', 400);
}

// ============ POSTS ============
function getPosts($conn) {
    $filter = $_GET['filter'] ?? '';
    $category = $_GET['category'] ?? '';
    $location = $_GET['location'] ?? '';
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = (int)($_GET['offset'] ?? 0);
    
    $query = "SELECT p.post_id, p.user_id, p.post_type, p.item_name, p.item_description, p.category, 
                     p.location, p.date_time, p.image_url, p.post_status, p.created_at,
                     u.name as user_name, u.email, u.university
              FROM posts p
              JOIN users u ON p.user_id = u.user_id
              WHERE p.post_status = 'approved'";
    
    if ($filter === 'lost') {
        $query .= " AND p.post_type = 'lost'";
    } elseif ($filter === 'found') {
        $query .= " AND p.post_type = 'found'";
    }
    
    if (!empty($category)) {
        $category = sanitizeInput($category);
        $query .= " AND p.category = '$category'";
    }
    
    if (!empty($location)) {
        $location = sanitizeInput($location);
        $query .= " AND p.location LIKE '%$location%'";
    }
    
    $query .= " ORDER BY p.created_at DESC LIMIT $offset, $limit";
    
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

function getPost($conn) {
    $postId = (int)($_GET['post_id'] ?? 0);
    
    if ($postId === 0) {
        sendError('Post ID is required', 400);
    }
    
    $query = "SELECT p.*, u.name as user_name, u.email, u.phone, u.university
              FROM posts p
              JOIN users u ON p.user_id = u.user_id
              WHERE p.post_id = $postId AND p.post_status = 'approved'";
    
    $result = $conn->query($query);
    
    if (!$result || $result->num_rows === 0) {
        sendError('Post not found', 404);
    }
    
    $post = $result->fetch_assoc();
    sendSuccess($post, 'Post retrieved successfully');
}

function getMyPosts($conn) {
    $userId = (int)$_SESSION['user_id'];
    
    $query = "SELECT post_id, post_type, item_name, item_description, category, location, 
                     date_time, image_url, post_status, created_at, updated_at
              FROM posts
              WHERE user_id = $userId
              ORDER BY created_at DESC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        sendError('Database error: ' . $conn->error, 500);
    }
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    
    sendSuccess($posts, 'Your posts retrieved successfully');
}

function createPost($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $userId = (int)$_SESSION['user_id'];
    $postType = sanitizeInput($_POST['post_type'] ?? '');
    $itemName = sanitizeInput($_POST['item_name'] ?? '');
    $itemDescription = sanitizeInput($_POST['item_description'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $dateTime = sanitizeInput($_POST['date_time'] ?? '');
    
    // Validation
    if (empty($postType) || !in_array($postType, ['lost', 'found'])) {
        sendError('Post type must be lost or found', 400);
    }
    
    if (empty($itemName) || empty($itemDescription) || empty($location) || empty($dateTime)) {
        sendError('All fields are required', 400);
    }
    
    // Handle image upload
    $imageUrl = null;
    if (isset($_FILES['image'])) {
        $imageUrl = uploadImage($_FILES['image']);
        if (!$imageUrl) {
            sendError('Image upload failed', 500);
        }
    }
    
    $imageUrl = $imageUrl ? $conn->real_escape_string($imageUrl) : null;
    
    $query = "INSERT INTO posts (user_id, post_type, item_name, item_description, category, location, date_time, image_url, post_status)
              VALUES ($userId, '$postType', '$itemName', '$itemDescription', '$category', '$location', '$dateTime', " . 
              ($imageUrl ? "'$imageUrl'" : "NULL") . ", 'pending')";
    
    if ($conn->query($query)) {
        $postId = $conn->insert_id;
        logAction($conn, $userId, 'CREATE_POST', ['post_id' => $postId, 'type' => $postType]);
        sendSuccess(['post_id' => $postId], 'Post created successfully. Waiting for approval.');
    } else {
        sendError('Failed to create post: ' . $conn->error, 500);
    }
}

function updatePost($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $userId = (int)$_SESSION['user_id'];
    $postId = (int)($_POST['post_id'] ?? 0);
    
    if ($postId === 0) {
        sendError('Post ID is required', 400);
    }
    
    // Check if user owns the post
    $checkQuery = "SELECT user_id FROM posts WHERE post_id = $postId";
    $result = $conn->query($checkQuery);
    
    if (!$result || $result->num_rows === 0) {
        sendError('Post not found', 404);
    }
    
    $post = $result->fetch_assoc();
    if ($post['user_id'] !== $userId) {
        sendError('You can only update your own posts', 403);
    }
    
    $itemName = sanitizeInput($_POST['item_name'] ?? '');
    $itemDescription = sanitizeInput($_POST['item_description'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    
    $query = "UPDATE posts SET item_name = '$itemName', item_description = '$itemDescription', 
              category = '$category', location = '$location' WHERE post_id = $postId";
    
    if ($conn->query($query)) {
        logAction($conn, $userId, 'UPDATE_POST', ['post_id' => $postId]);
        sendSuccess([], 'Post updated successfully');
    } else {
        sendError('Failed to update post: ' . $conn->error, 500);
    }
}

function deletePost($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $userId = (int)$_SESSION['user_id'];
    $postId = (int)($_POST['post_id'] ?? 0);
    
    if ($postId === 0) {
        sendError('Post ID is required', 400);
    }
    
    // Check if user owns the post
    $checkQuery = "SELECT user_id FROM posts WHERE post_id = $postId";
    $result = $conn->query($checkQuery);
    
    if (!$result || $result->num_rows === 0) {
        sendError('Post not found', 404);
    }
    
    $post = $result->fetch_assoc();
    if ($post['user_id'] !== $userId) {
        sendError('You can only delete your own posts', 403);
    }
    
    $query = "DELETE FROM posts WHERE post_id = $postId AND user_id = $userId";
    
    if ($conn->query($query)) {
        logAction($conn, $userId, 'DELETE_POST', ['post_id' => $postId]);
        sendSuccess([], 'Post deleted successfully');
    } else {
        sendError('Failed to delete post: ' . $conn->error, 500);
    }
}

function searchPosts($conn) {
    $keyword = sanitizeInput($_GET['q'] ?? '');
    
    if (empty($keyword)) {
        sendError('Search keyword is required', 400);
    }
    
    $query = "SELECT p.post_id, p.user_id, p.post_type, p.item_name, p.item_description, p.category,
                     p.location, p.date_time, p.image_url, p.post_status, p.created_at,
                     u.name as user_name
              FROM posts p
              JOIN users u ON p.user_id = u.user_id
              WHERE p.post_status = 'approved' AND
                    (p.item_name LIKE '%$keyword%' OR 
                     p.item_description LIKE '%$keyword%' OR
                     p.location LIKE '%$keyword%')
              ORDER BY p.created_at DESC LIMIT 20";
    
    $result = $conn->query($query);
    
    if (!$result) {
        sendError('Database error: ' . $conn->error, 500);
    }
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    
    sendSuccess($posts, 'Search results retrieved');
}

// ============ IMAGE UPLOAD ============
function uploadImage($file) {
    $maxSize = 5 * 1024 * 1024; // 5MB
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    $uploadDir = __DIR__ . '/../View/img/posts/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = time() . '_' . basename($file['name']);
    $filePath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return 'img/posts/' . $fileName;
    }
    
    return false;
}
?>
