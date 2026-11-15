-- BraFood — estrutura do banco de dados (somente schema, sem dados).
--
-- Gerado a partir de um dump do MariaDB 10.4 com o estado atual do projeto:
-- já inclui o resultado de todas as migrations da pasta migrations/, então
-- um banco novo deve usar ESTE arquivo (e não rodar as migrations).
--
-- Uso:
--   CREATE DATABASE brafood_php CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
--   mysql -u <usuario> -p brafood_php < database/schema.sql
--
-- O banco nasce vazio (sem categorias, receitas ou usuários). O primeiro
-- administrador é criado cadastrando uma conta pelo app e marcando-a no banco:
--   UPDATE usuarios SET is_admin = 1 WHERE email = 'seu-email@example.com';

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `brafood_php`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `avaliacoes`
--

CREATE TABLE `avaliacoes` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_receita` int(10) UNSIGNED NOT NULL,
  `nota` tinyint(3) UNSIGNED NOT NULL CHECK (`nota` between 1 and 5),
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `icone` varchar(255) DEFAULT NULL,
  `fundo` varchar(255) DEFAULT NULL,
  `css_class` varchar(100) DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 99 COMMENT 'Ordem de exibição no category-bar',
  `cor_primaria` varchar(20) DEFAULT NULL COMMENT 'Cor de fundo principal (ex: #8BACFF)',
  `gradiente_hover` varchar(150) DEFAULT NULL COMMENT 'Gradiente CSS no hover (ex: linear-gradient(...))'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `comentarios`
--

CREATE TABLE `comentarios` (
  `id` int(11) NOT NULL,
  `id_receita` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_comentario_pai` int(11) DEFAULT NULL,
  `texto` text NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `nota` tinyint(3) UNSIGNED DEFAULT NULL CHECK (`nota` between 1 and 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `descricoes_preparo`
--

CREATE TABLE `descricoes_preparo` (
  `id_passo` int(11) NOT NULL,
  `descricao` text NOT NULL,
  `ordem` int(11) DEFAULT NULL,
  `id_etapa` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `destaque_historico`
--

CREATE TABLE `destaque_historico` (
  `semana` char(8) NOT NULL,
  `receita_id` int(11) NOT NULL,
  `manual` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `etapas_preparo`
--

CREATE TABLE `etapas_preparo` (
  `id_secao_preparo` int(11) NOT NULL,
  `id_receita` int(11) NOT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `ordem` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `favoritos`
--

CREATE TABLE `favoritos` (
  `id_usuario` int(11) NOT NULL,
  `id_receita` int(11) NOT NULL,
  `data_favoritou` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('quero_fazer','ja_fiz') DEFAULT NULL,
  `nota` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ingredientes`
--

CREATE TABLE `ingredientes` (
  `id_ingrediente` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `id_item` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_ingredientes`
--

CREATE TABLE `itens_ingredientes` (
  `id_item` int(11) NOT NULL,
  `id_receita` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `newsletter_inscritos`
--

CREATE TABLE `newsletter_inscritos` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `inscrito_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `quadros`
--

CREATE TABLE `quadros` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(60) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `capa` varchar(255) DEFAULT NULL,
  `publico` tinyint(1) NOT NULL DEFAULT 0,
  `token_publico` char(16) DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `quadro_receitas`
--

CREATE TABLE `quadro_receitas` (
  `id_quadro` int(11) NOT NULL,
  `id_receita` int(11) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `adicionado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `receitas`
--

CREATE TABLE `receitas` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `eh_propria` tinyint(1) NOT NULL DEFAULT 1,
  `fonte_autor` varchar(150) DEFAULT NULL,
  `fonte_url` varchar(500) DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `titulo` varchar(50) NOT NULL,
  `resumo` varchar(255) NOT NULL,
  `dificuldade` tinyint(3) DEFAULT 1,
  `tempo_min` int(2) DEFAULT NULL,
  `tempo_hora` int(2) DEFAULT NULL,
  `porcoes` int(11) DEFAULT NULL,
  `tipo_porcao` varchar(40) NOT NULL,
  `imagem1` varchar(255) DEFAULT NULL,
  `qtd_fav` int(11) DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `media_avaliacao` decimal(3,2) DEFAULT NULL,
  `qtd_avaliacoes` int(10) UNSIGNED DEFAULT 0,
  `destaque` tinyint(1) NOT NULL DEFAULT 0,
  `destaque_desde` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `receitas_secoes_carrossel`
--

CREATE TABLE `receitas_secoes_carrossel` (
  `id` int(11) NOT NULL,
  `secao_id` int(11) NOT NULL,
  `receita_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `receita_imagens`
--

CREATE TABLE `receita_imagens` (
  `id` int(11) NOT NULL,
  `id_receita` int(11) NOT NULL,
  `caminho` varchar(255) NOT NULL,
  `ordem` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `receita_subcategoria`
--

CREATE TABLE `receita_subcategoria` (
  `id_receita` int(11) NOT NULL,
  `id_subcategoria` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `secoes_carrossel`
--

CREATE TABLE `secoes_carrossel` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `url_fundo` varchar(255) DEFAULT NULL,
  `cor_fundo` varchar(30) DEFAULT NULL COMMENT 'Cor CSS de fundo (ex: #ff5733). Usada quando url_fundo for NULL.',
  `fundo_titulo` varchar(255) DEFAULT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `id_subcategoria` int(11) DEFAULT NULL,
  `modo` enum('aleatorio','manual') NOT NULL DEFAULT 'aleatorio',
  `ordem` int(11) DEFAULT 0,
  `ativo_de` char(5) DEFAULT NULL,
  `ativo_ate` char(5) DEFAULT NULL,
  `fixar_ate` date DEFAULT NULL,
  `titulo_cor_tipo` enum('solido','gradiente') DEFAULT 'gradiente',
  `titulo_cor_solid` varchar(30) DEFAULT NULL,
  `titulo_gradiente_1` varchar(30) DEFAULT NULL,
  `titulo_gradiente_2` varchar(30) DEFAULT NULL,
  `card_exibir` enum('categoria','subcategoria') NOT NULL DEFAULT 'categoria'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `sessoes`
--

CREATE TABLE `sessoes` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `token` char(64) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_uso` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `subcategorias`
--

CREATE TABLE `subcategorias` (
  `id` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `apelido` varchar(30) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `token` varchar(64) DEFAULT NULL,
  `genero` enum('F','M','Outro') DEFAULT NULL,
  `bio` varchar(255) DEFAULT 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur vel lorem vel metus facilisis fermentum.',
  `foto_perfil` varchar(255) DEFAULT 'assets/imagens/icones/pessoas.png',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = administrador do sistema'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_avaliacao` (`id_usuario`,`id_receita`),
  ADD KEY `idx_receita` (`id_receita`);

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_nome` (`nome`);

--
-- Índices de tabela `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_receita` (`id_receita`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_comentario_pai` (`id_comentario_pai`);

--
-- Índices de tabela `descricoes_preparo`
--
ALTER TABLE `descricoes_preparo`
  ADD PRIMARY KEY (`id_passo`),
  ADD KEY `fk_descricoes_etapas` (`id_etapa`);

--
-- Índices de tabela `destaque_historico`
--
ALTER TABLE `destaque_historico`
  ADD PRIMARY KEY (`semana`),
  ADD KEY `idx_destaque_historico_receita` (`receita_id`);

--
-- Índices de tabela `etapas_preparo`
--
ALTER TABLE `etapas_preparo`
  ADD PRIMARY KEY (`id_secao_preparo`),
  ADD KEY `id_receita` (`id_receita`);

--
-- Índices de tabela `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id_usuario`,`id_receita`),
  ADD KEY `id_receita` (`id_receita`);

--
-- Índices de tabela `ingredientes`
--
ALTER TABLE `ingredientes`
  ADD PRIMARY KEY (`id_ingrediente`),
  ADD KEY `idx_id_item` (`id_item`);

--
-- Índices de tabela `itens_ingredientes`
--
ALTER TABLE `itens_ingredientes`
  ADD PRIMARY KEY (`id_item`),
  ADD KEY `idx_receita` (`id_receita`);

--
-- Índices de tabela `newsletter_inscritos`
--
ALTER TABLE `newsletter_inscritos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`);

--
-- Índices de tabela `quadros`
--
ALTER TABLE `quadros`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_quadros_token` (`token_publico`),
  ADD KEY `idx_quadros_usuario` (`id_usuario`);

--
-- Índices de tabela `quadro_receitas`
--
ALTER TABLE `quadro_receitas`
  ADD PRIMARY KEY (`id_quadro`,`id_receita`),
  ADD KEY `idx_quadro_receitas_receita` (`id_receita`);

--
-- Índices de tabela `receitas`
--
ALTER TABLE `receitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `idx_destaque` (`destaque`);

--
-- Índices de tabela `receitas_secoes_carrossel`
--
ALTER TABLE `receitas_secoes_carrossel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `secao_id` (`secao_id`),
  ADD KEY `receita_id` (`receita_id`);

--
-- Índices de tabela `receita_imagens`
--
ALTER TABLE `receita_imagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_receita` (`id_receita`);

--
-- Índices de tabela `receita_subcategoria`
--
ALTER TABLE `receita_subcategoria`
  ADD PRIMARY KEY (`id_receita`,`id_subcategoria`),
  ADD KEY `id_subcategoria` (`id_subcategoria`);

--
-- Índices de tabela `secoes_carrossel`
--
ALTER TABLE `secoes_carrossel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `id_subcategoria` (`id_subcategoria`);

--
-- Índices de tabela `sessoes`
--
ALTER TABLE `sessoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sessoes_token` (`token`),
  ADD KEY `idx_sessoes_usuario` (`id_usuario`);

--
-- Índices de tabela `subcategorias`
--
ALTER TABLE `subcategorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uq_usuarios_apelido` (`apelido`),
  ADD UNIQUE KEY `uq_usuarios_token` (`token`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de tabela `descricoes_preparo`
--
ALTER TABLE `descricoes_preparo`
  MODIFY `id_passo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=267;

--
-- AUTO_INCREMENT de tabela `etapas_preparo`
--
ALTER TABLE `etapas_preparo`
  MODIFY `id_secao_preparo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=185;

--
-- AUTO_INCREMENT de tabela `ingredientes`
--
ALTER TABLE `ingredientes`
  MODIFY `id_ingrediente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=276;

--
-- AUTO_INCREMENT de tabela `itens_ingredientes`
--
ALTER TABLE `itens_ingredientes`
  MODIFY `id_item` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT de tabela `newsletter_inscritos`
--
ALTER TABLE `newsletter_inscritos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `quadros`
--
ALTER TABLE `quadros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `receitas`
--
ALTER TABLE `receitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de tabela `receitas_secoes_carrossel`
--
ALTER TABLE `receitas_secoes_carrossel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `receita_imagens`
--
ALTER TABLE `receita_imagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `secoes_carrossel`
--
ALTER TABLE `secoes_carrossel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `sessoes`
--
ALTER TABLE `sessoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `subcategorias`
--
ALTER TABLE `subcategorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`id_receita`) REFERENCES `receitas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentarios_ibfk_3` FOREIGN KEY (`id_comentario_pai`) REFERENCES `comentarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `descricoes_preparo`
--
ALTER TABLE `descricoes_preparo`
  ADD CONSTRAINT `fk_descricoes_etapas` FOREIGN KEY (`id_etapa`) REFERENCES `etapas_preparo` (`id_secao_preparo`) ON DELETE CASCADE;

--
-- Restrições para tabelas `etapas_preparo`
--
ALTER TABLE `etapas_preparo`
  ADD CONSTRAINT `etapas_preparo_ibfk_1` FOREIGN KEY (`id_receita`) REFERENCES `receitas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`id_receita`) REFERENCES `receitas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ingredientes`
--
ALTER TABLE `ingredientes`
  ADD CONSTRAINT `fk_ingredientes_itens` FOREIGN KEY (`id_item`) REFERENCES `itens_ingredientes` (`id_item`) ON DELETE CASCADE;

--
-- Restrições para tabelas `receitas`
--
ALTER TABLE `receitas`
  ADD CONSTRAINT `receitas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `receitas_secoes_carrossel`
--
ALTER TABLE `receitas_secoes_carrossel`
  ADD CONSTRAINT `receitas_secoes_carrossel_ibfk_receita` FOREIGN KEY (`receita_id`) REFERENCES `receitas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `receitas_secoes_carrossel_ibfk_secao` FOREIGN KEY (`secao_id`) REFERENCES `secoes_carrossel` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `receita_imagens`
--
ALTER TABLE `receita_imagens`
  ADD CONSTRAINT `receita_imagens_ibfk_1` FOREIGN KEY (`id_receita`) REFERENCES `receitas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `receita_subcategoria`
--
ALTER TABLE `receita_subcategoria`
  ADD CONSTRAINT `receita_subcategoria_ibfk_1` FOREIGN KEY (`id_receita`) REFERENCES `receitas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `receita_subcategoria_ibfk_2` FOREIGN KEY (`id_subcategoria`) REFERENCES `subcategorias` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `secoes_carrossel`
--
ALTER TABLE `secoes_carrossel`
  ADD CONSTRAINT `secoes_carrossel_ibfk_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `secoes_carrossel_ibfk_subcategoria` FOREIGN KEY (`id_subcategoria`) REFERENCES `subcategorias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `sessoes`
--
ALTER TABLE `sessoes`
  ADD CONSTRAINT `fk_sessoes_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `subcategorias`
--
ALTER TABLE `subcategorias`
  ADD CONSTRAINT `subcategorias_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
