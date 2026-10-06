<?php

declare(strict_types=1);

require_once __DIR__ . '/../app.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($path === '/index.php') {
    $path = '/';
}

$routes = [
    '/' => ['GET', 'HEAD'],
    '/quote' => ['GET', 'HEAD'],
    '/preview' => ['POST'],
];

try {
    if (!isset($routes[$path])) {
        throw new HttpError(404, 'Страница не найдена: ' . $path);
    }

    if (!in_array($method, $routes[$path], true)) {
        $allow = implode(', ', $routes[$path]);
        throw new HttpError(405, "Метод {$method} не разрешён для {$path}. Разрешено: {$allow}.", ['Allow' => $allow]);
    }

    switch ($path) {
        case '/':
            page(200, 'Расчёт стоимости', formsBlock());
            break;

        case '/quote':
            [$first, $second] = getQuantities($_GET);
            $total = totalMinor($first, $second);
            page(200, 'Результат расчёта', resultBlock($first, $second, $total));
            break;

        case '/preview':
            [$first, $second] = postQuantities($_POST);
            $total = totalMinor($first, $second);
            $note = '    <p class="note">Это только предпросмотр: заказ не сохраняется и никуда не отправляется.</p>';
            page(200, 'Предпросмотр заказа', resultBlock($first, $second, $total) . "\n" . $note);
            break;
    }
} catch (HttpError $error) {
    page(
        $error->status,
        'Ошибка ' . $error->status,
        '    <p class="error">' . e($error->getMessage()) . '</p>',
        $error->headers,
    );
} catch (TodoError $todo) {
    page(501, 'Не реализовано', '    <p class="error">Не выполнен TODO: ' . e($todo->getMessage()) . '</p>');
} catch (Throwable $exception) {
    error_log((string) $exception);
    page(500, 'Внутренняя ошибка', '    <p class="error">Внутренняя ошибка сервера.</p>');
}
