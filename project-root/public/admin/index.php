<?php
require_once __DIR__ . '/../../helpers/auth.php';
requireAdmin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/css/style.css" />
</head>
<body>
<?php include __DIR__ . '/../../templates/header.php'; ?>

<h1>Admin Dashboard</h1>
<p>Welcome, admin!</p>

<nav>
    <ul>
        <li><a href="/admin/books/index.php">Manage Books</a></li>
        <li><a href="/admin/authors/index.php">Manage Authors</a></li>
        <li><a href="/admin/categories/index.php">Manage Genres</a></li>
        <li><a href="/admin/series/index.php">Manage Series</a></li>
        <li><a href="/admin/users/index.php">Manage Users</a></li>
        <li><a href="/logout.php">Logout</a></li>
    </ul>
</nav>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
</body>
</html>
