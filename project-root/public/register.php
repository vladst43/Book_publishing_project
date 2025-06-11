<?php
require_once __DIR__ . '/../helpers/sanitizer.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/init.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    }

    $first_name = sanitizeString($_POST['first_name'] ?? '');
    $last_name = sanitizeString($_POST['last_name'] ?? '');
    $username = sanitizeString($_POST['username'] ?? '');
    $email = sanitizeEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    
    
    if (!validateRequired($first_name) || !validateMinLength($first_name, 2)) {
        $errors[] = 'First name is required and must be at least 2 characters';
    } elseif (!validateNameCharacters($first_name)) {
        $errors[] = 'First name can only contain letters, hyphens, apostrophes, and spaces';
    }
    
    if (!validateRequired($last_name) || !validateMinLength($last_name, 2)) {
        $errors[] = 'Last name is required and must be at least 2 characters';
    } elseif (!validateNameCharacters($last_name)) {
        $errors[] = 'Last name can only contain letters, hyphens, apostrophes, and spaces';
    }

    if (!validateRequired($username)) {
        $errors[] = 'Username is required';
    } elseif (!validateMinLength($username, 3) || !validateMaxLength($username, 50)) {
        $errors[] = 'Username must be between 3 and 50 characters';
    }

    if (!validateEmail($email)) {
        $errors[] = 'Invalid email address';
    }

    if (!validateRequired($password)) {
        $errors[] = 'Password is required';
    } else {
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'Password must contain at least one digit';
        }
        if (!preg_match('/[\W_]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
    }

    if ($password !== $password_confirm) {
        $errors[] = 'Passwords do not match';
    }


    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email OR username = :username');
        $stmt->execute(['email' => $email, 'username' => $username]);
        if ($stmt->fetch()) {
            $errors[] = 'Registration failed. Please try a different email or username.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('
                INSERT INTO users (first_name, last_name, username, email, password_hash, role)
                VALUES (:first_name, :last_name, :username, :email, :password_hash, :role)
            ');
            $stmt->execute([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'username' => $username,
                'email' => $email,
                'password_hash' => $password_hash,
                'role' => 'user',
            ]);
        
            $_SESSION['success_message'] = 'Registration successful. Please log in.';
            header('Location: login.php');
            exit;
        }        
    }
}

$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register</title>
    <link rel="stylesheet" href="css/style.css" />
</head>
<body>

<?php include __DIR__ . '/../templates/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card shadow-sm mt-4 mb-4">
            <h2 class="text-center mb-4" style="color:#FFA500;">Sign up</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="register.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>" />

                <div class="mb-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" required minlength="2" maxlength="50"
                        value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" />
                </div>

                <div class="mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" required minlength="2" maxlength="50"
                        value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" />
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required minlength="3" maxlength="50"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" />
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="8" />
                </div>

                <div class="mb-3">
                    <label for="password_confirm" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="8" />
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-warning" style="background-color:#FFA500; color:white;">Sign up</button>
                </div>
            </form>
            <div class="text-center mt-3">
                <span>Already have an account? <a href="login.php" style="color:#FFA500;">Login</a></span>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
