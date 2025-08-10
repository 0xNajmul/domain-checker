<?php
// update_database.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = getDbConnection();
    
    // Check if the domains table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'domains'");
    if ($stmt->rowCount() === 0) {
        // Create the domains table if it doesn't exist
        $pdo->exec("
            CREATE TABLE domains (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                available TINYINT(1) DEFAULT 0,
                expiryDate DATETIME NULL,
                whoisRaw TEXT NULL,
                createdAt DATETIME NOT NULL,
                updatedAt DATETIME NOT NULL,
                UNIQUE KEY unique_domain (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        echo "Created 'domains' table.\n";
    } else {
        // Check and add missing columns if needed
        $columns = [
            'whoisRaw ' => "ALTER TABLE domains ADD COLUMN whoisRaw TEXT NULL AFTER expiryDate",
            'createdAt' => "ALTER TABLE domains ADD COLUMN createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'updatedAt' => "ALTER TABLE domains ADD COLUMN updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        foreach ($columns as $column => $sql) {
            $stmt = $pdo->query("SHOW COLUMNS FROM domains LIKE '$column'");
            if ($stmt->rowCount() === 0) {
                $pdo->exec($sql);
                echo "Added column '$column' to 'domains' table.\n";
            }
        }
        
        // Try to add unique constraint if it doesn't exist
        try {
            $pdo->exec("ALTER TABLE domains ADD UNIQUE KEY unique_domain (name)");
            echo "Added unique constraint on 'name' column.\n";
        } catch (Exception $e) {
            // Ignore if the constraint already exists
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                throw $e;
            }
        }
    }
    
    echo "Database is up to date.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Redirect back to index after 3 seconds
echo "<script>
    setTimeout(function() {
        window.location.href = 'index.php';
    }, 3000);
</script>";

echo "<p>Database update complete. <a href='index.php'>Click here</a> to return to the main page if not redirected automatically.</p>";
