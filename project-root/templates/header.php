<?php
// templates/header.php
require_once __DIR__ . '/../helpers/auth.php';

// Get the current page to conditionally display buttons
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Book Publishing Site</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Якщо є кастомні стилі, підключіть їх: -->
    <link rel="stylesheet" href="/css/style.css" />
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
        }
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: #f97316;
        }
        .navbar-brand:hover {
            color: #ea580c;
        }
        .nav-link {
            color: #333;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }
        .nav-link:hover {
            color: #f97316;
        }
        .nav-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .nav-btn.login, .nav-btn.signup {
            background-color: #f97316;
            color: white;
            border: none;
        }
        .nav-btn.login:hover, .nav-btn.signup:hover {
            background-color: #ea580c;
        }
        .nav-btn.logout {
            background-color: #dc2626;
            color: white;
            border: none;
        }
        .nav-btn.logout:hover {
            background-color: #b91c1c;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container">
                <a class="navbar-brand" href="#">Book Publishing Site</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <?php if ($currentPage !== 'index.php'): ?>
                            <li class="nav-item">
                                <a href="/index.php" class="nav-link nav-btn"><i class="fas fa-home me-2"></i>Home</a>
                            </li>
                        <?php endif; ?>
                        <?php if (isLoggedIn() && $currentPage !== 'profile.php'): ?>
                            <li class="nav-item">
                                <a href="/profile.php" class="nav-link nav-btn"><i class="fas fa-user me-2"></i>Profile</a>
                            </li>
                        <?php endif; ?>
                    </ul>
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