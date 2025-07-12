<?php
session_start();
require '../config/db.php';

// Check if logged in and role is Patient
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Patient') {
    header("Location: ../login.php");
    exit();
}

$patient = $_SESSION['user'];
$error = "";
$success = "";

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type'])) {
    if ($_POST['form_type'] === 'profile_update') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $user_id = $patient['id'];

        if ($name === '' || $email === '') {
            $error = "Name and email are required.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error = "Email already in use.";
            } else {
                if ($password !== '') {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $name, $email, $hashed_password, $user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $name, $email, $user_id);
                }
                if ($stmt->execute()) {
                    $success = "Profile updated successfully.";
                    $_SESSION['user']['name'] = $name;
                    $_SESSION['user']['email'] = $email;
                    $patient['name'] = $name;
                    $patient['email'] = $email;
                } else {
                    $error = "Failed to update profile.";
                }
            }
            $stmt->close();
        }
    } elseif ($_POST['form_type'] === 'refill_request') {
        $prescription_id = intval($_POST['prescription_id']);
        $patient_id = $patient['id'];

        $stmt = $conn->prepare("INSERT INTO refill_requests (prescription_id, patient_id, request_date, status) VALUES (?, ?, NOW(), 'Pending')");
        $stmt->bind_param("ii", $prescription_id, $patient_id);
        if ($stmt->execute()) {
            $success = "Refill request sent.";
        } else {
            $error = "Failed to send refill request.";
        }
        $stmt->close();
    }
}

// Fetch patient's prescriptions INCLUDING doctor_name
$stmt = $conn->prepare("SELECT id, medicine, dosage, status, created_at, doctor_name FROM prescriptions WHERE patient_name = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $patient['name']);
$stmt->execute();
$prescriptions = $stmt->get_result();
$stmt->close();

// Fetch medicines
$medicines = $conn->query("SELECT * FROM medicines ORDER BY name ASC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Patient Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background: #f9f9f9; }
    .container { margin-top: 40px; }
    .card { margin-bottom: 30px; padding: 20px; border-radius: 10px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
  </style>
</head>
<body>
  <div class="container">
    <div class="d-flex justify-content-between mb-4">
      <h3>Welcome, <?= htmlspecialchars($patient['name']) ?></h3>
      <a href="../logout.php" class="btn btn-danger">Logout</a>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Profile Update -->
    <div class="card">
      <h5>Update Profile</h5>
      <form method="POST" class="mb-3">
        <input type="hidden" name="form_type" value="profile_update" />
        <div class="mb-3">
          <label>Name</label>
          <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($patient['name']) ?>" />
        </div>
        <div class="mb-3">
          <label>Email</label>
          <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($patient['email'] ?? '') ?>" />
        </div>
        <div class="mb-3">
          <label>New Password (leave blank to keep current)</label>
          <input type="password" name="password" class="form-control" />
        </div>
        <button type="submit" class="btn btn-primary">Update Profile</button>
      </form>
    </div>

    <!-- Prescriptions -->
    <div class="card">
      <h5>Your Prescriptions</h5>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>#</th>
            <th>Medicine</th>
            <th>Dosage</th>
            <th>Doctor</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Refill</th>
          </tr>
        </thead>
        <tbody>
          <?php $i=1; while($p = $prescriptions->fetch_assoc()): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($p['medicine']) ?></td>
              <td><?= htmlspecialchars($p['dosage']) ?></td>
              <td><?= htmlspecialchars($p['doctor_name']) ?></td>
              <td><?= htmlspecialchars($p['status']) ?></td>
              <td><?= htmlspecialchars($p['created_at']) ?></td>
              <td>
                <?php if ($p['status'] === 'Approved'): ?>
                  <form method="POST" style="margin:0;">
                    <input type="hidden" name="form_type" value="refill_request" />
                    <input type="hidden" name="prescription_id" value="<?= $p['id'] ?>" />
                    <button type="submit" class="btn btn-sm btn-outline-primary">Request Refill</button>
                  </form>
                <?php else: ?>
                  <em>N/A</em>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- Medicines -->
    <div class="card">
      <h5>Available Medicines</h5>
      <table class="table table-striped">
        <thead>
          <tr>
            <th>#</th>
            <th>Medicine</th>
            <th>Quantity</th>
            <th>Expiry Date</th>
          </tr>
        </thead>
        <tbody>
          <?php $j=1; while($m = $medicines->fetch_assoc()): ?>
            <tr>
              <td><?= $j++ ?></td>
              <td><?= htmlspecialchars($m['name']) ?></td>
              <td><?= $m['quantity'] ?></td>
              <td><?= $m['expiry_date'] ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </div>
</body>
</html>
