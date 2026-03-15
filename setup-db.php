<?php
/**
 * ELK Valuations - Database Setup
 * Initializes the firms and users tables for multi-tenancy.
 */

require_once 'db.php';

try {
    $pdo = DB::getInstance();

    // 1. Create firms table
    $pdo->exec("CREATE TABLE IF NOT EXISTS firms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        logo_url VARCHAR(255),
        primary_color VARCHAR(7) DEFAULT '#c5a059',
        secondary_color VARCHAR(7) DEFAULT '#050505',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 2. Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        firm_id INT NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        name VARCHAR(255),
        role ENUM('admin', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 3. Update valuations table if firm_id/user_id don't have constraints (optional but good)
    // For now let's just ensure they exist. The save-valuation.php already uses them.

    // 4. Seed initial data (GTA Accounting)
    $stmt = $pdo->prepare("SELECT id FROM firms WHERE slug = 'gta'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO firms (name, slug, primary_color, secondary_color) 
                   VALUES ('GTA Accounting', 'gta', '#c5a059', '#050505')");
        $firm_id = $pdo->lastInsertId();
        
        // Initial user
        $password = password_hash('ELK_GTA_2026!', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (firm_id, email, password_hash, name, role) 
                   VALUES ($firm_id, 'jamie@elk.digital', '$password', 'Jamie Elkins', 'admin')");
    }

    echo "Database setup completed successfully.";

} catch (Exception $e) {
    echo "Error setting up database: " . $e->getMessage();
}
