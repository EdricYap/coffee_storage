<?php

declare(strict_types=1);

session_start();


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function go(string $page, array $params = []): never
{
    $query = http_build_query(['page' => $page] + $params);
    header('Location: index.php?' . $query);
    exit;
}

function notify(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function postString(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}
