<?php
require_once __DIR__ . '/../../../helpers/auth.php';
require_once __DIR__ . '/../../../config/db.php';
requireAdmin();

$errors = [];
$last_name = '';
$first_name = '';
$birth_date = '';
$country = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $birth_date = trim($_POST['birth_date']);
    $country = trim($_POST['country']);

    if ($last_name === '') $errors[] = "Last name is required.";
    if ($first_name === '') $errors[] = "First name is required.";

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO authors (last_name, first_name, birth_date, country) VALUES (?, ?, ?, ?)");
        $stmt->execute([$last_name, $first_name, $birth_date ?: null, $country ?: null]);
        header('Location: index.php?created=1');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card shadow-sm mt-4 mb-4">
            <h2 class="text-center mb-4" style="color:#FFA500;">Add Author</h2>
            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="post" novalidate>
                <div class="mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($last_name) ?>" required />
                </div>
                <div class="mb-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($first_name) ?>" required />
                </div>
                <div class="mb-3">
                    <label for="birth_date" class="form-label">Birth Date</label>
                    <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?= htmlspecialchars($birth_date) ?>" />
                </div>
                <div class="mb-3">
                    <label for="country" class="form-label">Country</label>
                    <input type="text" class="form-control" id="country" name="country" value="<?= htmlspecialchars($country) ?>" />
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-warning" style="background:#FFA500; color:white;">Add Author</button>
                    <a href="index.php" class="btn btn-secondary mt-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
