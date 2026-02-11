<?php
/**
 * SHM PANEL - DATABASE HELPER
 * ===========================
 * Common database operations to keep views clean.
 */

if (!isset($pdo)) {
    // Ensure PDO is available if this file is included standalone (though it shouldn't be)
    global $pdo;
}

/**
 * Fetch client data by ID
 */
function get_client_data($cid) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.*, p.name as pkg_name, p.max_emails, p.max_databases, p.max_domains, p.disk_mb 
        FROM clients c 
        JOIN packages p ON c.package_id = p.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$cid]);
    return $stmt->fetch();
}

/**
 * Fetch client domains by ID
 */
function get_client_domains($cid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM domains WHERE client_id = ?");
    $stmt->execute([$cid]);
    return $stmt->fetchAll();
}

/**
 * Fetch client usage statistics
 */
function get_client_usage($cid) {
    global $pdo;
    
    // Databases
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM client_databases WHERE client_id = ?");
        $stmt->execute([$cid]);
        $usage_db = $stmt->fetchColumn();
    } catch (Exception $e) {
        $usage_db = 0;
    }

    // Emails
    // Note: detailed query for emails based on domains owned by client
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM mail_users 
        WHERE domain_id IN (
            SELECT id FROM mail_domains WHERE domain IN (
                SELECT domain FROM domains WHERE client_id = ?
            )
        )
    ");
    $stmt->execute([$cid]);
    $usage_mail = $stmt->fetchColumn();

    return [
        'db' => $usage_db,
        'mail' => $usage_mail
    ];
}

/**
 * Fetch traffic data for the last 7 days
 */
function get_traffic_data($cid) {
    global $pdo;
    
    $traffic_data = $pdo->query("
        SELECT date, SUM(bytes_sent) as total_bytes, SUM(hits) as total_hits 
        FROM domain_traffic 
        WHERE domain_id IN (SELECT id FROM domains WHERE client_id = $cid) 
        AND date >= DATE(NOW() - INTERVAL 7 DAY)
        GROUP BY date 
        ORDER BY date ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Format for JS
    $dates = [];
    $hits = [];
    $bytes = [];

    // Fill missing dates with 0
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $found = false;
        foreach ($traffic_data as $row) {
            if ($row['date'] == $d) {
                $dates[] = date('M d', strtotime($d));
                $hits[] = (int) $row['total_hits'];
                $bytes[] = round($row['total_bytes'] / 1024 / 1024, 2); // MB
                $found = true;
                break;
            }
        }
        if (!$found) {
            $dates[] = date('M d', strtotime($d));
            $hits[] = 0;
            $bytes[] = 0;
        }
    }

    return [
        'dates' => $dates,
        'hits' => $hits,
        'bytes' => $bytes
    ];
}
?>
