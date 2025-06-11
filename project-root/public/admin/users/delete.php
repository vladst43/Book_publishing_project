<?php
require_once __DIR__ . '/../../../helpers/auth.php';
require_once __DIR__ . '/../../../config/db.php';
requireAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    include __DIR__ . '/../includes/header.php';
    echo "<div class='alert alert-danger mt-4'>User not found.</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php?deleted=1');
    exit;
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card shadow-sm mt-4 mb-4">
            <h2 class="text-center mb-4" style="color:#FFA500;">Delete User</h2>
            <div class="alert alert-warning">
                <strong>Are you sure you want to delete this user?</strong><br>
                <b>Username:</b> <?= htmlspecialchars($user['username']) ?><br>
                <b>Email:</b> <?= htmlspecialchars($user['email']) ?><br>
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
