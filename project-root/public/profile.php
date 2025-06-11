<?php
// public/profile.php
require_once __DIR__ . '/../helpers/init.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/sanitizer.php';
require_once __DIR__ . '/../helpers/auth.php';

require_once __DIR__ . '/../vendor/autoload.php';
use Hashids\Hashids;

requireLogin(); 

$config = require __DIR__ . '/../config/config.php';
$hashids = new Hashids($config['hashids_salt'], 10);
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT first_name, last_name, username, email, role
    FROM users
    WHERE id = :id
    LIMIT 1
");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    header('Location: /login.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT b.*, 
           CONCAT(a.first_name, ' ', a.last_name) AS author_name,
           c.name AS category_name,
           s.name AS series_name
    FROM books b
    JOIN authors a ON b.author_id = a.id
    JOIN categories c ON b.category_id = c.id
    LEFT JOIN series s ON b.series_id = s.id
    JOIN favorites f ON b.id = f.book_id
    WHERE f.user_id = :user_id
    ORDER BY f.created_at DESC
");
$stmt->execute(['user_id' => $userId]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Book Publishing</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include __DIR__ . '/../templates/header.php'; ?>

<div class="container">
    <section class="profile-section">
        <h1>User Profile</h1>
        <div class="profile-card">
                <div class="profile-info">
                <h2>Account Information</h2>
                <p><i class="fas fa-user"></i> <strong>First Name:</strong> <?= htmlspecialchars($user['first_name']) ?></p>
                <p><i class="fas fa-user"></i> <strong>Last Name:</strong> <?= htmlspecialchars($user['last_name']) ?></p>
                <p><i class="fas fa-user-circle"></i> <strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
                <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><i class="fas fa-user-tag"></i> <strong>Role:</strong> <?= htmlspecialchars($user['role']) ?></p>
            </div>
            <!-- <div class="profile-actions">
                <button class="btn-edit">Edit Profile</button>
            </div> -->
        </div>
    </section>

    <section class="favorites-section">
        <h2>Favorite Books</h2>
        <?php if (empty($favorites)): ?>
            <p>No favorite books yet.</p>
        <?php else: ?>
            <div class="book-carousel">
                <?php foreach ($favorites as $book): ?>
                    <?php $encodedId = $hashids->encode($book['id']); ?>
                    <div class="book-card">
                        <a href="book.php?id=<?= htmlspecialchars($encodedId) ?>">
                            <img src="<?= htmlspecialchars($book['cover_image'] ?? 'images/default_cover.jpg') ?>" alt="Cover of <?= htmlspecialchars($book['title']) ?>">
                            <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
                            <div class="book-author"><?= htmlspecialchars($book['author_name']) ?></div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>