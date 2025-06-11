
<?php
require_once '../../helpers/auth.php';
requireManager();
require_once '../../config/db.php';
require_once '../../templates/header.php';

// Get all books with stock
$stmt = $pdo->query("SELECT b.title, CONCAT(a.first_name, ' ', a.last_name) AS author, c.name AS category, b.stock_quantity
    FROM books b
    LEFT JOIN authors a ON b.author_id = a.id
    LEFT JOIN categories c ON b.category_id = c.id
    ORDER BY b.title");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mt-4">
  <h2>Stock Report</h2>
  <div class="mb-3">
    <button class="btn btn-primary me-2" onclick="copyTableToClipboard()">Copy to clipboard</button>
    <a href="report.php?export=csv" class="btn btn-success">Export CSV</a>
  </div>
  <div class="table-responsive">
    <table class="table table-bordered align-middle mb-0" id="stockTable">
      <thead class="table-light">
        <tr>
          <th>Title</th>
          <th>Author</th>
          <th>Category</th>
          <th>Stock</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($books as $book): ?>
        <tr>
          <td><?= htmlspecialchars($book['title']) ?></td>
          <td><?= htmlspecialchars($book['author']) ?></td>
          <td><?= htmlspecialchars($book['category']) ?></td>
          <td><?= ($book['stock_quantity'] !== null) ? (int)$book['stock_quantity'] : '<span class="text-muted">—</span>' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="mt-3 text-muted small">
    <i>Tip: After copying, paste into Excel or Google Sheets.</i>
  </div>
</div>
<script>
function copyTableToClipboard() {
  const table = document.getElementById('stockTable');
  let text = '';
  for (let r = 0; r < table.rows.length; r++) {
    let row = [];
    for (let c = 0; c < table.rows[r].cells.length; c++) {
      row.push(table.rows[r].cells[c].innerText);
    }
    text += row.join('\t') + '\n';
  }
  navigator.clipboard.writeText(text).then(function() {
    alert('Table copied to clipboard!');
  });
}
</script>
<?php
// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=stock_report.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Title', 'Author', 'Category', 'Stock']);
    foreach ($books as $book) {
        fputcsv($output, [
            $book['title'],
            $book['author'],
            $book['category'],
            $book['stock_quantity'] !== null ? (int)$book['stock_quantity'] : ''
        ]);
    }
    fclose($output);
    exit;
}
require_once '../../templates/footer.php';
