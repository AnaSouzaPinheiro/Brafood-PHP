-- =====================================================================
-- Quadros — Fase 3: compartilhar (link público só leitura)
-- Rode UMA vez em cada banco (local e produção / phpMyAdmin).
-- (A ordenação usa as colunas `ordem` criadas em 2026-11-08_quadros.sql.)
-- =====================================================================

SET NAMES utf8mb4;

-- publico = 1: qualquer pessoa com o link vê o quadro, e ele aparece no
-- perfil público (/chef/{apelido}). token_publico é o identificador do
-- link (/quadro/{token}) — gerado na primeira vez que o quadro fica público
-- e mantido depois, para links já enviados continuarem valendo.
ALTER TABLE quadros
  ADD COLUMN publico       TINYINT(1) NOT NULL DEFAULT 0 AFTER capa,
  ADD COLUMN token_publico CHAR(16)   NULL AFTER publico,
  ADD UNIQUE KEY uq_quadros_token (token_publico);
