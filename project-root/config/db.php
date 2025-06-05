<?php

$host = 'localhost';
$dbname = 'book_publishing';
$user = 'webuser';
$pass = 'strP@ss';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
    PDO::ATTR_EMULATE_PREPARES => false,              
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, $options);
} catch (PDOException $e) {
    if (getenv('APP_ENV') === 'development') {
        die('Database connection failed: ' . $e->getMessage());
    } else {
        
        error_log($e->getMessage());
        die('Internal server error');
    }
}
