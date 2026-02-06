<?php
require_once '../app/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipientId = $_POST['recipient_id'] ?? null;
    if (!$recipientId) die("Recipient ID missing");

    if (isset($_FILES['recipient_picture']) && $_FILES['recipient_picture']['error'] === 0) {
        $uploadDir = '../uploads/recipients/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = time() . '_' . basename($_FILES['recipient_picture']['name']);
        $targetFile = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['recipient_picture']['tmp_name'], $targetFile)) {
            $recipient_picture = '/uploads/recipients/' . $filename;

            // Update DB
            $stmt = $pdo->prepare("UPDATE recipients SET recipient_picture = :picture WHERE id = :id");
            $stmt->execute([':picture' => $filename, ':id' => $recipientId]);

            header("Location: /admin/recipients.php"); // redirect back
            exit;
        } else {
            die("Error uploading file.");
        }
    } else {
        die("No file uploaded or upload error.");
    }
}
