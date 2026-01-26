<?php

// Start session
session_start();

require_once 'Routing.php';
$path = trim($_SERVER['REQUEST_URI'], '/');
$path = parse_url($path, PHP_URL_PATH);

// Redirect root to dashboard
if (empty($path) || $path === 'index.php') {
    header('Location: /dashboard');
    exit();
}

Routing::run($path);
