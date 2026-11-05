-- =====================================================================
-- Home dinâmica: seções sazonais, fixar no topo e histórico do destaque
-- Rode UMA vez em cada banco (local e produção / phpMyAdmin).
-- =====================================================================

SET NAMES utf8mb4;

-- Período em que a seção aparece, todo ano (formato MM-DD).
-- Ex.: Festa Junina 06-01 → 07-31 | Natal 12-01 → 01-06 (atravessa o ano)
-- NULL nos dois = aparece o ano todo.
-- fixar_ate: a seção fica em primeiro lugar até esta data (inclusive).
ALTER TABLE secoes_carrossel
  ADD COLUMN ativo_de  CHAR(5) NULL AFTER ordem,
  ADD COLUMN ativo_ate CHAR(5) NULL AFTER ativo_de,
  ADD COLUMN fixar_ate DATE    NULL AFTER ativo_ate;

-- Uma receita da semana por semana ISO (ex.: 2026-W45).
-- manual = 1 quando escolhida pelo admin; 0 quando escolhida automaticamente.
CREATE TABLE IF NOT EXISTS destaque_historico (
  semana     CHAR(8)    NOT NULL PRIMARY KEY,
  receita_id INT        NOT NULL,
  manual     TINYINT(1) NOT NULL DEFAULT 0,
  criado_em  TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_destaque_historico_receita (receita_id)
);

-- Períodos das seções sazonais já existentes (ajuste no painel se quiser)
UPDATE secoes_carrossel SET ativo_de = '06-01', ativo_ate = '07-31'
WHERE LOWER(titulo) LIKE '%junina%' AND ativo_de IS NULL;

UPDATE secoes_carrossel SET ativo_de = '12-01', ativo_ate = '01-06'
WHERE LOWER(titulo) LIKE '%natal%' AND ativo_de IS NULL;
