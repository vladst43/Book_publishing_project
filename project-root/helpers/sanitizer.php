<?php

function sanitizeString(string $str): string {
    $str = trim($str);
    $str = stripslashes($str);
    $str = htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $str;
}

function sanitizeInt($value): int {
    return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : 0;
}

function sanitizeEmail(string $email): string {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return $email;
}

function sanitizeReviewText($input) {

    $sanitized = strip_tags($input);

    $sanitized = trim($sanitized);
    return $sanitized;
}