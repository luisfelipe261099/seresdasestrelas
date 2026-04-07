<?php
/**
 * Seres das Estrelas OS — Configuração e Conexão PDO (TiDB Cloud)
 * Credenciais via variáveis de ambiente (Vercel / .env local)
 */

// Carregar .env local se existir (dev)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            putenv(trim($line));
        }
    }
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = getenv('TIDB_HOST') ?: 'gateway01.us-east-1.prod.aws.tidbcloud.com';
    $port = getenv('TIDB_PORT') ?: '4000';
    $user = getenv('TIDB_USER') ?: '';
    $pass = getenv('TIDB_PASS') ?: '';
    $db   = getenv('TIDB_DB')   ?: 'seresdasestrelas';

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // TiDB Cloud exige SSL
    if (getenv('TIDB_SSL') !== 'false') {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        $caPath = getenv('TIDB_CA_PATH');
        if ($caPath && file_exists($caPath)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
        }
    }

    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo;
}

// Helpers
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function bloco_nome(int $b): string {
    return match($b) {
        1 => 'Limpeza e Desintoxicação',
        2 => 'Reequilíbrio e Cura',
        3 => 'Expansão e Propósito',
        default => 'Indefinido'
    };
}

function bloco_badge(int $b): string {
    $cls = match($b) {
        1 => 'badge-bloco1',
        2 => 'badge-bloco2',
        3 => 'badge-bloco3',
        default => 'badge-bloco1'
    };
    return '<span class="badge-bloco ' . $cls . '">Bloco ' . $b . '</span>';
}

function whatsapp_link(string $num): string {
    $clean = preg_replace('/\D/', '', $num);
    if (!str_starts_with($clean, '55')) $clean = '55' . $clean;
    return 'https://wa.me/' . e($clean);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(403);
        die('Token inválido.');
    }
}
