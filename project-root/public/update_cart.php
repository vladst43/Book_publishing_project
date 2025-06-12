<?php
require_once __DIR__ . '/../helpers/cart.php';
require_once __DIR__ . '/../helpers/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'], $_POST['qty'], $_POST['csrf_token'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        http_response_code(403);
        exit('CSRF validation failed');
    }
    $bookId = (int)$_POST['book_id'];
    $qty = (int)$_POST['qty'];
    updateCart($bookId, $qty);
    $_SESSION['flash'] = 'Quantity updated!';
    header('Location: /cart.php');
    exit;
} else {
    http_response_code(400);
    exit('Invalid request');
}
