<?php
session_start();
require '../config/db.php';

// Check if logged in and role is Doctor
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Doctor') {
    header("Location: ../login.php");
    exit();
}

$doctor = $_SESSION['user'];
$error = "";
$success = "";

$isEdit = false;
$editPrescription = null;

// Handle prescription deletion
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM prescriptions WHERE id = ? AND doctor_name = ?");
    $stmt->bind_param("is", $delId, $doctor['name']);
    if ($stmt->execute()) {
        $success = "Prescription deleted successfully.";
    } else {
        $error = "Failed to delete prescription.";
    }
    $stmt->close();
    header("Location: doctor.php");
    exit();
}

// Handle adding a new prescription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type'])) {
    if ($_POST['form_type'] === 'add_prescription') {
        $patient_name = trim($_POST['patient_name']);
        $medicine = trim($_POST['medicine']);
        $dosage = trim($_POST['dosage']);
        $doctor_name = $doctor['name'];

        if ($patient_name === '' || $medicine === '' || $dosage === '') {
            $error = "All fields are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO prescriptions (patient_name, medicine, dosage, status, doctor_name, created_at) VALUES (?, ?, ?, 'Pending', ?, NOW())");
            $stmt->bind_param("ssss", $patient_name, $medicine, $dosage, $doctor_name);
            if ($stmt->execute()) {
                $success = "Prescription added successfully.";
            } else {
                $error = "Failed to add prescription.";
            }
            $stmt->close();
        }
    } elseif ($_POST['form_type'] === 'edit_prescription') {
        $prescription_id = intval($_POST['id']);
        $patient_name = trim($_POST['patient_name']);
        $medicine = trim($_POST['medicine']);
        $dosage = trim($_POST['dosage']);
        $doctor_name = $doctor['name'];

        if ($patient_name === '' || $medicine === '' || $dosage === '') {
            $error = "All fields are required.";
        } else {
            $stmt = $conn->prepare("UPDATE prescriptions SET patient_name = ?, medicine = ?, dosage = ?, doctor_name = ? WHERE id = ? AND doctor_name = ?");
            $stmt->bind_param("ssssis", $patient_name, $medicine, $dosage, $doctor_name, $prescription_id, $doctor_name);
            if ($stmt->execute()) {
                $success = "Prescription updated successfully.";
                $isEdit = false;
                $editPrescription = null;
            } else {
                $error = "Failed to update prescription.";
            }
            $stmt->close();
        }
    }
}

// Handle fetching prescription to edit
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM prescriptions WHERE id = ? AND doctor_name = ?");
    $stmt->bind_param("is", $editId, $doctor['name']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $editPrescription = $result->fetch_assoc();
        $isEdit = true;
    }
    $stmt->close();
}

// Fetch prescriptions created by this doctor
$stmt = $conn->prepare("SELECT * FROM prescriptions WHERE doctor_name = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $doctor['name']);
$stmt->execute();
$prescriptions = $stmt->get_result();
$stmt->close();

// <-- UPDATED TABLE NAME HERE -->
$patients_res = $conn->query("SELECT name FROM users1 WHERE role = 'Patient' ORDER BY name ASC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Doctor Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background: #f8f9fa; }
    .container { margin-top: 40px; }
    .card { margin-bottom: 30px; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
  </style>
</head>
<body>
  <div class="container">
    <div class="d-flex justify-content-between mb-4">
      <h3>👨‍⚕️ Welcome Dr. <?= htmlspecialchars($doctor['name']) ?></h3>
      <a href="../logout.php" class="btn btn-danger">Logout</a>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Add/Edit Prescription Form -->
    <div class="card">
      <h5><?= $isEdit ? "Edit Prescription" : "Add New Prescription" ?></h5>
      <form method="POST">
        <input type="hidden" name="form_type" value="<?= $isEdit ? "edit_prescription" : "add_prescription" ?>" />
        <?php if ($isEdit): ?>
          <input type="hidden" name="id" value="<?= $editPrescription['id'] ?>" />
        <?php endif; ?>
        <div class="mb-3">
          <label for="patient_name">Patient Name</label>
          <select name="patient_name" id="patient_name" class="form-select" required>
            <option value="">Select Patient</option>
            <?php while ($p = $patients_res->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars($p['name']) ?>" <?= ($isEdit && $editPrescription['patient_name'] === $p['name']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['name']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-3">
          <label for="medicine">Medicine</label>
          <input type="text" name="medicine" id="medicine" class="form-control" required value="<?= $isEdit ? htmlspecialchars($editPrescription['medicine']) : '' ?>" />
        </div>
        <div class="mb-3">
          <label for="dosage">Dosage</label>
          <input type="text" name="dosage" id="dosage" class="form-control" required value="<?= $isEdit ? htmlspecialchars($editPrescription['dosage']) : '' ?>" />
        </div>
        <button type="submit" class="btn btn-primary"><?= $isEdit ? "Update Prescription" : "Add Prescription" ?></button>
        <?php if ($isEdit): ?>
          <a href="doctor.php" class="btn btn-secondary ms-2">Cancel</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- View Prescriptions -->
    <div class="card">
      <h5>Your Prescriptions</h5>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>#</th>
            <th>Patient</th>
            <th>Medicine</th>
            <th>Dosage</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i=1; while($row = $prescriptions->fetch_assoc()): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($row['patient_name']) ?></td>
              <td><?= htmlspecialchars($row['medicine']) ?></td>
              <td><?= htmlspecialchars($row['dosage']) ?></td>
              <td><?= htmlspecialchars($row['status']) ?></td>
              <td><?= htmlspecialchars($row['created_at']) ?></td>
              <td>
                <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this prescription?');" class="btn btn-sm btn-danger ms-1">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
          <?php if ($prescriptions->num_rows === 0): ?>
            <tr><td colspan="7" class="text-center">No prescriptions found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</body>
</html>
