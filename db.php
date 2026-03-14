<?php
/**
 * ELK Valuations - Database Connection
 * Connects to Google Cloud SQL via Private IP
 */

class DB {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $host = '10.141.0.3'; // Your Cloud SQL Private IP
        $db   = 'valuations_platform';
        $user = 'elk_admin';
        $pass = 'ELK_Admin_Password_2026!'; // We will move this to env var soon
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new DB();
        }
        return self::$instance->pdo;
    }
}
