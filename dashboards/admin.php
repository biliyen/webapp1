<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

$successMsg = "";
$errorMsg = "";

// Current logged in admin ID
$current_admin_id = $_SESSION['user']['id'] ?? 0;

// DELETE USER
if (isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    if ($delete_id === $current_admin_id) {
        $errorMsg = "⚠️ You cannot delete your own Admin account!";
    } else {
        $stmt = $conn->prepare("DELETE FROM users1 WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $successMsg = "✅ User deleted successfully.";
        } else {
            $errorMsg = "❌ Failed to delete user.";
        }
        $stmt->close();
    }
}

// ADD USER
if (isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt_check = $conn->prepare("SELECT id FROM users1 WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $errorMsg = "⚠️ Email already exists.";
    } else {
        $stmt_check->close();

        $stmt_insert = $conn->prepare("INSERT INTO users1 (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("ssss", $name, $email, $password, $role);
        if ($stmt_insert->execute()) {
            $successMsg = "✅ User added successfully.";
        } else {
            $errorMsg = "❌ Failed to add user.";
        }
        $stmt_insert->close();
    }
}

// EDIT USER
if (isset($_POST['edit_user'])) {
    $id = intval($_POST['edit_id']);
    $name = $_POST['edit_name'];
    $email = $_POST['edit_email'];
    $role = $_POST['edit_role'];

    if (!empty($_POST['edit_password'])) {
        $password = password_hash($_POST['edit_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users1 SET name=?, email=?, role=?, password=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $email, $role, $password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users1 SET name=?, email=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $role, $id);
    }

    if ($stmt->execute()) {
        $successMsg = "✅ User updated successfully.";
    } else {
        $errorMsg = "❌ Failed to update user.";
    }
    $stmt->close();
}

// FETCH all users
$result = $conn->query("SELECT * FROM users1 ORDER BY id DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard - Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
    <style>
        body { background: #f8f9fa; }
        .dashboard-card { box-shadow: 0 0 20px rgba(0,0,0,0.1); border-radius: 15px; }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>👨‍💼 Admin Dashboard - Manage Users</h3>
        <div>
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus"></i> Add User
            </button>
            <a href="../logout.php" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>

    <?php if($successMsg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>
    <?php if($errorMsg): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="card dashboard-card p-4 bg-white">
        <div class="d-flex justify-content-between mb-3">
            <h5>📋 Registered Users</h5>
            <input id="searchInput" type="text" class="form-control w-25" placeholder="🔍 Search users...">
        </div>
        <table class="table table-hover table-bordered align-middle" id="userTable">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th><i class="bi bi-person"></i> Name</th>
                    <th><i class="bi bi-envelope"></i> Email</th>
                    <th><i class="bi bi-shield-lock"></i> Role</th>
                    <th><i class="bi bi-tools"></i> Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><span class="badge bg-primary"><?= $row['role'] ?></span></td>
                    <td>
                        <button 
                            class="btn btn-sm btn-warning me-1 editBtn" 
                            data-id="<?= $row['id'] ?>" 
                            data-name="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>" 
                            data-email="<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>" 
                            data-role="<?= $row['role'] ?>"
                            data-bs-toggle="modal" data-bs-target="#editUserModal"
                            title="Edit User">
                            <i class="bi bi-pencil-square"></i>
                        </button>

                        <?php if($row['id'] !== $current_admin_id): ?>
                            <form method="POST" action="" style="display:inline-block;" onsubmit="return confirm('Delete user <?= addslashes($row['name']) ?>?');">
                                <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Delete User">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete your own Admin account">
                                <i class="bi bi-trash"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content" novalidate>
      <div class="modal-header">
        <h5 class="modal-title" id="addUserLabel">➕ Add New User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div class="mb-3">
              <label>Name</label>
              <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
              <label>Email</label>
              <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
              <label>Password</label>
              <input type="password" name="password" class="form-control" required minlength="6">
          </div>
          <div class="mb-3">
              <label>Role</label>
              <select name="role" class="form-select" required>
                  <option value="Admin">Admin</option>
                  <option value="Pharmacist">Pharmacist</option>
                  <option value="Cashier">Cashier</option>
                  <option value="Doctor">Doctor</option>
                  <option value="Supplier">Supplier</option>
                  <option value="Patient">Patient</option>
              </select>
          </div>
          <input type="hidden" name="add_user" value="1">
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Add User</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content" novalidate>
      <div class="modal-header">
        <h5 class="modal-title" id="editUserLabel">✏️ Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" name="edit_id" id="edit_id">
          <div class="mb-3">
              <label>Name</label>
              <input type="text" name="edit_name" id="edit_name" class="form-control" required>
          </div>
          <div class="mb-3">
              <label>Email</label>
              <input type="email" name="edit_email" id="edit_email" class="form-control" required>
          </div>
          <div class="mb-3">
              <label>Password (leave blank to keep current)</label>
              <input type="password" name="edit_password" class="form-control" minlength="6">
          </div>
          <div class="mb-3">
              <label>Role</label>
              <select name="edit_role" id="edit_role" class="form-select" required>
                  <option value="Admin">Admin</option>
                  <option value="Pharmacist">Pharmacist</option>
                  <option value="Cashier">Cashier</option>
                  <option value="Doctor">Doctor</option>
                  <option value="Supplier">Supplier</option>
                  <option value="Patient">Patient</option>
              </select>
          </div>
          <input type="hidden" name="edit_user" value="1">
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-warning">Save Changes</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Live Search Filter
document.getElementById("searchInput").addEventListener("keyup", function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll("#userTable tbody tr").forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(filter) ? "" : "none";
    });
});

// Populate Edit Modal on Edit button click
document.querySelectorAll(".editBtn").forEach(btn => {
    btn.addEventListener("click", () => {
        document.getElementById("edit_id").value = btn.dataset.id;
        document.getElementById("edit_name").value = btn.dataset.name;
        document.getElementById("edit_email").value = btn.dataset.email;
        document.getElementById("edit_role").value = btn.dataset.role;
    });
});
</script>
</body>
</html>
