<?php
require_once __DIR__ . '/../../../helpers/auth.php';
require_once __DIR__ . '/../../../config/db.php';
requireAdmin();

$errors = [];
$username = '';
$email = '';
$password = '';
$first_name = '';
$last_name = '';
$role = 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $role = $_POST['role'] ?? 'user';

    if ($username === '') $errors[] = "Username is required.";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if ($password === '' || strlen($password) < 6) $errors[] = "Password (min 6 chars) is required.";
    if (!in_array($role, ['admin','user','manager','accountant'])) $errors[] = "Invalid role.";

    // Check unique username/email
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) $errors[] = "Username or email already exists.";

    if (!$errors) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password_hash, $first_name ?: null, $last_name ?: null, $role]);
        header('Location: index.php?created=1');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card shadow-sm mt-4 mb-4">
            <h2 class="text-center mb-4" style="color:#FFA500;">Add User</h2>
            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="post" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required />
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required />
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required />
                </div>
                <div class="mb-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($first_name) ?>" />
                </div>
                <div class="mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($last_name) ?>" />
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="user" <?= $role==='user'?'selected':'' ?>>User</option>
                        <option value="admin" <?= $role==='admin'?'selected':'' ?>>Admin</option>
                        <option value="manager" <?= $role==='manager'?'selected':'' ?>>Manager</option>
                        <option value="accountant" <?= $role==='accountant'?'selected':'' ?>>Accountant</option>
                    </select>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-warning" style="background:#FFA500; color:white;">Add User</button>
                    <a href="index.php" class="btn btn-secondary mt-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
