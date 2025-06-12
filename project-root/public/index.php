<?php
require_once __DIR__ . '/../helpers/init.php';
require_once __DIR__ . '/../config/db.php';
$config = require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/sanitizer.php';
require_once __DIR__ . '/../helpers/auth.php';

require_once __DIR__ . '/../vendor/autoload.php';
use Hashids\Hashids;

$hashids = new Hashids($config['hashids_salt'] ?: 'fallback_salt_2025', 10);

$error = '';
$search_raw = $_GET['search'] ?? '';
$search = trim(sanitizeString($search_raw));


if ($search !== '') {
    if (mb_strlen($search) > 100) {
        $error = 'Search term must not exceed 100 characters.';
        $search = mb_substr($search, 0, 100);
    }
    elseif (mb_strlen($search) < 2) {
        $error = 'Search term must be at least 2 characters long.';
        $search = '';
    }
    elseif (!preg_match('/^[\p{L}\p{N}\s\-\.,\'’]+$/u', $search)) {
        $error = 'Please use letters, numbers, spaces, hyphens, dots, commas, or apostrophes.';
        $search = '';
    }
} elseif (!empty($_GET['search'])) {
    $error = 'Please enter a search term.';
    $search = '';
}

$page = filter_var($_GET['page'] ?? 1, FILTER_SANITIZE_NUMBER_INT);
$page = max(1, $page);
$perPage = 15;
$offset = ($page - 1) * $perPage;


$category = $_GET['category'] ?? '';
$author = $_GET['author'] ?? '';


$totalBooks = 0;
if ($search !== '') {
    try {
        $stmt_count = $pdo->prepare("
            SELECT COUNT(*) 
            FROM books b
            JOIN authors a ON b.author_id = a.id
            WHERE b.title LIKE :title_search OR CONCAT(a.first_name, ' ', a.last_name) LIKE :author_search
        ");
        $stmt_count->execute([
            'title_search' => '%' . $search . '%',
            'author_search' => '%' . $search . '%'
        ]);
        $totalBooks = $stmt_count->fetchColumn();
    } catch (PDOException $e) {
        $error = 'Failed to count search results.';
        error_log('Count query error: ' . $e->getMessage() . ' | Search term: ' . $search);
    }
} else {
    try {
        $stmt_count = $pdo->query("SELECT COUNT(*) FROM books");
        $totalBooks = $stmt_count->fetchColumn();
    } catch (PDOException $e) {
        $error = 'Failed to count books.';
        error_log('Count query error: ' . $e->getMessage());
    }
}
$totalPages = max(1, ceil($totalBooks / $perPage));

$popular_books = [];
if ($search === '') {
    try {
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
        $popular_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = 'Failed to load popular books.';
        error_log('Popular books query error: ' . $e->getMessage() . ' | Query: SELECT ... LIMIT 10');
    }
}


$all_books = [];
try {
    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = '(b.title LIKE :title_search OR CONCAT(a.first_name, " ", a.last_name) LIKE :author_search)';
        $params['title_search'] = '%' . $search . '%';
        $params['author_search'] = '%' . $search . '%';
    }
    if ($category !== '') {
        $where[] = 'b.category_id = :category_id';
        $params['category_id'] = $category;
    }
    if ($author !== '') {
        $where[] = 'b.author_id = :author_id';
        $params['author_id'] = $author;
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $sql = "SELECT b.*, CONCAT(a.first_name, ' ', a.last_name) AS author_name, c.name AS category_name, s.name AS series_name FROM books b JOIN authors a ON b.author_id = a.id JOIN categories c ON b.category_id = c.id LEFT JOIN series s ON b.series_id = s.id $whereSql ORDER BY b.id DESC LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $all_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Failed to load books.';
    error_log('Books query error: ' . $e->getMessage() . ' | Search term: ' . $search . ' | Query: SELECT ... LIMIT ...');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - Book Publishing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include __DIR__ . '/../templates/header.php'; ?>

<div class="container">
    <h1 class="text-center mb-4">Welcome to Our Book Publishing Site</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="search-form-container">
        <form method="GET" action="index.php" id="search-form" class="search-form" novalidate>
            <div class="position-relative flex-grow-1">
                <input type="text" name="search" id="search-input" class="search-input form-control" placeholder="Search by title or author" value="<?php echo htmlspecialchars($search); ?>">
                <i class="fas fa-search search-icon"></i>
                <?php if ($search !== ''): ?>
                    <i class="fas fa-times clear-icon" id="clear-search"></i>
                <?php endif; ?>
            </div>
            <button type="submit" class="search-button">Search</button>
        </form>
        <p id="search-error" style="display: none;">Please enter a search term.</p>
    </div>

    <?php if ($search !== ''): ?>
        <div class="search-results-header">
            <h2 class="mb-3">Search Results for "<?php echo htmlspecialchars($search); ?>"</h2>
            <a href="index.php" class="btn btn-outline-orange">Show All Books</a>
        </div>
    <?php else: ?>
        <h2 class="mb-3">Popular Books</h2>
    <?php endif; ?>

    <div class="book-carousel">
        <?php if ($search === '' && !empty($popular_books) && is_array($popular_books)): ?>
            <?php foreach ($popular_books as $book): ?>
                <div class="book-card">
                    <a href="book.php?id=<?php echo htmlspecialchars($hashids->encode($book['id']), ENT_QUOTES, 'UTF-8'); ?>">
                        <img src="<?php echo htmlspecialchars(file_exists($book['cover_image'] ?? '') ? $book['cover_image'] : 'images/default_cover.jpg', ENT_QUOTES, 'UTF-8'); ?>" 
                             alt="Cover of <?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="book-title"><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="book-author"><?php echo htmlspecialchars($book['author_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="book-price"><?php echo htmlspecialchars($book['price'] !== null ? '$' . number_format($book['price'], 2) : 'N/A'); ?></div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No popular books found.</p>
        <?php endif; ?>
    </div>

    <?php

    $categoriesList = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $authorsList = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM authors ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="mb-0"><?php echo $search !== '' ? 'Search Results' : 'All Books'; ?></h2>
      <form method="get" class="d-flex align-items-center" style="gap:8px;">
        <?php if ($search !== ''): ?>
          <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
        <?php endif; ?>
        <label for="category" class="form-label mb-0 me-2">Category:</label>
        <select name="category" id="category" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All</option>
          <?php foreach ($categoriesList as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $category ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <label for="author" class="form-label mb-0 me-2">Author:</label>
        <select name="author" id="author" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All</option>
          <?php foreach ($authorsList as $a): ?>
            <option value="<?= $a['id'] ?>" <?= $a['id'] == $author ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
    <div class="book-collage">
        <?php if (!empty($all_books) && is_array($all_books)): ?>
            <?php foreach ($all_books as $book): ?>
                <div class="book-card">
                    <a href="book.php?id=<?php echo htmlspecialchars($hashids->encode($book['id']), ENT_QUOTES, 'UTF-8'); ?>">
                        <img src="<?php echo htmlspecialchars(file_exists($book['cover_image'] ?? '') ? $book['cover_image'] : 'images/default_cover.jpg', ENT_QUOTES, 'UTF-8'); ?>" 
                             alt="Cover of <?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="book-title"><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="book-author"><?php echo htmlspecialchars($book['author_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="book-price"><?php echo htmlspecialchars($book['price'] !== null ? '$' . number_format($book['price'], 2) : 'N/A'); ?></div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No <?php echo $search !== '' ? 'search results' : 'books'; ?> found.</p>
        <?php endif; ?>
    </div>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="index.php?page=<?php echo ($page - 1); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-outline-orange">Previous</a>
        <?php else: ?>
            <span class="disabled">Previous</span>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
            <a href="index.php?page=<?php echo ($page + 1); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-outline-orange">Next</a>
        <?php else: ?>
            <span class="disabled">Next</span>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('search-form').addEventListener('submit', function(e) {
        const searchInput = document.getElementById('search-input');
        const searchError = document.getElementById('search-error');
        const searchValue = searchInput.value.trim();
        
        if (searchValue === '') {
            e.preventDefault();
            searchError.style.display = 'block';
            searchInput.focus();
        } else {
            searchError.style.display = 'none';
        }
    });

    document.getElementById('search-input').addEventListener('input', function() {
        document.getElementById('search-error').style.display = 'none';
    });

    const clearSearch = document.getElementById('clear-search');
    if (clearSearch) {
        clearSearch.addEventListener('click', function() {
            document.getElementById('search-input').value = '';
            window.location.href = 'index.php';
        });
    }
</script>
</body>
</html>