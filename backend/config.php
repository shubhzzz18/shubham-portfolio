<?php
// config.php - MySQL connection

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = ""; // XAMPP default
$DB_NAME = "shubham_portfolio";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_errno) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit;
}

$conn->set_charset("utf8mb4");
