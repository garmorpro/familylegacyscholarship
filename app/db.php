<?php
// db.php
$configPath = __DIR__ . '/config.local.php';
if (!file_exists($configPath)) {
    die("Missing app/config.local.php. Copy app/config.local.example.php to app/config.local.php and fill in real credentials.");
}
$config = require $configPath;

$host = $config['db']['host'];
$db   = $config['db']['name'];
$user = $config['db']['user'];
$pass = $config['db']['pass'];
$port = $config['db']['port'];

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("We're having trouble connecting right now. Please try again shortly.");
}
?>
