<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/auth.php';

requireAccountant();

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

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=orders_export.csv');

$output = fopen('php://output', 'w');
$delimiter = ",";
$enclosure = '"';
$escape = "\\";
fputcsv($output, ['ID', 'Date', 'User', 'Status', 'Total'], $delimiter, $enclosure, $escape);
foreach ($orders as $order) {
    fputcsv($output, [
        $order['id'],
        $order['created_at'],
        $order['username'],
        $order['status'],
        number_format($order['total_price'], 2)
    ], $delimiter, $enclosure, $escape);
}
fclose($output);
exit;
