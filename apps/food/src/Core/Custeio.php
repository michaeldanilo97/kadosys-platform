<?php

declare(strict_types=1);

namespace Food\Core;

/**
 * Motor de custeio e precificacao - unica classe com a formula de
 * markup/margem/lucro/preco ideal de toda a plataforma. Usada tanto
 * por `Produto::recalcularCusto()` (persiste os campos calculados em
 * cache no produto) quanto pela futura tela avulsa de Precificacao
 * Inteligente (Fase 7, simulador que nao salva produto nenhum).
 *
 * Estatica e sem nenhuma dependencia de banco (so recebe numeros
 * prontos) de proposito - fica trivial de testar e reaproveitar dos
 * dois lugares sem duplicar a formula.
 */
final class Custeio
{
    /**
     * @param float $custoIngredientesTotal Soma do custo de todos os itens da
     *   ficha tecnica para o LOTE inteiro da receita (ja considerando a
     *   perda_percentual de cada item), antes de dividir pelo rendimento.
     * @param int $rendimento Quantas unidades a receita rende - o custo de
     *   ingrediente e dividido por isso pra chegar no custo por unidade
     *   vendida. Nunca pode ser zero (protegido internamente).
     * @param array{energia: float, gas: float, agua: float, embalagem: float,
     *   etiqueta: float, mao_obra: float, taxa_operacional: float,
     *   desperdicio: float} $overhead Custo estimado POR UNIDADE produzida
     *   de cada rateio - ja resolvido pelo chamador (override do produto ou
     *   padrao de `custeio_config`), nao recalculado aqui.
     * @param float $margemDesejadaPercentual Margem desejada sobre o preco de
     *   venda (nao markup sobre custo) - ex.: 30 significa que 30% do preco
     *   final vendido no balcao e lucro. Limitada a no maximo 95 pra nunca
     *   gerar um markup infinito/negativo.
     * @param float $comissaoIfoodPercentual Percentual descontado pelo iFood
     *   sobre o valor do pedido - usado so pra "engordar" o preco ideal
     *   desse canal (nao e a taxa fixa por distancia da Entrega II, que e
     *   calculada por pedido em `IfoodTaxaEntrega`, nao por produto).
     * @param float $taxaPagamentoOnlinePercentual Percentual descontado por um
     *   link de pagamento (Pix/cartao) usado no delivery proprio.
     *
     * @return array{
     *   custoIngredientesPorUnidade: float,
     *   custoOverheadPorUnidade: float,
     *   custoTotal: float,
     *   markup: float,
     *   margemPercentual: float,
     *   lucro: float,
     *   precoIdealBalcao: float,
     *   precoIdealWhatsapp: float,
     *   precoIdealIfood: float,
     *   precoIdealDelivery: float,
     * }
     */
    public static function calcular(
        float $custoIngredientesTotal,
        int $rendimento,
        array $overhead,
        float $margemDesejadaPercentual,
        float $comissaoIfoodPercentual,
        float $taxaPagamentoOnlinePercentual,
    ): array {
        $rendimento = max(1, $rendimento);
        $custoIngredientesPorUnidade = $custoIngredientesTotal / $rendimento;

        $custoOverheadPorUnidade = array_sum([
            $overhead['energia'] ?? 0.0,
            $overhead['gas'] ?? 0.0,
            $overhead['agua'] ?? 0.0,
            $overhead['embalagem'] ?? 0.0,
            $overhead['etiqueta'] ?? 0.0,
            $overhead['mao_obra'] ?? 0.0,
            $overhead['taxa_operacional'] ?? 0.0,
            $overhead['desperdicio'] ?? 0.0,
        ]);

        $custoTotal = $custoIngredientesPorUnidade + $custoOverheadPorUnidade;

        // Margem = % do PRECO (nao do custo) - markup = 100 / (100 - margem).
        // Limitada a 95% pra nunca dividir por zero/numero negativo.
        $margemClamped = min(95.0, max(0.0, $margemDesejadaPercentual));
        $markup = 100.0 / (100.0 - $margemClamped);

        $precoIdealBalcao = round($custoTotal * $markup, 2);
        $lucro = $precoIdealBalcao - $custoTotal;
        $margemPercentual = $precoIdealBalcao > 0 ? ($lucro / $precoIdealBalcao) * 100 : 0.0;

        // WhatsApp e um canal direto (sem comissao de plataforma) - mesmo
        // preco do balcao. iFood e delivery proprio (quando pago por link
        // online) tem uma taxa descontada do valor recebido, entao o preco
        // "ideal" precisa ser maior pra sobrar a mesma margem liquida.
        $precoIdealWhatsapp = $precoIdealBalcao;
        $precoIdealIfood = self::engordarPreco($precoIdealBalcao, $comissaoIfoodPercentual);
        $precoIdealDelivery = self::engordarPreco($precoIdealBalcao, $taxaPagamentoOnlinePercentual);

        return [
            'custoIngredientesPorUnidade' => round($custoIngredientesPorUnidade, 4),
            'custoOverheadPorUnidade' => round($custoOverheadPorUnidade, 4),
            'custoTotal' => round($custoTotal, 4),
            'markup' => round($markup, 4),
            'margemPercentual' => round($margemPercentual, 2),
            'lucro' => round($lucro, 4),
            'precoIdealBalcao' => $precoIdealBalcao,
            'precoIdealWhatsapp' => round($precoIdealWhatsapp, 2),
            'precoIdealIfood' => round($precoIdealIfood, 2),
            'precoIdealDelivery' => round($precoIdealDelivery, 2),
        ];
    }

    /**
     * Aumenta um preco liquido desejado para compensar um percentual
     * descontado depois da venda (comissao/taxa), de forma que o valor
     * que sobra apos o desconto ainda seja igual ao preco base.
     */
    private static function engordarPreco(float $precoBase, float $percentualDescontado): float
    {
        $percentualClamped = min(95.0, max(0.0, $percentualDescontado));

        if ($percentualClamped <= 0.0) {
            return $precoBase;
        }

        return $precoBase / (1 - $percentualClamped / 100);
    }
}
