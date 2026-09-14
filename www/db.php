<?php
$host = getenv('DB_HOST') ?: 'db';
$db = getenv('DB_NAME') ?: 'service_panel';
$user = getenv('DB_USER') ?: 'user';
$pass = getenv('DB_PASSWORD') ?: 'userpassword';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 5,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Write to error log, don't expose sensitive info to user
    file_put_contents('php://stderr', "DB Connection Error: " . $e->getMessage() . "\n");
    die("数据库连接失败，请检查日志。");
}
