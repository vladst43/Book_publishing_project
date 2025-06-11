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

<div class="text-center">
    <h1 class="mb-4" style="color:#FFA500;">Admin Panel</h1>
    <p class="lead">Welcome, admin! Here you can manage all the main data of the site:</p>
    <div class="row justify-content-center mt-5">
        <div class="col-md-8">
            <div class="list-group">
                <a href="/admin/books/index.php" class="list-group-item list-group-item-action">Books</a>
                <a href="/admin/authors/index.php" class="list-group-item list-group-item-action">Authors</a>
                <a href="/admin/categories/index.php" class="list-group-item list-group-item-action">Categories</a>
                <a href="/admin/users/index.php" class="list-group-item list-group-item-action">Users</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
</body>
</html>
