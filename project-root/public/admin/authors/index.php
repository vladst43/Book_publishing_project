<?php
require_once __DIR__ . '/../../../helpers/auth.php';
require_once __DIR__ . '/../../../config/db.php';
requireAdmin();

$stmt = $pdo->query("SELECT * FROM authors ORDER BY last_name, first_name");
$authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Authors</title>
    <link rel="stylesheet" href="/css/style.css" />
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0" style="color:#FFA500;">Authors</h1>
    <a href="create.php" class="btn btn-warning" style="background:#FFA500; color:#fff;">Add New Author</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Birth Date</th>
                    <th>Country</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($authors as $author): ?>
                <tr>
                    <td><?= htmlspecialchars($author['last_name']) ?></td>
                    <td><?= htmlspecialchars($author['first_name']) ?></td>
                    <td><?= htmlspecialchars($author['birth_date']) ?></td>
                    <td><?= htmlspecialchars($author['country']) ?></td>
                    <td>
                        <a href="edit.php?id=<?= urlencode($author['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <a href="delete.php?id=<?= urlencode($author['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this author?')">Delete</a>
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
