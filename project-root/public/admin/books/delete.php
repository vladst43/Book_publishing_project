<?php
require_once __DIR__ . '/../../../helpers/auth.php';
require_once __DIR__ . '/../../../config/db.php';
requireAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    include __DIR__ . '/../includes/header.php';
    echo "<div class='alert alert-danger mt-4'>Book not found.</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($book['cover_image']) && file_exists(__DIR__ . '/../../../public/' . $book['cover_image'])) {
        unlink(__DIR__ . '/../../../public/' . $book['cover_image']);
    }
    $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php?deleted=1');
    exit;
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card shadow-sm mt-4 mb-4">
            <h2 class="text-center mb-4" style="color:#FFA500;">Delete Book</h2>
            <div class="alert alert-warning">
                <strong>Are you sure you want to delete this book?</strong><br>
                <b>Title:</b> <?= htmlspecialchars($book['title']) ?><br>
                <b>Author ID:</b> <?= htmlspecialchars($book['author_id']) ?><br>
                <b>Category ID:</b> <?= htmlspecialchars($book['category_id']) ?><br>
            </div>
            <form method="post">
                <div class="d-grid">
                    <button type="submit" class="btn btn-danger">Yes, delete</button>
                    <a href="index.php" class="btn btn-secondary mt-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>