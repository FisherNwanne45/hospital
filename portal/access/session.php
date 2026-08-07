<?php
include('config.php');

if (isset($conn) && $conn instanceof mysqli) {
    $connection = $conn;
} else {
    $connection = mysqli_connect($servername, $username, $password, $dbname);
    if (!$connection) {
        die('Connection failed: ' . mysqli_connect_error());
    }
    $conn = $connection;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}