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

$authors = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM authors ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$title = $book['title'];
$isbn = $book['isbn'];
$publish_year = $book['publish_year'];
$language = $book['language'];
$price = $book['price'];
$stock_quantity = $book['stock_quantity'];
$author_id = $book['author_id'];
$category_id = $book['category_id'];
$description = $book['description'];
$cover = $book['cover_image'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $isbn = trim($_POST['isbn']);
    $publish_year = (int)$_POST['publish_year'];
    $language = trim($_POST['language']);
    $price = floatval($_POST['price']);
    $stock_quantity = (int)$_POST['stock_quantity'];
    $author_id = (int)$_POST['author_id'];
    $category_id = (int)$_POST['category_id'];
    $description = trim($_POST['description']);

    if ($title === '') $errors[] = "Title is required.";
    if ($isbn === '') $errors[] = "ISBN is required.";
    if ($publish_year < 1800 || $publish_year > (int)date('Y')) $errors[] = "Publish year is required and must be valid.";
    if ($language === '') $errors[] = "Language is required.";
    if ($price < 0) $errors[] = "Price must be non-negative.";
    if ($stock_quantity < 0) $errors[] = "Stock quantity must be non-negative.";
    if (!$author_id) $errors[] = "Author is required.";
    if (!$category_id) $errors[] = "Category is required.";

    if (!empty($_FILES['cover']['name'])) {
        $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $coverName = uniqid('cover_', true) . '.' . $ext;
            $destination = __DIR__ . '/../../../public/uploads/books/' . $coverName;
            if (move_uploaded_file($_FILES['cover']['tmp_name'], $destination)) {
                $cover = 'uploads/books/' . $coverName;
            } else {
                $errors[] = "Failed to upload cover image.";
            }
        } else {
            $errors[] = "Invalid cover image format.";
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare("UPDATE books SET title=?, isbn=?, publish_year=?, language=?, price=?, stock_quantity=?, author_id=?, category_id=?, description=?, cover_image=? WHERE id=?");
        $stmt->execute([$title, $isbn, $publish_year, $language, $price, $stock_quantity, $author_id, $category_id, $description, $cover, $id]);
        header('Location: index.php?updated=1');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card shadow-sm mt-4 mb-4">
            <h2 class="text-center mb-4" style="color:#FFA500;">Edit Book</h2>
            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" novalidate>
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required />
                </div>
                <div class="mb-3">
                    <label for="isbn" class="form-label">ISBN</label>
                    <input type="text" class="form-control" id="isbn" name="isbn" value="<?= htmlspecialchars($isbn) ?>" required />
                </div>
                <div class="mb-3">
                    <label for="publish_year" class="form-label">Publish Year</label>
                    <input type="number" class="form-control" id="publish_year" name="publish_year" min="1800" max="<?= date('Y') ?>" value="<?= htmlspecialchars($publish_year) ?>" required />
                </div>
                <div class="mb-3">
                    <label for="language" class="form-label">Language</label>
                    <input type="text" class="form-control" id="language" name="language" value="<?= htmlspecialchars($language) ?>" required />
                </div>
                <div class="mb-3">
                    <label for="price" class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?= htmlspecialchars($price) ?>" required />
                </div>
                <div class="mb-3">
                    <label for="stock_quantity" class="form-label">Stock Quantity</label>
                    <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="<?= htmlspecialchars($stock_quantity) ?>" required />
                </div>
                <div class="mb-3">
                    <label for="author_id" class="form-label">Author</label>
                    <select class="form-select" id="author_id" name="author_id" required>
                        <option value="">-- Select author --</option>
                        <?php foreach ($authors as $author): ?>
                            <option value="<?= $author['id'] ?>" <?= $author['id'] == $author_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($author['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">-- Select category --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= $category['id'] == $category_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="5"><?= htmlspecialchars($description) ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="cover" class="form-label">Cover Image (JPEG, PNG, WEBP, max 2MB)</label>
                    <?php if ($cover): ?>
                        <div class="mb-2">
                            <img src="/<?= htmlspecialchars($cover) ?>" alt="cover" style="max-width:100px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="cover" name="cover" accept=".jpg,.jpeg,.png,.webp" />
                    <small class="text-muted">Leave empty to keep current cover.</small>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-warning" style="background:#FFA500; color:white;">Save changes</button>
                    <a href="index.php" class="btn btn-secondary mt-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>