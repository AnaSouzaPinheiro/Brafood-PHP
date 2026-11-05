-- =====================================================================
-- Apelido passa a ser o identificador público do usuário (/chef/{apelido})
-- Formato: 3-30 caracteres, [a-z0-9_.], único.
--
-- Rode UMA vez em cada banco (local e produção / phpMyAdmin).
-- Faça backup da tabela usuarios antes (Exportar no phpMyAdmin).
-- =====================================================================

SET NAMES utf8mb4;

-- 1) Normaliza o apelido atual (ou usa o nome, se estiver vazio):
--    minúsculas, sem acento, espaços viram ".", remove outros caracteres
UPDATE usuarios
SET apelido = LEFT(
  TRIM(BOTH '.' FROM
    REGEXP_REPLACE(
      REGEXP_REPLACE(
        REGEXP_REPLACE(
          REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
          REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
            LOWER(TRIM(COALESCE(NULLIF(TRIM(apelido), ''), nome, ''))),
          'á','a'),'à','a'),'ã','a'),'â','a'),'ä','a'),'é','e'),'ê','e'),'è','e'),
          'í','i'),'ó','o'),'ô','o'),'õ','o'),'ö','o'),'ú','u'),'ü','u'),'ç','c'),
        '[[:space:]]+', '.'),
      '[^a-z0-9_.]', ''),
    '[.]{2,}', '.')
  ), 30);

-- 2) Muito curtos / vazios / reservados → chef{id}
UPDATE usuarios
SET apelido = CONCAT('chef', id)
WHERE apelido IS NULL
   OR CHAR_LENGTH(apelido) < 3
   OR apelido IN ('admin','administrador','brafood','suporte','perfil','chef',
                  'login','cadastro','api','root','sistema','moderador');

-- 3) Duplicados: mantém o mais antigo e acrescenta o id nos demais
UPDATE usuarios u
JOIN (
  SELECT a.id
  FROM usuarios a
  JOIN usuarios b ON LOWER(a.apelido) = LOWER(b.apelido) AND a.id > b.id
) dup ON dup.id = u.id
SET u.apelido = CONCAT(LEFT(u.apelido, 30 - CHAR_LENGTH(u.id)), u.id);

-- 4) Obrigatório e único
ALTER TABLE usuarios
  MODIFY apelido VARCHAR(30) NOT NULL,
  ADD UNIQUE KEY uq_usuarios_apelido (apelido);
