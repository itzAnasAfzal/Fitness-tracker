<?php
require_once __DIR__ . "/../app/config/db.php";

try {
    // Tips Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS tips (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )");
    echo "Tips table created/checked.<br>";

    // Routines Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS routines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )");
    echo "Routines table created/checked.<br>";

} catch (PDOException $e) {
    die("DB Setup failed: " . $e->getMessage());
}
?>
