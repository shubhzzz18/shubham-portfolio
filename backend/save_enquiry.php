<?php
// save_enquiry.php - save contact form in database

header("Content-Type: application/json");

// CORS (frontend 127.0.0.1:5500  / file इ. साठी)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Preflight request असल्यास
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// फक्त POST allow
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
    exit;
}

require_once "config.php";

// Raw body वाच
$raw = file_get_contents("php://input");
if ($raw === false || $raw === "") {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON (empty body)"
    ]);
    exit;
}

// JSON decode
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON"
    ]);
    exit;
}

// Fields मिळवा
$name    = trim($data["name"]    ?? "");
$contact = trim($data["contact"] ?? "");
$need    = trim($data["need"]    ?? "");
$message = trim($data["message"] ?? "");

// Basic validation
if ($name === "" || $contact === "" || $need === "") {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Name, contact and need are required."
    ]);
    exit;
}

// Prepared statement – SQL injection safe
$sql = "INSERT INTO enquiries (name, contact, need, message) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Prepare failed: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("ssss", $name, $contact, $need, $message);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Enquiry saved successfully."
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error while saving enquiry."
    ]);
}

$stmt->close();
$conn->close();
