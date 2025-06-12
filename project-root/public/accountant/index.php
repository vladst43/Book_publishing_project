<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/auth.php';

requireAccountant();

$orders = [];
$totalSales = 0;
$totalOrders = 0;
$topBooks = [];


$where = [];
$params = [];
if (!empty($_GET['user'])) {
    $where[] = 'u.username LIKE ?';
    $params[] = '%' . $_GET['user'] . '%';
}
if (!empty($_GET['status'])) {
    $where[] = 'o.status = ?';
    $params[] = $_GET['status'];
}
if (!empty($_GET['date_from'])) {
    $where[] = 'o.created_at >= ?';
    $params[] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $where[] = 'o.created_at <= ?';
    $params[] = $_GET['date_to'] . ' 23:59:59';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT o.id, o.created_at, o.total_price, o.status, u.username FROM orders o JOIN users u ON o.user_id = u.id $whereSql ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$stmt = $pdo->query("SELECT COUNT(*) as cnt, SUM(total_price) as sum FROM orders");
$stats = $stmt->fetch();
$totalOrders = $stats['cnt'] ?? 0;
$totalSales = $stats['sum'] ?? 0;

$stmt = $pdo->query("SELECT b.title, SUM(oi.quantity) as sold FROM order_items oi JOIN books b ON oi.book_id = b.id GROUP BY oi.book_id ORDER BY sold DESC LIMIT 5");
$topBooks = $stmt->fetchAll();

include __DIR__ . '/../../templates/header.php';
?>
<div class="container my-4">
    <h2 class="mb-4">Accountant Panel</h2>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-bg-light mb-3 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Total Sales</h5>
                    <p class="card-text fs-4 fw-bold">$<?= htmlspecialchars(number_format($totalSales, 2)) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-bg-light mb-3 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <p class="card-text fs-4 fw-bold"><?= htmlspecialchars($totalOrders) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-bg-light mb-3 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Top Books</h5>
                    <ul class="mb-0">
                        <?php foreach ($topBooks as $book): ?>
                            <li><?= htmlspecialchars($book['title']) ?> <span class="text-muted">(<?= $book['sold'] ?> sold)</span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="card-title mb-3">Orders</h4>

            <form class="row g-2 mb-4" method="get" action="">
                <div class="col-md-2">
                    <input type="text" name="user" class="form-control" placeholder="User" value="<?= htmlspecialchars($_GET['user'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= (($_GET['status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="paid" <?= (($_GET['status'] ?? '') === 'paid') ? 'selected' : '' ?>>Paid</option>
                        <option value="cancelled" <?= (($_GET['status'] ?? '') === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <a href="/accountant/index.php" class="btn btn-outline-secondary w-100">Reset</a>
                    <a href="/accountant/export_orders.php?<?= http_build_query($_GET) ?>" class="btn btn-outline-success w-100">Export CSV</a>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle rounded">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>User</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['id']) ?></td>
                            <td><?= htmlspecialchars($order['created_at']) ?></td>
                            <td><?= htmlspecialchars($order['username']) ?></td>
                            <td><?= htmlspecialchars($order['status']) ?></td>
                            <td>$<?= htmlspecialchars(number_format($order['total_price'], 2)) ?></td>
                            <td>
                                <a href="/accountant/order_details.php?id=<?= urlencode($order['id']) ?>" class="btn btn-outline-info btn-sm rounded-pill">Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../templates/footer.php'; ?>
