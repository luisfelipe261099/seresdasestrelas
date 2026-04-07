<?php
/**
 * Migração: cria tabela eventos
 * Acesse UMA VEZ e depois delete.
 */
require_once __DIR__ . '/config.php';
$db = getDB();

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS eventos (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            titulo          VARCHAR(200)  NOT NULL,
            descricao       TEXT          DEFAULT NULL,
            data_evento     DATE          NOT NULL,
            horario         VARCHAR(10)   NOT NULL DEFAULT '19:30',
            local_tipo      VARCHAR(30)   NOT NULL DEFAULT 'presencial',
            link_online     VARCHAR(500)  DEFAULT NULL,
            palestrantes    VARCHAR(500)  DEFAULT NULL,
            ativo           TINYINT       NOT NULL DEFAULT 1,
            criado_em       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo '✓ Tabela eventos criada com sucesso! Delete este arquivo agora.';
} catch (PDOException $e) {
    echo '✗ Erro: ' . $e->getMessage();
}
