<?php
/**
 * SHM PANEL - DATABASE REPAIR UTILITY
 */

define('INSTALLER_RUNNING', true);
require_once __DIR__ . '/config.php';
$schema = require __DIR__ . '/db_schema.php';

echo "Starting database repair/sync...\n";

foreach ($schema as $table => $sql) {
    // 1. Check if table exists
    $exists = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();

    if (!$exists) {
        echo "⚠ Table `$table` is missing. Creating...\n";
        try {
            $pdo->exec($sql);
            echo "✔ Table `$table` created.\n";
        } catch (PDOException $e) {
            echo "✘ Failed to create table `$table`: " . $e->getMessage() . "\n";
        }
        continue;
    }

    // 2. Simple Column Sync (Check for missing columns from the CREATE statement)
    // Extract column names from the CREATE TABLE statement
    preg_match_all("/^\s+([a-z0-9_]+)\s+/im", $sql, $matches);
    $expected_cols = $matches[1];

    // Get current columns
    $current_cols = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($expected_cols as $col) {
        // Skip keywords that might have been caught by regex
        if (in_array(strtoupper($col), ['PRIMARY', 'FOREIGN', 'UNIQUE', 'INDEX', 'KEY', 'CONSTRAINT', 'ENGINE', 'DEFAULT', 'CHARSET', 'COLLATE']))
            continue;

        if (!in_array($col, $current_cols)) {
            echo "⚠ Column `$col` is missing in table `$table`. Attempting to add...\n";

            // Extract the specific column definition line
            preg_match("/^\s+($col\s+.*?),?$/im", $sql, $col_match);
            if (isset($col_match[1])) {
                $col_def = rtrim($col_match[1], ',');
                try {
                    $pdo->exec("ALTER TABLE `$table` ADD COLUMN $col_def");
                    echo "✔ Column `$col` added to `$table`.\n";
                } catch (PDOException $e) {
                    echo "✘ Failed to add column `$col` to `$table`: " . $e->getMessage() . "\n";
                }
            } else {
                echo "✘ Could not find definition for column `$col` in schema.\n";
            }
        }
    }
}

echo "Database repair/sync completed.\n";
