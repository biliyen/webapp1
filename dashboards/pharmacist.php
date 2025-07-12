<?php
session_start();
require '../config/db.php';

// Check login
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Pharmacist') {
    header("Location: ../login.php");
    exit();
}

$pharmacist = $_SESSION['user']['name'];
$error = "";
$success = "";
$isEdit = false;

// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM medicines WHERE id = $id");
    header("Location: pharmacist.php");
    exit();
}

// Edit
$editMedicine = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM medicines WHERE id = $editId");
    if ($res->num_rows === 1) {
        $editMedicine = $res->fetch_assoc();
        $isEdit = true;
    }
}

// Add/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form_type'] === 'medicine') {
    $name = trim($_POST['name']);
    $quantity = intval($_POST['quantity']);
    $expiry_date = $_POST['expiry_date'];
    $id = intval($_POST['id'] ?? 0);

    if ($name === '' || $quantity <= 0 || $expiry_date === '') {
        $error = "All fields are required.";
    } else {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE medicines SET name = ?, quantity = ?, expiry_date = ? WHERE id = ?");
            $stmt->bind_param("sisi", $name, $quantity, $expiry_date, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO medicines (name, quantity, expiry_date) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $name, $quantity, $expiry_date);
        }

        if ($stmt->execute()) {
            header("Location: pharmacist.php");
            exit();
        } else {
            $error = "Failed to save medicine.";
        }
        $stmt->close();
    }
}

// Dispense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form_type'] === 'dispense') {
    $medicine_id = intval($_POST['medicine_id']);
    $quantity_dispensed = intval($_POST['quantity']);
    $patient = trim($_POST['patient']);

    $stmt = $conn->prepare("SELECT quantity, name FROM medicines WHERE id = ?");
    $stmt->bind_param("i", $medicine_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $medicine = $res->fetch_assoc();

    if ($medicine && $quantity_dispensed > 0 && $medicine['quantity'] >= $quantity_dispensed) {
        $new_qty = $medicine['quantity'] - $quantity_dispensed;
        $upd = $conn->prepare("UPDATE medicines SET quantity = ? WHERE id = ?");
        $upd->bind_param("ii", $new_qty, $medicine_id);
        $upd->execute();
        $upd->close();

        $success = "Dispensed {$quantity_dispensed} of {$medicine['name']} to " . ($patient ?: 'N/A');
    } else {
        $error = "Invalid quantity or insufficient stock.";
    }
    $stmt->close();
}

// Fetch data
$medicines = $conn->query("SELECT * FROM medicines ORDER BY name ASC");
$medList = $conn->query("SELECT * FROM medicines ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Pharmacist Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background: #f8f9fa; }
    .container { margin-top: 40px; }
    .btn-custom { background-color: #6f42c1; color: white; }
    .btn-custom:hover { background-color: #5a33a0; }
    .card { margin-bottom: 30px; padding: 20px; border-radius: 12px; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .low-stock { color: red; font-weight: bold; }
    @media print {
      .no-print { display: none; }
      body { background: white; }
    }
  </style>
  <script>
    function filterMedicines() {
      const input = document.getElementById('searchInput').value.toLowerCase();
      const rows = document.querySelectorAll('#medicineTable tbody tr');
      rows.forEach(row => {
        const name = row.cells[1].innerText.toLowerCase();
        row.style.display = name.includes(input) ? '' : 'none';
      });
    }

    function printReport() {
      window.print();
    }
  </script>
</head>
<body>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
      <h3>👨‍⚕️ Welcome, <?= htmlspecialchars($pharmacist) ?></h3>
      <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <!-- Medicine Management -->
    <div class="card">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-bold fs-5">📦 Manage Medicines</div>
        <div class="d-flex gap-2 no-print">
          <input type="text" id="searchInput" class="form-control form-control-sm" onkeyup="filterMedicines()" placeholder="🔍 Search medicine..." />
          <button onclick="printReport()" class="btn btn-sm btn-outline-secondary">🖨️ Print</button>
        </div>
      </div>
      <form method="POST" class="mb-3 no-print">
        <input type="hidden" name="form_type" value="medicine" />
        <input type="hidden" name="id" value="<?= $editMedicine['id'] ?? 0 ?>" />
        <div class="row g-3">
          <div class="col-md-4"><input type="text" class="form-control" name="name" placeholder="Medicine Name" value="<?= htmlspecialchars($editMedicine['name'] ?? '') ?>" required /></div>
          <div class="col-md-3"><input type="number" class="form-control" name="quantity" min="1" placeholder="Quantity" value="<?= htmlspecialchars($editMedicine['quantity'] ?? '') ?>" required /></div>
          <div class="col-md-3"><input type="date" class="form-control" name="expiry_date" value="<?= htmlspecialchars($editMedicine['expiry_date'] ?? '') ?>" required /></div>
          <div class="col-md-2 d-grid"><button class="btn btn-custom"><?= $isEdit ? 'Update' : 'Add' ?></button></div>
        </div>
      </form>

      <table class="table table-bordered table-hover" id="medicineTable">
        <thead>
          <tr>
            <th>#</th><th>Name</th><th>Quantity</th><th>Expiry Date</th><th class="no-print">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; while ($row = $medicines->fetch_assoc()): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td class="<?= $row['quantity'] <= 10 ? 'low-stock' : '' ?>"><?= $row['quantity'] ?></td>
              <td><?= $row['expiry_date'] ?></td>
              <td class="no-print">
                <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this medicine?')" class="btn btn-sm btn-danger">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- Dispense Medicine -->
    <div class="card no-print">
      <div class="fw-bold fs-5 mb-2">💊 Dispense Medicine</div>
      <form method="POST">
        <input type="hidden" name="form_type" value="dispense" />
        <div class="row g-3 align-items-center">
          <div class="col-md-4">
            <select name="medicine_id" class="form-select" required>
              <option value="">-- Select Medicine --</option>
              <?php while ($m = $medList->fetch_assoc()): ?>
                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?> (<?= $m['quantity'] ?> left)</option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-3"><input type="number" name="quantity" class="form-control" min="1" placeholder="Quantity" required /></div>
          <div class="col-md-3"><input type="text" name="patient" class="form-control" placeholder="Patient Name (optional)" /></div>
          <div class="col-md-2 d-grid"><button class="btn btn-custom">Dispense</button></div>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
