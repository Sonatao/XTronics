<?php
require "auth.php";
requireLogin();

if (currentUserRole() !== "admin") {
    die("Access denied");
}

require "db.php";

function logAction($pdo, $action, $details = "") {
    $stmt = $pdo->prepare("
        INSERT INTO audit_log (userId, action, ticketId, details)
        VALUES (?, ?, NULL, ?)
    ");
    $stmt->execute([currentUserId(), $action, $details]);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $mode = $_POST["mode"] ?? "";

    if ($mode === "create") {
        $username = trim($_POST["username"] ?? "");
        $password = trim($_POST["password"] ?? "");
        $role     = trim($_POST["role"] ?? "employee");

        if ($username === "" || $password === "") {
            $error = "Username and password are required.";
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Username already exists.";
            } else {
                $hash = hash("sha256", $password);
                $ins = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $ins->execute([$username, $hash, $role]);
                logAction($pdo, "create_user", "Created user: $username");
            }
        }
    }

    if ($mode === "update") {
        $id       = intval($_POST["id"] ?? 0);
        $username = trim($_POST["username"] ?? "");
        $password = trim($_POST["password"] ?? "");
        $role     = trim($_POST["role"] ?? "employee");

        if ($id <= 0) {
            $error = "Invalid user ID.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = "User not found.";
            } else {
                if ($user["role"] === "admin" && $role !== "admin") {
                    $countAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
                    if ($countAdmins <= 1) {
                        $error = "Cannot demote the last admin.";
                    }
                }

                if (!isset($error)) {
                    $fields = [];
                    $params = [];

                    if ($username !== "") {
                        $fields[] = "username = ?";
                        $params[] = $username;
                    }

                    if ($password !== "") {
                        $fields[] = "password = ?";
                        $params[] = hash("sha256", $password);
                    }

                    $fields[] = "role = ?";
                    $params[] = $role;

                    $params[] = $id;

                    $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
                    $upd = $pdo->prepare($sql);
                    $upd->execute($params);

                    logAction($pdo, "update_user", "Updated user ID: $id");
                }
            }
        }
    }

    if ($mode === "delete") {
        $id = intval($_POST["id"] ?? 0);

        if ($id <= 0) {
            $error = "Invalid user ID.";
        } else {
            if ($id === currentUserId()) {
                $error = "You cannot delete yourself.";
            } else {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    $error = "User not found.";
                } else {
                    if ($user["role"] === "admin") {
                        $countAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
                        if ($countAdmins <= 1) {
                            $error = "Cannot delete the last admin.";
                        }
                    }

                    if (!isset($error)) {
                        $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
                        $del->execute([$id]);
                        logAction($pdo, "delete_user", "Deleted user ID: $id");
                    }
                }
            }
        }
    }
}

$users = $pdo->query("SELECT id, username, role, createdAt FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>User Management - XTronics Ticketing</title>
    <link rel="stylesheet" href="styles.base.css">
    <link id="themeStylesheet" rel="stylesheet" href="theme.minimal.css">
</head>
<body>

<button id="themeToggle" class="theme-toggle">Switch Theme</button>

<main>
    <section class="title">
        <h1>User Management</h1>
        <p><a href="index.php">Back to Tickets</a></p>
    </section>

    <section class="informationTable">
        <h2>Existing Users</h2>
        <table class="auditTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo $u["id"]; ?></td>
                        <td><?php echo htmlspecialchars($u["username"]); ?></td>
                        <td><?php echo htmlspecialchars($u["role"]); ?></td>
                        <td><?php echo $u["createdAt"]; ?></td>
                        <td>
                            <button onclick="openEditUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['role'], ENT_QUOTES); ?>')">Edit</button>
                            <?php if ($u["id"] !== currentUserId()): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user?');">
                                    <input type="hidden" name="mode" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (isset($error)): ?>
            <p style="color:red; margin-top:0.75rem;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
    </section>

    <section class="informationTable">
        <h2>Create New User</h2>
        <form method="POST">
            <input type="hidden" name="mode" value="create">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role">
                <option value="employee">Employee</option>
                <option value="admin">Admin</option>
            </select>
            <button type="submit">Create User</button>
        </form>
    </section>

    <div id="userEditModal" class="modal hidden">
        <div class="modal-content">
            <h1>Edit User</h1>
            <form method="POST">
                <input type="hidden" name="mode" value="update">
                <input type="hidden" name="id" id="editUserId">
                <input type="text" name="username" id="editUsername" placeholder="Username">
                <input type="password" name="password" placeholder="New Password (leave blank to keep)">
                <select name="role" id="editRole">
                    <option value="employee">Employee</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit">Save Changes</button>
                <button type="button" onclick="closeUserEdit()">Cancel</button>
            </form>
        </div>
    </div>
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

function openEditUser(id, username, role) {
    document.getElementById("editUserId").value = id;
    document.getElementById("editUsername").value = username;
    document.getElementById("editRole").value = role;
    document.getElementById("userEditModal").classList.remove("hidden");
}

function closeUserEdit() {
    document.getElementById("userEditModal").classList.add("hidden");
}
</script>

</body>
</html>
