-- =========================================================
-- SERES DAS ESTRELAS OS — Database Schema (TiDB / MySQL)
-- =========================================================

CREATE DATABASE IF NOT EXISTS seresdasestrelas;
USE seresdasestrelas;

-- Usuários do sistema (login)
CREATE TABLE IF NOT EXISTS usuarios (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(100)  NOT NULL,
  email       VARCHAR(150)  NOT NULL UNIQUE,
  senha_hash  VARCHAR(255)  NOT NULL,
  nivel       ENUM('admin','secretaria') NOT NULL DEFAULT 'admin',
  criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Pacientes
CREATE TABLE IF NOT EXISTS pacientes (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nome            VARCHAR(150)  NOT NULL,
  whatsapp        VARCHAR(20)   NOT NULL,
  email           VARCHAR(150)  DEFAULT NULL,
  data_nascimento DATE          DEFAULT NULL,
  ocupacao        VARCHAR(100)  DEFAULT NULL,
  bloco_atual     TINYINT       NOT NULL DEFAULT 1 COMMENT '1=Limpeza, 2=Reequilíbrio, 3=Expansão',
  status_ativo    TINYINT(1)    NOT NULL DEFAULT 1,
  criado_em       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- Sessões / Notas clínicas
CREATE TABLE IF NOT EXISTS sessoes_notas (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  paciente_id     INT           NOT NULL,
  data_sessao     DATE          NOT NULL,
  texto_nota      TEXT          NOT NULL,
  tipo_tratamento VARCHAR(80)   DEFAULT 'Psicanálise',
  bloco_referente TINYINT       NOT NULL DEFAULT 1,
  criado_em       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);

-- Anamneses (formulário público)
CREATE TABLE IF NOT EXISTS anamneses (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  paciente_id     INT           DEFAULT NULL,
  nome            VARCHAR(150)  NOT NULL,
  whatsapp        VARCHAR(20)   NOT NULL,
  respostas_json  JSON          NOT NULL,
  criado_em       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE SET NULL
);

-- Inserir usuária admin padrão (senha: estrelas2026)
-- A senha será gerada pelo setup.php
