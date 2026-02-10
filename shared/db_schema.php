<?php
/**
 * SHM PANEL - DATABASE SCHEMA DEFINITION
 */

return [
    "clients" => "
        CREATE TABLE IF NOT EXISTS clients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(32) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            fullname VARCHAR(255),
            status ENUM('active','suspended','pending') DEFAULT 'active',
            package_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "domains" => "
        CREATE TABLE IF NOT EXISTS domains (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            domain VARCHAR(255) UNIQUE NOT NULL,
            document_root VARCHAR(500),
            php_version VARCHAR(10) DEFAULT '8.2',
            ssl_active BOOLEAN DEFAULT 0,
            status ENUM('active','suspended') DEFAULT 'active',
            parent_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
            INDEX idx_client_id (client_id),
            INDEX idx_domain (domain)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "packages" => "
        CREATE TABLE IF NOT EXISTS packages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50),
            price DECIMAL(10,2) DEFAULT 0.00,
            disk_mb INT,
            max_domains INT,
            max_emails INT,
            max_databases INT DEFAULT 5,
            max_bandwidth_mb INT DEFAULT 10240,
            features TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "transactions" => "
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT,
            amount DECIMAL(10,2),
            currency VARCHAR(10),
            payment_gateway VARCHAR(20),
            transaction_id VARCHAR(100),
            status VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "admins" => "
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(255),
            role ENUM('superadmin','admin','moderator') DEFAULT 'admin',
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "mail_domains" => "
        CREATE TABLE IF NOT EXISTS mail_domains (
            id INT AUTO_INCREMENT PRIMARY KEY,
            domain VARCHAR(255) UNIQUE NOT NULL,
            client_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "mail_users" => "
        CREATE TABLE IF NOT EXISTS mail_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            domain_id INT NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            quota_mb INT DEFAULT 1024,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (domain_id) REFERENCES mail_domains(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "ftp_users" => "
        CREATE TABLE IF NOT EXISTS ftp_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            userid VARCHAR(32) UNIQUE NOT NULL,
            passwd VARCHAR(255) NOT NULL,
            homedir VARCHAR(500) NOT NULL,
            uid INT DEFAULT 33,
            gid INT DEFAULT 33,
            shell VARCHAR(255) DEFAULT '/sbin/nologin',
            client_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "client_databases" => "
        CREATE TABLE IF NOT EXISTS client_databases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            db_name VARCHAR(64) UNIQUE NOT NULL,
            db_size_mb INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "client_db_users" => "
        CREATE TABLE IF NOT EXISTS client_db_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            db_user VARCHAR(32) NOT NULL,
            db_pass VARCHAR(255) NOT NULL,
            permissions TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "dns_records" => "
        CREATE TABLE IF NOT EXISTS dns_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            domain_id INT NOT NULL,
            type VARCHAR(10) NOT NULL,
            host VARCHAR(255) NOT NULL,
            value VARCHAR(500) NOT NULL,
            priority INT DEFAULT 10,
            ttl INT DEFAULT 86400,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE,
            INDEX idx_domain_id (domain_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "php_config" => "
        CREATE TABLE IF NOT EXISTS php_config (
            domain_id INT PRIMARY KEY,
            memory_limit VARCHAR(10) DEFAULT '512M',
            max_execution_time INT DEFAULT 300,
            upload_max_filesize VARCHAR(10) DEFAULT '512M',
            post_max_size VARCHAR(10) DEFAULT '512M',
            FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "domain_traffic" => "
        CREATE TABLE IF NOT EXISTS domain_traffic (
            id INT AUTO_INCREMENT PRIMARY KEY,
            domain_id INT NOT NULL,
            date DATE NOT NULL,
            bytes_sent BIGINT DEFAULT 0,
            hits INT DEFAULT 0,
            bandwidth_mb INT DEFAULT 0,
            UNIQUE KEY (domain_id, date),
            FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "malware_scans" => "
        CREATE TABLE IF NOT EXISTS malware_scans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            domain_id INT NOT NULL,
            status ENUM('running','clean','infected','failed') DEFAULT 'running',
            report TEXT,
            infected_files INT DEFAULT 0,
            scanned_files INT DEFAULT 0,
            scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "app_installations" => "
        CREATE TABLE IF NOT EXISTS app_installations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            domain_id INT NOT NULL,
            app_type VARCHAR(20) NOT NULL,
            db_name VARCHAR(64),
            db_user VARCHAR(32),
            db_pass VARCHAR(255),
            version VARCHAR(20),
            status VARCHAR(20) DEFAULT 'installing',
            installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
            FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "server_metrics" => "
        CREATE TABLE IF NOT EXISTS server_metrics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cpu_percent DECIMAL(5,2),
            memory_percent DECIMAL(5,2),
            disk_percent DECIMAL(5,2),
            load_avg DECIMAL(10,2),
            recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_recorded_at (recorded_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "api_logs" => "
        CREATE TABLE IF NOT EXISTS api_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            endpoint VARCHAR(255),
            method VARCHAR(10),
            ip_address VARCHAR(45),
            user_agent TEXT,
            response_time_ms INT,
            status_code INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_address (ip_address),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "security_logs" => "
        CREATE TABLE IF NOT EXISTS security_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50),
            severity ENUM('info','warning','critical'),
            source_ip VARCHAR(45),
            user_id INT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_type (event_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    "backups" => "
        CREATE TABLE IF NOT EXISTS backups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT,
            type ENUM('full','database','files'),
            filename VARCHAR(255),
            size_mb INT,
            location VARCHAR(500),
            encrypted BOOLEAN DEFAULT 0,
            status ENUM('completed','failed','in_progress'),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
];
