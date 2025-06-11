<?php
require_once __DIR__ . '/../helpers/cart.php';
require_once __DIR__ . '/../helpers/csrf.php';

// Перевірка POST-запиту та CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'], $_POST['csrf_token'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        http_response_code(403);
        exit('CSRF validation failed');
    }
    $bookId = (int)$_POST['book_id'];
    $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
    addToCart($bookId, $qty);
    // Flash-повідомлення (опціонально)
    $_SESSION['flash'] = 'Book added to cart!';
    // Повернення назад
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    http_response_code(400);
    exit('Invalid request');
}
