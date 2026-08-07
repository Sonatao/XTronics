<?php
require "db.php";
require "auth.php";
requireLogin();

header("Content-Type: application/json");

if (!isset($_POST["ticketId"])) {
    echo json_encode(["error" => "Missing ticketId"]);
    exit;
}

$ticketId = intval($_POST["ticketId"]);

if (!isset($_FILES["file"])) {
    echo json_encode(["error" => "No file uploaded"]);
    exit;
}

$file = $_FILES["file"];
$originalName = $file["name"];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

$allowed = ["pdf", "jpg", "jpeg", "png", "gif"];

if (!in_array($ext, $allowed)) {
    echo json_encode(["error" => "Invalid file type"]);
    exit;
}

$newName = uniqid("file_") . "." . $ext;
$target = __DIR__ . "/uploads/" . $newName;

if (!move_uploaded_file($file["tmp_name"], $target)) {
    echo json_encode(["error" => "Failed to save file"]);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO ticket_files (ticketId, filename, originalName)
    VALUES (?, ?, ?)
");
$stmt->execute([$ticketId, $newName, $originalName]);

// Audit log
$log = $pdo->prepare("
    INSERT INTO audit_log (userId, action, ticketId, details)
    VALUES (?, 'upload', ?, ?)
");
$log->execute([currentUserId(), $ticketId, "Uploaded file: $originalName"]);

echo json_encode(["success" => true]);
?>
