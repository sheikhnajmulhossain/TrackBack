<?php
$host = "localhost";
$user = "trackback_db";     //  NOT root
$password = "Track@b1";         //  correct password
$dbName = "trackback_db"; //  prefix added

$conn = mysqli_connect($host, $user, $password, $dbName);

if (!$conn) {
    die("DB Connection Failed: " . mysqli_connect_error());
}