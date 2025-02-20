<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$taskId = $_POST['task_id'] ?? null;
error_log('delete_task_photo.php accessed'); // Log script access
$photoType = $_POST['photo_type'] ?? null;

if (!$taskId || !is_numeric($taskId)) {
    echo json_encode(['success' => false, 'error' => 'Invalid task ID']);
    exit;
}

if (!in_array($photoType, ['before_photo', 'after_photo'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid photo type']);
    exit;
}

try {
    // Get the file path from the database
    $stmt = $pdo->prepare("SELECT {$photoType} FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Task not found']);
        exit;
    }

    $filePath = $row[$photoType];
    
    error_log('File path from DB: ' . $filePath); // Log file path

    if (empty($filePath)) {
        echo json_encode(['success' => false, 'error' => 'Photo not found for this task']);
        exit;
    }

    // Construct the full file path (server-side path)
    $fullFilePath = $_SERVER['DOCUMENT_ROOT'] . $filePath;


    // Delete the file from the server
    if (file_exists($fullFilePath)) {
        if (!unlink($fullFilePath)) {
            error_log("Failed to delete file: " . $fullFilePath); // Log the error
            echo json_encode(['success' => false, 'error' => 'Failed to delete file from server']);
            exit;
        }
    } else {
        error_log("File does not exist: " . $fullFilePath); // Log if file not found
        // It's not necessarily an error if the file doesn't exist (might have been deleted manually)
        // So, we continue to clear the database entry.
    }

    // Update the database to set the photo column to NULL
    $stmt = $pdo->prepare("UPDATE tasks SET {$photoType} = NULL WHERE id = ?");
    $stmt->execute([$taskId]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('Database Error in delete_task_photo.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

?>
