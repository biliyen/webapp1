<?php
session_start();

// If user is not logged in, redirect to login page
if (!isset($_SESSION['name']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$name = $_SESSION['name'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Welcome</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5 text-center">
    <h2>Welcome, <?= htmlspecialchars($name) ?>!</h2>
    <p>You are logged in as <strong><?= htmlspecialchars($role) ?></strong>.</p>

    <?php if ($role == 'Admin'): ?>
        <a href="admin.php" class="btn btn-primary">Go to Admin Dashboard</a>
    <?php elseif ($role == 'Pharmacist'): ?>
        <a href="pharmacist.php" class="btn btn-primary">Go to Pharmacist Page</a>
    <?php endif; ?>

    <br><br>
    <a href="logout.php" class="btn btn-danger">Logout</a>
</div>
</body>
</html>
