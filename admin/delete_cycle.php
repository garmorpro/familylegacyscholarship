<?php
require '../app/db.php'; // Make sure this points to your PDO connection
require_once '../app/require_admin.php';
require_once '../app/csrf.php';
require_once '../app/cycle_cleanup.php';

csrf_require();

// Same archived_at identifier archives.php / archive_cycle.php use, taken
// as-is and unreformatted so it matches exactly.
$archivedAt = $_POST['archived_at'] ?? '';

if ($archivedAt === '') {
    header("Location: archives.php?cycle_error=" . urlencode("No cycle specified."));
    exit;
}

try {
    $deletedCount = delete_archived_cycle($pdo, $archivedAt);

    if ($deletedCount === 0) {
        header("Location: archives.php?cycle_error=" . urlencode("That cycle couldn't be found -- it may have already been deleted."));
        exit;
    }

    header("Location: archives.php?cycle_success=" . urlencode("Cycle deleted -- {$deletedCount} application(s) permanently removed."));
    exit;

} catch (Exception $e) {
    error_log("delete_cycle.php error: " . $e->getMessage());
    header("Location: archives.php?cycle_error=" . urlencode("Something went wrong deleting that cycle. Please try again."));
    exit;
}
