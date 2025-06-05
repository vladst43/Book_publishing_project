<?php

function validateRequired(string $value): bool {
    return strlen(trim($value)) > 0;
}

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateMaxLength(string $value, int $max): bool {
    return mb_strlen($value) <= $max;
}

function validateMinLength(string $value, int $min): bool {
    return mb_strlen($value) >= $min;
}

function validateIntRange(int $value, int $min, int $max): bool {
    return $value >= $min && $value <= $max;
}

function validateNameCharacters($name): bool {
    return preg_match('/^[\p{L}\'\- ]+$/u', $name); 
}