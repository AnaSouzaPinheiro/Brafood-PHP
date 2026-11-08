-- =====================================================================
-- Quadros de receitas salvas (área pessoal)
-- Rode UMA vez em cada banco (local e produção / phpMyAdmin).
-- =====================================================================
-- A tabela `favoritos` continua sendo o "salvo" (e alimenta qtd_fav).
-- Quadros são uma organização por cima dela: salvar num quadro também
-- favorita; desfavoritar tira a receita de todos os quadros do usuário.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS quadros (
  id         INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT          NOT NULL,
  nome       VARCHAR(60)  NOT NULL,
  descricao  VARCHAR(255) NULL,
  capa       VARCHAR(255) NULL,          -- reservado (capa personalizada)
  ordem      INT          NOT NULL DEFAULT 0,
  criado_em  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_quadros_usuario (id_usuario)
) DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS quadro_receitas (
  id_quadro     INT       NOT NULL,
  id_receita    INT       NOT NULL,
  ordem         INT       NOT NULL DEFAULT 0,
  adicionado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_quadro, id_receita),
  KEY idx_quadro_receitas_receita (id_receita)
) DEFAULT CHARSET = utf8mb4;

-- Preparação para a Fase 2 (status "quero fazer"/"já fiz" e nota pessoal)
ALTER TABLE favoritos
  ADD COLUMN status ENUM('quero_fazer', 'ja_fiz') NULL,
  ADD COLUMN nota   TEXT NULL;
