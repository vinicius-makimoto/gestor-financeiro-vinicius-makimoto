<?php
declare(strict_types=1);

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function inputData(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw !== '') { $decoded = json_decode($raw, true); if (is_array($decoded)) return $decoded; }
    return $_POST;
}
function requireLogin(): array
{
    if (empty($_SESSION['user'])) jsonResponse(['ok'=>false,'message'=>'Sua sessão expirou. Entre novamente.'], 401);
    return $_SESSION['user'];
}
function requireAdmin(): array
{
    $user = requireLogin();
    if (($user['role'] ?? '') !== 'admin') jsonResponse(['ok'=>false,'message'=>'Ação permitida somente ao administrador.'], 403);
    return $user;
}
function requireCsrf(): void
{
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$provided || !hash_equals($_SESSION['csrf'] ?? '', $provided)) jsonResponse(['ok'=>false,'message'=>'Sessão de segurança inválida. Atualize a página e tente novamente.'], 419);
}
function cleanText(mixed $value, int $max = 160): string
{
    $text = trim((string)$value);
    $length = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
    if ($length > $max) throw new InvalidArgumentException("O texto deve ter até {$max} caracteres.");
    return $text;
}
function newId(string $prefix): string { return $prefix . '_' . bin2hex(random_bytes(8)); }
function validDate(string $date): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

