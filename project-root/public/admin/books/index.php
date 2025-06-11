<?php
require_once __DIR__ . '/../../../helpers/auth.php';
require_once __DIR__ . '/../../../config/db.php';
requireAdmin();

$stmt = $pdo->query("SELECT b.id, b.title, CONCAT(a.first_name, ' ', a.last_name) AS author, c.name AS category
                     FROM books b
                     LEFT JOIN authors a ON b.author_id = a.id
                     LEFT JOIN categories c ON b.category_id = c.id
                     ORDER BY b.title");

$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Books</title>
    <link rel="stylesheet" href="/css/style.css" />
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0" style="color:#FFA500;">Books</h1>
    <a href="create.php" class="btn btn-warning" style="background:#FFA500; color:#fff;">Add New Book</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): ?>
                <tr>
                    <td><?= htmlspecialchars($book['title']) ?></td>
                    <td><?= htmlspecialchars($book['author']) ?></td>
                    <td><?= htmlspecialchars($book['category']) ?></td>
                    <td>
                        <a href="edit.php?id=<?= urlencode($book['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <a href="delete.php?id=<?= urlencode($book['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this book?')">Delete</a>
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
