-- =====================================================================
-- Nome em um campo só: o sobrenome passa a fazer parte do nome.
-- Rode UMA vez em cada banco (local e produção / phpMyAdmin),
-- depois de publicar o código novo (que já não usa a coluna sobrenome).
-- =====================================================================

SET NAMES utf8mb4;

-- Junta "nome sobrenome" em nome (só onde há sobrenome preenchido)
UPDATE usuarios
   SET nome = TRIM(CONCAT(nome, ' ', sobrenome))
 WHERE sobrenome IS NOT NULL AND TRIM(sobrenome) <> '';

ALTER TABLE usuarios DROP COLUMN sobrenome;
