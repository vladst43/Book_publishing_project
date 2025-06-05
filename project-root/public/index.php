<?php
// public/index.php
require_once __DIR__ . '/../helpers/init.php';
require_once __DIR__ . '/../config/db.php';
$config = require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/sanitizer.php';
require_once __DIR__ . '/../helpers/auth.php';

require_once __DIR__ . '/../vendor/autoload.php';
use Hashids\Hashids;

// Ініціалізація Hashids
$hashids = new Hashids($config['hashids_salt'] ?: 'fallback_salt_2025', 10);

$error = '';
$search_raw = $_GET['search'] ?? '';
$search = trim(sanitizeString($search_raw));

// Обмеження довжини пошуку
if (mb_strlen($search) > 100) {
    $search = mb_substr($search, 0, 100);
}

// Регулярний вираз для пошуку
if ($search !== '' && !preg_match('/^[\p{L}\p{N}\s\-\.,\'’]+$/', $search)) {
    $error = 'Please enter a valid keyword using letters, numbers, spaces, hyphens, dots, commas, or apostrophes.';
    $search = '';
}

// Пагінація
$page = filter_var($_GET['page'] ?? 1, FILTER_SANITIZE_NUMBER_INT);
$perPage = 50;
$offset = max(0, ($page - 1) * $perPage);

// Завантаження популярних книг
$books = [];
try {
    if ($search === '') {
        if (!empty($_GET) && $search_raw !== '') {
            $error = 'Please enter a valid keyword.';
        } elseif (!empty($_GET) && !isset($_GET['page'])) {
            $error = 'Please enter a keyword to search.';
        }

        $stmt = $pdo->query("
            SELECT b.*, 
                   CONCAT(a.first_name, ' ', a.last_name) AS author_name,
                   c.name AS category_name,
                   s.name AS series_name
            FROM books b
            JOIN authors a ON b.author_id = a.id
            JOIN categories c ON b.category_id = c.id
            LEFT JOIN series s ON b.series_id = s.id
            ORDER BY b.id DESC 
            LIMIT 10
        ");
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("
            SELECT b.*, 
                   CONCAT(a.first_name, ' ', a.last_name) AS author_name,
                   c.name AS category_name,
                   s.name AS series_name
            FROM books b
            JOIN authors a ON b.author_id = a.id
            JOIN categories c ON b.category_id = c.id
            LEFT JOIN series s ON b.series_id = s.id
            WHERE b.title LIKE :search OR CONCAT(a.first_name, ' ', a.last_name) LIKE :search
            ORDER BY b.id DESC 
            LIMIT 10
        ");
        $stmt->execute(['search' => '%' . $search . '%']);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = 'Failed to load books. Please try again later.';
    error_log($e->getMessage());
}

// Завантаження всіх книг
$all_books = [];
try {
    $stmt_ids = $pdo->query("SELECT id FROM books ORDER BY RAND() LIMIT $perPage OFFSET $offset");
    $ids = $stmt_ids->fetchAll(PDO::FETCH_COLUMN);
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt_all = $pdo->prepare("
            SELECT b.*, 
                   CONCAT(a.first_name, ' ', a.last_name) AS author_name,
                   c.name AS category_name,
                   s.name AS series_name
            FROM books b
            JOIN authors a ON b.author_id = a.id
            JOIN categories c ON b.category_id = c.id
            LEFT JOIN series s ON b.series_id = s.id
            WHERE b.id IN ($placeholders)
        ");
        $stmt_all->execute($ids);
        $all_books = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
    }
} catch ( PDOException $e) {
    $error = 'Failed to load all books. Please try again later.';
    error_log($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - Book Publishing</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .book-collage {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .book-collage .book-card {
            text-align: center;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 5px;
        }
        .book-collage img {
            max-width: 150px;
            height: auto;
            border-radius: 5px;
        }
        .book-collage .book-title {
            font-size: 16px;
            margin: 10px 0 5px;
            font-weight: bold;
        }
        .book-collage .book-author {
            font-size: .9rem;
            color: #666;
        }
        .book-collage a {
            text-decoration: none;
            color: inherit;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a {
            margin: 0 5px;
            padding: 5px 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
            text-decoration: none;
            color: #333;
        }
        .pagination a:hover {
            background-color: #f4f4f4;
        }
        .pagination .disabled {
            color: #999;
            pointer-events: none;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../templates/header.php'; ?>

<h1>Welcome to Our Book Publishing Site</h1>

<?php if ($error): ?>
    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="GET" action="index.php" novalidate>
    <input type="text" name="search" placeholder="Search by title or author" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
</form>

<h2>Popular Books</h2>
<div class="book-carousel">
    <?php if (!empty($books) && is_array($books)): ?>
        <?php foreach ($books as $book): ?>
            <div class="book-card">
                <a href="book.php?id=<?= htmlspecialchars($hashids->encode($book['id']), ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars(file_exists($book['cover_image'] ?? '') ? $book['cover_image'] : 'images/default_cover.jpg', ENT_QUOTES, 'UTF-8') ?>" 
                         alt="Cover of <?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="book-title"><?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="book-author"><?= htmlspecialchars($book['author_name'], ENT_QUOTES, 'UTF-8') ?></div>
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No popular books found.</p>
    <?php endif; ?>
</div>

<h2>All Books</h2>
<div class="book-collage">
    <?php if (!empty($all_books) && is_array($all_books)): ?>
        <?php foreach ($all_books as $book): ?>
            <div class="book-card">
                <a href="book.php?id=<?= htmlspecialchars($hashids->encode($book['id']), ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars(file_exists($book['cover_image'] ?? '') ? $book['cover_image'] : 'images/default_cover.jpg', ENT_QUOTES, 'UTF-8') ?>" 
                         alt="Cover of <?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="book-title"><?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="book-author"><?= htmlspecialchars($book['author_name'], ENT_QUOTES, 'UTF-8') ?></div>
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No books found.</p>
    <?php endif; ?>
</div>

<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="index.php?page=<?= ($page - 1) ?><?= $search ? '&search=' . urlencode($search) : '' ?>">Previous</a>
    <?php else: ?>
        <span class="disabled">Previous</span>
    <?php endif; ?>
    <a href="index.php?page=<?= ($page + 1) ?><?= $search ? '&search=' . urlencode($search) : '' ?>">Next</a>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>