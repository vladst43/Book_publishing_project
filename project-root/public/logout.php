<?php
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/init.php';

logoutUser();
header('Location: /index.php');
exit;
