<?php

declare(strict_types=1);

/*
 * Маршрутизатор для встроенного сервера PHP:
 * php -S localhost:8088 -t public router.php
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . '/public' . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/public/index.php';
