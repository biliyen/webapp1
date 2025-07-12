<?php
require '../config/auth.php';
require_role('Admin');
require '../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: Admin.php");
    exit;
}

$message = '';
$stmt = $conn->prepare("SELECT * FROM users1 WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    header("Location: Admin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    if ($name === '' || $role === '') {
        $message = "❌ Name and role required.";
    } else {
        try {
            if ($password !== '') {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users1 SET name = ?, role = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $role, $hashedPassword, $id]);
            } else {
                $stmt = $conn->prepare("UPDATE users1 SET name = ?, role = ? WHERE id = ?");
                $stmt->execute([$name, $role, $id]);
            }
            header("Location: Admin.php?updated=1");
            exit;
        } catch (PDOException $e) {
            $message = "❌ Update failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2>Edit User</h2>
    <?php if ($message): ?>
        <div class="alert alert-danger"><?= $message ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Role</label>
            <select name="role" class="form-control" required>
                <?php
                $roles = ['Admin', 'Pharmacist', 'Cashier', 'Doctor', 'Supplier', 'Patient'];
                foreach ($roles as $r) {
                    echo "<option value='$r'" . ($user['role'] === $r ? ' selected' : '') . ">$r</option>";
                }
                ?>
            </select>
        </div>
        <div class="mb-3">
            <label>New Password (leave blank to keep current password)</label>
            <input type="password" name="password" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Update User</button>
        <a href="Admin.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
