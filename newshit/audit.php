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
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Audit Log - XTronics Ticketing</title>
    <link rel="stylesheet" href="styles.base.css">
    <link id="themeStylesheet" rel="stylesheet" href="theme.minimal.css">
</head>
<body>

<button id="themeToggle" class="theme-toggle">Switch Theme</button>

<main>
    <section class="title">
        <h1>Audit Log</h1>
        <p><a href="index.php">Back to Tickets</a></p>
    </section>

    <section class="informationTable">
        <h2>All Actions</h2>
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
    </section>
</main>

<script>
const themeToggleBtn = document.getElementById("themeToggle");
const themeLink = document.getElementById("themeStylesheet");

let currentTheme = localStorage.getItem("xt_theme") || "minimal";

function applyTheme(theme) {
    if (theme === "minimal") {
        themeLink.href = "theme.minimal.css";
        themeToggleBtn.textContent = "Switch to GitHub Dark";
    } else {
        themeLink.href = "theme.github.css";
        themeToggleBtn.textContent = "Switch to Minimal Light";
    }
    currentTheme = theme;
    localStorage.setItem("xt_theme", theme);
}

applyTheme(currentTheme);

themeToggleBtn.addEventListener("click", () => {
    applyTheme(currentTheme === "minimal" ? "github" : "minimal");
});
</script>

</body>
</html>
