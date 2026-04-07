<?php
/**
 * Auth middleware — autenticação via token em cookie (compatível com Vercel serverless)
 */
require_once __DIR__ . '/config.php';

define('AUTH_COOKIE_NAME', 'se_auth_token');
define('AUTH_TOKEN_DAYS', 30);

function _loadUserFromToken(): ?array {
    static $cached = false;
    static $user = null;
    if ($cached) return $user;
    $cached = true;

    $token = $_COOKIE[AUTH_COOKIE_NAME] ?? '';
    if (!$token || strlen($token) < 64) return null;

    try {
        $db = getDB();
        $st = $db->prepare('SELECT t.user_id, t.expires_at, u.id, u.nome, u.email, u.nivel
                            FROM auth_tokens t
                            JOIN usuarios u ON u.id = t.user_id
                            WHERE t.token = ? LIMIT 1');
        $st->execute([$token]);
        $row = $st->fetch();

        if (!$row) return null;
        if (strtotime($row['expires_at']) < time()) {
            $db->prepare('DELETE FROM auth_tokens WHERE token = ?')->execute([$token]);
            return null;
        }

        $user = [
            'id'    => $row['id'],
            'nome'  => $row['nome'],
            'email' => $row['email'],
            'nivel' => $row['nivel'],
        ];
        return $user;
    } catch (\Exception $e) {
        return null;
    }
}

function requireLogin(): void {
    if (!_loadUserFromToken()) {
        header('Location: login.php');
        exit;
    }
}

function currentUser(): ?array {
    return _loadUserFromToken();
}

function isAdmin(): bool {
    $u = _loadUserFromToken();
    return $u && ($u['nivel'] ?? '') === 'admin';
}

function createAuthToken(int $userId): string {
    $token = bin2hex(random_bytes(48));
    $expires = date('Y-m-d H:i:s', strtotime('+' . AUTH_TOKEN_DAYS . ' days'));
    $db = getDB();
    $st = $db->prepare('INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
    $st->execute([$userId, $token, $expires]);

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie(AUTH_COOKIE_NAME, $token, [
        'expires'  => strtotime($expires),
        'path'     => '/',
        'secure'   => $secure,
        'httponly'  => true,
        'samesite' => 'Lax',
    ]);
    return $token;
}

function destroyAuthToken(): void {
    $token = $_COOKIE[AUTH_COOKIE_NAME] ?? '';
    if ($token) {
        try {
            $db = getDB();
            $db->prepare('DELETE FROM auth_tokens WHERE token = ?')->execute([$token]);
        } catch (\Exception $e) {}
    }
    setcookie(AUTH_COOKIE_NAME, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly'  => true,
        'samesite' => 'Lax',
    ]);
}

// CSRF — funciona sem session, usa token do cookie como seed
function csrf_token(): string {
    $authToken = $_COOKIE[AUTH_COOKIE_NAME] ?? 'no-auth';
    return hash_hmac('sha256', 'csrf', $authToken);
}

function csrf_check(): void {
    $expected = csrf_token();
    $sent = $_POST['csrf'] ?? '';
    if (!$sent || !hash_equals($expected, $sent)) {
        http_response_code(403);
        die('Token inválido.');
    }
}
