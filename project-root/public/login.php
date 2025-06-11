<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth.php';
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

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = 'Email and password are required';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user['id'], $user['role']);
            header('Location: /');
            exit;
        } else {
            $errors[] = 'Incorrect email or password';
        }
    }
}

$csrf_token = generateCsrfToken();
?>



<?php include __DIR__ . '/../templates/header.php'; ?>
<link rel="stylesheet" href="/css/footer-fix.css" />

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card shadow-sm mt-4 mb-4">
            <h2 class="text-center mb-4" style="color:#FFA500;">Log in</h2>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach ?>
                    </ul>
                </div>
            <?php endif ?>

            <form method="post" action="/login.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>" />

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required />
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-warning" style="background-color:#FFA500; color:white;">Log in</button>
                </div>
            </form>
            <div class="text-center mt-3">
                <span>Don't have an account? <a href="/register.php" style="color:#FFA500;">Sign up</a></span>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>