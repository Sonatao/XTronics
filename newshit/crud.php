<?php
require "db.php";

/* ============================================================
   CREATE
   ============================================================ */
if (isset($_POST["action"]) && $_POST["action"] === "create") {

    $stmt = $pdo->prepare("
        INSERT INTO orders 
        (orderDate, customerName, buyer, poNumber, partNumber, shippingMethod, notes, trackingNumber, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST["orderDate"],
        $_POST["customerName"],
        $_POST["buyer"],
        $_POST["poNumber"],
        $_POST["partNumber"],
        $_POST["shippingMethod"],
        $_POST["notes"],
        $_POST["trackingNumber"],
        $_POST["status"]
    ]);

    echo json_encode(["success" => true]);
    exit;
}

/* ============================================================
   FETCH ALL
   ============================================================ */
if (isset($_GET["action"]) && $_GET["action"] === "read") {
    $orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($orders);
    exit;
}

/* ============================================================
   FETCH SINGLE (for editing)
   ============================================================ */
if (isset($_GET["action"]) && $_GET["action"] === "fetch") {
    $id = $_GET["id"];
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}

/* ============================================================
   UPDATE + HISTORY
   ============================================================ */
if (isset($_POST["action"]) && $_POST["action"] === "update") {

    $id = $_POST["id"];

    // Fetch old version
    $old = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $old->execute([$id]);
    $oldData = $old->fetch(PDO::FETCH_ASSOC);

    // Insert history
    $history = $pdo->prepare("
        INSERT INTO order_history 
        (orderId, orderDate, customerName, buyer, poNumber, partNumber, shippingMethod, notes, trackingNumber, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $history->execute([
        $id,
        $oldData["orderDate"],
        $oldData["customerName"],
        $oldData["buyer"],
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
            orderDate = ?, customerName = ?, buyer = ?, poNumber = ?, partNumber = ?, 
            shippingMethod = ?, notes = ?, trackingNumber = ?, status = ?
        WHERE id = ?
    ");

    $update->execute([
        $_POST["orderDate"],
        $_POST["customerName"],
        $_POST["buyer"],
        $_POST["poNumber"],
        $_POST["partNumber"],
        $_POST["shippingMethod"],
        $_POST["notes"],
        $_POST["trackingNumber"],
        $_POST["status"],
        $id
    ]);

    echo json_encode(["success" => true]);
    exit;
}

/* ============================================================
   DELETE
   ============================================================ */
if (isset($_GET["action"]) && $_GET["action"] === "delete") {
    $id = $_GET["id"];
    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(["success" => true]);
    exit;
}

/* ============================================================
   HISTORY FETCH
   ============================================================ */
if (isset($_GET["action"]) && $_GET["action"] === "history") {
    $id = $_GET["id"];
    $stmt = $pdo->prepare("SELECT * FROM order_history WHERE orderId = ? ORDER BY id DESC");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

?>
