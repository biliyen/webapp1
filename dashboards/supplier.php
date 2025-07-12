<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Supplier') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Supplier Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>Welcome Supplier, <?= $_SESSION['user']['name'] ?> 🚚</h2>
    <a href="../logout.php" class="btn btn-danger float-end">Logout</a>
    <p class="mt-4">You can manage deliveries and stock here.</p>
</div>
</body>
</html>
