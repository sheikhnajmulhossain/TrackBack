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


setCorsHeaders();


$action = $_GET['action'] ?? $_POST['action'] ?? null;


$authRequiredActions = ['create_post', 'update_post', 'delete_post', 'get_my_posts'];


if (in_array($action, $authRequiredActions)) {
    validateSession();
}


switch ($action) {
    case 'get_posts':
        getPosts($conn);
        break;
    case 'get_post':
        getPost($conn);
        break;
    case 'create_post':
        createPostHandler($conn);
        break;
    case 'update_post':
        updatePostHandler($conn);
        break;
    case 'delete_post':
        deletePostHandler($conn);
        break;
    case 'search_posts':
        searchPostsHandler($conn);
        break;
    case 'get_my_posts':
        getMyPosts($conn);
        break;
    default:
        sendError('Invalid action', 400);
}


function getPosts($conn) {
    $filter = $_GET['filter'] ?? '';
    $category = $_GET['category'] ?? '';
    $location = $_GET['location'] ?? '';
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = (int)($_GET['offset'] ?? 0);
    
    $filters = [
        'limit' => $limit,
        'offset' => $offset
    ];
    

    if (isset($_SESSION['university']) && !empty($_SESSION['university'])) {
        $filters['university'] = $_SESSION['university'];
    }
    
    if ($filter === 'lost' || $filter === 'found') {
        $filters['type'] = $filter;
    }
    
    if (!empty($category)) {
        $filters['category'] = $category;
    }
    
    if (!empty($location)) {
        $filters['location'] = $location;
    }
    
    $posts = getApprovedPosts($conn, $filters);
    
    if ($posts === false) {
        sendError('Database error', 500);
    }
    
    sendSuccess($posts, 'Posts retrieved successfully');
}

function getPost($conn) {
    $postId = (int)($_GET['post_id'] ?? 0);
    
    if ($postId === 0) {
        sendError('Post ID is required', 400);
    }
    
    $post = getApprovedPostById($conn, $postId);
    
    if (!$post) {
        sendError('Post not found', 404);
    }
    
    sendSuccess($post, 'Post retrieved successfully');
}

function getMyPosts($conn) {
    $userId = (int)$_SESSION['user_id'];
    
    $posts = getUserPosts($conn, $userId);
    
    if ($posts === false) {
        sendError('Database error', 500);
    }
    
    sendSuccess($posts, 'Your posts retrieved successfully');
}

function createPostHandler($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $userId = (int)$_SESSION['user_id'];
    $postType = $_POST['post_type'] ?? '';
    $itemName = $_POST['item_name'] ?? '';
    $itemDescription = $_POST['item_description'] ?? '';
    $category = $_POST['category'] ?? '';
    $location = $_POST['location'] ?? '';
    $dateTime = $_POST['date_time'] ?? '';
    
    if (empty($postType) || !in_array($postType, ['lost', 'found'])) {
        sendError('Post type must be lost or found', 400);
    }
    
    if (empty($itemName) || empty($itemDescription) || empty($location) || empty($dateTime)) {
        sendError('All fields are required', 400);
    }
    

    $imageUrl = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && $_FILES['image']['size'] > 0) {
        $imageUrl = uploadImage($_FILES['image'], 'posts');
        if ($imageUrl === false) {
            sendError('Image upload failed. Only JPEG, PNG, GIF, and WebP images under 5MB are allowed.', 400);
        }
    }
    
    $postId = insertPost($conn, $userId, $postType, $itemName, $itemDescription, $category, $location, $dateTime, $imageUrl);
    
    if ($postId) {
        logAction($conn, $userId, 'CREATE_POST', ['post_id' => $postId, 'type' => $postType]);
        sendSuccess(['post_id' => $postId], 'Post created successfully. Waiting for approval.');
    } else {
        sendError('Failed to create post', 500);
    }
}

function updatePostHandler($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }

    $userId = (int)$_SESSION['user_id'];
    $postId = (int)($_POST['post_id'] ?? 0);

    if ($postId === 0) {
        sendError('Post ID is required', 400);
    }

    $ownsPost = userOwnsPost($conn, $postId, $userId);

    if ($ownsPost === null) {
        sendError('Post not found', 404);
    }

    if ($ownsPost === false) {
        sendError('You can only update your own posts', 403);
    }

    $postType = $_POST['post_type'] ?? '';
    $itemName = $_POST['item_name'] ?? '';
    $itemDescription = $_POST['item_description'] ?? '';
    $category = $_POST['category'] ?? '';
    $location = $_POST['location'] ?? '';
    $dateTime = $_POST['date_time'] ?? '';

    if (empty($postType) || !in_array($postType, ['lost', 'found'])) {
        sendError('Post type must be lost or found', 400);
    }

    if (empty($itemName) || empty($itemDescription) || empty($location) || empty($dateTime)) {
        sendError('All fields are required', 400);
    }

    $imageUrl = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && $_FILES['image']['size'] > 0) {
        $imageUrl = uploadImage($_FILES['image'], 'posts');
        if ($imageUrl === false) {
            sendError('Image upload failed. Only JPEG, PNG, GIF, and WebP images under 5MB are allowed.', 400);
        }
    }

    if (updatePostFull($conn, $postId, $postType, $itemName, $itemDescription, $category, $location, $dateTime, $imageUrl)) {
        logAction($conn, $userId, 'UPDATE_POST', ['post_id' => $postId]);
        sendSuccess([], 'Post updated successfully');
    } else {
        sendError('Failed to update post', 500);
    }
}

function deletePostHandler($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $userId = (int)$_SESSION['user_id'];
    $postId = (int)($_POST['post_id'] ?? 0);
    
    if ($postId === 0) {
        sendError('Post ID is required', 400);
    }
    
    $ownsPost = userOwnsPost($conn, $postId, $userId);
    
    if ($ownsPost === null) {
        sendError('Post not found', 404);
    }
    
    if ($ownsPost === false) {
        sendError('You can only delete your own posts', 403);
    }
    
    if (deletePostByUser($conn, $postId, $userId)) {
        logAction($conn, $userId, 'DELETE_POST', ['post_id' => $postId]);
        sendSuccess([], 'Post deleted successfully');
    } else {
        sendError('Failed to delete post', 500);
    }
}

function searchPostsHandler($conn) {
    $keyword = $_GET['q'] ?? '';
    
    if (empty($keyword)) {
        sendError('Search keyword is required', 400);
    }
    
    $university = isset($_SESSION['university']) && !empty($_SESSION['university']) ? $_SESSION['university'] : null;
    
    $posts = searchPosts($conn, $keyword, $university);
    
    if ($posts === false) {
        sendError('Database error', 500);
    }
    
    sendSuccess($posts, 'Search results retrieved');
}
?>
