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

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Log in</title>
    <link rel="stylesheet" href="/css/style.css" />
</head>
<body>
    <h1>Log in</h1>
    <?php if ($errors): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
    <form method="post" action="/login.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>" />
        <label>Email: <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" /></label><br/>
        <label>Password: <input type="password" name="password" required /></label><br/>
        <button type="submit">Log in</button>
    </form>
    <p>Don't have an account? <a href="/register.php">Sign up</a></p>
</body>
</html>