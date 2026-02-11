# SHM Panel - Professional Hosting Control Panel

**SHM Panel** is a lightweight, custom-built Web Hosting Control Panel designed for performance and security. It provides a two-tiered management system:
- **WHM (Web Host Manager)**: For server administrators to manage accounts, packages, and server health.
- **cPanel (Client Panel)**: For end-users to manage domains, emails, databases, and files.

---

## 🚀 Technology Stack
- **Backend**: Native PHP 8.1+ (No framework, high performance).
- **Database**: MySQL / MariaDB (PDO).
- **Frontend**: HTML5, Tailwind CSS, JavaScript (ApexCharts, Chart.js).
- **Server**: Nginx, Redis, ProFTPD, Postfix/Dovecot.
- **OS**: Ubuntu 20.04+ / Debian 11+.

---

## 🛠️ Installation Guide

### Option 1: Production (Linux Server)
**Best for**: Live servers (VPS/Dedicated).
**Requires**: Root access on a fresh Ubuntu 20.04+ install.

1.  **Upload the Project**: Copy all files to `/root/shm-panel/`.
2.  **Run Installer**:
    ```bash
    chmod +x install.sh
    sudo ./install.sh
    ```
3.  **Follow Prompts**: Enter your Main Domain and Admin Email.
4.  **Done**: The script installs the entire stack (LEMP, Mail, DNS) and outputs your admin credentials.

### Option 2: Local Development (Windows/Mac)
**Best for**: Development, UI testing, and customization.
**Requires**: XAMPP, WAMP, or MAMP.

1.  **Setup**: Place the `shm-panel` folder in your web server's root (e.g., `htdocs`).
2.  **Run Web Installer**:
    - Open your browser to: `http://localhost/shm-panel/install.php`
3.  **Configure**:
    - **DB Host**: `localhost`
    - **DB User/Pass**: Your local MySQL credentials.
    - **DB Name**: `shm_panel`
4.  **Install**: Click **Install SHM Panel**.
    - This creates the database and `shared/config.local.php`.
    - **Note**: System commands (like restarting services) are **mocked** on Windows to allow UI development without a Linux backend.

---

## 🔧 Management Commands (Production Only)

The system uses the `shm-manage` CLI tool for root-level operations.

| Command | Description |
| :--- | :--- |
| `sudo shm-manage vhost-tool sync-all` | Rebuilds all VirtualHost configs (Nginx). |
| `sudo shm-manage fix-permissions <user>` | Fixes ownership/perms for a client account. |
| `sudo shm-manage delete-account <user>` | Permanently removes a client account. |
| `sudo shm-manage dns-tool sync <domain_id>` | Refreshes DNS zones for a domain. |

---

## 📁 Directory Structure

| Directory | Purpose |
| :--- | :--- |
| **`cpanel/`** | Client Interface (Dashboard, File Manager, Domain Manager). |
| **`whm/`** | Admin Interface (Server Stats, Account Managment). |
| **`shared/`** | Core Logic (`config.php`, `db_helper.php`, Schema). |
| **`landing/`** | Public facing sales/landing page. |
| **`shm-manage`** | Backend binary/script for system operations. |

---

## 🔒 Security Features
- **Isolation**: Each client runs under a separate system user.
- **Mock Bridge**: Securely separates PHP frontend from Root backend operations.
- **Hardening**: Auto-configured Firewall (UFW) and Fail2ban rules.
