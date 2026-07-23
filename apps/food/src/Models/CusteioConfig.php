<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Configuracao global de custeio: 1 linha por restaurante, com os
 * valores padrao de overhead (custo estimado por unidade produzida)
 * que valem pra todo produto sem override proprio, alem da margem
 * desejada padrao e das taxas de iFood/pagamento online usadas por
 * `Food\Core\Custeio` pra calcular o preco ideal por canal.
 *
 * A tela de edicao desses valores fica pra Fase 7 (Precificacao
 * Inteligente) - por ora o unico ponto de entrada e
 * `obterOuCriar()`, que semeia a linha com os defaults do banco na
 * primeira vez que a Fase 3 (Produtos) precisar dela.
 */
final class CusteioConfig
{
    private const SELECT_COLUNAS = 'id, restaurante_id, valor_energia_padrao, valor_gas_padrao,
        valor_agua_padrao, valor_embalagem_padrao, valor_etiqueta_padrao, valor_mao_obra_padrao,
        valor_taxa_operacional_padrao, valor_desperdicio_padrao, margem_desejada_padrao,
        comissao_ifood_padrao, taxa_pagamento_online_padrao';

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
        public readonly float $valorEnergiaPadrao,
        public readonly float $valorGasPadrao,
        public readonly float $valorAguaPadrao,
        public readonly float $valorEmbalagemPadrao,
        public readonly float $valorEtiquetaPadrao,
        public readonly float $valorMaoObraPadrao,
        public readonly float $valorTaxaOperacionalPadrao,
        public readonly float $valorDesperdicioPadrao,
        public readonly float $margemDesejadaPadrao,
        public readonly float $comissaoIfoodPadrao,
        public readonly float $taxaPagamentoOnlinePadrao,
    ) {
    }

    /**
     * Retorna a configuracao de custeio do restaurante, criando com os
     * valores padrao do banco (DEFAULT das colunas) se ainda nao
     * existir - todo restaurante acaba tendo exatamente uma linha.
     */
    public static function obterOuCriar(int $restauranteId): self
    {
        $existente = self::buscar($restauranteId);

        if ($existente !== null) {
            return $existente;
        }

        $stmt = Database::connection()->prepare(
            'INSERT INTO custeio_config (restaurante_id, created_at, updated_at) VALUES (:restaurante_id, NOW(), NOW())'
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return self::buscar($restauranteId)
            ?? throw new \RuntimeException('Falha ao criar configuracao de custeio.');
    }

    /**
     * Atualiza os valores padrao de overhead/margem/taxas - usado pela
     * tela de Precificacao Inteligente (Fase 7). Nao recalcula custo de
     * produto nenhum sozinho: quem usa override (valor proprio no
     * produto) nao e afetado; quem usa o padrao passa a ver o novo
     * valor so na proxima vez que o custo for recalculado (ex.: ao
     * salvar o produto de novo ou registrar uma compra).
     */
    public static function atualizar(
        int $restauranteId,
        float $valorEnergiaPadrao,
        float $valorGasPadrao,
        float $valorAguaPadrao,
        float $valorEmbalagemPadrao,
        float $valorEtiquetaPadrao,
        float $valorMaoObraPadrao,
        float $valorTaxaOperacionalPadrao,
        float $valorDesperdicioPadrao,
        float $margemDesejadaPadrao,
        float $comissaoIfoodPadrao,
        float $taxaPagamentoOnlinePadrao,
    ): void {
        self::obterOuCriar($restauranteId);

        $stmt = Database::connection()->prepare(
            'UPDATE custeio_config SET valor_energia_padrao = :energia, valor_gas_padrao = :gas,
                valor_agua_padrao = :agua, valor_embalagem_padrao = :embalagem, valor_etiqueta_padrao = :etiqueta,
                valor_mao_obra_padrao = :mao_obra, valor_taxa_operacional_padrao = :taxa_operacional,
                valor_desperdicio_padrao = :desperdicio, margem_desejada_padrao = :margem,
                comissao_ifood_padrao = :comissao_ifood, taxa_pagamento_online_padrao = :taxa_pagamento_online,
                updated_at = NOW()
             WHERE restaurante_id = :restaurante_id'
        );
        $stmt->execute([
            'energia' => max(0, $valorEnergiaPadrao),
            'gas' => max(0, $valorGasPadrao),
            'agua' => max(0, $valorAguaPadrao),
            'embalagem' => max(0, $valorEmbalagemPadrao),
            'etiqueta' => max(0, $valorEtiquetaPadrao),
            'mao_obra' => max(0, $valorMaoObraPadrao),
            'taxa_operacional' => max(0, $valorTaxaOperacionalPadrao),
            'desperdicio' => max(0, $valorDesperdicioPadrao),
            'margem' => min(95.0, max(0, $margemDesejadaPadrao)),
            'comissao_ifood' => min(95.0, max(0, $comissaoIfoodPadrao)),
            'taxa_pagamento_online' => min(95.0, max(0, $taxaPagamentoOnlinePadrao)),
            'restaurante_id' => $restauranteId,
        ]);
    }

    private static function buscar(int $restauranteId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM custeio_config WHERE restaurante_id = :restaurante_id LIMIT 1'
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Monta o array de overhead ja pronto pra `Food\Core\Custeio::calcular()`,
     * a partir dos overrides do produto (quando preenchidos) ou destes
     * valores padrao.
     *
     * @return array{energia: float, gas: float, agua: float, embalagem: float,
     *   etiqueta: float, mao_obra: float, taxa_operacional: float, desperdicio: float}
     */
    public function overheadResolvido(Produto $produto): array
    {
        return [
            'energia' => $produto->custoEnergiaOverride ?? $this->valorEnergiaPadrao,
            'gas' => $produto->custoGasOverride ?? $this->valorGasPadrao,
            'agua' => $produto->custoAguaOverride ?? $this->valorAguaPadrao,
            'embalagem' => $produto->custoEmbalagemOverride ?? $this->valorEmbalagemPadrao,
            'etiqueta' => $produto->custoEtiquetaOverride ?? $this->valorEtiquetaPadrao,
            'mao_obra' => $produto->custoMaoObraOverride ?? $this->valorMaoObraPadrao,
            'taxa_operacional' => $produto->custoTaxaOperacionalOverride ?? $this->valorTaxaOperacionalPadrao,
            'desperdicio' => $produto->custoDesperdicioOverride ?? $this->valorDesperdicioPadrao,
        ];
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            restauranteId: (int) $row['restaurante_id'],
            valorEnergiaPadrao: (float) $row['valor_energia_padrao'],
            valorGasPadrao: (float) $row['valor_gas_padrao'],
            valorAguaPadrao: (float) $row['valor_agua_padrao'],
            valorEmbalagemPadrao: (float) $row['valor_embalagem_padrao'],
            valorEtiquetaPadrao: (float) $row['valor_etiqueta_padrao'],
            valorMaoObraPadrao: (float) $row['valor_mao_obra_padrao'],
            valorTaxaOperacionalPadrao: (float) $row['valor_taxa_operacional_padrao'],
            valorDesperdicioPadrao: (float) $row['valor_desperdicio_padrao'],
            margemDesejadaPadrao: (float) $row['margem_desejada_padrao'],
            comissaoIfoodPadrao: (float) $row['comissao_ifood_padrao'],
            taxaPagamentoOnlinePadrao: (float) $row['taxa_pagamento_online_padrao'],
        );
    }
}
