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
        PDO::ATTR_PERSISTENT         => true,
        PDO::ATTR_TIMEOUT            => 5,
    ];

    // TiDB Cloud exige SSL
    if (getenv('TIDB_SSL') !== 'false') {
        $caPath = getenv('TIDB_CA_PATH');
        if ($caPath && file_exists($caPath)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        } else {
            // Sem CA local: habilita SSL mas sem verificação de certificado
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            $options[PDO::MYSQL_ATTR_SSL_CA] = '';
        }
    }

    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo;
}

// Helpers
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Preload de blocos — cache em arquivo (60s) para evitar query DB em toda navegação
function _preloadBlocos(): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    $cacheFile = sys_get_temp_dir() . '/se_blocos_cache.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 60) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if ($data) { $cache = $data; return $cache; }
    }

    try {
        $db = getDB();
        $rows = $db->query("SELECT numero, nome, cor FROM blocos ORDER BY ordem ASC")->fetchAll();
        $cache = [];
        foreach ($rows as $r) {
            $cache[(int)$r['numero']] = ['nome' => $r['nome'], 'cor' => $r['cor']];
        }
        @file_put_contents($cacheFile, json_encode($cache));
    } catch (\Exception $e) {
        $cache = [
            1 => ['nome' => 'Bloco 1', 'cor' => '#60a5fa'],
            2 => ['nome' => 'Bloco 2', 'cor' => '#a78bfa'],
            3 => ['nome' => 'Bloco 3', 'cor' => '#E0A458'],
        ];
    }
    return $cache;
}

function bloco_nome(int $b): string {
    $blocos = _preloadBlocos();
    return $blocos[$b]['nome'] ?? 'Indefinido';
}

function bloco_badge(int $b): string {
    $blocos = _preloadBlocos();
    $cor = $blocos[$b]['cor'] ?? '#E0A458';
    return '<span class="badge-bloco" style="background:' . $cor . '20;color:' . $cor . ';border:1px solid ' . $cor . '40;">Bloco ' . $b . '</span>';
}

function invalidar_cache_blocos(): void {
    @unlink(sys_get_temp_dir() . '/se_blocos_cache.json');
}

function whatsapp_link(string $num): string {
    $clean = preg_replace('/\D/', '', $num);
    if (!str_starts_with($clean, '55')) $clean = '55' . $clean;
    return 'https://wa.me/' . e($clean);
}
