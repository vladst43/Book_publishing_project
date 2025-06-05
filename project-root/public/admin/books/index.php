<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../config/db.php';
requireAdmin();

$stmt = $pdo->query("SELECT b.id, b.title, a.name AS author, c.name AS category
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
<?php include __DIR__ . '/../../templates/header.php'; ?>

<h1>Books</h1>
<a href="create.php">Add New Book</a>

<table border="1" cellpadding="5" cellspacing="0">
    <thead>
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
                <a href="edit.php?id=<?= urlencode($book['id']) ?>">Edit</a> |
                <a href="delete.php?id=<?= urlencode($book['id']) ?>" onclick="return confirm('Delete this book?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
</body>
</html>
