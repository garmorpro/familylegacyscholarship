<?php
require_once '../app/db.php';
require_once '../app/require_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipientId = $_POST['recipient_id'] ?? null;
    if (!$recipientId || !ctype_digit((string)$recipientId)) die("Recipient ID missing");

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

    // Cap file size at 5MB
    $maxBytes = 5 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        die("File is too large. Maximum size is 5MB.");
    }

    // Verify the upload is actually an image (not just named like one) and
    // determine its real type ourselves rather than trusting the client.
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        die("Uploaded file is not a valid image.");
    }

    $allowedTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    $detectedType = $imageInfo[2];
    if (!isset($allowedTypes[$detectedType])) {
        die("Unsupported image type. Allowed types: JPG, PNG, GIF, WEBP.");
    }

    $uploadDir = '../uploads/recipients/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            die("Failed to create upload directory.");
        }
    }

    // Generate a filename ourselves — never derived from user input.
    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $allowedTypes[$detectedType];
    $targetFile = $uploadDir . $filename;

    // Move the uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        $error = error_get_last();
        error_log("Failed to move uploaded recipient picture: " . ($error['message'] ?? 'unknown'));
        die("Error saving uploaded file.");
    }

    // Update DB with just the filename
    try {
        $stmt = $pdo->prepare("UPDATE recipients SET recipient_picture = :picture WHERE id = :id");
        $stmt->execute([
            ':picture' => $filename,
            ':id' => $recipientId
        ]);
    } catch (PDOException $e) {
        error_log("Database error saving recipient picture: " . $e->getMessage());
        die("Database error saving picture.");
    }

    // Redirect back to recipients page
    header("Location: /admin/recipients.php");
    exit;
}
