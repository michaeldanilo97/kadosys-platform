<?php

declare(strict_types=1);

namespace Food\Core;

/**
 * Calcula o valor liquido de um pedido do iFood sob o modelo "Entrega
 * II" (comissao percentual do valor do pedido + taxa fixa de entrega
 * por faixa de distancia, ambas descontadas do que o restaurante
 * recebe). Funcao pura, sem banco - usada tanto pela Precificacao
 * Inteligente (simulador avulso) quanto, futuramente, pelo registro
 * financeiro de um pedido de origem iFood (pra mostrar o valor liquido
 * real recebido, nao o valor bruto do pedido).
 *
 * Os percentuais/faixas do modelo real do iFood mudam por contrato e
 * regiao - os valores aqui sao os informados no spec original do
 * produto, entao ficam como constantes desta classe (unico lugar da
 * plataforma que precisa saber disso) pra facilitar ajustar depois se
 * o contrato do restaurante for diferente.
 */
final class IfoodTaxaEntrega
{
    public const COMISSAO_PERCENTUAL = 12.0;

    private const TAXA_ATE_3KM = 3.99;
    private const TAXA_ATE_5KM = 5.99;
    private const TAXA_ATE_7KM = 7.99;
    private const TAXA_ACIMA_7KM = 9.99;

    /**
     * @return array{comissao: float, taxaFixa: float, valorLiquido: float}
     */
    public static function calcular(float $valorPedido, float $distanciaKm): array
    {
        $valorPedido = max(0.0, $valorPedido);
        $comissao = round($valorPedido * (self::COMISSAO_PERCENTUAL / 100), 2);
        $taxaFixa = self::taxaPorDistancia($distanciaKm);
        $valorLiquido = round($valorPedido - $comissao - $taxaFixa, 2);

        return [
            'comissao' => $comissao,
            'taxaFixa' => $taxaFixa,
            'valorLiquido' => $valorLiquido,
        ];
    }

    private static function taxaPorDistancia(float $distanciaKm): float
    {
        $distanciaKm = max(0.0, $distanciaKm);

        return match (true) {
            $distanciaKm <= 3.0 => self::TAXA_ATE_3KM,
            $distanciaKm <= 5.0 => self::TAXA_ATE_5KM,
            $distanciaKm <= 7.0 => self::TAXA_ATE_7KM,
            default => self::TAXA_ACIMA_7KM,
        };
    }
}
