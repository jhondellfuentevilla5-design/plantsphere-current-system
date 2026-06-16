<?php
// Auto-detect environment
$isLocalhost = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1'])
               || str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost');

if ($isLocalhost) {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db   = 'plantsphere_secure_authentication_system_db';
} else {
    $host = 'sql305.infinityfree.com';
    $user = 'if0_42188908';
    $pass = '200306dell';
    $db   = 'if0_42188908_plantsphere';
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if ($isLocalhost) {
        die("Database connection failed: " . $e->getMessage());
    } else {
        error_log("DB Error: " . $e->getMessage());
        die("<h3 style='font-family:sans-serif;color:#c00;text-align:center;margin-top:80px;'>
            Unable to connect to database. Please contact the administrator.</h3>");
    }
}
?>
