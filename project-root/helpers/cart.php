<?php
// Функції для роботи з корзиною (зберігання у сесії)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Add item to cart (DB persistent for logged-in users)
function addToCart($bookId, $qty = 1) {
    require_once __DIR__ . '/../config/db.php';
    $bookId = (int)$bookId;
    $qty = (int)$qty;
    if ($bookId < 1 || $qty < 1) return;
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        // Find or create cart
        $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cartId = $stmt->fetchColumn();
        if (!$cartId) {
            $pdo->prepare("INSERT INTO carts (user_id) VALUES (?)")->execute([$userId]);
            $cartId = $pdo->lastInsertId();
        }
        // Add or update item
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND book_id = ?");
        $stmt->execute([$cartId, $bookId]);
        $item = $stmt->fetch();
        if ($item) {
            $newQty = $item['quantity'] + $qty;
            $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?")->execute([$newQty, $item['id']]);
        } else {
            $pdo->prepare("INSERT INTO cart_items (cart_id, book_id, quantity) VALUES (?, ?, ?)")->execute([$cartId, $bookId, $qty]);
        }
    } else {
        // Fallback to session for guests
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (isset($_SESSION['cart'][$bookId])) {
            $_SESSION['cart'][$bookId] += $qty;
        } else {
            $_SESSION['cart'][$bookId] = $qty;
        }
    }
}

// Update item quantity in cart
function updateCart($bookId, $qty) {
    require_once __DIR__ . '/../config/db.php';
    $bookId = (int)$bookId;
    $qty = (int)$qty;
    if ($bookId < 1) return;
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cartId = $stmt->fetchColumn();
        if ($cartId) {
            if ($qty < 1) {
                $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ? AND book_id = ?")->execute([$cartId, $bookId]);
            } else {
                $stmt = $pdo->prepare("SELECT id FROM cart_items WHERE cart_id = ? AND book_id = ?");
                $stmt->execute([$cartId, $bookId]);
                $itemId = $stmt->fetchColumn();
                if ($itemId) {
                    $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?")->execute([$qty, $itemId]);
                }
            }
        }
    } else {
        if ($qty < 1) {
            removeFromCart($bookId);
        } else {
            $_SESSION['cart'][$bookId] = $qty;
        }
    }
}

// Remove item from cart
function removeFromCart($bookId) {
    require_once __DIR__ . '/../config/db.php';
    $bookId = (int)$bookId;
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cartId = $stmt->fetchColumn();
        if ($cartId) {
            $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ? AND book_id = ?")->execute([$cartId, $bookId]);
        }
    } else {
        if (isset($_SESSION['cart'][$bookId])) {
            unset($_SESSION['cart'][$bookId]);
        }
    }
}

// Get all items from cart
function getCartItems() {
    global $pdo;
    if (!isset($pdo)) {
        require __DIR__ . '/../config/db.php';
    }
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT c.id as cart_id FROM carts c WHERE c.user_id = ?");
        $stmt->execute([$userId]);
        $cartId = $stmt->fetchColumn();
        $items = [];
        if ($cartId) {
            $stmt = $pdo->prepare("SELECT book_id, quantity FROM cart_items WHERE cart_id = ?");
            $stmt->execute([$cartId]);
            foreach ($stmt->fetchAll() as $row) {
                $items[$row['book_id']] = $row['quantity'];
            }
        }
        return $items;
    } else {
        return isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    }
}

// Порахувати загальну кількість товарів у корзині
function getCartCount() {
    return array_sum(getCartItems());
}

// Clear cart
function clearCart() {
    global $pdo;
    if (!isset($pdo)) {
        require __DIR__ . '/../config/db.php';
    }
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cartId = $stmt->fetchColumn();
        if ($cartId) {
            $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cartId]);
        }
    } else {
        unset($_SESSION['cart']);
    }
}

// Порахувати загальну суму (потрібно передати масив книг з цінами)
function getCartTotal($books) {
    $cart = getCartItems();
    $total = 0;
    foreach ($cart as $bookId => $qty) {
        if (isset($books[$bookId])) {
            $total += $books[$bookId]['price'] * $qty;
        }
    }
    return $total;
}

