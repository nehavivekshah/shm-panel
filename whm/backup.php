<?php
require_once __DIR__ . '/../shared/config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];

    try {
        if ($action == 'create_backup') {
            $user = $_POST['user'];
            $type = $_POST['type'] ?? 'full';

            // cmd() wrapper in config.php handles execution
            // Command format: backup create USER TYPE
            $res = cmd("backup create " . escapeshellarg($user) . " " . escapeshellarg($type));
            
            // Check output for success
            if (strpos($res, 'Backup created') !== false) {
                 echo json_encode(['status' => 'success', 'msg' => "Backup started/created for $user"]);
            } else {
                 throw new Exception("Backup failed: " . $res);
            }
            exit;
        }

        if ($action == 'list_backups') {
            $user = $_POST['user'];
            // Command format: backup list USER
            $output = cmd("backup list " . escapeshellarg($user));
            
            $backups = [];
            if (!empty($output) && strpos($output, 'No backups found') === false) {
                $lines = explode("\n", trim($output));
                foreach ($lines as $line) {
                    // Expected: filename|size|date
                    // But wait, cmd() in config.php for Windows might return mock data in a different format
                    // Standard Linux `find` output from shm-manage is: filename|size|date
                    
                    $parts = explode('|', $line);
                    if (count($parts) >= 3) {
                        $backups[] = [
                            'filename' => $parts[0],
                            'size' => $parts[1],
                            'date' => $parts[2]
                        ];
                    } elseif (count($parts) == 1 && !empty($parts[0])) {
                         // Fallback for simple list or mock
                         $backups[] = [
                            'filename' => $parts[0],
                            'size' => 'Unknown',
                            'date' => 'Unknown'
                        ];
                    }
                }
            }
            
            echo json_encode(['status' => 'success', 'data' => $backups]);
            exit;
        }

        if ($action == 'delete_backup') {
            $user = $_POST['user'];
            $file = $_POST['file'];
            
            // Command format: backup delete USER FILENAME
            $res = cmd("backup delete " . escapeshellarg($user) . " " . escapeshellarg($file));
            
            if (strpos($res, 'Backup deleted') !== false) {
                echo json_encode(['status' => 'success', 'msg' => 'Backup deleted']);
            } else {
                throw new Exception("Delete failed: " . $res);
            }
            exit;
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}

// Data Handling
$clients = $pdo->query("SELECT * FROM clients")->fetchAll(PDO::FETCH_ASSOC);

include 'layout/header.php';
?>

<div class="mb-8">
    <h2 class="text-2xl font-bold text-white mb-2">Backup Manager</h2>
    <p class="text-slate-400 text-sm">Create and manage backups for client accounts.</p>
</div>

<!-- ACTIONS GRID -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    
    <!-- CREATE BACKUP CARD -->
    <div class="glass-panel p-8 rounded-3xl relative overflow-hidden lg:col-span-1">
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-600/10 rounded-full blur-3xl"></div>
        
        <h3 class="text-xl font-bold mb-6 flex items-center gap-3 text-white font-heading">
            <div class="p-2 bg-indigo-500/10 rounded-lg border border-indigo-500/20 text-indigo-500">
                <i data-lucide="archive" class="w-5 h-5"></i>
            </div>
            Create New Backup
        </h3>

        <form onsubmit="handleCreateBackup(event)" class="space-y-4 relative z-10">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Select Client</label>
                <select id="user-select" name="user" required onchange="loadBackups()"
                    class="w-full bg-slate-900/50 p-4 rounded-xl border border-slate-700 text-slate-300 outline-none focus:border-indigo-500 focus:bg-slate-900 transition">
                    <option value="" disabled selected>Choose a client...</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['username'] ?>"><?= $c['username'] ?> (<?= $c['email'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Backup Type</label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="full" checked class="peer sr-only">
                        <div class="p-3 rounded-xl border border-slate-700 bg-slate-900/30 text-center peer-checked:border-indigo-500 peer-checked:bg-indigo-500/10 peer-checked:text-white transition hover:bg-slate-800">
                            <i data-lucide="layers" class="w-5 h-5 mx-auto mb-1"></i>
                            <span class="text-xs font-bold">Full</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="db" class="peer sr-only">
                        <div class="p-3 rounded-xl border border-slate-700 bg-slate-900/30 text-center peer-checked:border-indigo-500 peer-checked:bg-indigo-500/10 peer-checked:text-white transition hover:bg-slate-800">
                            <i data-lucide="database" class="w-5 h-5 mx-auto mb-1"></i>
                            <span class="text-xs font-bold">DB</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="files" class="peer sr-only">
                        <div class="p-3 rounded-xl border border-slate-700 bg-slate-900/30 text-center peer-checked:border-indigo-500 peer-checked:bg-indigo-500/10 peer-checked:text-white transition hover:bg-slate-800">
                            <i data-lucide="folder" class="w-5 h-5 mx-auto mb-1"></i>
                            <span class="text-xs font-bold">Files</span>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-500 py-3.5 rounded-xl font-bold mt-4 shadow-lg shadow-indigo-600/20 text-white transition border border-indigo-500/50">
                Start Backup Process
            </button>
        </form>
    </div>

    <!-- EXISTING BACKUPS LIST -->
    <div class="glass-panel p-8 rounded-3xl relative overflow-hidden lg:col-span-2 flex flex-col h-full">
        <h3 class="text-xl font-bold mb-6 text-white font-heading">Existing Backups</h3>
        
        <div class="overflow-y-auto flex-1 custom-scrollbar max-h-[500px]">
            <table class="w-full text-left">
                <thead class="bg-slate-900/50 text-[10px] font-bold uppercase text-slate-400 sticky top-0 backdrop-blur-md">
                    <tr>
                        <th class="p-4 rounded-tl-xl">Filename</th>
                        <th class="p-4">Size</th>
                        <th class="p-4">Date</th>
                        <th class="p-4 text-right rounded-tr-xl">Actions</th>
                    </tr>
                </thead>
                <tbody id="backup-list" class="divide-y divide-slate-700/50">
                    <tr>
                        <td colspan="4" class="p-8 text-center text-slate-500 italic">Select a client to view backups.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>

<script>
    // Format bytes to human readable
    function formatBytes(bytes, decimals = 2) {
        if (!+bytes) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
    }

    async function loadBackups() {
        const user = document.getElementById('user-select').value;
        const list = document.getElementById('backup-list');
        
        if (!user) {
            list.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-slate-500 italic">Select a client to view backups.</td></tr>';
            return;
        }

        list.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-slate-500"><i data-lucide="loader-2" class="w-6 h-6 animate-spin mx-auto mb-2 text-indigo-500"></i>Loading backups...</td></tr>';
        lucide.createIcons();

        const fd = new FormData(); 
        fd.append('ajax_action', 'list_backups');
        fd.append('user', user);

        try {
            const response = await fetch('', { method: 'POST', body: fd });
            const res = await response.json();
            
            list.innerHTML = '';
            
            if (res.status === 'success' && res.data && res.data.length > 0) {
                res.data.forEach(b => {
                    // Try to parse size if it's strictly numeric bytes, otherwise display as is
                    const displaySize = isNaN(b.size) ? b.size : formatBytes(b.size);
                    
                    list.innerHTML += `
                        <tr class="hover:bg-slate-800/30 transition group border-b border-slate-800/50 last:border-0">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded bg-slate-800 text-indigo-400">
                                        <i data-lucide="file-archive" class="w-4 h-4"></i>
                                    </div>
                                    <span class="font-mono text-sm text-slate-200 font-medium">${b.filename}</span>
                                </div>
                            </td>
                            <td class="p-4 text-sm text-slate-400">${displaySize}</td>
                            <td class="p-4 text-sm text-slate-400">${b.date}</td>
                            <td class="p-4 text-right">
                                <button onclick="deleteBackup('${user}', '${b.filename}')" 
                                    class="p-2 hover:bg-red-500/10 text-slate-500 hover:text-red-500 rounded-lg transition" title="Delete Backup">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                     `;
                });
                lucide.createIcons();
            } else {
                list.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-slate-500">No backups found for this user.</td></tr>';
            }
        } catch (e) {
            console.error(e);
            list.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-red-400">Error loading backups.</td></tr>';
        }
    }

    async function handleCreateBackup(e) {
        e.preventDefault();
        const user = document.getElementById('user-select').value;
        if (!user) {
            showToast('error', 'Please select a client');
            return;
        }

        const btn = e.target.querySelector('button[type="submit"]');
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin inline-block mr-2"></i> Processing...';
        
        // Use FormData
        const fd = new FormData(e.target);
        fd.append('ajax_action', 'create_backup');

        try {
            const response = await fetch('', { method: 'POST', body: fd });
            const res = await response.json();
            
            if (res.status === 'success') {
                showToast('success', res.msg);
                loadBackups(); // Refresh list
            } else {
                showToast('error', res.msg || 'Backup failed');
            }
        } catch (error) {
            showToast('error', 'Server connection error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
        }
    }

    async function deleteBackup(user, file) {
        if (!confirm('Are you sure you want to permanently delete this backup file?')) return;
        
        const fd = new FormData();
        fd.append('ajax_action', 'delete_backup');
        fd.append('user', user);
        fd.append('file', file);
        
        try {
            const response = await fetch('', { method: 'POST', body: fd });
            const res = await response.json();
            
            if (res.status === 'success') {
                showToast('success', 'Backup deleted successfully');
                loadBackups();
            } else {
                showToast('error', res.msg || 'Delete failed');
            }
        } catch (e) {
            showToast('error', 'Server error');
        }
    }
</script>
