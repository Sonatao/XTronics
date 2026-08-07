<?php
session_start();
require "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && hash("sha256", $password) === $user["password"]) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["role"] = $user["role"];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Login - XTronics Ticketing</title>
    <link rel="stylesheet" href="styles.base.css">
    <link id="themeStylesheet" rel="stylesheet" href="theme.minimal.css">
</head>
<body>

<button id="themeToggle" class="theme-toggle">Switch Theme</button>

<main>
    <section class="login-container">
        <h1>XTronics Ticketing</h1>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <?php if ($error): ?>
            <p style="color:red; margin-top:0.75rem;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
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
