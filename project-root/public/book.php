<?php
require_once __DIR__ . '/../helpers/init.php';
require_once __DIR__ . '/../config/db.php';
$config = require_once __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../helpers/sanitizer.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

require_once __DIR__ . '/../vendor/autoload.php';
use Hashids\Hashids;

requireLogin();

$hashids = new Hashids($config['hashids_salt'], 10);
$error = '';
$success = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $error = 'Book ID is required.';
} else {
    $decodedId = $hashids->decode($_GET['id']);
    if (empty($decodedId)) {
        $error = 'Invalid book ID.';
    } else {
        $bookId = $decodedId[0];
        $stmt = $pdo->prepare("
            SELECT b.*, 
                   CONCAT(a.first_name, ' ', a.last_name) AS author_name,
                   c.name AS category_name,
                   s.name AS series_name
            FROM books b
            JOIN authors a ON b.author_id = a.id
            JOIN categories c ON b.category_id = c.id
            LEFT JOIN series s ON b.series_id = s.id
            WHERE b.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $bookId]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$book) {
            $error = 'Book not found.';
        } else {
            
            $stmt = $pdo->prepare("
                SELECT id FROM favorites 
                WHERE user_id = :user_id AND book_id = :book_id
                LIMIT 1
            ");
            $stmt->execute([
                'user_id' => $_SESSION['user_id'],
                'book_id' => $bookId
            ]);
            $isFavorited = $stmt->fetch() !== false;

            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_favorites'])) {
                if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                    $error = 'Invalid CSRF token.';
                } else if ($isFavorited) {
                    $error = 'This book is already in your favorites.';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO favorites (user_id, book_id)
                        VALUES (:user_id, :book_id)
                    ");
                    try {
                        $stmt->execute([
                            'user_id' => $_SESSION['user_id'],
                            'book_id' => $bookId
                        ]);
                        
                        header("Location: book.php?id=" . urlencode($_GET['id']));
                        exit;
                    } catch (PDOException $e) {
                        $error = 'Failed to add book to favorites. Please try again.';
                    }
                }
            }

            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
                if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                    $error = 'Invalid CSRF token.';
                } else {
                    $reviewTextRaw = $_POST['review_text'] ?? '';
                    $reviewText = sanitizeString($reviewTextRaw);
                    if (empty($reviewText)) {
                        $error = 'Review text is required.';
                    } elseif (mb_strlen($reviewText) < 10) {
                        $error = 'Review must be at least 10 characters long.';
                    } elseif (mb_strlen($reviewText) > 1000) {
                        $error = 'Review must not exceed 1000 characters.';
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO reviews (user_id, book_id, review_text)
                            VALUES (:user_id, :book_id, :review_text)
                        ");
                        try {
                            $stmt->execute([
                                'user_id' => $_SESSION['user_id'],
                                'book_id' => $bookId,
                                'review_text' => $reviewTextRaw
                            ]);
                            $success = 'Review submitted successfully!';
                        } catch (PDOException $e) {
                            $error = 'Failed to submit review. Please try again.';
                        }
                    }
                }
            }

            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
                if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                    $error = 'Invalid CSRF token.';
                } else {
                    $reviewId = filter_var($_POST['review_id'] ?? '', FILTER_VALIDATE_INT);
                    if ($reviewId === false || $reviewId <= 0) {
                        $error = 'Invalid review ID.';
                    } else {
                        $canDeleteAny = isAdmin();
                        $sql = $canDeleteAny
                            ? "SELECT id FROM reviews WHERE id = :review_id AND book_id = :book_id LIMIT 1"
                            : "SELECT id FROM reviews WHERE id = :review_id AND user_id = :user_id AND book_id = :book_id LIMIT 1";
                        $params = [
                            'review_id' => $reviewId,
                            'book_id' => $bookId
                        ];
                        if (!$canDeleteAny) {
                            $params['user_id'] = $_SESSION['user_id'];
                        }
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        if ($stmt->fetch()) {
                            $deleteSql = $canDeleteAny
                                ? "DELETE FROM reviews WHERE id = :review_id"
                                : "DELETE FROM reviews WHERE id = :review_id AND user_id = :user_id";
                            $deleteParams = ['review_id' => $reviewId];
                            if (!$canDeleteAny) {
                                $deleteParams['user_id'] = $_SESSION['user_id'];
                            }
                            try {
                                $stmt = $pdo->prepare($deleteSql);
                                $stmt->execute($deleteParams);
                                $success = 'Review deleted successfully!';
                            } catch (PDOException $e) {
                                $error = 'Failed to delete review. Please try again.';
                            }
                        } else {
                            $error = $canDeleteAny ? 'Review not found.' : 'You can only delete your own reviews.';
                        }
                    }
                }
            }

            
            $stmt = $pdo->prepare("
                SELECT r.id, r.review_text, r.created_at, u.username, r.user_id
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                WHERE r.book_id = :book_id
                ORDER BY r.created_at DESC
            ");
            $stmt->execute(['book_id' => $bookId]);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Details - Book Publishing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include __DIR__ . '/../templates/header.php'; ?>

    <div class="container py-4">
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!$error && $book): ?>
            <h1 class="text-center mb-4"><?php echo htmlspecialchars($book['title']); ?></h1>
            <div class="card mb-4">
                <div class="row g-0">
                    <div class="col-md-4">
                        <img src="<?php echo htmlspecialchars($book['cover_image'] ?? 'images/default_cover.jpg'); ?>" class="img-fluid rounded-start" alt="Cover of <?php echo htmlspecialchars($book['title']); ?>">
                    </div>
                    <div class="col-md-8">
                        <div class="card-body">
                            <p class="card-text"><strong>Author:</strong> <?php echo htmlspecialchars($book['author_name']); ?></p>
                            <p class="card-text"><strong>Category:</strong> <?php echo htmlspecialchars($book['category_name']); ?></p>
                            <?php if ($book['series_name']): ?>
                                <p class="card-text"><strong>Series:</strong> <?php echo htmlspecialchars($book['series_name']); ?></p>
                            <?php endif; ?>
                            <p class="card-text"><strong>Published:</strong> <?php echo htmlspecialchars($book['publish_year'] ?? 'N/A'); ?></p>
                            <p class="card-text"><strong>Price:</strong> <?php echo htmlspecialchars($book['price'] !== null ? '$' . number_format($book['price'], 2) : 'N/A'); ?></p>
                            <p class="card-text"><strong>Stock Quantity:</strong> <?php echo htmlspecialchars($book['stock_quantity'] !== null ? $book['stock_quantity'] : 'N/A'); ?></p>
                            <p class="card-text"><strong>Description:</strong> <?php echo htmlspecialchars($book['description'] ?? 'No description available.'); ?></p>
                            <form method="post" action="book.php?id=<?php echo htmlspecialchars($_GET['id']); ?>" novalidate style="display:inline-block; margin-right:10px;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="add_to_favorites" value="1">
                                <button type="submit" class="btn btn-warning" <?php echo $isFavorited ? 'disabled' : ''; ?>>
                                    <i class="fas fa-heart me-2"></i><?php echo $isFavorited ? 'Already in Favorites' : 'Add to Favorites'; ?>
                                </button>
                            </form>

                            <form method="post" action="/add_to_cart.php" style="display:inline-block;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCsrfToken()); ?>">
                                <input type="hidden" name="book_id" value="<?php echo (int)$book['id']; ?>">
                                <input type="number" name="qty" value="1" min="1" max="<?php echo (int)($book['stock_quantity'] ?? 99); ?>" style="width:70px; display:inline-block;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-shopping-cart me-2"></i>Add to cart
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="mb-3">Reviews</h2>
            <form method="post" action="book.php?id=<?php echo htmlspecialchars($_GET['id']); ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="mb-3">
                    <label class="form-label">Your Review:</label>
                    <textarea name="review_text" rows="5" maxlength="1000" required class="form-control"></textarea>
                </div>
                <button type="submit" name="submit_review" class="btn btn-success">Submit Review</button>
            </form>

            <?php if (empty($reviews)): ?>
                <p class="text-muted mt-3">No reviews yet. Be the first to share your thoughts!</p>
            <?php else: ?>
                <div class="mt-3">
                    <?php foreach ($reviews as $review): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <p class="card-text"><strong><?php echo htmlspecialchars($review['username']); ?></strong> on <?php echo htmlspecialchars($review['created_at']); ?>:</p>
                                <p class="card-text"><?php echo htmlspecialchars($review['review_text']); ?></p>
                                <?php if ($review['user_id'] === $_SESSION['user_id'] || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')): ?>
                                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" onclick="setReviewId(<?php echo htmlspecialchars($review['id']); ?>)">
                                        <i class="fas fa-trash-alt me-2"></i>Delete Review
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmDeleteLabel">Confirm Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to delete this review?
                        </div>
                        <div class="modal-footer">
                            <form method="post" action="book.php?id=<?php echo htmlspecialchars($_GET['id']); ?>" novalidate id="deleteForm">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="review_id" id="reviewIdToDelete" value="">
                                <button type="submit" name="delete_review" class="btn btn-danger">Yes, Delete</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setReviewId(reviewId) {
            document.getElementById('reviewIdToDelete').value = reviewId;
        }
    </script>
</body>
</html>