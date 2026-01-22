<?php
// Model/database.php

mysqli_report(MYSQLI_REPORT_OFF);

$host = "localhost";
$user = "trackback_db";
$password = "Track@b1";
$dbName = "trackback_db";

$conn = new mysqli($host, $user, $password, $dbName);

if ($conn->connect_errno) {
    // ❌ DO NOT die() or echo
    error_log("DB CONNECTION FAILED: " . $conn->connect_error);
    // let auth_api.php handle JSON response
}

$conn->set_charset("utf8mb4");
