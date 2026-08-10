<?php
require "db.php";
require "auth.php";
requireLogin();

header("Content-Type: application/json");

function logAction($pdo, $action, $ticketId = null, $details = "") {
    $stmt = $pdo->prepare("
        INSERT INTO audit_log (userId, action, ticketId, details)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([currentUserId(), $action, $ticketId, $details]);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["mode"]) && $_POST["mode"] === "delete") {
    if (currentUserRole() !== "admin") {
        echo json_encode(["error" => "Permission denied"]);
        exit;
    }

    $id = intval($_POST["id"] ?? 0);
    if ($id <= 0) {
        echo json_encode(["error" => "Invalid file ID"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM ticket_files WHERE id = ?");
    $stmt->execute([$id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        echo json_encode(["error" => "File not found"]);
        exit;
    }

    $path = __DIR__ . "/uploads/" . $file["filename"];
    if (file_exists($path)) {
        unlink($path);
    }

    $del = $pdo->prepare("DELETE FROM ticket_files WHERE id = ?");
    $del->execute([$id]);

    logAction($pdo, "delete_attachment", $file["ticketId"], "Deleted file: " . $file["originalName"]);

    echo json_encode(["success" => true]);
    exit;
}

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

logAction($pdo, "upload", $ticketId, "Uploaded file: $originalName");

echo json_encode(["success" => true]);
?>
