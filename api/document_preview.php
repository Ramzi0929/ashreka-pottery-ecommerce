<?php
session_start();
require_once '../config/database_enhanced.php';

// Try to load document reader if available
$documentReaderAvailable = false;
if (file_exists('../document_reader/vendor/autoload.php')) {
    require_once '../document_reader/vendor/autoload.php';
    $documentReaderAvailable = class_exists('Heritage\DocumentReader\DocumentReader');
}

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Document ID required']);
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM heritage_archive WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode([
        'error' => 'Document not found in database',
        'title' => 'Document Not Found',
        'description' => 'This heritage document could not be located.',
        'content' => 'Document not found in the heritage archive.',
        'file_path' => '',
        'success' => false
    ]);
    exit;
}

$content = '';

if ($item['file_path']) {
    $filePath = '../' . $item['file_path'];
    $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    if ($documentReaderAvailable && in_array($fileExt, ['pdf', 'doc', 'docx', 'txt'])) {
        // Use advanced document reader
        $extractedText = \Heritage\DocumentReader\DocumentReader::extractText($filePath);
        
        // Limit text size to prevent memory issues
        if (strlen($extractedText) > 1000000) {
            $extractedText = substr($extractedText, 0, 1000000) . "\n\n[Text truncated - document contains more content. Download full document to view all content.]";
        }
        
        $content = "Heritage Document: " . ($item['title'] ?: 'Untitled') . "\n\n";
        $content .= "Description: " . ($item['description'] ?: 'No description available.') . "\n\n";
        $content .= "Document Content:\n" . $extractedText;
    } else {
        // Fallback to simple handling
        switch ($fileExt) {
            case 'txt':
                if (file_exists($filePath)) {
                    $content = file_get_contents($filePath);
                } else {
                    $content = 'Text file not found.';
                }
                break;
            case 'pdf':
                $content = 'PDF Document\n\n' . ($item['description'] ?: 'No description available.') . '\n\nThis PDF contains heritage information. Click download to view the full content.';
                break;
            case 'doc':
            case 'docx':
                $content = 'Word Document\n\n' . ($item['description'] ?: 'No description available.') . '\n\nThis Word document contains heritage information. Click download to view the full content.';
                break;
            default:
                $content = 'Heritage Document\n\n' . ($item['description'] ?: 'No description available.') . '\n\nClick download to view the full content.';
        }
    }
} else {
    $content = 'Heritage Document Information\n\n';
    $content .= 'Title: ' . ($item['title'] ?: 'Untitled') . '\n\n';
    $content .= 'Description: ' . ($item['description'] ?: 'No description available.') . '\n\n';
    
    if ($item['content']) {
        $content .= 'Heritage Content:\n' . $item['content'];
    } else {
        $content .= 'This heritage item contains cultural and historical information.';
    }
}

echo json_encode([
    'success' => true,
    'title' => $item['title'] ?: 'Heritage Document',
    'description' => $item['description'] ?: 'Heritage document description',
    'content' => $content,
    'file_type' => $item['file_path'] ? pathinfo($item['file_path'], PATHINFO_EXTENSION) : 'text',
    'file_path' => $item['file_path'] ?: '',
    'advanced_reader' => $documentReaderAvailable
]);
?>