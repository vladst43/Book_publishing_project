<?php
require_once __DIR__ . '/../../../helpers/auth.php';
require_once __DIR__ . '/../../../helpers/csrf.php';
require_once __DIR__ . '/../../../helpers/validation.php';
require_once __DIR__ . '/../../../helpers/sanitizer.php';
require_once __DIR__ . '/../../../config/db.php';

requireAdmin();

$errors = [];
$title = '';
$isbn = '';
$publish_year = '';
$language = '';
$author_id = '';
$series_id = '';
$category_id = '';
$price = '';
$stock_quantity = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    }

    $title = sanitizeString($_POST['title'] ?? '');
    $isbn = sanitizeString($_POST['isbn'] ?? '');
    $publish_year = (int)($_POST['publish_year'] ?? 0);
    $language = sanitizeString($_POST['language'] ?? '');
    $author_id = (int)($_POST['author_id'] ?? 0);
    $series_id = !empty($_POST['series_id']) ? (int)$_POST['series_id'] : null;
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = isset($_POST['price']) ? floatval($_POST['price']) : null;
    $stock_quantity = isset($_POST['stock_quantity']) ? (int)$_POST['stock_quantity'] : null;
    $description = sanitizeString($_POST['description'] ?? '');

    if (empty($title)) {
        $errors[] = 'Title is required.';
    }
    if (empty($isbn)) {
        $errors[] = 'ISBN is required.';
    } elseif (!preg_match('/^[0-9Xx-]{10,20}$/', $isbn)) {
        $errors[] = 'ISBN must be 10-20 characters (digits, X, -).';
    }
    if ($publish_year < 1800 || $publish_year > (int)date('Y')) {
        $errors[] = 'Publish year is required and must be valid.';
    }
    if (empty($language)) {
        $errors[] = 'Language is required.';
    }
    if ($author_id <= 0) {
        $errors[] = 'Author is required.';
    }
    if ($category_id <= 0) {
        $errors[] = 'Category is required.';
    }
    if ($price !== null && $price < 0) {
        $errors[] = 'Price must be non-negative.';
    }
    if ($stock_quantity !== null && $stock_quantity < 0) {
        $errors[] = 'Stock quantity must be non-negative.';
    }

    $coverImage = null;
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $file = $_FILES['cover'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error uploading cover image.';
        } elseif (!in_array(mime_content_type($file['tmp_name']), $allowedMimeTypes, true)) {
            $errors[] = 'Cover image must be JPEG, PNG, or WEBP.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Cover image size must be less than 2MB.';
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFileName = bin2hex(random_bytes(16)) . '.' . $ext;
            $destination = __DIR__ . '/../../../public/uploads/books/' . $newFileName;
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $errors[] = 'Failed to save cover image.';
            } else {
                $coverImage = 'uploads/books/' . $newFileName;
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO books (title, isbn, publish_year, language, author_id, series_id, category_id, price, stock_quantity, description, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $title,
            $isbn,
            $publish_year,
            $language,
            $author_id,
            $series_id,
            $category_id,
            $price,
            $stock_quantity,
            $description,
            $coverImage
        ]);
        header('Location: index.php');
        exit;
    }
}

$authors = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM authors ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Add New Book</title>
<link rel="stylesheet" href="/css/style.css" />
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card shadow-sm mt-4 mb-4">
            <h2 class="text-center mb-4" style="color:#FFA500;">Add New Book</h2>

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
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>" />
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
                    <label for="series_id" class="form-label">Series (optional)</label>
                    <input type="number" class="form-control" id="series_id" name="series_id" value="<?= htmlspecialchars($series_id) ?>" />
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
                    <label for="price" class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?= htmlspecialchars($price) ?>" />
                </div>
                <div class="mb-3">
                    <label for="stock_quantity" class="form-label">Stock Quantity</label>
                    <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="<?= htmlspecialchars($stock_quantity) ?>" />
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
                    <input type="file" class="form-control" id="cover" name="cover" accept=".jpg,.jpeg,.png,.webp" />
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-warning" style="background:#FFA500; color:white;">Add Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
