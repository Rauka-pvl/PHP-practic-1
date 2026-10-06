<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app.php';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'];
try {
    if (!in_array($path, ['/', '/quote', '/preview'], true)) {
        http_response_code(404);
        page('Ресурс не найден', '<a href="/">К формам</a>');
    } elseif (!in_array($method, $path === '/preview' ? ['POST'] : ['GET', 'HEAD'], true)) {
        http_response_code(405);
        header('Allow: ' . ($path === '/preview' ? 'POST' : 'GET, HEAD'));
        page('Метод не разрешён', '<a href="/">К формам</a>');
    } elseif ($path === '/') {
        $prices = prices();
        $body = '<p>PHP ' . PHP_VERSION . '; время UTC ' . gmdate('H:i:s') . '</p>';
        $body .= '<p>' . h($prices[0]) . ': ' . $prices[1] . '; ' . h($prices[2]) . ': ' . $prices[3] . ' минимальных единиц.</p>';
        foreach (['/quote' => 'get', '/preview' => 'post'] as $action => $formMethod) {
            $body .= '<form action="' . $action . '" method="' . $formMethod . '">';
            $body .= '<h2>' . strtoupper($formMethod) . '</h2>';
            $body .= '<label>Первое количество<input name="qty_first" type="number" min="0" max="99" value="2" required></label>';
            $body .= '<label>Второе количество<input name="qty_second" type="number" min="0" max="99" value="1" required></label>';
            $body .= '<button>Рассчитать</button></form>';
        }
        page('Расчёт по HTTP-запросу', $body);
    } else {
        [$first, $second] = $path === '/quote' ? getQuantities($_GET) : postQuantities($_POST);
        $total = totalMinor($first, $second);
        page('Результат расчёта', '<p>Сумма: <strong>' . number_format($total / 100, 2, '.', '')
            . '</strong>; минимальные единицы: ' . $total . '</p><p>Расчёт выполнен, данные не сохранены.</p><a href="/">К формам</a>');
    }
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    page('Ошибка данных', '<p>' . h($error->getMessage()) . '</p>');
} catch (Throwable $error) {
    $pending = str_starts_with($error->getMessage(), 'TODO ');
    http_response_code($pending ? 501 : 500);
    page('Операция не выполнена', '<p>' . h($pending ? $error->getMessage() : 'Проверьте журнал PHP') . '</p>');
    if (!$pending) error_log((string) $error);
}
