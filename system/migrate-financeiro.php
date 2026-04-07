<?php
/**
 * Migração — Tabelas financeiras (mensalidades + lançamentos)
 * Acesse uma vez: /system/migrate-financeiro.php
 */
require_once __DIR__ . '/config.php';

$db = getDB();

$sqls = [
    "CREATE TABLE IF NOT EXISTS mensalidades (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        paciente_id  INT NOT NULL,
        mes_referencia VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
        valor        DECIMAL(10,2) NOT NULL DEFAULT 0,
        data_vencimento DATE NULL,
        data_pagamento  DATE NULL,
        status       ENUM('pendente','pago','atrasado','cancelado') DEFAULT 'pendente',
        forma_pagamento VARCHAR(30) NULL COMMENT 'pix,dinheiro,cartao,transferencia',
        observacao   TEXT NULL,
        criado_em    DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_pac_mes (paciente_id, mes_referencia)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS lancamentos (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        tipo            ENUM('receita','despesa') NOT NULL,
        categoria       VARCHAR(60) NOT NULL,
        descricao       VARCHAR(255) NULL,
        valor           DECIMAL(10,2) NOT NULL,
        data_lancamento DATE NOT NULL,
        forma_pagamento VARCHAR(30) NULL,
        paciente_id     INT NULL,
        mensalidade_id  INT NULL,
        observacao      TEXT NULL,
        criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

$ok = true;
foreach ($sqls as $sql) {
    try {
        $db->exec($sql);
    } catch (PDOException $e) {
        echo "ERRO: " . $e->getMessage() . "<br>";
        $ok = false;
    }
}

if ($ok) {
    echo "✓ Tabelas financeiras criadas com sucesso! Delete este arquivo agora.";
} else {
    echo "<br>Houve erros. Verifique acima.";
}
