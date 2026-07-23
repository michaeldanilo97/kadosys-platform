<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Custeio;
use Food\Core\Database;

/**
 * Produto vendido (o prato/item do cardapio). Os campos de "cache de
 * custeio" (custo_total, markup, margem_percentual, lucro,
 * preco_ideal_*) nunca sao escritos diretamente por
 * create()/update() - so `recalcularCusto()` os atualiza, sempre
 * derivados da ficha tecnica (ver FichaTecnicaItem) + configuracao de
 * custeio (ver CusteioConfig). Isso evita o cache ficar desatualizado
 * em relacao a receita/precos de ingrediente reais.
 */
final class Produto
{
    public const STATUS_ATIVO = 'ativo';
    public const STATUS_PAUSADO = 'pausado';
    public const STATUS_INATIVO = 'inativo';

    /** @var array<int, string> */
    public const STATUS_VALIDOS = [self::STATUS_ATIVO, self::STATUS_PAUSADO, self::STATUS_INATIVO];

    private const SELECT_COLUNAS = 'id, restaurante_id, categoria_id, codigo, codigo_barras, nome, descricao,
        foto_path, tags, observacoes, tempo_preparo_min, peso_g, rendimento, status,
        preco_balcao, preco_whatsapp, preco_ifood, preco_promocao, preco_delivery_proprio,
        custo_energia_override, custo_gas_override, custo_agua_override, custo_embalagem_override,
        custo_etiqueta_override, custo_mao_obra_override, custo_taxa_operacional_override,
        custo_desperdicio_override, custo_total, markup, margem_percentual, lucro,
        preco_ideal_balcao, preco_ideal_whatsapp, preco_ideal_ifood, preco_ideal_delivery,
        created_at, updated_at';

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
        public readonly ?int $categoriaId,
        public readonly ?string $codigo,
        public readonly ?string $codigoBarras,
        public readonly string $nome,
        public readonly ?string $descricao,
        public readonly ?string $fotoPath,
        public readonly ?string $tags,
        public readonly ?string $observacoes,
        public readonly ?int $tempoPreparoMin,
        public readonly ?float $pesoG,
        public readonly int $rendimento,
        public readonly string $status,
        public readonly float $precoBalcao,
        public readonly ?float $precoWhatsapp,
        public readonly ?float $precoIfood,
        public readonly ?float $precoPromocao,
        public readonly ?float $precoDeliveryProprio,
        public readonly ?float $custoEnergiaOverride,
        public readonly ?float $custoGasOverride,
        public readonly ?float $custoAguaOverride,
        public readonly ?float $custoEmbalagemOverride,
        public readonly ?float $custoEtiquetaOverride,
        public readonly ?float $custoMaoObraOverride,
        public readonly ?float $custoTaxaOperacionalOverride,
        public readonly ?float $custoDesperdicioOverride,
        public readonly float $custoTotal,
        public readonly float $markup,
        public readonly float $margemPercentual,
        public readonly float $lucro,
        public readonly float $precoIdealBalcao,
        public readonly float $precoIdealWhatsapp,
        public readonly float $precoIdealIfood,
        public readonly float $precoIdealDelivery,
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
            'SELECT ' . self::SELECT_COLUNAS . " FROM produtos {$where} ORDER BY nome ASC LIMIT {$perPage} OFFSET {$offset}"
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
            'SELECT ' . self::SELECT_COLUNAS . ' FROM produtos WHERE id = :id AND restaurante_id = :restaurante_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * @param array{codigo: ?string, codigo_barras: ?string, descricao: ?string, tags: ?string,
     *   observacoes: ?string, tempo_preparo_min: ?int, peso_g: ?float, energia: ?float, gas: ?float,
     *   agua: ?float, embalagem: ?float, etiqueta: ?float, mao_obra: ?float, taxa_operacional: ?float,
     *   desperdicio: ?float} $extra
     */
    public static function create(
        int $restauranteId,
        ?int $categoriaId,
        string $nome,
        int $rendimento,
        float $precoBalcao,
        ?float $precoWhatsapp,
        ?float $precoIfood,
        ?float $precoPromocao,
        ?float $precoDeliveryProprio,
        array $extra,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO produtos (restaurante_id, categoria_id, codigo, codigo_barras, nome, descricao,
                tags, observacoes, tempo_preparo_min, peso_g, rendimento, status,
                preco_balcao, preco_whatsapp, preco_ifood, preco_promocao, preco_delivery_proprio,
                custo_energia_override, custo_gas_override, custo_agua_override, custo_embalagem_override,
                custo_etiqueta_override, custo_mao_obra_override, custo_taxa_operacional_override,
                custo_desperdicio_override, created_at, updated_at)
             VALUES (:restaurante_id, :categoria_id, :codigo, :codigo_barras, :nome, :descricao,
                :tags, :observacoes, :tempo_preparo_min, :peso_g, :rendimento, :status,
                :preco_balcao, :preco_whatsapp, :preco_ifood, :preco_promocao, :preco_delivery_proprio,
                :energia, :gas, :agua, :embalagem, :etiqueta, :mao_obra, :taxa_operacional, :desperdicio,
                NOW(), NOW())'
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'categoria_id' => $categoriaId,
            'codigo' => $extra['codigo'] ?? null,
            'codigo_barras' => $extra['codigo_barras'] ?? null,
            'nome' => trim($nome),
            'descricao' => $extra['descricao'] ?? null,
            'tags' => $extra['tags'] ?? null,
            'observacoes' => $extra['observacoes'] ?? null,
            'tempo_preparo_min' => $extra['tempo_preparo_min'] ?? null,
            'peso_g' => $extra['peso_g'] ?? null,
            'rendimento' => max(1, $rendimento),
            'status' => self::STATUS_ATIVO,
            'preco_balcao' => $precoBalcao,
            'preco_whatsapp' => $precoWhatsapp,
            'preco_ifood' => $precoIfood,
            'preco_promocao' => $precoPromocao,
            'preco_delivery_proprio' => $precoDeliveryProprio,
            'energia' => $extra['energia'] ?? null,
            'gas' => $extra['gas'] ?? null,
            'agua' => $extra['agua'] ?? null,
            'embalagem' => $extra['embalagem'] ?? null,
            'etiqueta' => $extra['etiqueta'] ?? null,
            'mao_obra' => $extra['mao_obra'] ?? null,
            'taxa_operacional' => $extra['taxa_operacional'] ?? null,
            'desperdicio' => $extra['desperdicio'] ?? null,
        ]);

        $id = (int) Database::connection()->lastInsertId();
        self::recalcularCusto($id, $restauranteId);

        return $id;
    }

    public static function update(
        int $id,
        int $restauranteId,
        ?int $categoriaId,
        string $nome,
        int $rendimento,
        string $status,
        float $precoBalcao,
        ?float $precoWhatsapp,
        ?float $precoIfood,
        ?float $precoPromocao,
        ?float $precoDeliveryProprio,
        array $extra,
    ): void {
        $stmt = Database::connection()->prepare(
            'UPDATE produtos SET categoria_id = :categoria_id, codigo = :codigo, codigo_barras = :codigo_barras,
                nome = :nome, descricao = :descricao, tags = :tags, observacoes = :observacoes,
                tempo_preparo_min = :tempo_preparo_min, peso_g = :peso_g, rendimento = :rendimento,
                status = :status, preco_balcao = :preco_balcao, preco_whatsapp = :preco_whatsapp,
                preco_ifood = :preco_ifood, preco_promocao = :preco_promocao,
                preco_delivery_proprio = :preco_delivery_proprio,
                custo_energia_override = :energia, custo_gas_override = :gas, custo_agua_override = :agua,
                custo_embalagem_override = :embalagem, custo_etiqueta_override = :etiqueta,
                custo_mao_obra_override = :mao_obra, custo_taxa_operacional_override = :taxa_operacional,
                custo_desperdicio_override = :desperdicio, updated_at = NOW()
             WHERE id = :id AND restaurante_id = :restaurante_id'
        );
        $stmt->execute([
            'categoria_id' => $categoriaId,
            'codigo' => $extra['codigo'] ?? null,
            'codigo_barras' => $extra['codigo_barras'] ?? null,
            'nome' => trim($nome),
            'descricao' => $extra['descricao'] ?? null,
            'tags' => $extra['tags'] ?? null,
            'observacoes' => $extra['observacoes'] ?? null,
            'tempo_preparo_min' => $extra['tempo_preparo_min'] ?? null,
            'peso_g' => $extra['peso_g'] ?? null,
            'rendimento' => max(1, $rendimento),
            'status' => in_array($status, self::STATUS_VALIDOS, true) ? $status : self::STATUS_ATIVO,
            'preco_balcao' => $precoBalcao,
            'preco_whatsapp' => $precoWhatsapp,
            'preco_ifood' => $precoIfood,
            'preco_promocao' => $precoPromocao,
            'preco_delivery_proprio' => $precoDeliveryProprio,
            'energia' => $extra['energia'] ?? null,
            'gas' => $extra['gas'] ?? null,
            'agua' => $extra['agua'] ?? null,
            'embalagem' => $extra['embalagem'] ?? null,
            'etiqueta' => $extra['etiqueta'] ?? null,
            'mao_obra' => $extra['mao_obra'] ?? null,
            'taxa_operacional' => $extra['taxa_operacional'] ?? null,
            'desperdicio' => $extra['desperdicio'] ?? null,
            'id' => $id,
            'restaurante_id' => $restauranteId,
        ]);

        self::recalcularCusto($id, $restauranteId);
    }

    public static function atualizarFoto(int $id, int $restauranteId, ?string $fotoPath): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE produtos SET foto_path = :foto_path WHERE id = :id AND restaurante_id = :restaurante_id'
        );
        $stmt->execute(['foto_path' => $fotoPath, 'id' => $id, 'restaurante_id' => $restauranteId]);
    }

    public static function delete(int $id, int $restauranteId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM produtos WHERE id = :id AND restaurante_id = :restaurante_id');
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
    }

    /**
     * Recalcula e persiste os campos de cache de custeio de UM produto,
     * a partir da ficha tecnica atual + configuracao de custeio (global
     * ou override do proprio produto). Chamada sempre que a ficha
     * tecnica muda (item adicionado/removido) ou o produto e salvo.
     */
    public static function recalcularCusto(int $produtoId, int $restauranteId): void
    {
        $produto = self::find($produtoId, $restauranteId);

        if ($produto === null) {
            return;
        }

        $custoIngredientesTotal = FichaTecnicaItem::custoTotalDoProduto($produtoId);
        $config = CusteioConfig::obterOuCriar($restauranteId);

        $resultado = Custeio::calcular(
            custoIngredientesTotal: $custoIngredientesTotal,
            rendimento: $produto->rendimento,
            overhead: $config->overheadResolvido($produto),
            margemDesejadaPercentual: $config->margemDesejadaPadrao,
            comissaoIfoodPercentual: $config->comissaoIfoodPadrao,
            taxaPagamentoOnlinePercentual: $config->taxaPagamentoOnlinePadrao,
        );

        $stmt = Database::connection()->prepare(
            'UPDATE produtos SET custo_total = :custo_total, markup = :markup,
                margem_percentual = :margem_percentual, lucro = :lucro,
                preco_ideal_balcao = :preco_ideal_balcao, preco_ideal_whatsapp = :preco_ideal_whatsapp,
                preco_ideal_ifood = :preco_ideal_ifood, preco_ideal_delivery = :preco_ideal_delivery
             WHERE id = :id AND restaurante_id = :restaurante_id'
        );
        $stmt->execute([
            'custo_total' => $resultado['custoTotal'],
            'markup' => $resultado['markup'],
            'margem_percentual' => $resultado['margemPercentual'],
            'lucro' => $resultado['lucro'],
            'preco_ideal_balcao' => $resultado['precoIdealBalcao'],
            'preco_ideal_whatsapp' => $resultado['precoIdealWhatsapp'],
            'preco_ideal_ifood' => $resultado['precoIdealIfood'],
            'preco_ideal_delivery' => $resultado['precoIdealDelivery'],
            'id' => $produtoId,
            'restaurante_id' => $restauranteId,
        ]);
    }

    /**
     * Recalcula em cascata o custo de todos os produtos que usam um
     * ingrediente na ficha tecnica - chamada sempre que o preco_atual
     * desse ingrediente muda (edicao manual hoje, entrada de Compra na
     * Fase 4), no mesmo request que atualiza o preco.
     */
    public static function recalcularCustoDeProdutosComIngrediente(int $ingredienteId, int $restauranteId): void
    {
        foreach (FichaTecnicaItem::produtoIdsComIngrediente($ingredienteId, $restauranteId) as $produtoId) {
            self::recalcularCusto($produtoId, $restauranteId);
        }
    }

    private static function contarComFiltro(string $where, array $params): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM produtos {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            restauranteId: (int) $row['restaurante_id'],
            categoriaId: $row['categoria_id'] !== null ? (int) $row['categoria_id'] : null,
            codigo: $row['codigo'] !== null ? (string) $row['codigo'] : null,
            codigoBarras: $row['codigo_barras'] !== null ? (string) $row['codigo_barras'] : null,
            nome: (string) $row['nome'],
            descricao: $row['descricao'] !== null ? (string) $row['descricao'] : null,
            fotoPath: $row['foto_path'] !== null ? (string) $row['foto_path'] : null,
            tags: $row['tags'] !== null ? (string) $row['tags'] : null,
            observacoes: $row['observacoes'] !== null ? (string) $row['observacoes'] : null,
            tempoPreparoMin: $row['tempo_preparo_min'] !== null ? (int) $row['tempo_preparo_min'] : null,
            pesoG: $row['peso_g'] !== null ? (float) $row['peso_g'] : null,
            rendimento: (int) $row['rendimento'],
            status: (string) $row['status'],
            precoBalcao: (float) $row['preco_balcao'],
            precoWhatsapp: $row['preco_whatsapp'] !== null ? (float) $row['preco_whatsapp'] : null,
            precoIfood: $row['preco_ifood'] !== null ? (float) $row['preco_ifood'] : null,
            precoPromocao: $row['preco_promocao'] !== null ? (float) $row['preco_promocao'] : null,
            precoDeliveryProprio: $row['preco_delivery_proprio'] !== null ? (float) $row['preco_delivery_proprio'] : null,
            custoEnergiaOverride: $row['custo_energia_override'] !== null ? (float) $row['custo_energia_override'] : null,
            custoGasOverride: $row['custo_gas_override'] !== null ? (float) $row['custo_gas_override'] : null,
            custoAguaOverride: $row['custo_agua_override'] !== null ? (float) $row['custo_agua_override'] : null,
            custoEmbalagemOverride: $row['custo_embalagem_override'] !== null ? (float) $row['custo_embalagem_override'] : null,
            custoEtiquetaOverride: $row['custo_etiqueta_override'] !== null ? (float) $row['custo_etiqueta_override'] : null,
            custoMaoObraOverride: $row['custo_mao_obra_override'] !== null ? (float) $row['custo_mao_obra_override'] : null,
            custoTaxaOperacionalOverride: $row['custo_taxa_operacional_override'] !== null ? (float) $row['custo_taxa_operacional_override'] : null,
            custoDesperdicioOverride: $row['custo_desperdicio_override'] !== null ? (float) $row['custo_desperdicio_override'] : null,
            custoTotal: (float) $row['custo_total'],
            markup: (float) $row['markup'],
            margemPercentual: (float) $row['margem_percentual'],
            lucro: (float) $row['lucro'],
            precoIdealBalcao: (float) $row['preco_ideal_balcao'],
            precoIdealWhatsapp: (float) $row['preco_ideal_whatsapp'],
            precoIdealIfood: (float) $row['preco_ideal_ifood'],
            precoIdealDelivery: (float) $row['preco_ideal_delivery'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }
}
