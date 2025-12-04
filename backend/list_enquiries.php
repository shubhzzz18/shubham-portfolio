<?php
// list_enquiries.php - return all enquiries as JSON

header("Content-Type: application/json");

// CORS for local dev (VS Code Live Server / Netlify preview)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// DB config + connection (same as save_enquiry.php)
require_once __DIR__ . "/config.php"; // this should create $conn (mysqli)

// If config.php did not set $conn, handle it
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]);
    exit;
}

// Fetch enquiries ordered by latest first
$sql = "SELECT id, name, contact, need, message, created_at 
        FROM enquiries 
        ORDER BY created_at DESC";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Query failed: " . $conn->error
    ]);
    exit;
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode([
    "success" => true,
    "data"    => $rows
]);

$conn->close();
