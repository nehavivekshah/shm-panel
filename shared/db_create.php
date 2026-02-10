<?php
/**
 * SHM PANEL - DATABASE CREATION UTILITY
 */

define('INSTALLER_RUNNING', true);
require_once __DIR__ . '/config.php';
$schema = require __DIR__ . '/db_schema.php';

echo "Starting database initialization...\n";

// 1. Ensure Database exists
try {
    // Re-connect without DB to ensure it exists
    $pdo_base = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
    $pdo_base->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_base->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✔ Database `$db_name` verified/created.\n";
} catch (PDOException $e) {
    die("✘ Failed to create/verify database: " . $e->getMessage() . "\n");
}

// 2. Connect to the database
try {
    $pdo->exec("USE `$db_name` text");
} catch (PDOException $e) {
    die("✘ Failed to switch to database `$db_name`: " . $e->getMessage() . "\n");
}

// 3. Create Tables
foreach ($schema as $table => $sql) {
    try {
        $pdo->exec($sql);
        echo "✔ Table `$table` verified/created.\n";
    } catch (PDOException $e) {
        echo "✘ Failed to create table `$table`: " . $e->getMessage() . "\n";
    }
}

// 4. Default Data
echo "Installing default data...\n";
$packages_sql = "INSERT IGNORE INTO packages (id, name, price, disk_mb, max_domains, max_emails, max_databases, max_bandwidth_mb, features) VALUES 
(1, 'Starter', 0.00, 2000, 1, 5, 2, 10240, 'Basic Support, 1 Domain, 5 Email Accounts'), 
(2, 'Evolution', 19.99, 10000, 5, 25, 10, 51200, 'Priority Support, 5 Domains, 25 Email Accounts'), 
(3, 'Corporate', 49.99, 50000, 20, 100, 50, 204800, '24/7 Premium Support, 20 Domains, 100 Email Accounts')";

try {
    $pdo->exec($packages_sql);
    echo "✔ Default packages installed.\n";
} catch (PDOException $e) {
    echo "✘ Failed to install default packages: " . $e->getMessage() . "\n";
}

echo "Database initialization completed successfully.\n";
