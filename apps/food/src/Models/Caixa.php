<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Sessao de caixa (abertura/fechamento) do turno. So pode existir UM
 * caixa aberto por vez por restaurante - a checagem fica no
 * CaixaController (chamar abrir() com um caixa ja aberto so criaria
 * uma segunda linha, sem trava no banco, igual ao padrao ja usado em
 * Barbearias\Models\Caixa).
 */
final class Caixa
{
    public const STATUS_ABERTO = 'aberto';
    public const STATUS_FECHADO = 'fechado';

    private const SELECT_COLUNAS = 'id, restaurante_id, usuario_id, status, valor_abertura, valor_fechamento_informado,
        observacoes_abertura, observacoes_fechamento, aberto_em, fechado_em';

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
        public readonly ?int $usuarioId,
        public readonly string $status,
        public readonly float $valorAbertura,
        public readonly ?float $valorFechamentoInformado,
        public readonly ?string $observacoesAbertura,
        public readonly ?string $observacoesFechamento,
        public readonly string $abertoEm,
        public readonly ?string $fechadoEm,
    ) {
    }

    public static function find(int $id, int $restauranteId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM caixas WHERE id = :id AND restaurante_id = :restaurante_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function aberto(int $restauranteId): ?self
    {
        $stmt = Database::connection()->prepare(
            "SELECT " . self::SELECT_COLUNAS . " FROM caixas
             WHERE restaurante_id = :restaurante_id AND status = 'aberto'
             ORDER BY aberto_em DESC LIMIT 1"
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function historico(int $restauranteId, int $page, int $perPage): array
    {
        $total = (int) self::contar($restauranteId);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM caixas WHERE restaurante_id = :restaurante_id
             ORDER BY aberto_em DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return [
            'items' => array_map(self::fromRow(...), $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ];
    }

    public static function abrir(int $restauranteId, ?int $usuarioId, float $valorAbertura, ?string $observacoes): int
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO caixas (restaurante_id, usuario_id, status, valor_abertura, observacoes_abertura, aberto_em)
             VALUES (:restaurante_id, :usuario_id, 'aberto', :valor_abertura, :observacoes, NOW())"
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'usuario_id' => $usuarioId,
            'valor_abertura' => max(0, $valorAbertura),
            'observacoes' => self::nullable($observacoes),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function fechar(int $id, int $restauranteId, float $valorFechamentoInformado, ?string $observacoes): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE caixas SET status = 'fechado', valor_fechamento_informado = :valor_fechamento,
                observacoes_fechamento = :observacoes, fechado_em = NOW()
             WHERE id = :id AND restaurante_id = :restaurante_id AND status = 'aberto'"
        );
        $stmt->execute([
            'valor_fechamento' => max(0, $valorFechamentoInformado),
            'observacoes' => self::nullable($observacoes),
            'id' => $id,
            'restaurante_id' => $restauranteId,
        ]);
    }

    /**
     * Saldo esperado em caixa agora: valor de abertura + receitas -
     * despesas ja lancadas nele (vendas do PDV, sangria, suprimento).
     */
    public static function saldoEsperado(self $caixa): float
    {
        return $caixa->valorAbertura + FinanceiroLancamento::saldoDoCaixa($caixa->id, $caixa->restauranteId);
    }

    private static function contar(int $restauranteId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM caixas WHERE restaurante_id = :restaurante_id');
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return (int) $stmt->fetchColumn();
    }

    private static function nullable(?string $valor): ?string
    {
        $valor = $valor !== null ? trim($valor) : '';

        return $valor === '' ? null : $valor;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            restauranteId: (int) $row['restaurante_id'],
            usuarioId: $row['usuario_id'] !== null ? (int) $row['usuario_id'] : null,
            status: (string) $row['status'],
            valorAbertura: (float) $row['valor_abertura'],
            valorFechamentoInformado: $row['valor_fechamento_informado'] !== null ? (float) $row['valor_fechamento_informado'] : null,
            observacoesAbertura: $row['observacoes_abertura'] ?? null,
            observacoesFechamento: $row['observacoes_fechamento'] ?? null,
            abertoEm: (string) $row['aberto_em'],
            fechadoEm: isset($row['fechado_em']) ? (string) $row['fechado_em'] : null,
        );
    }
}
