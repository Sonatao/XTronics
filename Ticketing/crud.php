<?php
require "db.php";
require "auth.php";
requireLogin();

/**
 * Write an audit log entry.
 */
function logAction($pdo, $action, $ticketId = null, $details = "") {
    $stmt = $pdo->prepare("
        INSERT INTO audit_log (userId, action, ticketId, details)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([currentUserId(), $action, $ticketId, $details]);
}

/* ============================================================
   CREATE
   ============================================================ */
if (isset($_POST["action"]) && $_POST["action"] === "create") {

    $stmt = $pdo->prepare("
        INSERT INTO orders 
        (orderDate, customerName, buyer, vendorOrder, poNumber, partNumber, shippingMethod, notes, trackingNumber, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
 
    $stmt->execute([
        $_POST["orderDate"] ?? null,
        $_POST["customerName"] ?? null,
        $_POST["buyer"] ?? null,
        $_POST["vendorOrder"] ?? null,
        $_POST["poNumber"] ?? null,
        $_POST["partNumber"] ?? null,
        $_POST["shippingMethod"] ?? null,
        $_POST["notes"] ?? null,
        $_POST["trackingNumber"] ?? null,
        $_POST["status"] ?? null
    ]);

    $ticketId = $pdo->lastInsertId();
    logAction($pdo, "create", $ticketId, "Created new ticket");

    echo json_encode(["success" => true, "id" => $ticketId]);
    exit;
}

/* ============================================================
   READ ALL (default)
   ============================================================ */
if (isset($_GET["action"]) && $_GET["action"] === "read") {
    $orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($orders);
    exit;
}

/* ============================================================
   SEARCH + FILTERS
   ============================================================ */
if (isset($_GET["action"]) && $_GET["action"] === "search") {
    $q         = $_GET["q"]         ?? "";
    $status    = $_GET["status"]    ?? "";
    $customer  = $_GET["customer"]  ?? "";
    $dateFrom  = $_GET["dateFrom"]  ?? "";
    $dateTo    = $_GET["dateTo"]    ?? "";

    $sql = "SELECT * FROM orders WHERE 1=1";
    $params = [];

    if ($q !== "") {
        $sql .= " AND (
            customerName LIKE ? OR
            buyer LIKE ? OR
            vendorOrder LIKE ? OR
            poNumber LIKE ? OR
            partNumber LIKE ? OR
            shippingMethod LIKE ? OR
            notes LIKE ? OR
            trackingNumber LIKE ? OR
            status LIKE ?
        )";
        $like = "%$q%";
        $params = array_merge($params, array_fill(0, 9, $like));
    }

    if ($status !== "") {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    if ($customer !== "") {
        $sql .= " AND customerName LIKE ?";
        $params[] = "%$customer%";
    }

    if ($dateFrom !== "") {
        $sql .= " AND orderDate >= ?";
        $params[] = $dateFrom;
    }

    if ($dateTo !== "") {
        $sql .= " AND orderDate <= ?";
        $params[] = $dateTo;
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* ============================================================
   FETCH SINGLE (for editing)
   ============================================================ */
if (isset($_GET["action"]) && $_GET["action"] === "fetch") {
    $id = $_GET["id"] ?? null;
    if (!$id) {
        echo json_encode(["error" => "Missing id"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}

/* ============================================================
   UPDATE + HISTORY
   ============================================================ */
if (isset($_POST["action"]) && $_POST["action"] === "update") {

    $id = $_POST["id"] ?? null;
    if (!$id) {
        echo json_encode(["error" => "Missing id"]);
        exit;
    }

    // Fetch old version
    $old = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $old->execute([$id]);
    $oldData = $old->fetch(PDO::FETCH_ASSOC);

    if (!$oldData) {
        echo json_encode(["error" => "Ticket not found"]);
        exit;
    }

    // Insert history
    $history = $pdo->prepare("
        INSERT INTO order_history 
        (orderId, orderDate, customerName, buyer, vendorOrder, poNumber, partNumber, shippingMethod, notes, trackingNumber, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $history->execute([
        $id,
        $oldData["orderDate"],
        $oldData["customerName"],
        $oldData["buyer"],
        $oldData["vendorOrder"] ?? null,
        $oldData["poNumber"],
        $oldData["partNumber"],
        $oldData["shippingMethod"],
        $oldData["notes"],
        $oldData["trackingNumber"],
        $oldData["status"]
    ]);

    // Update main record
    $update = $pdo->prepare("
        UPDATE orders SET
            orderDate = ?, customerName = ?, buyer = ?, vendorOrder = ?, poNumber = ?, partNumber = ?, 
            shippingMethod = ?, notes = ?, trackingNumber = ?, status = ?
        WHERE id = ?
    ");

    $update->execute([
        $_POST["orderDate"] ?? $oldData["orderDate"],
        $_POST["customerName"] ?? $oldData["customerName"],
        $_POST["buyer"] ?? $oldData["buyer"],
        $_POST["vendorOrder"] ?? $oldData["vendorOrder"] ?? null,
        $_POST["poNumber"] ?? $oldData["poNumber"],
        $_POST["partNumber"] ?? $oldData["partNumber"],
        $_POST["shippingMethod"] ?? $oldData["shippingMethod"],
        $_POST["notes"] ?? $oldData["notes"],
        $_POST["trackingNumber"] ?? $oldData["trackingNumber"],
        $_POST["status"] ?? $oldData["status"],
        $id
    ]);

    logAction($pdo, "update", $id, "Updated ticket");

    echo json_encode(["success" => true]);
    exit;
}

/* ============================================================
   DELETE (admin only)
   ============================================================ */
if (isset($_GET["action"]) && $_GET["action"] === "delete") {

    if (currentUserRole() !== "admin") {
        echo json_encode(["error" => "Permission denied"]);
        exit;
    }

    $id = $_GET["id"] ?? null;
    if (!$id) {
        echo json_encode(["error" => "Missing id"]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$id]);

    logAction($pdo, "delete", $id, "Deleted ticket");

    echo json_encode(["success" => true]);
    exit;
}

/* ============================================================
   HISTORY FETCH
   ============================================================ */
if (isset($_GET["action"]) && $_GET["action"] === "history") {
    $id = $_GET["id"] ?? null;
    if (!$id) {
        echo json_encode(["error" => "Missing id"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM order_history WHERE orderId = ? ORDER BY id DESC");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* ============================================================
   FETCH FILES FOR A TICKET
   ============================================================ */
if (isset($_GET["action"]) && $_GET["action"] === "files") {
    $id = $_GET["id"] ?? null;
    if (!$id) {
        echo json_encode(["error" => "Missing id"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM ticket_files WHERE ticketId = ? ORDER BY uploadedAt DESC");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

echo json_encode(["error" => "No valid action"]);
?>
