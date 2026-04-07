<?php
/**
 * Auth middleware — inclua no topo de cada página protegida
 */
session_start();
require_once __DIR__ . '/config.php';

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function currentUser(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'nome'  => $_SESSION['user_nome'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'nivel' => $_SESSION['user_nivel'] ?? 'admin',
    ];
}

function isAdmin(): bool {
    return ($_SESSION['user_nivel'] ?? '') === 'admin';
}
