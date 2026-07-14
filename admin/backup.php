<?php
$page_title = 'Backup & Restore';
require_once '../includes/header.php';
require_role('admin');

// ============================================
// CREATE BACKUP DIRECTORY
// ============================================
$backup_dir = __DIR__ . '/../backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

$db_backup_dir = $backup_dir . 'database/';
$file_backup_dir = $backup_dir . 'files/';
if (!is_dir($db_backup_dir)) mkdir($db_backup_dir, 0777, true);
if (!is_dir($file_backup_dir)) mkdir($file_backup_dir, 0777, true);

// ============================================
// HANDLE DATABASE BACKUP
// ============================================
if (isset($_GET['backup_db'])) {
    $backup_name = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_path = $db_backup_dir . $backup_name;
    
    // Get all tables
    $tables = [];
    $result = $mysqli->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    $sql_content = "-- ============================================\n";
    $sql_content .= "-- Database Backup\n";
    $sql_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql_content .= "-- Tables: " . implode(', ', $tables) . "\n";
    $sql_content .= "-- ============================================\n\n";
    $sql_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        $create_result = $mysqli->query("SHOW CREATE TABLE $table");
        $create_row = $create_result->fetch_row();
        $sql_content .= "-- Table: $table\n";
        $sql_content .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql_content .= $create_row[1] . ";\n\n";
        
        $data_result = $mysqli->query("SELECT * FROM $table");
        if ($data_result && $data_result->num_rows > 0) {
            $sql_content .= "INSERT INTO `$table` VALUES\n";
            $rows = [];
            while ($row = $data_result->fetch_row()) {
                $values = array_map(function($val) use ($mysqli) {
                    if ($val === null) return 'NULL';
                    return "'" . $mysqli->real_escape_string($val) . "'";
                }, $row);
                $rows[] = "(" . implode(', ', $values) . ")";
            }
            $sql_content .= implode(",\n", $rows) . ";\n\n";
        }
    }
    
    $sql_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    if (file_put_contents($backup_path, $sql_content)) {
        $log_entry = date('Y-m-d H:i:s') . " | Database backup created: $backup_name (" . number_format(filesize($backup_path) / 1024, 2) . " KB)\n";
        file_put_contents($backup_dir . 'backup_log.txt', $log_entry, FILE_APPEND);
        flash('Database backup created successfully!', 'success');
    } else {
        flash('Failed to create database backup.', 'danger');
    }
    redirect('admin/backup.php');
}

// ============================================
// HANDLE FILE BACKUP - SIMPLIFIED & FIXED
// ============================================
if (isset($_GET['backup_files'])) {
    $backup_name = 'files_backup_' . date('Y-m-d_H-i-s') . '.zip';
    $backup_path = $file_backup_dir . $backup_name;
    
    if (!class_exists('ZipArchive')) {
        flash('ZipArchive extension is not installed. Please enable it in php.ini', 'danger');
        redirect('admin/backup.php');
    }
    
    $zip = new ZipArchive();
    if ($zip->open($backup_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        flash('Failed to create ZIP file. Check folder permissions.', 'danger');
        redirect('admin/backup.php');
    }
    
    // Get the actual root directory
    $root_path = realpath(__DIR__ . '/../') . '/';
    
    // Folders to backup
    $folders_to_backup = [
        'uploads',
        'assets',
        'includes',
        'admin',
        'seller',
        'api'
    ];
    
    $file_count = 0;
    
    foreach ($folders_to_backup as $folder) {
        $full_path = $root_path . $folder;
        
        if (is_dir($full_path)) {
            // Add folder to zip
            $zip->addEmptyDir($folder);
            
            // Scan all files in folder
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($full_path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($files as $file) {
                $file_path = $file->getPathname();
                // Get relative path from root
                $relative_path = str_replace($root_path, '', $file_path);
                
                if (is_dir($file_path)) {
                    $zip->addEmptyDir($relative_path);
                } else {
                    if (is_readable($file_path)) {
                        $zip->addFile($file_path, $relative_path);
                        $file_count++;
                    }
                }
            }
        }
    }
    
    // Also add important root files
    $root_files = [
        'index.php', 'login.php', 'register.php', 'logout.php',
        'profile.php', 'shop.php', 'cart.php', 'checkout.php',
        'orders.php', 'product.php', 'about.php', 'contact.php',
        'faq.php', 'blog.php', 'blog_post.php', 'terms.php',
        'privacy.php', 'return_policy.php', 'shipping_info.php',
        'support.php', 'ai_assistant.php', 'wishlist.php',
        'manual.php', 'become_seller.php', 'seller.php',
        '.htaccess'
    ];
    
    foreach ($root_files as $file) {
        $file_path = $root_path . $file;
        if (file_exists($file_path) && is_readable($file_path)) {
            $zip->addFile($file_path, $file);
            $file_count++;
        }
    }
    
    $zip->close();
    
    if (file_exists($backup_path) && filesize($backup_path) > 0) {
        $log_entry = date('Y-m-d H:i:s') . " | File backup created: $backup_name (" . number_format(filesize($backup_path) / 1024, 2) . " KB) - $file_count files\n";
        file_put_contents($backup_dir . 'backup_log.txt', $log_entry, FILE_APPEND);
        flash("File backup created successfully! ($file_count files)", 'success');
    } else {
        flash('Failed to create file backup. Please check folder permissions.', 'danger');
    }
    redirect('admin/backup.php');
}

// ============================================
// HANDLE RESTORE DATABASE
// ============================================
if (isset($_GET['restore']) && isset($_GET['file'])) {
    $file = sanitize($_GET['file']);
    $backup_path = $db_backup_dir . $file;
    
    if (!file_exists($backup_path)) {
        flash('Backup file not found.', 'danger');
        redirect('admin/backup.php');
    }
    
    if (!isset($_GET['confirm'])) {
        flash('Please confirm restore by clicking the restore button again.', 'warning');
        redirect('admin/backup.php');
    }
    
    $sql = file_get_contents($backup_path);
    $queries = explode(";\n", $sql);
    
    $success_count = 0;
    $error_count = 0;
    
    $mysqli->query("SET FOREIGN_KEY_CHECKS=0");
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query) && strpos($query, '--') !== 0) {
            if ($mysqli->multi_query($query) === false) {
                $error_count++;
            } else {
                while ($mysqli->next_result()) {;}
                $success_count++;
            }
        }
    }
    
    $mysqli->query("SET FOREIGN_KEY_CHECKS=1");
    
    if ($error_count == 0) {
        flash("Database restored successfully! ($success_count queries executed)", 'success');
    } else {
        flash("Database restore completed with $error_count errors.", 'warning');
    }
    redirect('admin/backup.php');
}

// ============================================
// HANDLE DELETE BACKUP
// ============================================
if (isset($_GET['delete']) && isset($_GET['type']) && isset($_GET['file'])) {
    $type = sanitize($_GET['type']);
    $file = sanitize($_GET['file']);
    
    if ($type == 'db') {
        $path = $db_backup_dir . $file;
    } else if ($type == 'file') {
        $path = $file_backup_dir . $file;
    } else {
        flash('Invalid backup type.', 'danger');
        redirect('admin/backup.php');
    }
    
    if (file_exists($path) && unlink($path)) {
        flash('Backup deleted successfully.', 'success');
    } else {
        flash('Failed to delete backup.', 'danger');
    }
    redirect('admin/backup.php');
}

// ============================================
// HANDLE DOWNLOAD BACKUP
// ============================================
if (isset($_GET['download']) && isset($_GET['type']) && isset($_GET['file'])) {
    $type = sanitize($_GET['type']);
    $file = basename(sanitize($_GET['file']));
    
    if ($type == 'db') {
        $path = $db_backup_dir . $file;
        $mime = 'application/sql';
    } else if ($type == 'file') {
        $path = $file_backup_dir . $file;
        $mime = 'application/zip';
    } else {
        flash('Invalid backup type.', 'danger');
        redirect('admin/backup.php');
    }
    
    if (file_exists($path) && is_readable($path)) {
        if (ob_get_level()) ob_end_clean();
        
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        readfile($path);
        exit;
    } else {
        flash('Backup file not found or not readable.', 'danger');
        redirect('admin/backup.php');
    }
}

// ============================================
// GET BACKUP FILES
// ============================================
$db_backups = [];
$file_backups = [];

if (is_dir($db_backup_dir)) {
    $db_files = scandir($db_backup_dir);
    if ($db_files) {
        foreach ($db_files as $file) {
            if ($file != '.' && $file != '..') {
                $file_path = $db_backup_dir . $file;
                $db_backups[] = [
                    'name' => $file,
                    'size' => filesize($file_path),
                    'modified' => filemtime($file_path)
                ];
            }
        }
    }
}

if (is_dir($file_backup_dir)) {
    $file_files = scandir($file_backup_dir);
    if ($file_files) {
        foreach ($file_files as $file) {
            if ($file != '.' && $file != '..') {
                $file_path = $file_backup_dir . $file;
                $file_backups[] = [
                    'name' => $file,
                    'size' => filesize($file_path),
                    'modified' => filemtime($file_path)
                ];
            }
        }
    }
}

usort($db_backups, function($a, $b) {
    return $b['modified'] - $a['modified'];
});
usort($file_backups, function($a, $b) {
    return $b['modified'] - $a['modified'];
});

$total_space = disk_total_space(dirname(__DIR__));
$free_space = disk_free_space(dirname(__DIR__));
$used_space = $total_space - $free_space;
$usage_percent = ($used_space / $total_space) * 100;

$log_file = $backup_dir . 'backup_log.txt';
$logs = [];
if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    $logs = array_reverse(array_filter(explode("\n", $log_content)));
    $logs = array_slice($logs, 0, 20);
}
?>

<style>
    .backup-wrapper {
        display: flex;
        gap: 25px;
        min-height: calc(100vh - 200px);
    }
    .backup-sidebar {
        width: 280px;
        flex-shrink: 0;
    }
    .backup-content {
        flex: 1;
    }
    
    .backup-card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        margin-bottom: 25px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 25px;
    }
    
    .stat-box {
        background: white;
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    
    .stat-box .number {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1f2937;
    }
    
    .stat-box .label {
        font-size: 0.8rem;
        color: #6b7280;
        margin-top: 4px;
    }
    
    .stat-box .icon {
        font-size: 1.5rem;
        margin-bottom: 8px;
    }
    .stat-box.db .icon { color: #2563eb; }
    .stat-box.files .icon { color: #10b981; }
    .stat-box.space .icon { color: #f59e0b; }
    .stat-box.logs .icon { color: #7c3aed; }
    
    .btn-backup {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-backup:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        color: white;
    }
    
    .btn-backup-file {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-backup-file:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        color: white;
    }
    
    .btn-restore {
        background: #f59e0b;
        color: white;
        border: none;
        padding: 4px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.7rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }
    
    .btn-restore:hover {
        background: #d97706;
        transform: translateY(-1px);
    }
    
    .btn-delete {
        background: #ef4444;
        color: white;
        border: none;
        padding: 4px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.7rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }
    
    .btn-delete:hover {
        background: #dc2626;
        transform: translateY(-1px);
    }
    
    .btn-download {
        background: #2563eb;
        color: white;
        border: none;
        padding: 4px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.7rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }
    
    .btn-download:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
    }
    
    .backup-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .backup-table th,
    .backup-table td {
        padding: 10px 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        font-size: 0.85rem;
    }
    
    .backup-table th {
        background: #f8fafc;
        font-weight: 600;
        color: #1f2937;
    }
    
    .backup-table tr:hover td {
        background: #f8fafc;
    }
    
    .file-size {
        font-size: 0.8rem;
        color: #6b7280;
    }
    
    .backup-date {
        font-size: 0.8rem;
        color: #6b7280;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #6b7280;
    }
    
    .empty-state i {
        font-size: 2.5rem;
        color: #d1d5db;
        margin-bottom: 10px;
    }
    
    .disk-usage {
        margin-top: 15px;
        background: #f3f4f6;
        border-radius: 10px;
        height: 10px;
        overflow: hidden;
    }
    
    .disk-usage .fill {
        height: 100%;
        border-radius: 10px;
        background: linear-gradient(90deg, #2563eb, #60a5fa);
        transition: width 0.5s ease;
    }
    
    .disk-usage .fill.danger {
        background: linear-gradient(90deg, #ef4444, #f87171);
    }
    
    .disk-usage .fill.warning {
        background: linear-gradient(90deg, #f59e0b, #fbbf24);
    }
    
    .log-item {
        font-size: 0.8rem;
        padding: 6px 10px;
        border-bottom: 1px solid #f1f5f9;
        color: #4b5563;
        font-family: monospace;
    }
    
    .log-item:last-child {
        border-bottom: none;
    }
    
    @media (max-width: 992px) {
        .backup-wrapper { flex-direction: column; }
        .backup-sidebar { width: 100%; }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
    
    @media (max-width: 576px) {
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .backup-table { font-size: 0.75rem; }
        .backup-table th, .backup-table td { padding: 6px 8px; }
    }
</style>

<div class="container-fluid">
    <div class="backup-wrapper">
        <div class="backup-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="backup-content">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h2><i class="fa-solid fa-database"></i> Backup & Restore</h2>
                <div class="d-flex gap-2">
                    <a href="?backup_db=1" class="btn-backup" onclick="return confirm('Create database backup?')">
                        <i class="fa-solid fa-database"></i> Backup Database
                    </a>
                    <a href="?backup_files=1" class="btn-backup-file" onclick="return confirm('Create file backup? This may take a moment.')">
                        <i class="fa-solid fa-folder"></i> Backup Files
                    </a>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-box db">
                    <div class="icon"><i class="fa-solid fa-database"></i></div>
                    <div class="number"><?= count($db_backups) ?></div>
                    <div class="label">Database Backups</div>
                </div>
                <div class="stat-box files">
                    <div class="icon"><i class="fa-solid fa-folder"></i></div>
                    <div class="number"><?= count($file_backups) ?></div>
                    <div class="label">File Backups</div>
                </div>
                <div class="stat-box space">
                    <div class="icon"><i class="fa-solid fa-hard-drive"></i></div>
                    <div class="number"><?= round($usage_percent) ?>%</div>
                    <div class="label">Disk Usage</div>
                </div>
                <div class="stat-box logs">
                    <div class="icon"><i class="fa-regular fa-clock"></i></div>
                    <div class="number"><?= count($logs) ?></div>
                    <div class="label">Recent Logs</div>
                </div>
            </div>
            
            <div class="backup-card">
                <h5><i class="fa-solid fa-hard-drive"></i> Disk Space</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="disk-usage">
                            <div class="fill <?= $usage_percent > 80 ? 'danger' : ($usage_percent > 60 ? 'warning' : '') ?>" style="width: <?= min($usage_percent, 100) ?>%;"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <span class="text-muted small">Used: <?= round($used_space / (1024 * 1024 * 1024), 2) ?> GB</span>
                            <span class="text-muted small">Free: <?= round($free_space / (1024 * 1024 * 1024), 2) ?> GB</span>
                            <span class="text-muted small">Total: <?= round($total_space / (1024 * 1024 * 1024), 2) ?> GB</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-3 flex-wrap">
                            <div>
                                <span class="badge bg-primary">Database Backups</span>
                                <span class="ms-2"><?= count($db_backups) ?> files</span>
                            </div>
                            <div>
                                <span class="badge bg-success">File Backups</span>
                                <span class="ms-2"><?= count($file_backups) ?> files</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Database Backups -->
            <div class="backup-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fa-solid fa-database"></i> Database Backups</h5>
                    <span class="badge bg-primary rounded-pill"><?= count($db_backups) ?> backups</span>
                </div>
                
                <?php if (!empty($db_backups)): ?>
                <div class="table-responsive">
                    <table class="backup-table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Size</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($db_backups as $backup): ?>
                            <tr>
                                <td><code><?= $backup['name'] ?></code></td>
                                <td class="file-size"><?= number_format($backup['size'] / 1024, 2) ?> KB</td>
                                <td class="backup-date"><?= date('M d, Y h:i A', $backup['modified']) ?></td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <a href="?download=1&type=db&file=<?= urlencode($backup['name']) ?>" class="btn-download" title="Download">
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                        <a href="?restore=1&file=<?= urlencode($backup['name']) ?>" class="btn-restore" onclick="return confirm('Restore this database backup? All current data will be replaced.')" title="Restore">
                                            <i class="fa-solid fa-rotate"></i> Restore
                                        </a>
                                        <a href="?delete=1&type=db&file=<?= urlencode($backup['name']) ?>" class="btn-delete" onclick="return confirm('Delete this backup?')" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fa-regular fa-database"></i>
                    <p>No database backups found.</p>
                    <a href="?backup_db=1" class="btn-backup" style="display: inline-block;">Create First Backup</a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- File Backups -->
            <div class="backup-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fa-solid fa-folder"></i> File Backups</h5>
                    <span class="badge bg-success rounded-pill"><?= count($file_backups) ?> backups</span>
                </div>
                
                <?php if (!empty($file_backups)): ?>
                <div class="table-responsive">
                    <table class="backup-table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Size</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($file_backups as $backup): ?>
                            <tr>
                                <td><code><?= $backup['name'] ?></code></td>
                                <td class="file-size"><?= number_format($backup['size'] / 1024, 2) ?> KB</td>
                                <td class="backup-date"><?= date('M d, Y h:i A', $backup['modified']) ?></td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <a href="?download=1&type=file&file=<?= urlencode($backup['name']) ?>" class="btn-download" title="Download">
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                        <a href="?delete=1&type=file&file=<?= urlencode($backup['name']) ?>" class="btn-delete" onclick="return confirm('Delete this backup?')" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fa-regular fa-folder"></i>
                    <p>No file backups found.</p>
                    <a href="?backup_files=1" class="btn-backup-file" style="display: inline-block;">Create First Backup</a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="backup-card">
                <h5><i class="fa-regular fa-clock"></i> Recent Activity Log</h5>
                <?php if (!empty($logs)): ?>
                <div style="max-height: 200px; overflow-y: auto; background: #f8fafc; border-radius: 8px; padding: 10px;">
                    <?php foreach ($logs as $log): ?>
                        <?php if (!empty($log)): ?>
                            <div class="log-item"><?= htmlspecialchars($log) ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state" style="padding: 20px;">
                    <p>No backup activity logged yet.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="backup-card" style="background: #fef3c7; border-color: #f59e0b;">
                <h5><i class="fa-regular fa-circle-info"></i> Backup Tips</h5>
                <ul style="margin: 0; padding-left: 20px; color: #6b7280;">
                    <li>Create regular backups to protect your data.</li>
                    <li>Database backups include all your product, order, and user data.</li>
                    <li>File backups include uploads, assets, and core files.</li>
                    <li>Download backups to your computer for extra safety.</li>
                    <li>Be careful when restoring - it will overwrite current data.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>