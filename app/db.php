<?php
// db.php
$host = 'localhost';           // your Ubuntu server host
$db   = 'morgan_legacy_scholarship';
$user = 'dbadmin';
$pass = '***REMOVED-DB-PASSWORD***';
$port = 5432;

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
