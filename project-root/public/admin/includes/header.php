<?php
// public/admin/includes/header.php
require_once __DIR__ . '/../../../helpers/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css" />
    <style>
        .admin-nav {
            background: #FFA500;
            color: #fff;
            padding: 10px 0;
            margin-bottom: 30px;
        }
        .admin-nav a {
            color: #fff;
            margin: 0 15px;
            font-weight: 500;
            text-decoration: none;
        }
        .admin-nav a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <nav class="admin-nav text-center">
            <a href="/admin/index.php">Back to Admin panel</a>
            <a href="/admin/books/index.php">Books</a>
            <a href="/admin/authors/index.php">Authors</a>
            <a href="/admin/categories/index.php">Categories</a>
            <a href="/admin/users/index.php">Users</a>
            <a href="/index.php" style="float:right; margin-right:30px;">Home</a>
        </nav>
    </header>
    <main class="container py-4">
