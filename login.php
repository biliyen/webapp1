<?php
session_start();
require 'config/db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users1 WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($role !== $user['role']) {
                $error = "Selected role does not match user role.";
            } elseif (password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user;

                switch ($user['role']) {
                    case 'Admin':
                        header('Location: dashboards/admin.php');
                        break;
                    case 'Pharmacist':
                        header('Location: dashboards/pharmacist.php');
                        break;
                    case 'Cashier':
                        header('Location: dashboards/cashier.php');
                        break;
                    case 'Doctor':
                        header('Location: dashboards/doctor.php');
                        break;
                    case 'Supplier':
                        header('Location: dashboards/supplier.php');
                        break;
                    case 'Patient':
                        header('Location: dashboards/patient.php');
                        break;
                    default:
                        $error = "Unknown user role.";
                        session_destroy();
                        break;
                }
                exit();
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "Email not registered.";
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
<title>Login - Pharmacy Management System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
    body {
        background: linear-gradient(135deg, #6f42c1, #d63384);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .login-container {
        background: white;
        padding: 2.5rem 3rem;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 400px;
    }
    .login-container h2 {
        color: #6f42c1;
        margin-bottom: 1.5rem;
        font-weight: 700;
        text-align: center;
    }
    .form-control:focus {
        border-color: #6f42c1;
        box-shadow: 0 0 8px rgba(111, 66, 193, 0.4);
    }
    .btn-primary {
        background-color: #6f42c1;
        border: none;
        font-weight: 600;
        transition: background-color 0.3s ease;
    }
    .btn-primary:hover {
        background-color: #5a3490;
    }
    .error-msg {
        color: #dc3545;
        font-weight: 600;
        margin-bottom: 1rem;
        text-align: center;
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
        color: #5a3490;
    }
</style>
</head>
<body>
    <div class="login-container shadow-sm">
        <h2>Pharmacy Login</h2>
        <?php if (!empty($error)) : ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="role" class="form-label">Select Role</label>
                <select class="form-select" name="role" id="role" required>
                    <option value="" disabled selected>Choose your role</option>
                    <option value="Admin">Admin</option>
                    <option value="Pharmacist">Pharmacist</option>
                    <option value="Cashier">Cashier</option>
                    <option value="Doctor">Doctor</option>
                    <option value="Supplier">Supplier</option>
                    <option value="Patient">Patient</option>
                </select>
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
                    autofocus
                />
            </div>
            <div class="mb-4">
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
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <div class="toggle-link">
            <small>
                Don't have an account? 
                <a href="register.php">Register here</a>
            </small>
        </div>
        <p class="footer-text mt-4">© <?= date('Y') ?> Pharmacy Management System</p>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
