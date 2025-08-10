<?php
// includes/db.php
function getDbConnection() {
    static $pdo;
    
    if ($pdo === null) {
        $dotenv = parse_ini_file(__DIR__ . '/../.env');
        if ($dotenv === false) {
            die('Error: .env file not found');
        }

        $host = $dotenv['DB_HOST'] ?? 'localhost';
        $db   = $dotenv['DB_NAME'] ?? 'domainchecker';
        $user = $dotenv['DB_USER'] ?? 'root';
        $pass = $dotenv['DB_PASSWORD'] ?? '';
        
        try {
            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

function initDatabase() {
    $pdo = getDbConnection();
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS domains (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            expiryDate DATETIME NULL,
            whoisRaw TEXT NULL,
            available TINYINT(1) NULL,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

// Initialize database on include
initDatabase();