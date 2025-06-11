<?php
// templates/header.php
require_once __DIR__ . '/../helpers/auth.php';

// Get the current page to conditionally display buttons
$currentPage = basename($_SERVER['PHP_SELF']);
$isAdminPage = strpos($_SERVER['REQUEST_URI'], '/admin/') === 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Book Publishing Site</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css" />
    <link rel="stylesheet" href="/css/footer-fix.css" />
    
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container">
                <a class="navbar-brand" href="/index.php">Book Publishing Site</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                       
                        <?php if (isLoggedIn() && $currentPage !== 'profile.php'): ?>
                            <li class="nav-item">
                                <a href="/profile.php" class="nav-link nav-btn"><i class="fas fa-user me-2"></i>Profile</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a href="/cart.php" class="nav-link nav-btn">
                                <i class="fas fa-shopping-cart me-2"></i>Cart
                            </a>
                        </li>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'accountant'): ?>
                            <li class="nav-item">
                                <a href="/accountant/index.php" class="nav-link nav-btn" style="background:#FFA500; color:#fff;">
                                    <i class="fas fa-calculator me-2"></i>Accountant Panel
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && !$isAdminPage): ?>
                            <li class="nav-item">
                                <a href="/admin/index.php" class="nav-link nav-btn" style="background:#FFA500; color:#fff;">
                                    <i class="fas fa-tools me-2"></i>Admin panel
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <!-- Менеджерська панель -->
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'manager'): ?>
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item">
                                <a href="/manager/index.php" class="nav-link nav-btn">Склад</a>
                            </li>
                            <li class="nav-item">
                                <a href="/manager/report.php" class="nav-link nav-btn">Звітність</a>
                            </li>
                        </ul>
                    <?php endif; ?>
                    <ul class="navbar-nav">
                        <?php if (isLoggedIn()): ?>
                            <li class="nav-item">
                                <form method="post" action="/logout.php" style="display: inline;">
                                    <button type="submit" class="nav-btn logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</button>
                                </form>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a href="/login.php" class="nav-link nav-btn login"><i class="fas fa-sign-in-alt me-2"></i>Login</a>
                            </li>
                            <li class="nav-item">
                                <a href="/register.php" class="nav-link nav-btn signup"><i class="fas fa-user-plus me-2"></i>Sign up</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="container py-4">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>