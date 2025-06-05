<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../helpers/validation.php';
require_once __DIR__ . '/../../helpers/sanitizer.php';
require_once __DIR__ . '/../../config/db.php';

requireAdmin();

$errors = [];
$title = '';
$author_id = '';
$category_id = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    }

    $title = sanitizeString($_POST['title'] ?? '');
    $author_id = (int)($_POST['author_id'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = sanitizeString($_POST['description'] ?? '');

    if (empty($title)) {
        $errors[] = 'Title is required.';
    }
    if ($author_id <= 0) {
        $errors[] = 'Author is required.';
    }
    if ($category_id <= 0) {
        $errors[] = 'Category is required.';
    }

    $coverPath = null;
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
            $destination = __DIR__ . '/../../public/uploads/books/' . $newFileName;
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $errors[] = 'Failed to save cover image.';
            } else {
                $coverPath = 'uploads/books/' . $newFileName;
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO books (title, author_id, category_id, description, cover_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $author_id, $category_id, $description, $coverPath]);
        header('Location: index.php');
        exit;
    }
}

$authors = $pdo->query("SELECT id, name FROM authors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
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
<?php include __DIR__ . '/../../templates/header.php'; ?>

<h1>Add New Book</h1>

<?php if ($errors): ?>
    <ul style="color:red;">
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>" />
    <label>
        Title:<br />
        <input type="text" name="title" value="<?= htmlspecialchars($title) ?>" required />
    </label><br /><br />
    <label>
        Author:<br />
        <select name="author_id" required>
            <option value="">-- Select author --</option>
            <?php foreach ($authors as $author): ?>
                <option value="<?= $author['id'] ?>" <?= $author['id'] == $author_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($author['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br /><br />
    <label>
        Category:<br />
        <select name="category_id" required>
            <option value="">-- Select category --</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['id'] ?>" <?= $category['id'] == $category_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br /><br />
    <label>
        Description:<br />
        <textarea name="description" rows="5"><?= htmlspecialchars($description) ?></textarea>
    </label><br /><br />
    <label>
        Cover Image (JPEG, PNG, WEBP, max 2MB):<br />
        <input type="file" name="cover" accept=".jpg,.jpeg,.png,.webp" />
    </label><br /><br />
    <button type="submit">Add Book</button>
</form>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
</body>
</html>
