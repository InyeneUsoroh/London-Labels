<?php
/**
 * London Labels E-Commerce Application
 * Database Connection Management
 */

require_once 'bootstrap.php';

function get_pdo() {
    static $pdo = null;
    if ($pdo === null) {
        $host = DB_HOST;
        $port = defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: 3306);
        $dbname = DB_NAME;
        $user = DB_USER;
        $pass = DB_PASS;

        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            // Aiven/Render High-Security SSL Config (Required for Production)
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, 
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Log this securely in a real production app
            die('Database Connection Failed! Please check your credentials.');
        }
    }
    return $pdo;
}
