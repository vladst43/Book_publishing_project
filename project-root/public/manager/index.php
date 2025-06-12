
<?php
require_once '../../helpers/auth.php';
requireManager();
require_once '../../config/db.php';
require_once '../../templates/header.php';


$category = $_GET['category'] ?? '';
$author = $_GET['author'] ?? '';
$status = $_GET['status'] ?? '';

$query = "SELECT b.id, b.title, CONCAT(a.first_name, ' ', a.last_name) AS author, c.name AS category, b.stock_quantity
          FROM books b
          LEFT JOIN authors a ON b.author_id = a.id
          LEFT JOIN categories c ON b.category_id = c.id
          WHERE 1";
$params = [];
if ($category !== '') {
    $query .= " AND b.category_id = ?";
    $params[] = $category;
}
if ($author !== '') {
    $query .= " AND b.author_id = ?";
    $params[] = $author;
}
if ($status === 'in') {
    $query .= " AND b.stock_quantity > 0";
} elseif ($status === 'out') {
    $query .= " AND (b.stock_quantity IS NULL OR b.stock_quantity <= 0)";
}

$query .= " ORDER BY b.title";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$authors = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM authors ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);

$lowStockThreshold = 10;
?>
<div class="container mt-4">
  <h2>Book Stock</h2>
  <form class="row g-3 mb-3" method="get">
    <div class="col-md-3">
      <select class="form-select" name="category">
        <option value="">All categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $category ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select" name="author">
        <option value="">All authors</option>
        <?php foreach ($authors as $a): ?>
          <option value="<?= $a['id'] ?>" <?= $a['id'] == $author ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select" name="status">
        <option value="">All statuses</option>
        <option value="in" <?= $status === 'in' ? 'selected' : '' ?>>In stock</option>
        <option value="out" <?= $status === 'out' ? 'selected' : '' ?>>Out of stock</option>
      </select>
    </div>
    <div class="col-md-3">
      <button type="submit" class="btn btn-primary w-100">Filter</button>
    </div>
  </form>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Title</th>
            <th>Author</th>
            <th>Category</th>
            <th>Stock</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($books as $book): ?>
            <tr<?= ($book['stock_quantity'] !== null && $book['stock_quantity'] <= $lowStockThreshold) ? ' class="table-warning"' : '' ?>>
              <td><?= htmlspecialchars($book['title']) ?></td>
              <td><?= htmlspecialchars($book['author']) ?></td>
              <td><?= htmlspecialchars($book['category']) ?></td>
              <td>
                <?= ($book['stock_quantity'] !== null) ? (int)$book['stock_quantity'] : '<span class="text-muted">—</span>' ?>
                <?php if ($book['stock_quantity'] !== null && $book['stock_quantity'] <= $lowStockThreshold): ?>
                  <span class="badge bg-danger ms-2">Low</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="edit_stock.php?id=<?= urlencode($book['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="mt-3 text-end">
    <a href="report.php" class="btn btn-success">Export/Report</a>
  </div>
</div>
<?php require_once '../../templates/footer.php'; ?>
