<?php
    $host = "localhost";
    $user = "Admin";
    $pass = "123456789Admin!";
    $dbname = "xtronics";

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }  catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
?>
