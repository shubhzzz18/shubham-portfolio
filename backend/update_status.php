<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { exit; }

require_once __DIR__ . "/config.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["id"]) || !isset($data["status"])) {
    echo json_encode(["success" => false, "message" => "Missing data"]);
    exit;
}

$id = intval($data["id"]);
$status = $data["status"];

$sql = "UPDATE enquiries SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Status updated"]);
} else {
    echo json_encode(["success" => false, "message" => "Update failed"]);
}

$stmt->close();
$conn->close();
?>
