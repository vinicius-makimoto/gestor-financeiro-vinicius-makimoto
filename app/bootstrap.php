<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/app.php';
require_once __DIR__ . '/repositories/JsonRepository.php';
require_once __DIR__ . '/helpers/http.php';
if (!function_exists('mb_strtolower')) { function mb_strtolower(string $value): string { return strtolower($value); } }
if (!function_exists('mb_strtoupper')) { function mb_strtoupper(string $value): string { return strtoupper($value); } }
if (!function_exists('mb_substr')) { function mb_substr(string $value, int $start, ?int $length = null): string { return substr($value, $start, $length); } }
if (!function_exists('mb_strlen')) { function mb_strlen(string $value): int { return strlen($value); } }
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])]);
    session_start();
}
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
foreach (['usuarios','contas','categorias','transacoes'] as $collection) {
    $file = DATA_DIR . DIRECTORY_SEPARATOR . $collection . '.json';
    if (!is_file($file)) (new JsonRepository($collection))->saveAll([]);
}

