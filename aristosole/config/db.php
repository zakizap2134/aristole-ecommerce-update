<?php
// =====================================================
// Database Configuration - PDO Connection
// Requirement: Prepared Statements & Error Handling
// =====================================================

$host = 'localhost';
$dbname = 'aristosole_db';
$username = 'root';
$password = '';

try {
    // PDO Connection (Requirement)
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Start session if not started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} catch (PDOException $e) {
    // Error handling - don't expose sensitive info
    error_log("Database Error: " . $e->getMessage());
    die("System temporarily unavailable. Please try again later.");
}
?>