<?php
// Check karein ki Cloud (Render) ke Environment Variables available hain ya nahi
$cloud_host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? ($_SERVER['DB_HOST'] ?? null));

if (!empty($cloud_host)) {
    // 1. Render / Cloud (Aiven Database) Settings
    $host = $cloud_host;
    $user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? ($_SERVER['DB_USER'] ?? 'root'));
    $pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? ($_SERVER['DB_PASS'] ?? ''));
    $db   = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? ($_SERVER['DB_NAME'] ?? 'defaultdb'));
    $port = intval(getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? ($_SERVER['DB_PORT'] ?? 3306)));
} else {
    // 2. Localhost / WAMP / XAMPP Settings
    $host = '127.0.0.1';
    $user = 'root';
    $pass = ''; // WAMP me root ka default password khali hota hai
    $db   = 'epro';
    $port = 3306;
}

// Database Connection
$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>