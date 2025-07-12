<?php
session_start();
require 'config/db.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($name) || empty($email) || empty($role) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users1 WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email is already registered.";
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $insert_stmt = $conn->prepare("INSERT INTO users1 (name, email, role, password) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $name, $email, $role, $hashed_password);

            if ($insert_stmt->execute()) {
                $success = "Registration successful! You can now <a href='login.php'>login</a>.";
                // Optionally clear inputs on success
                $name = $email = $role = $password = $confirm_password = "";
            } else {
                $error = "Error occurred. Please try again.";
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Register - Pharmacy Management System</title>

<!-- Bootstrap 5 CSS CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

<style>
    body {
        background: linear-gradient(135deg, #4a148c, #880e4f);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .register-container {
        background: white;
        padding: 2.5rem 3rem;
        border-radius: 20px;
        box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        width: 100%;
        max-width: 450px;
    }
    .register-container h2 {
        color: #6f42c1;
        font-weight: 700;
        margin-bottom: 1.8rem;
        text-align: center;
    }
    .form-control:focus {
        border-color: #6f42c1;
        box-shadow: 0 0 10px rgba(111, 66, 193, 0.5);
    }
    .btn-primary {
        background-color: #6f42c1;
        border: none;
        font-weight: 600;
        transition: background-color 0.3s ease;
    }
    .btn-primary:hover {
        background-color: #572a9e;
    }
    .footer-text {
        text-align: center;
        margin-top: 1.5rem;
        font-size: 0.9rem;
        color: #6c757d;
    }
    .toggle-link {
        text-align: center;
        margin-top: 1rem;
    }
    .toggle-link a {
        color: #6f42c1;
        font-weight: 600;
        text-decoration: none;
    }
    .toggle-link a:hover {
        text-decoration: underline;
        color: #572a9e;
    }
    .alert a {
        color: #6f42c1;
        font-weight: 600;
        text-decoration: underline;
    }
</style>

</head>
<body>
    <div class="register-container shadow-sm">
        <h2>Create Account</h2>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif (!empty($success)) : ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate>
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input
                    type="text"
                    class="form-control"
                    id="name"
                    name="name"
                    required
                    placeholder="Enter your full name"
                    autofocus
                    value="<?= htmlspecialchars($name ?? '') ?>"
                />
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    required
                    placeholder="Enter your email"
                    value="<?= htmlspecialchars($email ?? '') ?>"
                />
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Select Role</label>
                <select class="form-select" name="role" id="role" required>
                    <option value="" disabled <?= empty($role) ? 'selected' : '' ?>>Choose your role</option>
                    <option value="Admin" <?= (isset($role) && $role === 'Admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="Pharmacist" <?= (isset($role) && $role === 'Pharmacist') ? 'selected' : '' ?>>Pharmacist</option>
                    <option value="Cashier" <?= (isset($role) && $role === 'Cashier') ? 'selected' : '' ?>>Cashier</option>
                    <option value="Doctor" <?= (isset($role) && $role === 'Doctor') ? 'selected' : '' ?>>Doctor</option>
                    <option value="Supplier" <?= (isset($role) && $role === 'Supplier') ? 'selected' : '' ?>>Supplier</option>
                    <option value="Patient" <?= (isset($role) && $role === 'Patient') ? 'selected' : '' ?>>Patient</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    required
                    placeholder="Enter your password"
                />
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input
                    type="password"
                    class="form-control"
                    id="confirm_password"
                    name="confirm_password"
                    required
                    placeholder="Confirm your password"
                />
            </div>

            <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>

        <div class="toggle-link">
            <small>
                Already have an account? 
                <a href="login.php">Login here</a>
            </small>
        </div>

        <p class="footer-text mt-4">© <?= date('Y') ?> Pharmacy Management System</p>
    </div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Optional: Simple client-side password match validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const pass = document.getElementById('password').value;
        const confirmPass = document.getElementById('confirm_password').value;
        if (pass !== confirmPass) {
            e.preventDefault();
            alert('Passwords do not match!');
        }
    });
</script>
</body>
</html>
