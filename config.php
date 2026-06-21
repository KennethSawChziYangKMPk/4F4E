<?php
// ==========================================
// ENVIRONMENT SETTINGS
// ==========================================
error_reporting(0);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Kuala_Lumpur');

// ==========================================
// LOAD ENVIRONMENT VARIABLES
// ==========================================
function loadEnv($path) {
    if (!file_exists($path)) {
        die('.env file not found');
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

loadEnv(__DIR__ . '/.env');

// ==========================================
// DATABASE CREDENTIALS
// ==========================================
$host = 'your-supabase-host';
$port = '5432';
$db   = 'postgres';
$user = 'your-username';
$pass = 'your-password';

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Database Connection Failed. Please contact the administrator or try again later.");
}
?>
