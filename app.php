<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/stage.php';

final class HttpError extends RuntimeException
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly array $headers = [],
    ) {
        parent::__construct($message);
    }
}

final class TodoError extends RuntimeException
{
}

function todo(string $name): never
{
    throw new TodoError($name);
}

/**
 * Проверяет количество: строка с десятичной записью целого от 0 до 99
 * без ведущих нулей, знака, пробелов и дробной части.
 */
function quantity(mixed $raw, string $field = 'количество'): int
{
    if ($raw === null) {
        throw new HttpError(400, "Поле {$field} отсутствует в запросе.");
    }

    if (!is_string($raw) || preg_match('/^(0|[1-9][0-9]?)$/D', $raw) !== 1) {
        throw new HttpError(400, "Поле {$field} должно быть целым числом от 0 до 99 без ведущих нулей.");
    }

    return (int) $raw;
}

/**
 * @return array{0: string, 1: int, 2: string, 3: int}
 */
function prices(): array
{
    if (!isset(VARIANTS[VARIANT])) {
        throw new LogicException('Неизвестный вариант в config.php: ' . VARIANT);
    }

    return VARIANTS[VARIANT];
}

/**
 * P01-GET
 *
 * @param array<string, mixed> $query
 * @return array{0: int, 1: int}
 */
function getQuantities(array $query): array
{
    $first = quantity($query['qty_first'] ?? null, 'qty_first');
    $second = quantity($query['qty_second'] ?? null, 'qty_second');

    return [$first, $second];
}

/**
 * P01-POST
 *
 * @param array<string, mixed> $post
 * @return array{0: int, 1: int}
 */
function postQuantities(array $post): array
{
    $first = quantity($post['qty_first'] ?? null, 'qty_first');
    $second = quantity($post['qty_second'] ?? null, 'qty_second');

    return [$first, $second];
}

/**
 * P01-SUM
 */
function totalMinor(int $first, int $second): int
{
    [, $firstPrice, , $secondPrice] = prices();

    return $first * $firstPrice + $second * $secondPrice;
}

function formatMoney(int $minor): string
{
    return sprintf('%d.%02d', intdiv($minor, 100), $minor % 100);
}

/**
 * P01-HEADER
 */
function practiceHeader(): void
{
    header('X-Practice: ' . PRACTICE_CODE);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * @param array<string, string> $headers
 */
function page(int $status, string $title, string $body, array $headers = []): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    practiceHeader();

    foreach ($headers as $name => $value) {
        header("{$name}: {$value}");
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
        return;
    }

    $safeTitle = e($title);
    $practice = e(PRACTICE_CODE);

    echo <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$safeTitle} — ПР {$practice}</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<main class="container">
    <header class="top">
        <p class="badge">PHP · Практическая работа {$practice}</p>
        <h1>{$safeTitle}</h1>
    </header>
{$body}
    <footer class="bottom"><a href="/">На главную</a> · <a href="/health.php">Диагностика</a></footer>
</main>
</body>
</html>
HTML;
}

function resultBlock(int $first, int $second, int $total): string
{
    [$firstName, $firstPrice, $secondName, $secondPrice] = prices();

    $rows = '';
    foreach ([[$firstName, $firstPrice, $first], [$secondName, $secondPrice, $second]] as [$name, $price, $qty]) {
        $rows .= sprintf(
            '<tr><td>%s</td><td>%s</td><td>%d</td><td>%s</td></tr>',
            e($name),
            formatMoney($price),
            $qty,
            formatMoney($price * $qty),
        );
    }

    $sum = formatMoney($total);

    return <<<HTML
    <table class="result">
        <thead><tr><th>Позиция</th><th>Цена</th><th>Кол-во</th><th>Стоимость</th></tr></thead>
        <tbody>{$rows}</tbody>
        <tfoot><tr><th colspan="3">Итого</th><th class="sum">{$sum}</th></tr></tfoot>
    </table>
HTML;
}

function formsBlock(): string
{
    [$firstName, $firstPrice, $secondName, $secondPrice] = prices();
    $first = e($firstName) . ' (' . formatMoney($firstPrice) . ')';
    $second = e($secondName) . ' (' . formatMoney($secondPrice) . ')';
    $now = gmdate('Y-m-d H:i:s');
    $variant = e(VARIANT);

    return <<<HTML
    <p class="meta">Вариант {$variant}. Текущее время сервера (UTC): <strong>{$now}</strong></p>
    <section class="forms">
        <form method="get" action="/quote" class="card">
            <h2>Расчёт (GET)</h2>
            <label>{$first} <input type="number" name="qty_first" min="0" max="99" value="2" required></label>
            <label>{$second} <input type="number" name="qty_second" min="0" max="99" value="1" required></label>
            <button type="submit">Рассчитать</button>
        </form>
        <form method="post" action="/preview" class="card">
            <h2>Предпросмотр (POST)</h2>
            <label>{$first} <input type="number" name="qty_first" min="0" max="99" value="2" required></label>
            <label>{$second} <input type="number" name="qty_second" min="0" max="99" value="1" required></label>
            <button type="submit">Предпросмотр</button>
        </form>
    </section>
HTML;
}
