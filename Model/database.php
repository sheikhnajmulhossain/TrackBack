<?php


error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log('database.php: Started');

include 'config.php';
error_log('database.php: About to connect');

$conn = mysqli_connect($host, $user, $password);
error_log('database.php: Connect attempted');

if (!$conn) {
    error_log('database.php: Connection failed');
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
error_log('database.php: Connection successful');

$sql = "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
error_log('database.php: About to create database');
if (!mysqli_query($conn, $sql)) {
    error_log('database.php: Database creation failed: ' . mysqli_error($conn));
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database creation failed']);
    exit;
}
error_log('database.php: Database created or exists');

mysqli_select_db($conn, $dbName);
error_log('database.php: Database selected');

mysqli_set_charset($conn, "utf8mb4");
error_log('database.php: Charset set');

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
error_log('database.php: Table check done, tables exist: ' . (mysqli_num_rows($tableCheck) > 0 ? 'yes' : 'no'));
if (mysqli_num_rows($tableCheck) == 0) {
    error_log('database.php: Creating tables');

    

    $sql = "CREATE TABLE IF NOT EXISTS `users` (
        `user_id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` varchar(20) NOT NULL,
        `name` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `password_hash` varchar(255) NOT NULL,
        `user_type` enum('admin','moderator','student') DEFAULT 'student',
        `university` varchar(100) DEFAULT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `profile_picture` varchar(255) DEFAULT NULL,
        `account_status` enum('active','banned','pending') DEFAULT 'pending',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`user_id`),
        UNIQUE KEY `student_id` (`student_id`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $sql);
    
    $sql = "CREATE TABLE IF NOT EXISTS `posts` (
        `post_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `post_type` enum('lost','found') NOT NULL,
        `item_name` varchar(100) NOT NULL,
        `item_description` text NOT NULL,
        `category` varchar(50) DEFAULT NULL,
        `location` varchar(200) NOT NULL,
        `date_time` datetime NOT NULL,
        `image_url` varchar(255) DEFAULT NULL,
        `post_status` enum('pending','approved','banned','resolved') DEFAULT 'pending',
        `flagged` tinyint(1) DEFAULT 0,
        `flag_reason` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`post_id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $sql);
    

    $sql = "CREATE TABLE IF NOT EXISTS `messages` (
        `message_id` int(11) NOT NULL AUTO_INCREMENT,
        `sender_id` int(11) NOT NULL,
        `receiver_id` int(11) NOT NULL,
        `subject` varchar(200) DEFAULT NULL,
        `message_content` text NOT NULL,
        `is_read` tinyint(1) DEFAULT 0,
        `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`message_id`),
        KEY `sender_id` (`sender_id`),
        KEY `receiver_id` (`receiver_id`),
        CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
        CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $sql);
    

    $sql = "CREATE TABLE IF NOT EXISTS `notifications` (
        `notification_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `notification_type` varchar(50) NOT NULL,
        `notification_message` text NOT NULL,
        `is_read` tinyint(1) DEFAULT 0,
        `related_post_id` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`notification_id`),
        KEY `user_id` (`user_id`),
        KEY `related_post_id` (`related_post_id`),
        CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
        CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`related_post_id`) REFERENCES `posts` (`post_id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $sql);

    $sql = "CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
        `token_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `token` varchar(255) NOT NULL,
        `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`token_id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $sql);

    $sql = "CREATE TABLE IF NOT EXISTS `warnings` (
        `warning_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `moderator_id` int(11) NOT NULL,
        `warning_reason` text NOT NULL,
        `warning_date` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`warning_id`),
        KEY `user_id` (`user_id`),
        KEY `moderator_id` (`moderator_id`),
        CONSTRAINT `warnings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
        CONSTRAINT `warnings_ibfk_2` FOREIGN KEY (`moderator_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $sql);
    

    $sql = "CREATE TABLE IF NOT EXISTS `post_flags` (
        `flag_id` int(11) NOT NULL AUTO_INCREMENT,
        `post_id` int(11) NOT NULL,
        `moderator_id` int(11) NOT NULL,
        `flag_reason` text NOT NULL,
        `flagged_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`flag_id`),
        KEY `post_id` (`post_id`),
        KEY `moderator_id` (`moderator_id`),
        CONSTRAINT `post_flags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
        CONSTRAINT `post_flags_ibfk_2` FOREIGN KEY (`moderator_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $sql);
    

    $sql = "CREATE TABLE IF NOT EXISTS `disputes` (
        `dispute_id` int(11) NOT NULL AUTO_INCREMENT,
        `post_id` int(11) NOT NULL,
        `claimant1_id` int(11) NOT NULL,
        `claimant2_id` int(11) NOT NULL,
        `moderator_id` int(11) DEFAULT NULL,
        `dispute_description` text NOT NULL,
        `dispute_status` enum('open','in_progress','resolved') DEFAULT 'open',
        `resolution` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `resolved_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`dispute_id`),
        KEY `post_id` (`post_id`),
        KEY `claimant1_id` (`claimant1_id`),
        KEY `claimant2_id` (`claimant2_id`),
        KEY `moderator_id` (`moderator_id`),
        CONSTRAINT `disputes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
        CONSTRAINT `disputes_ibfk_2` FOREIGN KEY (`claimant1_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
        CONSTRAINT `disputes_ibfk_3` FOREIGN KEY (`claimant2_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
        CONSTRAINT `disputes_ibfk_4` FOREIGN KEY (`moderator_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $sql);
    

    $sql = "CREATE TABLE IF NOT EXISTS `audit_logs` (
        `log_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `action_type` varchar(50) NOT NULL,
        `action_details` text DEFAULT NULL,
        `ip_address` varchar(45) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`log_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $sql);
}
error_log('database.php: Tables created or exist');
?>