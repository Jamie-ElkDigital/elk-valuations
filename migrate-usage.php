<?php
/**
 * ELK Valuations - Usage Log Migration
 * Creates a table to track AI usage and costs per firm.
 */

require_once 'db.php';

try {
    $pdo = DB::getInstance();

    $pdo->exec("CREATE TABLE IF NOT EXISTS usage_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        firm_id INT NOT NULL,
        user_id INT,
        action VARCHAR(50) NOT NULL, -- 'extract', 'narrative'
        prompt_tokens INT DEFAULT 0,
        completion_tokens INT DEFAULT 0,
        total_tokens INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    echo "Usage log table created successfully.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
