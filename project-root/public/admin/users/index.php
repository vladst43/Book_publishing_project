<?php
require_once __DIR__ . '/../../../helpers/auth.php';
require_once __DIR__ . '/../../../config/db.php';
requireAdmin();

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Users</title>
    <link rel="stylesheet" href="/css/style.css" />
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0" style="color:#FFA500;">Users</h1>
    <a href="create.php" class="btn btn-warning" style="background:#FFA500; color:#fff;">Add New User</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['first_name']) ?></td>
                    <td><?= htmlspecialchars($user['last_name']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                    <td>
                        <a href="edit.php?id=<?= urlencode($user['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <a href="delete.php?id=<?= urlencode($user['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
