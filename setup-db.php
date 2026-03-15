<?php
/**
 * ELK Valuations - Database Setup (Migration v2)
 * Initializes or updates firms and users tables.
 */

require_once 'db.php';

try {
    $pdo = DB::getInstance();

    // 1. Create/Update firms table
    $pdo->exec("CREATE TABLE IF NOT EXISTS firms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        logo_url VARCHAR(255),
        primary_color VARCHAR(7) DEFAULT '#c5a059',
        secondary_color VARCHAR(7) DEFAULT '#050505',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // Ensure migration: Add columns if they don't exist
    $columns = $pdo->query("SHOW COLUMNS FROM firms")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('secondary_color', $columns)) {
        $pdo->exec("ALTER TABLE firms ADD COLUMN secondary_color VARCHAR(7) DEFAULT '#050505' AFTER primary_color");
    }
    if (!in_array('logo_url', $columns)) {
        $pdo->exec("ALTER TABLE firms ADD COLUMN logo_url VARCHAR(255) AFTER slug");
    }

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

    // 3. Seed initial data (GTA Accounting)
    $stmt = $pdo->prepare("SELECT id FROM firms WHERE slug = 'gta'");
    $stmt->execute();
    $existing = $stmt->fetch();
    
    if (!$existing) {
        $pdo->exec("INSERT INTO firms (name, slug, primary_color, secondary_color) 
                   VALUES ('GTA Accounting', 'gta', '#c5a059', '#050505')");
        $gta_id = $pdo->lastInsertId();
        
        // Initial user
        $password = password_hash('ELK_GTA_2026!', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (firm_id, email, password_hash, name, role) 
                   VALUES ($gta_id, 'jamie@elk.digital', '$password', 'Jamie Elkins', 'admin')");
    }

    // 4. Seed ELK Digital (Super Admin Firm)
    $stmt = $pdo->prepare("SELECT id FROM firms WHERE slug = 'elk'");
    $stmt->execute();
    $elk = $stmt->fetch();

    if (!$elk) {
        $pdo->exec("INSERT INTO firms (name, slug, primary_color, secondary_color) 
                   VALUES ('ELK Digital', 'elk', '#00ffcc', '#0a0a0a')");
        $elk_id = $pdo->lastInsertId();

        $password = password_hash('ELK_Super_2026!', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (firm_id, email, password_hash, name, role) 
                   VALUES ($elk_id, 'admin@elk.digital', '$password', 'ELK Super Admin', 'admin')");
    }

    echo "Database setup/migration completed successfully.";

} catch (Exception $e) {
    echo "Error setting up database: " . $e->getMessage();
}
