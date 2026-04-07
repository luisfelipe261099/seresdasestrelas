<?php
/**
 * Migração — Blocos dinâmicos + campos financeiros no paciente
 * Acesse uma vez: /system/migrate-blocos-fin.php
 */
require_once __DIR__ . '/config.php';
$db = getDB();

$sqls = [
    // Tabela blocos customizáveis
    "CREATE TABLE IF NOT EXISTS blocos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        numero INT NOT NULL,
        nome VARCHAR(100) NOT NULL,
        descricao VARCHAR(255) NULL,
        cor VARCHAR(20) DEFAULT '#E0A458',
        ordem INT DEFAULT 0,
        ativo TINYINT(1) DEFAULT 1,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Inserir os 3 blocos padrão se tabela estiver vazia
    "INSERT IGNORE INTO blocos (id, numero, nome, descricao, cor, ordem) VALUES 
        (1, 1, 'Limpeza e Desintoxicação', 'Fase de limpeza energética e emocional', '#60a5fa', 1),
        (2, 2, 'Reequilíbrio e Cura', 'Fase de reequilíbrio e restauração', '#a78bfa', 2),
        (3, 3, 'Expansão e Propósito', 'Fase de expansão da consciência', '#E0A458', 3)",

    // Campos financeiros na tabela pacientes
    "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS valor_mensal DECIMAL(10,2) DEFAULT 0",
    "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS forma_pagamento VARCHAR(30) DEFAULT 'pix'",
    "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS tipo_cobranca ENUM('mensal','avista','parcelado') DEFAULT 'mensal'",
    "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS parcelas_total INT DEFAULT 1",
    "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS parcelas_pagas INT DEFAULT 0",
    "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS dia_vencimento INT DEFAULT 10",
    "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS observacao_financeira TEXT NULL",
];

$ok = true;
foreach ($sqls as $sql) {
    try {
        $db->exec($sql);
    } catch (PDOException $e) {
        // ALTER TABLE ADD COLUMN IF NOT EXISTS pode dar erro em TiDB < 6.x
        if (!str_contains($e->getMessage(), 'Duplicate column')) {
            echo "AVISO: " . $e->getMessage() . "<br>";
        }
    }
}

echo "✓ Migração concluída! Tabela blocos criada + campos financeiros adicionados.";
