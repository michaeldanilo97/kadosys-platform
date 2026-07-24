<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Duelo de quiz 1x1 entre duas criancas da MESMA igreja (o isolamento
 * entre igrejas e automatico - cada banco e de uma igreja so, uma
 * crianca nunca aparece nas consultas de outra). Sem chat de texto
 * livre: a unica forma de "conversa" e uma reacao de emoji pre-definida
 * (ver reagir()). Atualizado por polling no navegador (ver
 * kids-duelo.js), sem precisar de WebSocket.
 */
final class KidsDuelo
{
    private const BONUS_VENCEDOR = ['xp' => 20, 'moedas' => 12];
    private const BONUS_PARTICIPACAO = ['xp' => 5, 'moedas' => 3];

    /** @var array<int, string> */
    private const REACOES_PERMITIDAS = ['👍', '😂', '🎉', '😮', '❤️', '💪'];

    /**
     * Cria um convite de duelo - null se algo invalido (crianca convidada
     * nao existe/inativa/sem PIN, e a propria crianca, conteudo nao e um
     * quiz publicado, ou ja existe um duelo em aberto com esse mesmo
     * amigo). Retorna o id do duelo criado.
     */
    public static function convidar(int $conteudoId, int $criadorId, int $convidadoId): ?int
    {
        if ($criadorId === $convidadoId) {
            return null;
        }

        $convidado = KidsCrianca::find($convidadoId);

        if ($convidado === null || $convidado->status !== 'ativo' || !$convidado->temPin()) {
            return null;
        }

        $conteudo = KidsConteudo::find($conteudoId);

        if ($conteudo === null || $conteudo->status !== 'publicado' || $conteudo->tipo !== 'quiz' || $conteudo->quizPerguntas === null) {
            return null;
        }

        $pdo = Database::connection();

        $jaExiste = $pdo->prepare(
            "SELECT 1 FROM kids_duelos
             WHERE status IN ('pendente', 'em_andamento')
             AND ((criador_id = :a1 AND convidado_id = :b1) OR (criador_id = :b2 AND convidado_id = :a2))
             LIMIT 1"
        );
        $jaExiste->execute(['a1' => $criadorId, 'b1' => $convidadoId, 'b2' => $criadorId, 'a2' => $convidadoId]);

        if ($jaExiste->fetchColumn() !== false) {
            return null;
        }

        $inserir = $pdo->prepare(
            'INSERT INTO kids_duelos (conteudo_id, criador_id, convidado_id, created_at)
             VALUES (:conteudo_id, :criador_id, :convidado_id, NOW())'
        );
        $inserir->execute(['conteudo_id' => $conteudoId, 'criador_id' => $criadorId, 'convidado_id' => $convidadoId]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Convites pendentes recebidos por uma crianca - usado no polling da
     * home (ver kids-duelo.js) pra avisar "fulano te desafiou".
     *
     * @return array<int, array{id: int, criadorNome: string, conteudoTitulo: string}>
     */
    public static function pendentesPara(int $criancaId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT d.id, c.nome AS criador_nome, k.titulo AS conteudo_titulo
             FROM kids_duelos d
             INNER JOIN kids_criancas c ON c.id = d.criador_id
             INNER JOIN kids_conteudos k ON k.id = d.conteudo_id
             WHERE d.convidado_id = :crianca_id AND d.status = 'pendente'
             ORDER BY d.created_at DESC"
        );
        $stmt->execute(['crianca_id' => $criancaId]);

        return array_map(
            static fn (array $row) => [
                'id' => (int) $row['id'],
                'criadorNome' => (string) $row['criador_nome'],
                'conteudoTitulo' => (string) $row['conteudo_titulo'],
            ],
            $stmt->fetchAll()
        );
    }

    /**
     * Duelos em andamento (aceitos, mas ainda sem os dois terminarem) de
     * uma crianca - pra ela conseguir voltar pra sala se sair no meio.
     *
     * @return array<int, array{id: int, oponenteNome: string, conteudoTitulo: string}>
     */
    public static function emAndamentoPara(int $criancaId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT d.id, d.criador_id, d.convidado_id, c1.nome AS criador_nome, c2.nome AS convidado_nome, k.titulo AS conteudo_titulo
             FROM kids_duelos d
             INNER JOIN kids_criancas c1 ON c1.id = d.criador_id
             INNER JOIN kids_criancas c2 ON c2.id = d.convidado_id
             INNER JOIN kids_conteudos k ON k.id = d.conteudo_id
             WHERE (d.criador_id = :crianca_id OR d.convidado_id = :crianca_id2) AND d.status = 'em_andamento'
             ORDER BY d.created_at DESC"
        );
        $stmt->execute(['crianca_id' => $criancaId, 'crianca_id2' => $criancaId]);

        return array_map(
            static fn (array $row) => [
                'id' => (int) $row['id'],
                'oponenteNome' => (int) $row['criador_id'] === $criancaId ? (string) $row['convidado_nome'] : (string) $row['criador_nome'],
                'conteudoTitulo' => (string) $row['conteudo_titulo'],
            ],
            $stmt->fetchAll()
        );
    }

    /**
     * @return array{id: int, conteudoId: int, criadorId: int, convidadoId: int, status: string, criadorProgresso: int, convidadoProgresso: int, criadorTerminou: bool, convidadoTerminou: bool, vencedorId: ?int}|null
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM kids_duelos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return self::mapear($row);
    }

    public static function aceitar(int $duelId, int $convidadoId): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE kids_duelos SET status = 'em_andamento'
             WHERE id = :id AND convidado_id = :convidado_id AND status = 'pendente'"
        );
        $stmt->execute(['id' => $duelId, 'convidado_id' => $convidadoId]);

        return $stmt->rowCount() > 0;
    }

    public static function recusar(int $duelId, int $convidadoId): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE kids_duelos SET status = 'recusado'
             WHERE id = :id AND convidado_id = :convidado_id AND status = 'pendente'"
        );
        $stmt->execute(['id' => $duelId, 'convidado_id' => $convidadoId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Atualiza o progresso (respostas certas) da crianca no duelo - so
     * aceita enquanto o duelo esta em_andamento e so pro criador ou
     * convidado dele. Quando os dois ja tiverem terminado (ambos os
     * "terminado_em" preenchidos), fecha o duelo, decide o vencedor
     * (quem terminou primeiro) e concede o bonus - so uma vez, ja que a
     * proxima chamada encontra status diferente de 'em_andamento' e nao
     * faz nada.
     */
    public static function registrarProgresso(int $duelId, int $criancaId, int $progresso, bool $terminou): void
    {
        $duelo = self::find($duelId);

        if ($duelo === null || $duelo['status'] !== 'em_andamento') {
            return;
        }

        if ($criancaId !== $duelo['criadorId'] && $criancaId !== $duelo['convidadoId']) {
            return;
        }

        $pdo = Database::connection();
        $souCriador = $criancaId === $duelo['criadorId'];
        $campoProgresso = $souCriador ? 'criador_progresso' : 'convidado_progresso';
        $campoTerminado = $souCriador ? 'criador_terminado_em' : 'convidado_terminado_em';

        $sql = "UPDATE kids_duelos SET {$campoProgresso} = :progresso" .
            ($terminou ? ", {$campoTerminado} = COALESCE({$campoTerminado}, NOW())" : '') .
            ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['progresso' => $progresso, 'id' => $duelId]);

        if (!$terminou) {
            return;
        }

        $atualizado = self::find($duelId);

        if ($atualizado === null || !$atualizado['criadorTerminou'] || !$atualizado['convidadoTerminou']) {
            return;
        }

        self::finalizar($duelId, $atualizado['criadorId'], $atualizado['convidadoId']);
    }

    private static function finalizar(int $duelId, int $criadorId, int $convidadoId): void
    {
        $pdo = Database::connection();

        $tempos = $pdo->prepare(
            'SELECT criador_terminado_em, convidado_terminado_em FROM kids_duelos WHERE id = :id'
        );
        $tempos->execute(['id' => $duelId]);
        $row = $tempos->fetch();

        $vencedorId = strtotime((string) $row['criador_terminado_em']) <= strtotime((string) $row['convidado_terminado_em'])
            ? $criadorId
            : $convidadoId;
        $perdedorId = $vencedorId === $criadorId ? $convidadoId : $criadorId;

        $fechar = $pdo->prepare(
            "UPDATE kids_duelos SET status = 'finalizado', vencedor_id = :vencedor_id WHERE id = :id AND status = 'em_andamento'"
        );
        $fechar->execute(['vencedor_id' => $vencedorId, 'id' => $duelId]);

        if ($fechar->rowCount() === 0) {
            // Outra requisicao concorrente ja fechou este duelo - nao
            // concede o bonus de novo.
            return;
        }

        KidsCrianca::adicionarPontos($vencedorId, self::BONUS_VENCEDOR['xp'], self::BONUS_VENCEDOR['moedas']);
        KidsCrianca::adicionarPontos($perdedorId, self::BONUS_PARTICIPACAO['xp'], self::BONUS_PARTICIPACAO['moedas']);
    }

    /**
     * Reacao de emoji (nunca texto livre) - de proposito so aceita um
     * conjunto fixo pra nao virar uma caixa de mensagem disfarcada.
     */
    public static function reagir(int $duelId, int $criancaId, string $emoji): void
    {
        if (!in_array($emoji, self::REACOES_PERMITIDAS, true)) {
            return;
        }

        $duelo = self::find($duelId);

        if ($duelo === null || ($criancaId !== $duelo['criadorId'] && $criancaId !== $duelo['convidadoId'])) {
            return;
        }

        $souCriador = $criancaId === $duelo['criadorId'];
        $campo = $souCriador ? 'reacao_criador' : 'reacao_convidado';
        $campoEm = $souCriador ? 'reacao_criador_em' : 'reacao_convidado_em';

        $stmt = Database::connection()->prepare(
            "UPDATE kids_duelos SET {$campo} = :emoji, {$campoEm} = NOW() WHERE id = :id"
        );
        $stmt->execute(['emoji' => $emoji, 'id' => $duelId]);
    }

    /**
     * Snapshot do duelo do ponto de vista de uma das duas criancas -
     * usado tanto pra montar a tela inicial da sala quanto pela resposta
     * JSON do polling.
     *
     * @return array{status: string, meuProgresso: int, oponenteProgresso: int, meuNome: string, oponenteNome: string, meuTerminou: bool, oponenteTerminou: bool, vencedorSouEu: ?bool, reacaoOponente: ?string, reacaoOponenteEm: ?string}|null
     */
    public static function estadoPara(int $duelId, int $criancaId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT d.*, c1.nome AS criador_nome, c2.nome AS convidado_nome
             FROM kids_duelos d
             INNER JOIN kids_criancas c1 ON c1.id = d.criador_id
             INNER JOIN kids_criancas c2 ON c2.id = d.convidado_id
             WHERE d.id = :id'
        );
        $stmt->execute(['id' => $duelId]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $souCriador = (int) $row['criador_id'] === $criancaId;

        if (!$souCriador && (int) $row['convidado_id'] !== $criancaId) {
            return null;
        }

        return [
            'status' => (string) $row['status'],
            'meuProgresso' => $souCriador ? (int) $row['criador_progresso'] : (int) $row['convidado_progresso'],
            'oponenteProgresso' => $souCriador ? (int) $row['convidado_progresso'] : (int) $row['criador_progresso'],
            'meuNome' => $souCriador ? (string) $row['criador_nome'] : (string) $row['convidado_nome'],
            'oponenteNome' => $souCriador ? (string) $row['convidado_nome'] : (string) $row['criador_nome'],
            'meuTerminou' => ($souCriador ? $row['criador_terminado_em'] : $row['convidado_terminado_em']) !== null,
            'oponenteTerminou' => ($souCriador ? $row['convidado_terminado_em'] : $row['criador_terminado_em']) !== null,
            'vencedorSouEu' => $row['vencedor_id'] === null ? null : ((int) $row['vencedor_id'] === $criancaId),
            'reacaoOponente' => $souCriador ? $row['reacao_convidado'] : $row['reacao_criador'],
            'reacaoOponenteEm' => $souCriador ? $row['reacao_convidado_em'] : $row['reacao_criador_em'],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id: int, conteudoId: int, criadorId: int, convidadoId: int, status: string, criadorProgresso: int, convidadoProgresso: int, criadorTerminou: bool, convidadoTerminou: bool, vencedorId: ?int}
     */
    private static function mapear(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'conteudoId' => (int) $row['conteudo_id'],
            'criadorId' => (int) $row['criador_id'],
            'convidadoId' => (int) $row['convidado_id'],
            'status' => (string) $row['status'],
            'criadorProgresso' => (int) $row['criador_progresso'],
            'convidadoProgresso' => (int) $row['convidado_progresso'],
            'criadorTerminou' => $row['criador_terminado_em'] !== null,
            'convidadoTerminou' => $row['convidado_terminado_em'] !== null,
            'vencedorId' => $row['vencedor_id'] !== null ? (int) $row['vencedor_id'] : null,
        ];
    }
}
