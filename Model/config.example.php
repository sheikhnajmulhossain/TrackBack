<?php

mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv('TRACKBACK_DB_HOST') ?: 'localhost';
$user = getenv('TRACKBACK_DB_USER') ?: 'trackback';
$password = getenv('TRACKBACK_DB_PASSWORD');
$dbName = getenv('TRACKBACK_DB_NAME') ?: 'trackback_db';

if ($password === false || $password === '') {
    throw new RuntimeException('Set TRACKBACK_DB_PASSWORD in the server environment.');
}

$conn = new mysqli($host, $user, $password, $dbName);
if ($conn->connect_errno) {
    error_log('TrackBack database connection failed.');
} else {
    $conn->set_charset('utf8mb4');
}
