<?php
require_once __DIR__ . '/../../../helpers/auth.php';
require_once __DIR__ . '/../../../config/db.php';
requireAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    include __DIR__ . '/../includes/header.php';
    echo "<div class='alert alert-danger mt-4'>User not found.</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$username = $user['username'];
$email = $user['email'];
$first_name = $user['first_name'];
$last_name = $user['last_name'];
$role = $user['role'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $role = $_POST['role'] ?? 'user';

    if ($username === '') $errors[] = "Username is required.";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (!in_array($role, ['admin','user','manager','accountant'])) $errors[] = "Invalid role.";

    // Check unique username/email (exclude current user)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$username, $email, $id]);
    if ($stmt->fetchColumn() > 0) $errors[] = "Username or email already exists.";

    if (!$errors) {
        $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, first_name=?, last_name=?, role=? WHERE id=?");
        $stmt->execute([$username, $email, $first_name ?: null, $last_name ?: null, $role, $id]);
        header('Location: index.php?updated=1');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card shadow-sm mt-4 mb-4">
            <h2 class="text-center mb-4" style="color:#FFA500;">Edit User</h2>
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
                    <button type="submit" class="btn btn-warning" style="background:#FFA500; color:white;">Save changes</button>
                    <a href="index.php" class="btn btn-secondary mt-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
