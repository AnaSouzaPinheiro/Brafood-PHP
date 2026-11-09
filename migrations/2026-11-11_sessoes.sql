-- =====================================================================
-- Login por aparelho: cada login cria a própria sessão.
-- Antes havia um token só (usuarios.token) e entrar no computador
-- derrubava o login do celular (e vice-versa).
-- Rode UMA vez em cada banco (local e produção / phpMyAdmin).
-- Pode rodar antes ou depois do deploy: o código funciona nos dois casos.
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS sessoes (
  id          INT          NOT NULL AUTO_INCREMENT,
  id_usuario  INT          NOT NULL,
  token       CHAR(64)     NOT NULL,
  criado_em   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_uso  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sessoes_token (token),
  KEY idx_sessoes_usuario (id_usuario),
  CONSTRAINT fk_sessoes_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mantém quem já está logado agora (o token atual vira uma sessão)
INSERT IGNORE INTO sessoes (id_usuario, token)
SELECT id, token FROM usuarios
WHERE token IS NOT NULL AND CHAR_LENGTH(token) = 64;
