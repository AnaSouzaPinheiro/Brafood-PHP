<?php

namespace App\Model;

use App\Config\Database;
use App\Service\HomeRotacaoService;
use PDO;

class DestaqueModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /** Semanas sem repetir uma receita já destacada */
    private const SEMANAS_SEM_REPETIR = 8;

    /**
     * Receita da semana.
     *   1. Já escolhida nesta semana (histórico) → ela.
     *   2. Admin marcou uma nesta semana → ela (vira escolha manual da semana).
     *   3. Senão, escolhe automaticamente por pontuação e grava no histórico.
     */
    public function buscarDestaque(): ?array
    {
        $semana = HomeRotacaoService::semanaAtual();

        $stmt = $this->conn->prepare('SELECT receita_id, manual FROM destaque_historico WHERE semana = ?');
        $stmt->execute([$semana]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($linha && ($receita = $this->buscarReceita((int) $linha['receita_id']))) {
            $receita['manual'] = (int) $linha['manual'] === 1;
            return $receita;
        }

        // Escolha manual feita nesta semana pelo painel
        $stmt = $this->conn->prepare(
            'SELECT id FROM receitas WHERE destaque = 1 AND destaque_desde >= ? LIMIT 1'
        );
        $stmt->execute([HomeRotacaoService::inicioDaSemana()]);
        $manual = $stmt->fetchColumn();

        $idReceita = $manual ? (int) $manual : $this->escolherAutomaticamente();
        if (!$idReceita) {
            return null;
        }

        $this->registrarSemana($semana, $idReceita, (bool) $manual);
        $receita = $this->buscarReceita($idReceita);
        if ($receita) $receita['manual'] = (bool) $manual;
        return $receita;
    }

    /**
     * Pontuação: favoritos + avaliações + bônus para receitas novas,
     * com um sorteio leve (semente da semana) para variar entre empates.
     * Ignora receitas destacadas nas últimas semanas.
     */
    private function escolherAutomaticamente(array $excluir = []): ?int
    {
        $stmt = $this->conn->prepare(
            'SELECT receita_id FROM destaque_historico ORDER BY semana DESC LIMIT ' . self::SEMANAS_SEM_REPETIR
        );
        $stmt->execute();
        $recentes = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        $recentes = array_values(array_unique(array_merge($recentes, array_map('intval', $excluir))));

        $notIn = $recentes ? 'WHERE id NOT IN (' . implode(',', $recentes) . ')' : '';

        $sql = "SELECT id
                FROM receitas
                $notIn
                ORDER BY
                    COALESCE(qtd_fav, 0) * 2
                  + COALESCE(media_avaliacao, 0) * LEAST(COALESCE(qtd_avaliacoes, 0), 10)
                  + IF(data_criacao >= NOW() - INTERVAL 30 DAY, 5, 0)
                  + RAND(:seed) * 4
                DESC
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':seed', HomeRotacaoService::sementeSemanal(), PDO::PARAM_INT);
        $stmt->execute();
        $id = $stmt->fetchColumn();

        // Poucas receitas e todas destacadas recentemente: libera a repetição
        if (!$id && $recentes) {
            $id = $this->conn->query('SELECT id FROM receitas ORDER BY RAND() LIMIT 1')->fetchColumn();
        }

        return $id ? (int) $id : null;
    }

    private function registrarSemana(string $semana, int $idReceita, bool $manual): void
    {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare(
                'INSERT INTO destaque_historico (semana, receita_id, manual) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE receita_id = VALUES(receita_id), manual = VALUES(manual)'
            );
            $stmt->execute([$semana, $idReceita, $manual ? 1 : 0]);

            // Mantém a coluna "destaque" coerente com a escolha da semana
            $this->conn->exec('UPDATE receitas SET destaque = 0, destaque_desde = NULL WHERE destaque = 1');
            $stmt = $this->conn->prepare('UPDATE receitas SET destaque = 1, destaque_desde = NOW() WHERE id = ?');
            $stmt->execute([$idReceita]);

            $this->conn->commit();
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    private function buscarReceita(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT
                r.id,
                r.titulo,
                r.resumo,
                r.imagem1,
                r.qtd_fav,
                r.media_avaliacao,
                r.qtd_avaliacoes,
                r.destaque_desde,
                u.nome AS autor,
                c.nome AS categoria,
                s.nome AS subcategoria
            FROM receitas r
            LEFT JOIN usuarios u ON u.id = r.id_usuario
            LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
            LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
            LEFT JOIN categorias c ON c.id = s.id_categoria
            WHERE r.id = ?
            GROUP BY r.id
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Admin escolhe a receita da semana atual */
    public function ativarDestaque(int $idReceita): bool
    {
        $this->registrarSemana(HomeRotacaoService::semanaAtual(), $idReceita, true);
        return true;
    }

    /**
     * Admin desfaz a escolha da semana: volta para a escolha automática.
     * A receita que estava em destaque fica de fora (senão, se ela for a de
     * maior pontuação, o automático escolhe ela de novo e parece que nada
     * mudou). A nova escolha já é gravada e devolvida.
     */
    public function removerDestaque(): ?array
    {
        $semana = HomeRotacaoService::semanaAtual();

        $stmt = $this->conn->prepare('SELECT receita_id FROM destaque_historico WHERE semana = ?');
        $stmt->execute([$semana]);
        $atual = $stmt->fetchColumn()
            ?: $this->conn->query('SELECT id FROM receitas WHERE destaque = 1 LIMIT 1')->fetchColumn();

        $stmt = $this->conn->prepare('DELETE FROM destaque_historico WHERE semana = ?');
        $stmt->execute([$semana]);
        $this->conn->exec('UPDATE receitas SET destaque = 0, destaque_desde = NULL WHERE destaque = 1');

        $novo = $this->escolherAutomaticamente($atual ? [(int) $atual] : []);
        if (!$novo) {
            return null;
        }
        $this->registrarSemana($semana, $novo, false);
        $receita = $this->buscarReceita($novo);
        if ($receita) $receita['manual'] = false;
        return $receita;
    }

    /** Lista receitas para o seletor do admin (id + titulo + imagem) */
    public function listarParaSelector(string $busca = ''): array
    {
        if ($busca !== '') {
            $stmt = $this->conn->prepare("
                SELECT id, titulo, imagem1, destaque
                FROM receitas
                WHERE titulo LIKE ?
                ORDER BY titulo
                LIMIT 20
            ");
            $stmt->execute(['%' . $busca . '%']);
        } else {
            $stmt = $this->conn->prepare("
                SELECT id, titulo, imagem1, destaque
                FROM receitas
                ORDER BY data_criacao DESC
                LIMIT 20
            ");
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
