
<?php
require_once '../../helpers/auth.php';
requireManager();
require_once '../../config/db.php';
require_once '../../helpers/csrf.php';
require_once '../../helpers/sanitizer.php';
require_once '../../templates/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

if ($id <= 0) {
    echo '<div class="alert alert-danger mt-4">Invalid book ID.</div>';
    require_once '../../templates/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT id, title, stock_quantity FROM books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();
if (!$book) {
    echo '<div class="alert alert-danger mt-4">Book not found.</div>';
    require_once '../../templates/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $newStock = sanitizeInt($_POST['stock_quantity'] ?? '');
        if ($newStock < 0) {
            $error = 'Stock quantity must be zero or positive.';
        } else {
            $update = $pdo->prepare("UPDATE books SET stock_quantity = ? WHERE id = ?");
            $update->execute([$newStock, $id]);
            $success = 'Stock updated successfully.';
            $book['stock_quantity'] = $newStock;
        }
    }
}

$csrf_token = generateCsrfToken();
?>
<div class="container mt-4" style="max-width:500px;">
  <h2>Edit Stock</h2>
  <div class="mb-3"><strong>Book:</strong> <?= htmlspecialchars($book['title']) ?></div>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <div class="mb-3">
      <label for="stock_quantity" class="form-label">Stock quantity</label>
      <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" value="<?= (int)$book['stock_quantity'] ?>" required>
    </div>
    <div class="d-flex justify-content-between">
      <a href="index.php" class="btn btn-secondary">Back</a>
      <button type="submit" class="btn btn-primary">Save</button>
    </div>
  </form>
</div>
<?php require_once '../../templates/footer.php'; ?>
