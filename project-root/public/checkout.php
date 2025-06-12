<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/cart.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/auth.php';

requireLogin();

$cart = getCartItems();
$books = [];
$total = 0;

if ($cart) {
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $stmt = $pdo->query("SELECT * FROM books WHERE id IN ($ids)");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $books[$row['id']] = $row;
    }
    $total = getCartTotal($books);
}

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $flash = 'CSRF validation failed.';
    } elseif (!$cart) {
        $flash = 'Your cart is empty.';
    } else {
        $insufficientStock = [];
        foreach ($cart as $bookId => $qty) {
            if (!isset($books[$bookId]) || $books[$bookId]['stock_quantity'] < $qty) {
                $insufficientStock[] = $books[$bookId]['title'] ?? ('Book ID ' . $bookId);
            }
        }
        if (!empty($insufficientStock)) {
            $flash = 'Not enough stock for: ' . htmlspecialchars(implode(', ', $insufficientStock));
        } else {

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, 'pending')");
                $stmt->execute([$_SESSION['user_id'], $total]);
                $orderId = $pdo->lastInsertId();
                $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmtStock = $pdo->prepare("UPDATE books SET stock_quantity = stock_quantity - ? WHERE id = ?");
                foreach ($cart as $bookId => $qty) {
                    if (isset($books[$bookId])) {
                        $stmtItem->execute([$orderId, $bookId, $qty, $books[$bookId]['price']]);
                        $stmtStock->execute([$qty, $bookId]);
                    }
                }
                $pdo->commit();
                clearCart();
                $flash = 'Order placed successfully!';
                $cart = [];
            } catch (Exception $e) {
                $pdo->rollBack();
                $flash = 'Failed to place order. Please try again.';
            }
        }
    }
}

include __DIR__ . '/../templates/header.php';
?>
<div class="container">
    <h2>Checkout</h2>
    <?php if ($flash): ?>
        <div class="flash-message"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?php if (!$cart): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>

        <?php $hasStockWarning = false; ?>
        <?php foreach ($cart as $bookId => $qty): ?>
            <?php if (isset($books[$bookId]) && $books[$bookId]['stock_quantity'] < $qty): ?>
                <?php $hasStockWarning = true; ?>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($hasStockWarning): ?>
            <div class="alert alert-danger mb-3">Some items in your cart exceed available stock. Please update quantities.</div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle rounded shadow-sm overflow-hidden" style="border-radius: 16px;">
                <thead class="table-light">
                    <tr>
                        <th>Book</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($cart as $bookId => $qty): ?>
                    <?php if (!isset($books[$bookId])) continue; ?>
                    <tr class="bg-white">
                        <td class="fw-semibold"><?= htmlspecialchars($books[$bookId]['title']) ?></td>
                        <td>$<?= htmlspecialchars(number_format($books[$bookId]['price'], 2)) ?></td>
                        <td><?= $qty ?></td>
                        <td class="fw-semibold">$<?= htmlspecialchars(number_format($books[$bookId]['price'] * $qty, 2)) ?></td>
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
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                <button type="submit" class="btn btn-success btn-lg rounded-pill px-5">Confirm order</button>
            </form>
        </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
