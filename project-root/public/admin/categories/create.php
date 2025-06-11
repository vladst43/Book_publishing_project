<?php
require_once __DIR__ . '/../../../helpers/auth.php';
require_once __DIR__ . '/../../../config/db.php';
requireAdmin();

$errors = [];
$name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    if ($name === '') $errors[] = "Name is required.";
    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
        header('Location: index.php?created=1');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card shadow-sm mt-4 mb-4">
            <h2 class="text-center mb-4" style="color:#FFA500;">Add Category</h2>
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
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required />
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-warning" style="background:#FFA500; color:white;">Add Category</button>
                    <a href="index.php" class="btn btn-secondary mt-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
