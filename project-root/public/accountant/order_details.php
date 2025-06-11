<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/auth.php';

requireAccountant();

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = null;
$orderItems = [];

if ($orderId > 0) {
    $stmt = $pdo->prepare("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if ($order) {
        $stmt = $pdo->prepare("SELECT oi.*, b.title FROM order_items oi JOIN books b ON oi.book_id = b.id WHERE oi.order_id = ?");
        $stmt->execute([$orderId]);
        $orderItems = $stmt->fetchAll();
    }
}

include __DIR__ . '/../../templates/header.php';
?>
<div class="container my-4">
    <a href="/accountant/index.php" class="btn btn-outline-secondary mb-3">&larr; Back to Orders</a>
    <?php if (!$order): ?>
        <div class="alert alert-danger">Order not found.</div>
    <?php else: ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h4 class="card-title mb-3">Order #<?= htmlspecialchars($order['id']) ?></h4>
                <p><strong>Date:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
                <p><strong>User:</strong> <?= htmlspecialchars($order['username']) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
                <p><strong>Total:</strong> $<?= htmlspecialchars(number_format($order['total_price'], 2)) ?></p>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">Order Items</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle rounded">
                        <thead class="table-light">
                            <tr>
                                <th>Book</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['title']) ?></td>
                                <td>$<?= htmlspecialchars(number_format($item['price'], 2)) ?></td>
                                <td><?= htmlspecialchars($item['quantity']) ?></td>
                                <td>$<?= htmlspecialchars(number_format($item['price'] * $item['quantity'], 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../../templates/footer.php'; ?>
