<?php
require '../config/auth.php';
require_role('Admin');
require '../config/db.php';

$id = $_GET['id'] ?? null;

if (!$id || $id == $_SESSION['user_id']) {
    header("Location: Admin.php?error=cant_delete_self");
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM users1 WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: Admin.php?deleted=1");
    exit;
} catch (PDOException $e) {
    header("Location: Admin.php?error=" . urlencode($e->getMessage()));
    exit;
}
