<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Ingrediente - a base da Ficha Tecnica (Fase 3). "categoria" aqui e
 * texto livre do proprio usuario (ex.: "Laticínios", "Embalagens") -
 * DIFERENTE da tabela `categorias`, que categoriza PRODUTOS (o prato
 * vendido), nao ingredientes. estoque_atual/estoque_minimo em DECIMAL
 * porque ingrediente se compra e se usa fracionado (ex.: 2,5kg).
 */
final class Ingrediente
{
    private const SELECT_COLUNAS = 'id, restaurante_id, nome, categoria, fornecedor_id, codigo, unidade,
        preco_atual, preco_medio, ultima_compra_em, estoque_atual, estoque_minimo, localizacao,
        observacao, foto_path, ativo, created_at, updated_at';

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
        public readonly string $nome,
        public readonly ?string $categoria,
        public readonly ?int $fornecedorId,
        public readonly ?string $codigo,
        public readonly string $unidade,
        public readonly float $precoAtual,
        public readonly ?float $precoMedio,
        public readonly ?string $ultimaCompraEm,
        public readonly float $estoqueAtual,
        public readonly float $estoqueMinimo,
        public readonly ?string $localizacao,
        public readonly ?string $observacao,
        public readonly ?string $fotoPath,
        public readonly bool $ativo,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $restauranteId, int $page, int $perPage, string $search = ''): array
    {
        $where = 'WHERE restaurante_id = :restaurante_id';
        $params = ['restaurante_id' => $restauranteId];

        if ($search !== '') {
            $where .= ' AND (nome LIKE :busca OR codigo LIKE :busca_codigo)';
            $params['busca'] = '%' . $search . '%';
            $params['busca_codigo'] = '%' . $search . '%';
        }

        $total = (int) self::contarComFiltro($where, $params);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM ingredientes {$where} ORDER BY nome ASC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return [
            'items' => array_map(self::fromRow(...), $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ];
    }

    public static function find(int $id, int $restauranteId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM ingredientes WHERE id = :id AND restaurante_id = :restaurante_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function ativos(int $restauranteId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM ingredientes WHERE restaurante_id = :restaurante_id AND ativo = 1 ORDER BY nome ASC'
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Ingredientes ativos com estoque no ou abaixo do minimo cadastrado -
     * usado pro alerta de reposicao.
     *
     * @return array<int, self>
     */
    public static function comEstoqueBaixo(int $restauranteId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM ingredientes
             WHERE restaurante_id = :restaurante_id AND ativo = 1 AND estoque_atual <= estoque_minimo
             ORDER BY nome ASC'
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function create(
        int $restauranteId,
        string $nome,
        ?string $categoria,
        ?int $fornecedorId,
        ?string $codigo,
        string $unidade,
        float $precoAtual,
        float $estoqueAtual,
        float $estoqueMinimo,
        ?string $localizacao,
        ?string $observacao,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO ingredientes (restaurante_id, nome, categoria, fornecedor_id, codigo, unidade,
                preco_atual, estoque_atual, estoque_minimo, localizacao, observacao, ativo, created_at, updated_at)
             VALUES (:restaurante_id, :nome, :categoria, :fornecedor_id, :codigo, :unidade,
                :preco_atual, :estoque_atual, :estoque_minimo, :localizacao, :observacao, 1, NOW(), NOW())'
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'nome' => trim($nome),
            'categoria' => self::vazioParaNulo($categoria),
            'fornecedor_id' => $fornecedorId,
            'codigo' => self::vazioParaNulo($codigo),
            'unidade' => $unidade,
            'preco_atual' => $precoAtual,
            'estoque_atual' => $estoqueAtual,
            'estoque_minimo' => $estoqueMinimo,
            'localizacao' => self::vazioParaNulo($localizacao),
            'observacao' => self::vazioParaNulo($observacao),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(
        int $id,
        int $restauranteId,
        string $nome,
        ?string $categoria,
        ?int $fornecedorId,
        ?string $codigo,
        string $unidade,
        float $precoAtual,
        float $estoqueAtual,
        float $estoqueMinimo,
        ?string $localizacao,
        ?string $observacao,
        bool $ativo,
    ): void {
        $stmt = Database::connection()->prepare(
            'UPDATE ingredientes SET nome = :nome, categoria = :categoria, fornecedor_id = :fornecedor_id,
                codigo = :codigo, unidade = :unidade, preco_atual = :preco_atual, estoque_atual = :estoque_atual,
                estoque_minimo = :estoque_minimo, localizacao = :localizacao, observacao = :observacao,
                ativo = :ativo, updated_at = NOW()
             WHERE id = :id AND restaurante_id = :restaurante_id'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'categoria' => self::vazioParaNulo($categoria),
            'fornecedor_id' => $fornecedorId,
            'codigo' => self::vazioParaNulo($codigo),
            'unidade' => $unidade,
            'preco_atual' => $precoAtual,
            'estoque_atual' => $estoqueAtual,
            'estoque_minimo' => $estoqueMinimo,
            'localizacao' => self::vazioParaNulo($localizacao),
            'observacao' => self::vazioParaNulo($observacao),
            'ativo' => $ativo ? 1 : 0,
            'id' => $id,
            'restaurante_id' => $restauranteId,
        ]);
    }

    public static function atualizarFoto(int $id, int $restauranteId, ?string $fotoPath): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE ingredientes SET foto_path = :foto_path WHERE id = :id AND restaurante_id = :restaurante_id'
        );
        $stmt->execute(['foto_path' => $fotoPath, 'id' => $id, 'restaurante_id' => $restauranteId]);
    }

    /**
     * Registra a entrada de uma Compra: soma a quantidade no estoque,
     * atualiza o preco_atual pro valor pago desta vez, recalcula o
     * preco_medio (custo medio ponderado - media entre o estoque que
     * já tinha ao preco medio anterior e a quantidade nova ao preco
     * desta compra) e marca a data da ultima compra. Quem dispara o
     * recalculo de custo dos produtos afetados é o chamador (ver
     * CompraItem::create()), depois que o preco muda de verdade.
     */
    public static function registrarEntradaCompra(int $id, int $restauranteId, float $quantidade, float $precoUnitario, string $dataCompra): void
    {
        $ingrediente = self::find($id, $restauranteId);

        if ($ingrediente === null) {
            return;
        }

        $estoqueAntes = $ingrediente->estoqueAtual;
        $precoBaseParaMedia = $ingrediente->precoMedio ?? $ingrediente->precoAtual;
        $novoEstoque = $estoqueAntes + $quantidade;
        $novoPrecoMedio = $novoEstoque > 0
            ? (($estoqueAntes * $precoBaseParaMedia) + ($quantidade * $precoUnitario)) / $novoEstoque
            : $precoUnitario;

        $stmt = Database::connection()->prepare(
            'UPDATE ingredientes SET estoque_atual = estoque_atual + :quantidade, preco_atual = :preco_atual,
                preco_medio = :preco_medio, ultima_compra_em = :ultima_compra_em, updated_at = NOW()
             WHERE id = :id AND restaurante_id = :restaurante_id'
        );
        $stmt->execute([
            'quantidade' => $quantidade,
            'preco_atual' => $precoUnitario,
            'preco_medio' => $novoPrecoMedio,
            'ultima_compra_em' => $dataCompra,
            'id' => $id,
            'restaurante_id' => $restauranteId,
        ]);
    }

    public static function delete(int $id, int $restauranteId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM ingredientes WHERE id = :id AND restaurante_id = :restaurante_id');
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
    }

    private static function vazioParaNulo(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $valor = trim($valor);

        return $valor === '' ? null : $valor;
    }

    private static function contarComFiltro(string $where, array $params): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM ingredientes {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            restauranteId: (int) $row['restaurante_id'],
            nome: (string) $row['nome'],
            categoria: $row['categoria'] !== null ? (string) $row['categoria'] : null,
            fornecedorId: $row['fornecedor_id'] !== null ? (int) $row['fornecedor_id'] : null,
            codigo: $row['codigo'] !== null ? (string) $row['codigo'] : null,
            unidade: (string) $row['unidade'],
            precoAtual: (float) $row['preco_atual'],
            precoMedio: $row['preco_medio'] !== null ? (float) $row['preco_medio'] : null,
            ultimaCompraEm: $row['ultima_compra_em'] !== null ? (string) $row['ultima_compra_em'] : null,
            estoqueAtual: (float) $row['estoque_atual'],
            estoqueMinimo: (float) $row['estoque_minimo'],
            localizacao: $row['localizacao'] !== null ? (string) $row['localizacao'] : null,
            observacao: $row['observacao'] !== null ? (string) $row['observacao'] : null,
            fotoPath: $row['foto_path'] !== null ? (string) $row['foto_path'] : null,
            ativo: (bool) $row['ativo'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }
}
