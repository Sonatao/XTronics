<?php
require "auth.php";
requireLogin();

if (currentUserRole() !== "admin") {
    die("Access denied");
}

require "db.php";

$logs = $pdo->query("
    SELECT audit_log.*, users.username
    FROM audit_log
    JOIN users ON users.id = audit_log.userId
    ORDER BY audit_log.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Audit Log</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h1>Audit Log</h1>
<p><a href="index.php">Back to Tickets</a></p>

<table class="auditTable">
    <thead>
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Action</th>
            <th>Ticket ID</th>
            <th>Details</th>
            <th>Timestamp</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?php echo $log["id"]; ?></td>
                <td><?php echo htmlspecialchars($log["username"]); ?></td>
                <td><?php echo htmlspecialchars($log["action"]); ?></td>
                <td><?php echo $log["ticketId"]; ?></td>
                <td><?php echo htmlspecialchars($log["details"]); ?></td>
                <td><?php echo $log["createdAt"]; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
