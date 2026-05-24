<?php
session_start();
require_once '../config/database_enhanced.php';
require_once '../includes/functions.php';

class AdminController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function addHeritageContent($data, $files) {
        try {
            $filePath = null;
            $videoUrl = null;
            
            // Handle file upload
            if ($data['content_type'] === 'image' || $data['content_type'] === 'document') {
                if (isset($files['heritage_file']) && $files['heritage_file']['error'] === 0) {
                    $filePath = $this->uploadHeritageFile($files['heritage_file'], $data['content_type']);
                    if (!$filePath) {
                        throw new Exception('Failed to upload file');
                    }
                }
            } elseif ($data['content_type'] === 'video_link') {
                $videoUrl = $data['video_url'];
            }
            
            // Insert heritage item
            $stmt = $this->pdo->prepare("
                INSERT INTO heritage_archive (title, description, content_type, file_path, video_url, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['content_type'],
                $filePath,
                $videoUrl,
                $_SESSION['user_id']
            ]);
            
            return ['success' => true, 'message' => 'Heritage content added successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to add heritage content: ' . $e->getMessage()];
        }
    }
    
    public function deleteHeritageContent($heritageId) {
        try {
            // Get heritage item info
            $stmt = $this->pdo->prepare("SELECT * FROM heritage_archive WHERE id = ?");
            $stmt->execute([$heritageId]);
            $heritage = $stmt->fetch();
            
            if (!$heritage) {
                return ['success' => false, 'message' => 'Heritage item not found'];
            }
            
            $this->pdo->beginTransaction();
            
            // Delete file if exists
            if ($heritage['file_path'] && file_exists('../' . $heritage['file_path'])) {
                unlink('../' . $heritage['file_path']);
            }
            
            // Delete from database
            $stmt = $this->pdo->prepare("DELETE FROM heritage_archive WHERE id = ?");
            $stmt->execute([$heritageId]);
            
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Heritage content deleted successfully'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to delete heritage content: ' . $e->getMessage()];
        }
    }
    
    public function createBackup() {
        try {
            $backupDir = '../backups/';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $backupDir . "backup_$timestamp.sql";
            
            // Create database backup
            $command = "mysqldump --user=root --password= --host=localhost ashreka_pottery_system > $backupFile";
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                // Create zip with files
                $zipFile = $backupDir . "full_backup_$timestamp.zip";
                $zip = new ZipArchive();
                
                if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
                    // Add database backup
                    $zip->addFile($backupFile, "database_$timestamp.sql");
                    
                    // Add uploads folder
                    $this->addFolderToZip('../assets/uploads/', $zip, 'uploads/');
                    
                    $zip->close();
                    
                    // Remove SQL file (it's now in the zip)
                    unlink($backupFile);
                    
                    return ['success' => true, 'message' => "Backup created successfully: full_backup_$timestamp.zip"];
                }
            }
            
            return ['success' => false, 'message' => 'Backup creation failed'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Backup failed: ' . $e->getMessage()];
        }
    }
    
    public function clearCache() {
        try {
            // Clear various cache directories
            $cacheDirectories = [
                '../cache/',
                '../tmp/',
                '../assets/cache/'
            ];
            
            $clearedFiles = 0;
            
            foreach ($cacheDirectories as $dir) {
                if (is_dir($dir)) {
                    $files = glob($dir . '*');
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                            $clearedFiles++;
                        }
                    }
                }
            }
            
            // Clear PHP opcache if available
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            
            return ['success' => true, 'message' => "Cache cleared successfully. $clearedFiles files removed."];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Cache clearing failed: ' . $e->getMessage()];
        }
    }
    
    public function systemCheck() {
        try {
            $checks = [];
            
            // Database connection check
            try {
                $this->pdo->query("SELECT 1");
                $checks['database'] = ['status' => 'OK', 'message' => 'Database connection successful'];
            } catch (Exception $e) {
                $checks['database'] = ['status' => 'ERROR', 'message' => 'Database connection failed'];
            }
            
            // File permissions check
            $uploadDir = '../assets/uploads/';
            if (is_writable($uploadDir)) {
                $checks['file_permissions'] = ['status' => 'OK', 'message' => 'Upload directory is writable'];
            } else {
                $checks['file_permissions'] = ['status' => 'WARNING', 'message' => 'Upload directory may not be writable'];
            }
            
            // Disk space check
            $freeBytes = disk_free_space('.');
            $totalBytes = disk_total_space('.');
            $usedPercent = (($totalBytes - $freeBytes) / $totalBytes) * 100;
            
            if ($usedPercent < 80) {
                $checks['disk_space'] = ['status' => 'OK', 'message' => sprintf('Disk usage: %.1f%%', $usedPercent)];
            } else {
                $checks['disk_space'] = ['status' => 'WARNING', 'message' => sprintf('High disk usage: %.1f%%', $usedPercent)];
            }
            
            // PHP version check
            $phpVersion = PHP_VERSION;
            if (version_compare($phpVersion, '7.4.0', '>=')) {
                $checks['php_version'] = ['status' => 'OK', 'message' => "PHP version: $phpVersion"];
            } else {
                $checks['php_version'] = ['status' => 'WARNING', 'message' => "Old PHP version: $phpVersion"];
            }
            
            // Count issues
            $errors = array_filter($checks, function($check) { return $check['status'] === 'ERROR'; });
            $warnings = array_filter($checks, function($check) { return $check['status'] === 'WARNING'; });
            
            $summary = sprintf(
                'System check completed. %d errors, %d warnings found.',
                count($errors),
                count($warnings)
            );
            
            return [
                'success' => true, 
                'message' => $summary,
                'checks' => $checks
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'System check failed: ' . $e->getMessage()];
        }
    }
    
    private function uploadHeritageFile($file, $contentType) {
        $uploadDir = '../assets/uploads/heritage/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Validate file size (10MB max)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('File size exceeds 10MB limit');
        }
        
        // Validate file type
        $allowedTypes = [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'document' => ['pdf', 'doc', 'docx', 'txt']
        ];
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes[$contentType])) {
            throw new Exception('Invalid file type for selected content type');
        }
        
        // Generate unique filename
        $filename = 'heritage_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return 'assets/uploads/heritage/' . $filename;
        }
        
        return false;
    }
    
    private function addFolderToZip($folder, $zip, $zipPath = '') {
        $files = scandir($folder);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $fullPath = $folder . $file;
            $zipFilePath = $zipPath . $file;
            
            if (is_dir($fullPath)) {
                $zip->addEmptyDir($zipFilePath);
                $this->addFolderToZip($fullPath . '/', $zip, $zipFilePath . '/');
            } else {
                $zip->addFile($fullPath, $zipFilePath);
            }
        }
    }
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    $admin = new AdminController($pdo);
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_heritage':
            $result = $admin->addHeritageContent($_POST, $_FILES);
            break;
            
        case 'delete_heritage':
            $result = $admin->deleteHeritageContent($_POST['heritage_id']);
            break;
            
        case 'create_backup':
            $result = $admin->createBackup();
            break;
            
        case 'clear_cache':
            $result = $admin->clearCache();
            break;
            
        case 'system_check':
            $result = $admin->systemCheck();
            break;
            
        default:
            $result = ['success' => false, 'message' => 'Invalid action'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($result);
}
?>