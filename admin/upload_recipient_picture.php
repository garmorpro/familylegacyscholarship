<?php
require_once '../app/db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipientId = $_POST['recipient_id'] ?? null;
    if (!$recipientId) die("Recipient ID missing");

    if (!isset($_FILES['recipient_picture'])) {
        die("No file uploaded.");
    }

    $file = $_FILES['recipient_picture'];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => "The uploaded file exceeds the upload_max_filesize directive.",
            UPLOAD_ERR_FORM_SIZE  => "The uploaded file exceeds the MAX_FILE_SIZE directive.",
            UPLOAD_ERR_PARTIAL    => "The uploaded file was only partially uploaded.",
            UPLOAD_ERR_NO_FILE    => "No file was uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload.",
        ];
        $code = $file['error'];
        $message = $errorMessages[$code] ?? "Unknown upload error.";
        die("File upload error: $message");
    }

    $uploadDir = '../uploads/recipients/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            die("Failed to create upload directory.");
        }
    }

    // Generate unique filename
    $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\.-]/', '_', basename($file['name']));
    $targetFile = $uploadDir . $filename;

    // Move the uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        $error = error_get_last();
        die("Error moving uploaded file. Last error: " . ($error['message'] ?? 'unknown'));
    }

    // Update DB with just the filename
    try {
        $stmt = $pdo->prepare("UPDATE recipients SET recipient_picture = :picture WHERE id = :id");
        $stmt->execute([
            ':picture' => $filename,
            ':id' => $recipientId
        ]);
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }

    // Redirect back to recipients page
    header("Location: /admin/recipients.php");
    exit;
}
