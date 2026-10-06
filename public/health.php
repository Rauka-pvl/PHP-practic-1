<?php

declare(strict_types=1);

require_once __DIR__ . '/../app.php';

$sapi = PHP_SAPI;
$webServer = $sapi !== 'cli';
$software = $_SERVER['SERVER_SOFTWARE'] ?? 'неизвестно';

$rows = [
    'Версия PHP' => PHP_VERSION,
    'Номер работы' => (string) PRACTICE_NUMBER,
    'Вариант' => VARIANT,
    'SAPI' => $sapi,
    'Выполнение через веб-сервер' => $webServer ? 'да' : 'нет',
    'Сервер' => $software,
    'Время UTC' => gmdate('Y-m-d H:i:s'),
];

$html = '';
foreach ($rows as $name => $value) {
    $html .= '<tr><th>' . e($name) . '</th><td>' . e($value) . '</td></tr>';
}

page(200, 'Диагностика окружения', "    <table class=\"result\"><tbody>{$html}</tbody></table>");
