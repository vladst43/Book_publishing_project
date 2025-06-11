<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/cart.php';
require_once __DIR__ . '/../helpers/csrf.php';

$sessionAlreadyStarted = session_status() === PHP_SESSION_ACTIVE;
if (!$sessionAlreadyStarted) {
    session_start();
}
$cart = getCartItems();
$books = [];
$total = 0;

if ($cart) {
    // Отримати дані про книги з БД
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $stmt = $pdo->query("SELECT * FROM books WHERE id IN ($ids)");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $books[$row['id']] = $row;
    }
    $total = getCartTotal($books);
}

// Flash-повідомлення
$flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
unset($_SESSION['flash']);

include __DIR__ . '/../templates/header.php';
?>
<div class="container">
    <h2>Cart</h2>
    <?php if ($flash): ?>
        <div class="flash-message"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?php if (!$cart): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle rounded shadow-sm overflow-hidden" style="border-radius: 16px;">
                <thead class="table-light">
                    <tr>
                        <th>Book</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($cart as $bookId => $qty): ?>
                    <?php if (!isset($books[$bookId])) continue; ?>
                    <tr class="bg-white">
                        <td class="fw-semibold"><?= htmlspecialchars($books[$bookId]['title']) ?></td>
                        <td>$<?= htmlspecialchars(number_format($books[$bookId]['price'], 2)) ?></td>
                        <td>
                            <form action="/update_cart.php" method="post" class="d-flex align-items-center gap-2">
                                <input type="number" name="qty" value="<?= $qty ?>" min="1" class="form-control form-control-sm rounded-pill" style="width:70px;">
                                <input type="hidden" name="book_id" value="<?= $bookId ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                                <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill">Update</button>
                            </form>
                        </td>
                        <td class="fw-semibold">$<?= htmlspecialchars(number_format($books[$bookId]['price'] * $qty, 2)) ?></td>
                        <td>
                            <form action="/remove_from_cart.php" method="post" style="display:inline;">
                                <input type="hidden" name="book_id" value="<?= $bookId ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="cart-total mt-4 p-3 bg-light rounded shadow-sm d-flex justify-content-between align-items-center" style="max-width: 400px; margin: 0 auto;">
            <span class="fs-5 fw-bold">Total:</span>
            <span class="fs-5 fw-bold text-success">$<?= htmlspecialchars(number_format($total, 2)) ?></span>
        </div>
        <div class="text-center mt-3">
            <a href="/checkout.php" class="btn btn-success btn-lg rounded-pill px-5">Checkout</a>
        </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
