<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { exit; }

require_once __DIR__ . "/config.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["id"])) {
    echo json_encode(["success" => false, "message" => "Missing ID"]);
    exit;
}

$id = intval($data["id"]);

$sql = "DELETE FROM enquiries WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Deleted"]);
} else {
    echo json_encode(["success" => false, "message" => "Delete failed"]);
}

$stmt->close();
$conn->close();
?>
