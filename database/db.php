<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "plantsphere_secure_authentication_system_db";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // In production, log this and show a generic message
    die("Database connection failed: " . $e->getMessage());
}
?>
